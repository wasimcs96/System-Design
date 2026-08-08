# J2 — Design Twitter / News Feed

> **Section:** Case Studies | **Difficulty:** Hard | **Interview Frequency:** ★★★★★

---

## 1. Problem Statement

Design Twitter's core features: tweet, follow users, view personalized home timeline.

**Functional Requirements:**
- Post a tweet (text, image, video)
- Follow / unfollow users
- View home timeline (tweets from people you follow, reverse-chron)
- View user profile timeline

**Non-Functional Requirements:**
- 300M DAU, ~100M tweets/day
- Read-heavy: each user reads ~10x more than they write
- Home timeline load < 500ms p99
- Eventual consistency OK (1-2s stale)
- High availability (99.99%)

---

## 2. Capacity Estimation

```
Tweet writes: 100M / 86400 = ~1,160 tweets/sec (peak: ~10K)
Timeline reads: 300M x 10 / 86400 = ~34,700 reads/sec (peak: ~300K)
Read:Write ratio = 30:1

Storage per tweet: ~300 bytes text + metadata
Storage: 100M x 300 bytes x 365 days x 5 years = ~55 TB

Media (30% tweets have images): 30M x 200KB = 6 TB/day
```

---

## 3. The Core Problem: Fan-Out

When a user tweets, how do we deliver it to all followers' timelines?

```
APPROACH 1: Fan-out on Read (Pull Model)
  Timeline read = fetch all N followees' tweets, merge, sort
  Problem: Taylor Swift follows 100M people -> 100M DB queries on each read
  Good for: celebrities who follow many people

APPROACH 2: Fan-out on Write (Push Model) -- Twitter's choice
  On tweet: pre-compute and write tweet_id to each follower's timeline cache
  Timeline read = single Redis list lookup (sub-millisecond)
  Problem: Kylie Jenner 300M followers -> 300M cache writes per tweet
  Good for: regular users

TWITTER'S ACTUAL SOLUTION: Hybrid
  Regular users (< 1M followers): fan-out on write (push to all follower caches)
  Celebrities (>= 1M followers): fan-out on read (pull at read time, merge with pre-computed)
  Read path: merge pre-computed cache + real-time celebrity tweets
```

---

## 4. High-Level Design

```
[POST /tweets]
     |
     v
[Tweet Service] -> [DB: tweets table] -> [Kafka: "new_tweet" event]
                                                  |
                                         [Fan-out Service]
                                         /                                         [<1M followers]                 [>=1M followers]
                          push to follower               skip cache write
                          timeline caches                (pull on read)

[GET /timeline]
     |
     v
[Timeline Service]
     |
     +---> [Redis: user_timeline:{user_id}] -- sorted set of tweet_ids
     |             |
     |             v (cache miss or celebrity merge needed)
     |      [Tweets DB / Cassandra]
     |
     +---> [Celebrity tweets] -- pull from followee's tweet list, merge
```

---

## 5. Database Schema

```sql
-- tweets (Cassandra -- high write, time-series, no JOINs needed)
CREATE TABLE tweets (
    tweet_id   BIGINT,   -- Snowflake ID (includes timestamp)
    user_id    BIGINT,
    content    TEXT,
    media_urls LIST<TEXT>,
    created_at TIMESTAMP,
    PRIMARY KEY (tweet_id)
);

-- user profile timeline (recent tweets by a user)
CREATE TABLE user_timeline (
    user_id   BIGINT,
    tweet_id  BIGINT,
    PRIMARY KEY (user_id, tweet_id)  -- partition by user, cluster by tweet_id DESC
) WITH CLUSTERING ORDER BY (tweet_id DESC);

-- follows (who does user X follow?)
CREATE TABLE follows (
    follower_id  BIGINT,
    followee_id  BIGINT,
    PRIMARY KEY (follower_id, followee_id)
);

-- home timeline (Redis sorted set is primary; this is cold storage backup)
-- Key: "home:{user_id}"  Value: sorted set of tweet_ids (score = timestamp)
```

---

## 6. Tweet Posting Flow

```php
// 1. Write tweet to Cassandra
$tweetId = $snowflake->nextId();
$cassandra->insert('tweets', [
    'tweet_id'   => $tweetId,
    'user_id'    => $userId,
    'content'    => $content,
    'created_at' => new \DateTimeImmutable(),
]);

// 2. Publish to Kafka for async fan-out
$kafka->publish('new_tweet', json_encode([
    'tweet_id'         => $tweetId,
    'user_id'          => $userId,
    'follower_count'   => $this->getFollowerCount($userId),
]));

// Fan-out consumer (separate service):
// For each follower (if user is not celebrity):
//   ZADD home:{follower_id} {timestamp} {tweet_id}
//   ZREMRANGEBYRANK home:{follower_id} 0 -801  // keep last 800 tweets
```

---

## 7. Timeline Read Flow

```php
function getHomeTimeline(int $userId, int $limit = 20): array {
    // 1. Get tweet_ids from Redis sorted set (newest first)
    $tweetIds = $this->redis->zRevRange("home:{$userId}", 0, $limit - 1);

    // 2. Merge in celebrity tweets (pull model)
    $celebrities  = $this->getCelebrityFollowees($userId);
    $celebTweetIds = [];
    foreach ($celebrities as $celebId) {
        $ids = $this->cassandra->query(
            "SELECT tweet_id FROM user_timeline WHERE user_id = ? LIMIT 20",
            [$celebId]
        );
        $celebTweetIds = array_merge($celebTweetIds, $ids);
    }

    // 3. Merge + sort by tweet_id (Snowflake IDs are time-ordered)
    $allIds = array_unique(array_merge($tweetIds, $celebTweetIds));
    rsort($allIds);
    $finalIds = array_slice($allIds, 0, $limit);

    // 4. Hydrate tweet objects (batch fetch from Cassandra or Redis cache)
    return $this->hydrateTweets($finalIds);
}
```

---

## 8. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Fan-out | Hybrid push+pull | Push fast for regular users; pull avoids celebrity problem |
| Tweet storage | Cassandra | High write throughput, time-series access pattern |
| Timeline cache | Redis sorted set (ZADD) | O(log N) insert, O(1) range read |
| Tweet IDs | Snowflake | Time-ordered, distributed, 64-bit |
| Consistency | Eventual (~2s) | Fan-out is async; acceptable for social feed |

---

## 9. Interview Q&A

**Q: Why not store the full tweet in the timeline cache, just the tweet_id?**
> Storing only tweet_ids keeps the cache small (8 bytes vs ~300 bytes per entry). When a tweet is edited or deleted, you only need to update the tweets table -- the timeline cache auto-reflects the latest content when hydrated. If you cached full tweets, you'd have to invalidate every follower's timeline cache entry on each edit/delete.

**Q: What if a user has 500M followers (like a head of state)?**
> The hybrid model handles this: users above a follower threshold (e.g., 1M) are classified as celebrities. Their tweets are not pushed to follower caches. Instead, the read path checks if any of your followees are celebrities, fetches their latest tweets directly, and merges them into your timeline. This trades slightly higher read latency for dramatically lower write amplification.

---

## 10. Key Takeaways

```
+--------------------------------------------------------------------+
| Fan-out on write = fast reads; celebrity problem at scale         |
| Hybrid: push for <1M followers, pull for celebrities             |
| Redis ZADD = O(log N) sorted timeline, keep last 800 tweet_ids   |
| Cassandra = high write throughput, time-series tweets            |
| Snowflake IDs = time-ordered, use as sort key (no created_at needed)|
+--------------------------------------------------------------------+
```
