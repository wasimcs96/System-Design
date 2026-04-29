# G — Capacity Estimation

> **Section:** Methodology | **Level:** Intermediate | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Before designing a system, you need to know its scale. How many users? How much data? How many requests per second? Capacity estimation is back-of-envelope math to answer these questions quickly.

**Technical:** Capacity estimation involves rapid estimation of: QPS (queries per second), storage requirements, bandwidth, memory, and server count. The goal is order-of-magnitude accuracy to inform architecture decisions, not precision.

---

## 2. Quick Reference Numbers (memorize these)

```
POWERS OF 10:
Thousand  = 10^3 = K
Million   = 10^6 = M
Billion   = 10^9 = B

TIME:
1 day   = 86,400 seconds  ~= 100K seconds
1 month = 2.6M seconds
1 year  = 31.5M seconds   ~= 30M seconds

STORAGE:
1 char  = 1 byte (ASCII)
1 KB    = 10^3 bytes
1 MB    = 10^6 bytes
1 GB    = 10^9 bytes
1 TB    = 10^12 bytes

LATENCY NUMBERS (Jeff Dean, Google):
L1 cache: 0.5 ns
RAM access: 100 ns
SSD read: 100 us (microseconds)
HDD seek: 10 ms
Round trip within datacenter: 0.5 ms
Round trip same city: 1 ms
Round trip cross-country: 40 ms
Round trip overseas: 150 ms

THROUGHPUT RULES OF THUMB:
SSD: 500 MB/s sequential read
HDD: 100 MB/s sequential read
Network (same DC): 10 Gbps = 1.25 GB/s
Database (small queries): ~10K QPS per server
Redis: ~100K ops/sec per server
Kafka: ~1M messages/sec per broker
```

---

## 3. Worked Examples

### Example 1: Twitter-like Feed

**Assumptions:**
- 300M DAU
- Each user tweets 1x/day average
- 10% of users are active at peak hour
- Each tweet = 280 chars = ~300 bytes + metadata = ~1 KB

**QPS:**
- Total tweets/day: 300M / 86400s = ~3,500 tweets/sec
- Peak (10x average): ~35,000 tweets/sec

**Storage (tweets):**
- 300M tweets/day × 1 KB = 300 GB/day
- 5-year retention: 300 GB × 365 × 5 = ~550 TB total

**Timeline reads (heavy read load):**
- Each user checks timeline 5x/day
- Read QPS: 300M × 5 / 86400 = ~17,000 reads/sec
- Peak: ~170,000 reads/sec

**Servers:**
- Each app server: ~5,000 req/sec
- Needed: 170,000 / 5,000 = ~34 app servers (at peak)

---

### Example 2: Instagram-like Photo Storage

**Assumptions:**
- 500M DAU
- 100M photo uploads/day
- Average photo = 200 KB
- Photos kept forever, but after 2 years compress to 50 KB

**Write QPS:**
- 100M uploads/day / 86400 = ~1,160 uploads/sec

**Storage (photos):**
- New photos/year: 100M × 365 × 200 KB = 7.3 PB/year
- After compression (Year 2+): 7.3 PB × 0.25 = ~1.8 PB/year effective
- 5-year estimate: 7.3 + 4×1.8 = ~14.5 PB

**Bandwidth (read):**
- 500M users × 20 photo views/day = 10B views/day
- 10B × 200KB / 86400s = ~23 GB/s peak
- With CDN caching 99%: only 1% hits origin = 230 MB/s origin bandwidth

---

### Example 3: Rate Limiting (Redis memory)

**Setup:** Rate limit 100 requests/minute per user, 10M active users

**Memory per user (sliding window counter):**
- Key: "ratelimit:user:{id}" = ~25 bytes
- Value: sorted set with timestamps = ~60 bytes per entry × 100 entries = ~6 KB per user
- Total: 10M × 6 KB = 60 GB of Redis memory

**Better: Token bucket (just 1 counter per user):**
- Key + value: ~100 bytes per user
- 10M × 100 bytes = 1 GB — much more memory-efficient

---

## 4. Estimation Template

```
STEP 1: CLARIFY SCALE
- DAU (Daily Active Users): ?
- Read:Write ratio: ?
- Peak vs average: usually 2-10x

STEP 2: QPS
- Writes/sec = DAU x writes_per_user / 86400
- Reads/sec  = DAU x reads_per_user  / 86400
- Peak QPS   = avg QPS x peak_multiplier (typically 3-10x)

STEP 3: STORAGE
- Size per record = sum of all fields
- Daily growth = writes/day x bytes_per_record
- Total = daily_growth x retention_days

STEP 4: BANDWIDTH
- Ingress = write_QPS x bytes_per_write
- Egress  = read_QPS  x bytes_per_read

STEP 5: SERVERS
- Required servers = peak_QPS / QPS_per_server
- Add 30% buffer for headroom
```

---

## 5. Common Rounding Tricks

```php
// Useful approximations for mental math:
// 1M users / day = 12 users/second (86400 ~= 100K)
// 1B users, 10 req/day = 100K req/sec = 100 QPS * 1000
// 1KB * 1M = 1 GB
// 1MB * 1M = 1 TB
// 1B rows * 1KB = 1 TB (10^9 * 10^3 = 10^12)

// Always round to nearest power of 10 for estimations:
// 86400 sec/day -> 100K
// 3600 sec/hour -> 4K
// 604800 sec/week -> 600K

// Storage tiering:
// Hot data (Redis): GBs (expensive) -- only active sessions, counters
// Warm data (SQL/NoSQL DB): TBs -- recent records, searchable
// Cold data (S3/Glacier): PBs -- historical, analytics, backups

// Typical record sizes:
// User profile: ~1 KB
// Tweet/post: ~1 KB
// Image metadata: ~200 bytes
// Image file: 200 KB (compressed JPEG)
// Video (1 min, 720p): ~100 MB
// Video (1 min, 4K): ~500 MB
// Log line: ~200 bytes
```

---

## 7. Interview Q&A

**Q1: Walk me through estimating QPS for a URL shortener with 100M DAU.**
> Assumptions: 100M DAU. 1% create short URLs (1M/day), 99% redirect (99M/day). Write QPS: 1M / 86400 = ~12 writes/sec. Read QPS: 99M / 86400 = ~1,150 reads/sec. Peak read QPS (5x): ~5,750 reads/sec. Storage: Each URL entry ~500 bytes. 1M new URLs/day = 500 MB/day. 5-year: 500 MB x 365 x 5 = ~900 GB total. Read:write ratio = 100:1 -- heavily read. Caching strategy: cache the most popular 20% of URLs (80/20 rule) in Redis = 180 GB for full 5-year dataset, or ~36 GB for popular 20%.

**Q2: How do you estimate server count?**
> Rule of thumb: each commodity app server (8 cores, 16GB RAM) handles ~5,000 HTTP requests/second for lightweight API calls. For DB-heavy operations: ~500-1,000 req/sec. Example: 50,000 peak QPS / 5,000 per server = 10 app servers. Add 30-50% buffer = 13-15 servers. Always express in powers of 10 in interviews: "around 10-20 servers." More important is identifying bottlenecks: is it CPU, memory, DB connections, or network?

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| Memorize: 86400 sec/day; 1B x 1KB = 1TB; Redis 100K ops/sec      |
| Write QPS = DAU x writes_per_day / 86400                          |
| Peak = avg x 3-10x (depends on traffic pattern)                   |
| Round aggressively: 86400 -> 100K in mental math                  |
| Storage = records_per_day x bytes_per_record x retention_days     |
| Read:write ratio guides caching and DB read replica decisions      |
+--------------------------------------------------------------------+
```
