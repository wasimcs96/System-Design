# J7 — Design a Web Crawler

> **Section:** Case Studies | **Difficulty:** Medium-Hard | **Interview Frequency:** ★★★☆☆

---

## 1. Problem Statement

Design a distributed web crawler (like Googlebot).

**Functional Requirements:**
- Given a set of seed URLs, crawl the web by following links
- Store crawled page content
- Revisit pages periodically to detect changes
- Respect robots.txt and crawl rate limits

**Non-Functional Requirements:**
- Crawl 1 billion pages, revisit every 30 days
- Throughput: ~400 pages/second sustained
- Handle duplicate URLs
- Polite: don't overload any single website
- Fault-tolerant (crawler workers can fail)

---

## 2. Capacity Estimation

```
Target: 1B pages, revisit every 30 days
Pages/day: 1B / 30 = ~33M pages/day
Pages/sec: 33M / 86400 = ~385 pages/sec -> ~400 pages/sec

Average page size: 100 KB HTML
Storage (content): 400 pages/sec x 100 KB x 86400 = ~3.5 TB/day -> store in S3
URL storage: 1B URLs x 100 bytes avg = 100 GB (fits in DB)

Workers needed:
  Each worker: ~5 pages/sec (network + parse)
  Workers: 400 / 5 = 80 crawler workers
```

---

## 3. High-Level Design

```
[Seed URLs]
     |
     v
[URL Frontier (Priority Queue)] -- what to crawl next
     |
     v
[Crawler Workers] (80 workers, each handles one domain at a time)
     |
     +---> [DNS Resolver Cache] (avoid DNS overhead per URL)
     |
     +---> [Fetch HTML] (HTTP GET with timeout)
     |
     +---> [Parse HTML] -- extract links, content
     |
     +---> [Duplicate Detector] -- Bloom Filter (URL seen before?)
     |
     +---> [Link Extractor] -> [URL Normalizer] -> [URL Frontier]
     |
     +---> [Content Storage] -> [S3: raw HTML]
     |
     +---> [Metadata DB] -> [PostgreSQL: url, status, last_crawled]

[Robots.txt Cache] -- per domain, TTL 24h
[Politeness Module] -- per-domain rate limiter
```

---

## 4. URL Frontier (Priority Queue)

```
The URL Frontier determines crawl ORDER and POLITENESS.

Two-level queue design:
  Level 1: Priority queues (front queues)
    - High priority: important pages (PageRank, freshness score)
    - Low priority: rarely changing pages
    - Assign priority based on: domain authority, link count, last modified

  Level 2: Politeness queues (back queues)
    - One queue per domain
    - Ensures we don't send more than 1 req/sec to a domain
    - Worker picks a queue whose domain is ready (last fetch > 1s ago)

Implementation with Redis:
  ZADD frontier {priority_score} {url}  -- sorted set as priority queue
  Per-domain: SET domain:last_fetch:{host} {timestamp} EX 1
              (if key exists: domain is rate-limited, skip to next)
```

---

## 5. Duplicate Detection

```php
// Two types of duplicates to handle:

// 1. URL Deduplication (same URL encountered multiple times from links)
//    Solution: Bloom Filter (low memory, fast, allows false positives)
//    1B URLs x 10 bits each = 10 Gb = 1.25 GB -- fits in Redis

class UrlBloomFilter {
    private const HASH_FUNCTIONS = 7;    // optimal for p(false positive) ~1%

    public function isSeenOrAdd(string $url): bool {
        $normalizedUrl = $this->normalizeUrl($url);
        $alreadySeen   = true;

        for ($i = 0; $i < self::HASH_FUNCTIONS; $i++) {
            $bit = $this->hash($normalizedUrl, $i) % $this->bitCount;
            if (!$this->redis->getBit('url_bloom', $bit)) {
                $alreadySeen = false;
            }
            $this->redis->setBit('url_bloom', $bit, 1);
        }
        return $alreadySeen;
    }

    private function normalizeUrl(string $url): string {
        $parsed = parse_url(strtolower($url));
        // Remove fragments (#section), default ports, trailing slashes
        // Sort query parameters: ?b=2&a=1 -> ?a=1&b=2
        return $this->buildCanonicalUrl($parsed);
    }
}

// 2. Content Deduplication (different URLs, same content -- mirrors/duplicates)
//    Solution: SimHash (locality-sensitive hash)
//    Near-duplicate detection: if simhash(pageA) XOR simhash(pageB) <= 3 bits different -> duplicate
//    Store 64-bit simhash per page, compare on insert
```

---

## 6. Robots.txt & Politeness

```php
class RobotsCache {
    // Cache robots.txt per domain, TTL 24 hours
    public function canCrawl(string $url): bool {
        $host       = parse_url($url, PHP_URL_HOST);
        $cacheKey   = "robots:{$host}";
        $robotsTxt  = $this->redis->get($cacheKey);

        if ($robotsTxt === null) {
            $robotsTxt = $this->fetch("https://{$host}/robots.txt");
            $this->redis->setEx($cacheKey, 86400, $robotsTxt ?? '');
        }

        return $this->parseRobots($robotsTxt, $url, 'Googlebot');
    }
}

// Politeness: per-domain rate limit
class PolitenessModule {
    public function isAllowed(string $host): bool {
        $key      = "crawl:rate:{$host}";
        $lastFetch = (float) $this->redis->get($key);
        $now       = microtime(true);

        if ($now - $lastFetch < 1.0) {
            return false;  // Too soon -- min 1 second between requests to same domain
        }

        $this->redis->setEx($key, 5, $now);  // TTL 5s to auto-clean idle domains
        return true;
    }
}
```

---

## 7. Handling Failures & Retries

```
Worker crash: URL was dequeued but not processed
Solution: Visibility timeout pattern (same as SQS)
  - When a worker dequeues a URL, it's marked "in-progress" (not deleted)
  - If not acknowledged within 60s: URL re-appears in queue
  - Worker acks on success: URL removed from queue + marked as crawled in DB

HTTP failures:
  - 4xx: don't retry (client error -- bad URL), mark as dead
  - 5xx or timeout: retry with exponential backoff (max 3 retries)
  - Retry delay: 1s, 4s, 16s

URL states in DB:
  PENDING -> IN_PROGRESS -> CRAWLED | FAILED | SKIPPED(robots.txt)
```

---

## 8. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| URL dedup | Bloom Filter | 1.25 GB for 1B URLs; tiny false positive rate acceptable |
| Content dedup | SimHash | Detect near-duplicate pages efficiently |
| Politeness | Per-domain Redis TTL | Simple, distributed across workers |
| Frontier | Two-level priority+politeness queue | Balance importance vs politeness |
| Fault tolerance | Visibility timeout (SQS-style) | No URL lost on worker crash |

---

## 9. Interview Q&A

**Q: What if the same URL is discovered by two workers simultaneously?**
> The Bloom Filter check + add is done via Redis, but it's not atomic in the naive implementation. Use Redis Lua script to atomically check-and-set all bloom filter bits, or use a distributed lock around the check+enqueue operation. In practice, duplicate enqueuing is acceptable -- the content-level deduplication catches it: if the URL is already CRAWLED in the DB, the worker skips the fetch.

**Q: How do you prioritize fresh news articles over static old pages?**
> Assign a priority score when inserting into the URL Frontier. Score factors: (1) Domain authority (news.bbc.co.uk > random-blog.com); (2) Change frequency (pages with Last-Modified headers that change often get higher priority); (3) Freshness (last crawled time -- older = higher priority for revisit); (4) Link count (pages linked from many sources = more important). Use a min-heap priority queue (or Redis sorted set with score = priority).

---

## 10. Key Takeaways

```
+--------------------------------------------------------------------+
| Bloom Filter = 1.25 GB for 1B URL deduplication (false positive OK)|
| Two-level frontier = priority (importance) + politeness (per domain)|
| SimHash = near-duplicate content detection in O(1)                |
| Visibility timeout = fault tolerant; no URL lost on worker crash  |
| Robots.txt: cache per domain 24h; always check before crawling    |
+--------------------------------------------------------------------+
```
