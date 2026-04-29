# J6 — Design Instagram

> **Section:** Case Studies | **Difficulty:** Hard | **Interview Frequency:** ★★★★☆

---

## 1. Problem Statement

Design Instagram's core features: photo upload, follow users, home feed.

**Functional Requirements:**
- Upload photos / videos with captions
- Follow / unfollow users
- View personalized home feed (posts from followees)
- Like and comment on posts
- View user profile

**Non-Functional Requirements:**
- 500M DAU, 100M photo uploads/day
- Feed load < 1 second p99
- 99.99% availability
- Photos stored permanently

---

## 2. Capacity Estimation

```
Photo uploads: 100M / 86400 = ~1,160 uploads/sec (peak: ~10K)
Photo size: avg 200 KB (compressed JPEG)
Storage: 100M x 200 KB = 20 TB/day

Feed reads: 500M x 5 sessions/day = 2.5B / 86400 = ~29K reads/sec (peak: ~290K)
Read:Write = 25:1 (read heavy -- caching critical)

Likes/comments: 500M x 20 actions/day / 86400 = ~115K writes/sec (peak: ~1M)
-> Separate service from feed (don't slow down photo uploads)
```

---

## 3. High-Level Design

```
UPLOAD PATH:
[App] -> [API Gateway] -> [Upload Service]
                               |
                    Pre-signed URL to S3 raw
                               |
                       [S3 raw-photos]
                               |
                    [Lambda: S3 event trigger]
                               |
                       [Kafka: photo_uploaded]
                               |
                  +------------+------------+
                  |            |            |
           [Image Processor]  [Feed Fan-out] [Search Indexer]
           (resize, compress)  Service       (Elasticsearch)
                  |
         [S3 processed-photos]
                  |
           [Photo CDN]

FEED READ PATH:
[App] -> [Feed Service] -> [Redis: home_feed:{user_id}]
                                  | miss
                          [Cassandra: feed_items]
                                  |
                          [Hydrate post objects]
                          (batch fetch from PostgreSQL + Redis)
```

---

## 4. Photo Storage & CDN

```php
// Upload flow: pre-signed URL pattern (same as YouTube)
// Client uploads directly to S3 -- no app server in upload path

// Image processing (Lambda or worker service):
// Input: s3://raw-photos/{user_id}/{uuid}.jpg
// Output:
//   s3://photos/{photo_id}/original.jpg   (2MB - rarely served)
//   s3://photos/{photo_id}/large.jpg      (1080px, ~300KB)
//   s3://photos/{photo_id}/medium.jpg     (640px, ~100KB - feed thumbnail)
//   s3://photos/{photo_id}/small.jpg      (150px, ~20KB - profile grid)

// CDN URL pattern (content-addressable for long cache TTL):
// https://cdn.instagram.com/photos/{photo_id}/medium.jpg
// Cache-Control: public, max-age=31536000 (1 year)
// Cache-busting: not needed -- photo_id never changes

// Database reference:
// photos.image_url = "photos/{photo_id}" (relative path -- CDN base URL in config)
```

---

## 5. Feed Generation (Fan-out on Write with Hybrid)

```php
// On photo upload: push to followers' feed caches
// Same hybrid as Twitter (see J2):
//   - Regular users (<1M followers): fan-out on write
//   - Celebrities (>=1M followers): pull on read + merge

// Fan-out worker (Kafka consumer):
function fanOut(int $postId, int $authorId): void {
    $followerIds = $this->db->getFollowerIds($authorId);  // paginated

    // Push to Redis sorted set (home feed cache):
    foreach (array_chunk($followerIds, 500) as $batch) {
        $pipeline = $this->redis->pipeline();
        foreach ($batch as $followerId) {
            // ZADD home_feed:{followerId} {postId} {postId}
            // Score = postId (Snowflake = time-ordered, so newest = highest score)
            $pipeline->zAdd("home_feed:{$followerId}", $postId, $postId);
            // Keep last 500 posts only
            $pipeline->zRemRangeByRank("home_feed:{$followerId}", 0, -501);
        }
        $pipeline->execute();
    }
}

// Feed read:
function getFeed(int $userId, int $limit = 20, int $cursor = 0): array {
    // cursor = last seen post_id (pagination)
    $postIds = $this->redis->zRevRangeByScore(
        "home_feed:{$userId}",
        $cursor > 0 ? "({$cursor}" : '+inf',
        '-inf',
        ['LIMIT' => [0, $limit]]
    );

    // Batch hydrate post objects
    $posts = $this->getPostsByIds($postIds);  // Redis L2 cache -> PostgreSQL

    return $posts;
}
```

---

## 6. Likes & Comments

```sql
-- Likes: high write volume, eventually consistent count is fine
CREATE TABLE likes (
    post_id   BIGINT,
    user_id   BIGINT,
    created_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (post_id, user_id)  -- natural dedup
);
-- Index: (user_id, post_id) for "did I like this?" check

-- Counter: Redis INCR likes_count:{post_id}
-- Sync to PostgreSQL every 5 minutes (acceptable staleness)

-- Comments: ordered by time, nested replies
CREATE TABLE comments (
    id         BIGINT PRIMARY KEY,
    post_id    BIGINT NOT NULL,
    user_id    BIGINT NOT NULL,
    parent_id  BIGINT,             -- NULL for top-level, comment_id for reply
    content    TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX idx_comments_post ON comments(post_id, created_at ASC);
```

---

## 7. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Fan-out | Hybrid push+pull | Push for regular users, pull for celebrities |
| Feed storage | Redis sorted set + Cassandra cold | Fast reads from Redis, cold storage fallback |
| Like count | Redis counter + async flush | Handle 1M likes/sec without DB bottleneck |
| Photo storage | S3 + CDN | Unlimited scale, 1-year cache TTL |
| Post IDs | Snowflake | Time-ordered = natural sort key for feed |

---

## 8. Interview Q&A

**Q: What if a user's Redis feed cache is cold (new user, or Redis restart)?**
> On cache miss: fall through to Cassandra's persistent feed_items table. If that's also empty (new user): run a one-time "feed bootstrap" that fetches the latest N posts from all followees, writes to Cassandra, and populates the Redis cache. This is done asynchronously -- the first feed request may return a slight delay or empty state with a "populating your feed" message. After bootstrap, all subsequent requests hit Redis.

**Q: How would you add an Explore/Discover page (posts from people you don't follow)?**
> Explore requires a recommendation system separate from the feed. Architecture: (1) offline batch job (Spark/Flink) computes user interest vectors based on interactions -- runs nightly; (2) stores top-500 recommended post_ids per user in a separate Redis key "explore:{user_id}"; (3) Explore endpoint reads from this key. Freshness is less critical for Explore vs Home Feed, so batch computation is acceptable. Real-time signals (trending hashtags, viral posts) are layered in via a separate trending service.

---

## 9. Key Takeaways

```
+--------------------------------------------------------------------+
| Pre-signed S3 upload + CDN = no app servers in media path        |
| Resize to multiple sizes at upload time (not on every read)      |
| Hybrid fan-out: push for regular, pull for celebrities           |
| Redis sorted set ZADD with post_id as score = time-ordered feed  |
| Like counts: Redis INCR + async flush = high write throughput    |
+--------------------------------------------------------------------+
```
