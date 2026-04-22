# Section 6: Capacity Estimation Practice

> Mastering back-of-the-envelope calculations is mandatory for Amazon SDE-2/SDE-3 interviews.
> Practice until you can do these in < 3 minutes mentally.

---

## Reference Numbers to Memorize

```
Time:
1 day     = 86,400 seconds ≈ 10^5 seconds
1 month   = 2.5 million seconds ≈ 2.5 × 10^6 seconds
1 year    = 31.5 million seconds ≈ 3 × 10^7 seconds

Approximations:
1 million  = 10^6
1 billion  = 10^9
1 trillion = 10^12

Storage:
1 KB = 10^3 bytes
1 MB = 10^6 bytes
1 GB = 10^9 bytes
1 TB = 10^12 bytes
1 PB = 10^15 bytes

Bandwidth:
1 Gbps = 125 MB/s
10 Gbps = 1.25 GB/s

Network latency (same datacenter): ~0.5ms
SSD random read: ~0.1ms
HDD random read: ~10ms
```

---

## Example 1: Design Twitter / X — Capacity Estimation

### Given Assumptions
- 300 million Monthly Active Users (MAU)
- 100 million Daily Active Users (DAU)
- Average user reads: 50 tweets/day
- Average user writes: 2 tweets/day
- Average tweet size: 280 chars (UTF-8) + metadata = ~300 bytes
- Tweet includes media 10% of the time
- Media average size: 500 KB (image) or 2 MB (video, compressed)

---

### QPS Estimation

**Write QPS (Tweet creation):**
```
100M DAU × 2 tweets/day = 200M tweets/day
200M ÷ 86,400 sec/day   = ~2,315 tweets/sec
Peak (3× average)        = ~7,000 tweets/sec (write QPS)
```

**Read QPS (Timeline reads):**
```
100M DAU × 50 reads/day = 5 billion reads/day
5B ÷ 86,400             = ~57,870 reads/sec
Peak (3× average)        = ~175,000 reads/sec (read QPS)

Read:Write ratio = 57,870 : 2,315 ≈ 25:1 (read-heavy system)
```

---

### Storage Estimation

**Text tweet storage (5-year retention):**
```
200M tweets/day × 300 bytes/tweet = 60 GB/day
60 GB/day × 365 days/year × 5 years = 109,500 GB ≈ 110 TB

Add 30% overhead (indexes, metadata) → ~143 TB
```

**Media storage:**
```
10% of tweets have media = 20M tweets/day with media
80% images, 20% video:
  Images: 16M × 500 KB = 8,000 GB = 8 TB/day
  Videos: 4M × 2 MB   = 8,000 GB = 8 TB/day
Total media: 16 TB/day

5-year media storage: 16 TB × 365 × 5 = ~29 PB
CDN reduces origin load significantly (hot content cached)
```

**Total storage (5 years):**
```
Text tweets: ~143 TB
Media:       ~29 PB
Indexes:     ~500 TB (Elasticsearch for search)
Total:       ~30 PB
```

---

### Bandwidth Estimation

**Outbound (read traffic):**
```
175,000 reads/sec × 300 bytes (text only) = 52.5 MB/sec
+ media delivery: ~10% reads with media = 17,500 reads/sec × 50 KB avg = 875 MB/sec
Total outbound: ~930 MB/sec ≈ 1 GB/sec

With CDN: 90% served from CDN → origin sees ~100 MB/sec
```

**Inbound (write traffic):**
```
7,000 writes/sec × 300 bytes = 2.1 MB/sec text
+ media uploads: 700 media/sec × 500 KB avg = 350 MB/sec
Total inbound: ~352 MB/sec
```

---

### Memory (Cache) Estimation

**Hot tweet cache:**
```
80/20 rule: 20% of tweets get 80% of traffic
20% of daily tweets: 20M tweets/day
Cache last 3 days: 60M hot tweets × 300 bytes = 18 GB

Redis cluster: 3 nodes × 8 GB = 24 GB ✓ (with room for growth)
```

**Timeline cache (pre-computed feeds):**
```
100M users × 200 tweet IDs in feed × 8 bytes/ID = 160 GB
160 GB Redis cluster (16 nodes × 10 GB each)
```

---

## Example 2: Design URL Shortener — Capacity Estimation

### Assumptions
- 100M new URLs created per day
- 10:1 read-to-write ratio → 1B redirects/day
- Average URL size: 200 bytes (long URL + metadata)
- Short code: 7 characters = 7 bytes
- Retention: URLs kept for 5 years

---

### QPS

**Write (URL creation):**
```
100M/day ÷ 86,400 = 1,157 writes/sec
Peak (3×): 3,470 writes/sec
```

**Read (redirect):**
```
1B/day ÷ 86,400 = 11,574 reads/sec
Peak (3×): 34,722 reads/sec ≈ 35K reads/sec
```

---

### Storage

**URL database:**
```
100M URLs/day × 365 × 5 years = 182.5 billion URLs
Each URL record:
  - short_code:   7 bytes
  - long_url:    200 bytes
  - user_id:      8 bytes
  - created_at:   8 bytes
  - metadata:    50 bytes
  Total:        273 bytes ≈ 300 bytes

182.5B × 300 bytes = 54.75 TB ≈ 55 TB

With 20% overhead (indexes): ~66 TB
```

**Cache (hot URLs):**
```
20% of URLs get 80% of traffic
20% of 182.5B = 36.5B URLs
But realistically: top 10M URLs handle most traffic

10M × 300 bytes = 3 GB Redis cache ← very manageable
```

---

### Bandwidth

**Read traffic:**
```
35K redirects/sec × 300 bytes = 10.5 MB/sec
(Redirect response is tiny: just HTTP 301 + Location header)

With CDN caching: browsers cache 301 → CDN handles most;
actual origin requests may be 10× lower = ~1 MB/sec
```

---

## Example 3: Design WhatsApp — Capacity Estimation

### Assumptions
- 2 billion registered users
- 500 million DAU
- Each user sends 40 messages/day on average
- 70% messages are text (100 bytes), 20% images (100 KB), 10% video (1 MB)
- Messages stored for 30 days on server (end-to-end encrypted)

---

### QPS

**Message sends:**
```
500M DAU × 40 messages/day = 20 billion messages/day
20B ÷ 86,400 = 231,480 messages/sec ≈ 231K msg/sec
Peak (3×): ~700K msg/sec
```

**Active WebSocket connections:**
```
500M DAU × assume 60% online at peak = 300M connections
Each connection: ~50 KB memory
300M × 50 KB = 15 TB memory needed for connection state
Distribute across 150,000 WebSocket servers (each handling 2,000 connections)
```

---

### Storage

**Message storage (30-day retention):**
```
20B messages/day × 30 days = 600B messages stored

Text (70%): 420B × 100 bytes = 42 TB
Image (20%): 120B × 100 KB = 12,000 TB = 12 PB
Video (10%): 60B × 1 MB = 60,000 TB = 60 PB

Total: ~72 PB for 30-day retention

With compression (video: 5× compression): ~30 PB
```

**Daily storage addition:**
```
20B msg × average 300 bytes (blended) = 6 TB text/day
Image + video ≈ 2.4 PB/day → 72 PB/month
```

---

### Bandwidth

**Inbound (uploads):**
```
231K msg/sec:
  Text: 161K × 100B = 16 MB/sec
  Image: 46K × 100KB = 4.6 GB/sec
  Video: 23K × 1MB = 23 GB/sec
Total inbound: ~28 GB/sec ≈ 224 Gbps
(Distributed across multiple upload servers + CDN edge)
```

**Outbound:**
```
Each message goes to average 1.5 recipients (some group messages)
Outbound ≈ 1.5 × inbound = ~42 GB/sec
```

---

## Example 4: Design Netflix — Capacity Estimation

### Assumptions
- 250M subscribers
- 100M concurrent streams at peak
- Average video quality: 4 Mbps (mix of SD, HD, 4K)
- Library: 15,000 titles
- Average movie size: 4 GB (4 Mbps × 2 hours × 3600 sec / 8 bits)
- Average TV series: 30 episodes × 1 GB = 30 GB
- Transcoded into 10 bitrate variants per title

---

### Streaming QPS

```
100M concurrent streams × 4 Mbps average
= 400,000,000 Mbps
= 400,000 Gbps
= 400 Tbps total outbound bandwidth

This is why Netflix is the largest ISP peering partner globally.
Netflix deploys CDN (Open Connect) inside ISP networks.
```

---

### Storage

**Video storage:**
```
Movies: 10,000 titles × 4 GB × 10 variants = 400 TB
TV: 5,000 series × 30 GB × 10 variants = 1.5 PB
Total: ~2 PB for full library

With subtitles, thumbnails, metadata: ~3 PB total
```

**User data storage:**
```
250M users × 10 KB (profile, history, preferences) = 2.5 TB
Watch history: 250M × 100 shows × 100 bytes = 2.5 TB
Total user data: ~5 TB
```

---

### Cache Estimation

```
80/20 rule: Top 20% of titles (3,000 titles) account for 80% of streams
3,000 titles × 4 GB (HD) × 10 variants = 120 TB hot content
CDN edge caches: 200+ PoPs × 600 GB = 120 TB total edge capacity
→ Most popular content cached at all edges
→ Origin only delivers long-tail content
```

---

## Example 5: Design Uber — Capacity Estimation

### Assumptions
- 100M active riders
- 5M active drivers
- 20M trips/day
- Driver location update: every 5 seconds
- Trip average duration: 30 minutes
- Search radius: 5 km

---

### Location Update QPS

**Driver location updates:**
```
5M active drivers (assume 1M online at peak)
Each driver updates every 5 seconds
1M drivers ÷ 5 seconds = 200,000 location updates/sec = 200K writes/sec

Location data per update:
  driver_id: 8 bytes
  lat/lon:   16 bytes
  timestamp: 8 bytes
  status:    1 byte
  Total:     33 bytes

200K × 33 bytes = 6.6 MB/sec inbound for location data
```

---

### Ride Matching QPS

```
20M trips/day ÷ 86,400 sec = 231 trip requests/sec
Peak (5× — morning/evening commute): 1,155 requests/sec

Each request:
- Look up drivers within 5km radius
- Rank by ETA
- Assign driver

Geospatial query: Redis GEORADIUS within 5km
  Redis handles 100K+ geospatial queries/sec → headroom for 1,155/sec ✓
```

---

### Storage

**Trip records:**
```
20M trips/day × 1 KB per trip record = 20 GB/day
5-year retention: 20 GB × 365 × 5 = 36.5 TB
With GPS path data (1 point/5sec × 30min = 360 points × 16 bytes = 5.76 KB):
20M × 5.76 KB = 115 GB/day GPS data
5 years: 210 TB GPS data
Total trip data: ~250 TB over 5 years
```

**Driver location (ephemeral):**
```
1M online drivers × 33 bytes = 33 MB in Redis
Tiny — all fits in a single Redis instance
(Expire location after driver goes offline)
```

---

## Estimation Framework Summary

Use this mental template for any system:

```
1. USERS:
   - DAU = MAU × 30%  (rule of thumb)
   - Peak = 3× average (rule of thumb)

2. READS PER USER PER DAY:
   QPS_read = DAU × reads_per_day ÷ 86,400

3. WRITES PER USER PER DAY:
   QPS_write = DAU × writes_per_day ÷ 86,400

4. STORAGE PER RECORD × RECORDS_PER_DAY × RETENTION_DAYS

5. BANDWIDTH = QPS × average_message_size

6. CACHE = QPS_read × hit_ratio × object_size
   (20% objects = 80% traffic → cache 20% of working set)
```

---

## Quick Mental Math Tricks

| Problem | Trick |
|---------|-------|
| "X per day to per second" | Divide by 10^5 (close enough to 86,400) |
| "100M requests/day" | = 1,000 req/sec |
| "1B requests/day" | = 10,000 req/sec |
| "10B requests/day" | = 100,000 req/sec |
| "1M users × 1 KB each" | = 1 GB |
| "1B users × 1 KB each" | = 1 TB |
| "1B users × 1 MB each" | = 1 PB |

---

*Next: [Section 7 — Final Preparation Strategy](./Section-7-Final-Preparation-Strategy.md)*
