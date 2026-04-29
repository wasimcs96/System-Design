# J1 — Design a URL Shortener (TinyURL)

> **Section:** Case Studies | **Difficulty:** Medium | **Interview Frequency:** ★★★★★

---

## 1. Problem Statement

Design a URL shortening service like TinyURL or bit.ly.

**Functional Requirements:**
- Given a long URL, generate a unique short URL (e.g., `tiny.ly/aB3xK`)
- Redirect short URL to original long URL
- (Optional) Custom alias, expiry, analytics (click counts)

**Non-Functional Requirements:**
- 100M DAU, 1% create (1M writes/day), 99% redirect (99M reads/day)
- Read:Write = 100:1 — heavily read dominant
- 99.99% availability (redirection must always work)
- Low latency redirects (<10ms p99)
- URLs should not be guessable/enumerable

---

## 2. Capacity Estimation

```
Write QPS: 1M / 86400 = ~12 writes/sec (peak: ~120)
Read QPS:  99M / 86400 = ~1,150 reads/sec (peak: ~11,500)

Storage per URL record:
  short_code: 7 chars = 7 bytes
  long_url:   ~200 bytes average
  user_id:    8 bytes
  created_at: 8 bytes
  expires_at: 8 bytes
  Total: ~250 bytes per record

Storage (10 years):
  1M writes/day x 365 x 10 x 250 bytes = ~900 GB  (fits in one DB)

Cache (20% hot URLs serve 80% traffic):
  1M URLs/day x 365 x 250 bytes x 20% = ~18 GB/year in Redis -- very manageable
```

---

## 3. High-Level Design

```
[Client]
   |
   v
[CDN / Edge Cache]  <-- cache popular redirects at edge (TTL: 1 hour)
   |
   v
[Load Balancer]
   |
   v
[Redirect Service]  ----> [Redis Cache] (short_code -> long_url, TTL: 24h)
        |                       | miss
        |                       v
        |                 [DB Read Replica]
        v
[Write Service]  ------> [Primary DB (PostgreSQL)]
        |
        v
[ID Generator Service]  (Snowflake or pre-allocated ID ranges)

[Analytics Service] <-- async from Redirect Service via Kafka
```

---

## 4. Short Code Generation (Critical Design Choice)

### Option 1: Hash + Truncate
```php
// MD5/SHA256 the long URL, take first 7 chars
$shortCode = substr(base64_encode(md5($longUrl, true)), 0, 7);
// Problem: collisions! Different URLs can produce same 7-char prefix
// Fix: detect collision, append counter and re-hash
```

### Option 2: Base62 Encoded Auto-Increment ID (RECOMMENDED)
```php
// Characters: a-z A-Z 0-9 = 62 chars
// 7 chars = 62^7 = 3.5 trillion unique URLs
const BASE62_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

function toBase62(int $num): string {
    $result = '';
    while ($num > 0) {
        $result = BASE62_CHARS[$num % 62] . $result;
        $num    = intdiv($num, 62);
    }
    return str_pad($result, 7, BASE62_CHARS[0], STR_PAD_LEFT);
}

// Flow:
// 1. INSERT into DB (gets auto-increment ID, e.g., 123456789)
// 2. Encode ID to base62: toBase62(123456789) = "8M0kX"
// 3. Store mapping: short_code -> id in DB
// 4. Return short URL

// Problem with single DB auto-increment: bottleneck at scale
// Solution: pre-allocate ID ranges (each write server gets a range of 1M IDs)
```

### Option 3: Snowflake ID (distributed unique IDs)
See C12 for full Snowflake implementation.

---

## 5. Database Schema

```sql
CREATE TABLE urls (
    id          BIGINT       PRIMARY KEY AUTO_INCREMENT,
    short_code  VARCHAR(10)  UNIQUE NOT NULL,
    long_url    TEXT         NOT NULL,
    user_id     BIGINT,
    created_at  TIMESTAMP    DEFAULT NOW(),
    expires_at  TIMESTAMP,
    click_count BIGINT       DEFAULT 0
);

CREATE INDEX idx_short_code ON urls(short_code);  -- lookup by short code
CREATE INDEX idx_user_id    ON urls(user_id, created_at DESC);  -- user's links
```

---

## 6. Redirect Flow

```
GET /aB3xK
  1. Check CDN edge cache -> HIT: return 301/302 immediately
  2. Check Redis cache   -> HIT: return redirect
  3. Check DB read replica -> HIT: write to Redis (TTL 24h), return redirect
  4. Not found: return 404

301 vs 302 redirect:
  301 Permanent: browser caches it -> no future requests to our servers
                 Pro: reduced load. Con: can't update/expire links
  302 Temporary: browser always hits our servers -> we can track clicks, expire URLs
  -> Use 302 for analytics and expiry support
```

---

## 7. Custom Alias & Expiry

```php
// Custom alias: user provides their own short code
// Validate: alphanumeric, 4-20 chars, not already taken
// Store same as auto-generated, skip ID->base62 step

// Expiry cleanup:
// Lazy deletion: check expires_at on every redirect, return 410 Gone
// Eager deletion: background job sweeps expired URLs nightly
// -> Use lazy deletion (simple) + periodic cleanup job
```

---

## 8. Analytics (Click Counting)

```
Sync (simple): UPDATE urls SET click_count = click_count + 1 WHERE short_code = ?
  Problem: write on every read -- kills read performance

Async (recommended):
  Redirect Service -> Kafka topic "url_clicks" -> Analytics Consumer
  Consumer: batch-aggregates counts, writes to ClickHouse/Cassandra every 30s
  Counts may be 30s stale -- acceptable for analytics
```

---

## 9. Trade-offs Table

| Decision | Choice | Reason |
|----------|--------|--------|
| Short code generation | Base62(auto-increment ID) | No collisions, simple, sortable |
| Redirect type | 302 | Analytics + expiry support |
| Cache | Redis TTL 24h | 18GB serves 80% traffic |
| Analytics | Async via Kafka | Don't slow down redirects |
| Storage | PostgreSQL | 900 GB fits easily, simple |

---

## 10. Interview Q&A

**Q: How do you handle hash collisions?**
> With base62(auto-increment ID), there are zero collisions -- each ID is unique by definition. With hash-based approach, detect collision by checking if short_code exists in DB; if yes, append a suffix and retry. In practice, choose base62 to avoid this entirely.

**Q: How would you scale to 1 billion writes/day?**
> Current design handles ~12 writes/sec easily. At 1B/day = 11,500 writes/sec: shard PostgreSQL by short_code (or use distributed ID like Snowflake), replace single Redis with Redis Cluster, use Kafka for async ID pre-allocation. Also consider Cassandra for the URL mapping table (simple key-value access pattern).

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| Base62(auto-increment ID) = zero collision, simple, fast         |
| 302 redirect = analytics + expiry; 301 = no tracking             |
| Redis cache + CDN edge cache = <10ms p99 redirect latency        |
| Async click counting via Kafka = don't slow down redirects       |
| 62^7 = 3.5 trillion URLs -- 7 chars sufficient for decades       |
+--------------------------------------------------------------------+
```
