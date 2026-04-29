# I — System Design Interview Methodology

> **Section:** Methodology | **Level:** All | **Interview Frequency:** ★★★★★

---

## 1. Overview

A 45-minute system design interview has a predictable structure. Following a repeatable framework prevents panic, ensures coverage, and demonstrates senior engineering instincts. This guide provides the exact framework, common mistakes, and a worked example.

---

## 2. The 5-Step Framework

```
TIME ALLOCATION (45-minute interview):
  Step 1:  5 min — Requirements Gathering
  Step 2:  5 min — Capacity Estimation (back-of-envelope)
  Step 3: 10 min — High-Level Design (major components + data flow)
  Step 4: 15 min — Deep Dive (database schema, APIs, critical paths)
  Step 5:  5 min — Trade-offs + Failure Scenarios + Monitoring
  Buffer:  5 min — Questions from you + overflow

(55-min interviews: add 10 min to deep dive)
```

---

## 3. Step 1: Requirements Gathering (5 min)

**Purpose:** Narrow scope. Interviewers intentionally give vague problems to test if you can identify the right constraints.

**Always ask:**
- Functional requirements: "What are the 3 core features?" (not 10)
- Scale: "DAU? Global or single region?"
- Consistency: "Is it OK to show slightly stale data? How stale?"
- Availability: "What's the acceptable downtime? 99.9%? 99.99%?"
- Special constraints: "Any specific tech? On-premise or cloud?"

**Example (Design Instagram Feed):**
- FR: Upload photos, follow users, view personalized feed
- NFR: 500M DAU, globally distributed, feed can be 1-2s stale, 99.99% availability
- Explicitly out of scope: stories, reels, ads, DMs

```
REQUIREMENTS TEMPLATE:
Functional (what it does):
  1. [Core feature 1]
  2. [Core feature 2]
  3. [Core feature 3]

Non-Functional (quality attributes):
  - Scale: [DAU], [QPS estimate]
  - Latency: [p99 target, e.g., <200ms feed load]
  - Availability: [SLA, e.g., 99.99%]
  - Consistency: [strong/eventual, acceptable staleness]
  - Durability: [e.g., zero data loss for uploads]
```

---

## 4. Step 2: Capacity Estimation (5 min)

**Purpose:** Justify your architecture choices. 100 QPS system needs different solutions than 1M QPS.

```
ESTIMATE IN THIS ORDER:
1. QPS (read + write separately)
2. Storage (daily + total)
3. Bandwidth (if relevant)
4. Identify bottleneck: CPU? DB? Memory? Network?

Instagram Feed Example:
- Write QPS: 100M uploads/day / 86400 = ~1,160 uploads/sec (peak: ~10K)
- Read QPS: 500M users x 10 views/day / 86400 = ~58K reads/sec (peak: ~500K)
- Read:Write = 50:1 -> HEAVILY read dominant -> caching is critical
- Photo storage: 100M x 200KB = 20 TB/day -> Object storage (S3)
- Feed metadata: 1B x 200 bytes = 200 GB/day (manageable in DB)
```

---

## 5. Step 3: High-Level Design (10 min)

**Purpose:** Draw the main boxes and arrows. Get overall architecture agreed before diving deep.

**Components to include:**
- Clients (web, mobile)
- CDN (if media/static content)
- Load Balancer(s)
- App servers (stateless)
- Primary DB (relational or NoSQL)
- Cache (Redis)
- Message Queue (if async operations)
- Object Storage (if files)
- Search Service (if full-text search)

```
Instagram Feed High-Level:

[Mobile App] ---> [CDN] ---> [Photo Server]
                        ---> [API Gateway]
                                 |
                    [Load Balancer]
                         /                       [Feed Service]  [Upload Service]
                    |                |
              [Redis Cache]    [S3 Object Store]
              [Cassandra DB]   [Media CDN]
                    |
            [Fan-out Service]
                    |
            [Message Queue (Kafka)]
```

**Explain data flow in 2 sentences:** "When a user uploads a photo, the upload service stores it in S3 and emits an event to Kafka. The fan-out service consumes this, writes to each follower's feed in Cassandra, and invalidates their Redis cache."

---

## 6. Step 4: Deep Dive (15 min)

**Pick 2-3 critical components to dig into.** Ask the interviewer: "Would you like me to dive into the feed generation algorithm, the database schema, or the photo upload flow?"

**Database Schema:**
```sql
-- Users
users(id, username, email, created_at)

-- Follows
follows(follower_id, followee_id, created_at)
-- Index: (follower_id) for "who do I follow"
-- Index: (followee_id) for "who follows me"

-- Posts
posts(id, user_id, image_url, caption, created_at)
-- Index: (user_id, created_at DESC) for user profile page

-- Feed (pre-computed, Cassandra)
feed_items(user_id, post_created_at, post_id, post_user_id)
-- Partition key: user_id
-- Clustering key: post_created_at DESC (newest first)
```

**Critical API:**
```
GET /feed?user_id=123&cursor=<timestamp>&limit=20
Returns: [{ post_id, image_url, author, likes, created_at }]

POST /posts
Body: { image_data (base64), caption }
Returns: { post_id, upload_url }
```

**Feed Generation Strategies (pull vs push):**
```
PUSH (fan-out on write) -- Instagram uses this:
  When user A posts, write to all follower feeds NOW
  Pros: fast read (feed pre-computed)
  Cons: "celebrity problem" -- Kylie Jenner 300M followers = 300M writes per post
  Fix: hybrid -- celebrities use pull, regular users use push

PULL (fan-out on read):
  When user B opens app, query posts from all N followees
  Pros: simple, no fan-out problem
  Cons: slow read (N DB queries), not scalable for large N
```

---

## 7. Step 5: Trade-offs + Failure Scenarios (5 min)

**Always discuss proactively:**
- What happens when the primary DB goes down? (Read replica promotion)
- What if the cache goes down? (Fall through to DB -- Redis not critical path)
- What if Kafka consumer falls behind? (Dead letter queue, alert on consumer lag)
- Data consistency: is it OK to show duplicate posts in feed? (Usually yes)

**Trade-offs to mention:**
- Push fan-out: fast reads, slow writes for celebrities
- Cassandra: high write throughput, no JOINs, eventual consistency
- Denormalization: faster reads, data duplication (storage cost)

---

## 8. Common Mistakes to Avoid

```
BEGINNER MISTAKES:
  x Jump into solution without requirements
  x Design for exactly 1M users (not scalable, not flexible)
  x Forget about failure scenarios
  x Use one technology for everything (one DB for all)
  x Say "I would use microservices" without justification

INTERMEDIATE MISTAKES:
  x Over-engineer: 15 microservices for an app that serves 1K users
  x Ignore read/write ratio (determines caching strategy)
  x Forget CDN for media-heavy systems
  x Use synchronous calls everywhere (should use async for non-critical path)
  x Forget database indexes

HOW TO HANDLE "I DON'T KNOW":
  - "I'm not familiar with the exact implementation of X, but here's how I'd approach it..."
  - "I know [related concept Y] which works similarly because..."
  - "Let me reason through this from first principles..."
  Never say "I don't know" and stop. Always attempt to reason.
```

---

## 9. Worked Example: Design YouTube

**Step 1 - Requirements (2 min):**
- FR: Upload videos, stream videos, search videos, view counts
- Scale: 2B users, 500 hours video uploaded/minute
- NFR: 99.9% availability, eventual consistency OK, CDN required

**Step 2 - Estimation (3 min):**
- Upload: 500 hrs/min x 1GB/hr = ~500 GB/min = ~8 GB/sec ingress
- View QPS: 2B users x 5 views/day / 86400 = ~115K views/sec
- Storage: 500 GB/min x 1440 min/day = ~720 TB/day (with multiple resolutions: 3-4 PB/day)
- Key insight: read:write ~20:1, storage is the dominant challenge

**Step 3 - HLD (5 min):**
```
[Upload]   -> API Gateway -> Upload Service -> [Raw S3] -> Transcoding Queue
                                                              -> Transcoding Workers (1080p, 720p, 360p)
                                                              -> [Processed S3] -> [Video CDN]
[Stream]   -> API Gateway -> Video Service -> [Video CDN] (99% cache hits)
[Search]   -> API Gateway -> Search Service -> Elasticsearch
[Metadata] -> [PostgreSQL] (video metadata, user data)
[Views]    -> [Redis] (increment counter) -> [Cassandra] (persist)
```

**Step 4 - Deep Dive: Video Transcoding (5 min):**
- DAG (Directed Acyclic Graph) of transcoding jobs
- Each video: split into 1-minute chunks, transcode in parallel (50x speedup)
- Output: HLS (HTTP Live Streaming) -- adaptive bitrate
- Metadata: video_id, resolution, chunk_count, manifest_url stored in PostgreSQL

**Step 5 - Trade-offs (2 min):**
- Eventual consistency: view counts lag (Redis batches writes to Cassandra every 60s)
- CDN cache: videos cached for 1 year with content-addressable URLs (SHA256 hash in URL)
- Transcoding failure: retry queue with exponential backoff, DLQ after 3 failures

---

## 10. Interview Cheat Sheet

```
QUESTION: "Design [X]"

1. REPEAT back: "So we need to design X that supports Y at Z scale?"
2. ASK: "What are the 3 core features? What's the expected scale?"
3. STATE scope: "I'll focus on A, B, C and exclude D, E"
4. ESTIMATE: QPS, storage, bandwidth
5. DRAW: start with client -> CDN/LB -> service -> DB
6. EXPLAIN data flow: "When X happens, the system does Y, then Z"
7. DEEP DIVE: pick 1-2 interesting problems to solve in depth
8. TRADE-OFFS: proactively mention CAP trade-offs, consistency choices

SHOW THESE SENIOR INSTINCTS:
  - Separate read and write paths (CQRS)
  - Async by default (Kafka for non-critical path)
  - Cache invalidation strategy (not just "add a cache")
  - Failure modes (what if service X is down?)
  - Monitoring (SLI/SLO, error budget)
  - DB indexing (which queries are you optimizing for?)
```

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| Step 1-2 (10 min): Requirements + Estimation before drawing      |
| Step 3 (10 min): High-level diagram, explain data flow           |
| Step 4 (15 min): Deep dive 2-3 critical components              |
| Step 5 (5 min): Trade-offs, failure scenarios, monitoring        |
| Celebrity problem: hybrid push+pull for fan-out at scale         |
| Read:write ratio drives caching + database choice                |
| Never say "I don't know" and stop -- always reason out loud      |
+--------------------------------------------------------------------+
```
