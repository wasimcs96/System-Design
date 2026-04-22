# Section 5: Detailed Design Solutions (5 Critical Systems)

> Each solution follows the structured interview framework:  
> Requirements → Capacity → Architecture → Deep Dive → Scaling → Trade-offs

---

## Solution 1: Design a URL Shortener (TinyURL)

---

### Step 1: Requirement Clarification

**Functional:**
- Given a long URL, generate a unique short URL
- Redirect short URL → original URL (within 100ms)
- Short URL custom aliases (optional feature)
- Analytics: click count per URL (optional)
- URL expiry (optional)

**Non-Functional:**
- 100M new URLs created per day
- 10:1 read-to-write ratio → 1B redirects/day
- Low latency: redirect < 100ms (p99)
- High availability: 99.99%
- Durability: URLs must not be lost

**Out of scope:** User authentication, URL preview, abuse detection

---

### Step 2: Capacity Estimation

**Writes:**
- 100M URLs/day ÷ 86,400 seconds = ~1,160 writes/sec
- Peak: ~3,000 writes/sec (assume 3× average)

**Reads:**
- 10:1 ratio → 1B redirects/day ÷ 86,400 = ~11,600 reads/sec
- Peak: ~35,000 reads/sec

**Storage:**
- Each URL record: 500 bytes (short code + long URL + metadata)
- 100M records/day × 365 days × 5 years = 182 billion records
- 182B × 500 bytes = ~91 TB over 5 years
- **Manageable with sharding**

**Cache:**
- 20% of URLs receive 80% of traffic (hot URLs)
- Cache top 20M URLs: 20M × 500B = 10 GB → fits comfortably in Redis

**Bandwidth:**
- Read: 35K req/sec × 500B = 17.5 MB/sec inbound
- Write: 3K req/sec × 500B = 1.5 MB/sec

---

### Step 3: High-Level Architecture

```
+--------+     +-------+     +-----------+     +----------+
| Client | --> |  CDN  | --> | API       | --> |  URL     |
|        |     | (miss)| --> | Gateway   | --> |  Service |
+--------+     +-------+     +-----------+     +----+-----+
                                                    |
                              +---------------------+-----+
                              |                           |
                         +----+----+               +------+-----+
                         |  Redis  |               | DynamoDB   |
                         |  Cache  |               | (URL store)|
                         +---------+               +------------+
                                                         |
                                                   +-----+-------+
                                                   | Analytics   |
                                                   | (Kafka +    |
                                                   |  ClickHouse)|
                                                   +-------------+
```

**CDN layer:** Cache the HTTP 301/302 redirect responses at the edge.  
The redirect response is cacheable: `Cache-Control: max-age=3600`

---

### Step 4: API Design

**Create Short URL:**
```
POST /v1/urls
Authorization: Bearer <token>
Content-Type: application/json

{
    "long_url": "https://www.amazon.com/dp/B08N5WRWNW?ref=very-long-tracking-param",
    "custom_alias": "amazon-fire",    // optional
    "expires_at": "2025-12-31"        // optional
}

Response 201 Created:
{
    "short_url": "https://tiny.ly/x8kQ2pR",
    "short_code": "x8kQ2pR",
    "long_url": "https://www.amazon.com/...",
    "created_at": "2024-01-01T00:00:00Z",
    "expires_at": "2025-12-31T00:00:00Z"
}
```

**Redirect:**
```
GET /x8kQ2pR

Response 301 Moved Permanently (or 302 for analytics):
Location: https://www.amazon.com/dp/B08N5WRWNW?ref=very-long-tracking-param
Cache-Control: max-age=3600
```

**Get URL Stats:**
```
GET /v1/urls/x8kQ2pR/stats

Response 200:
{
    "short_code": "x8kQ2pR",
    "click_count": 15842,
    "created_at": "2024-01-01T00:00:00Z",
    "top_countries": ["US", "IN", "GB"]
}
```

---

### Step 5: Short Code Generation

**Option A: Hash-based (MD5/SHA256 + truncate)**
- MD5(long_url) → 128-bit hash → take first 43 bits → Base62 encode → 7-char code
- Problem: Collisions possible. Need collision detection + retry.
- Problem: Same URL always generates same code (deterministic)

**Option B: Auto-increment ID + Base62 encode (RECOMMENDED)**
```
Algorithm:
1. Insert URL into DB → get auto-increment ID (e.g., ID = 2,009,215,674)
2. Base62 encode: charset = [0-9A-Za-z] (62 characters)
3. 2,009,215,674 in base 62 = "x8kQ2pR" (7 characters)

Base62 allows: 62^7 = 3.5 trillion unique codes
```

**Base62 encoding:**
```python
def encode(num):
    charset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
    result = []
    while num > 0:
        result.append(charset[num % 62])
        num //= 62
    return ''.join(reversed(result))
```

**ID Generation for distributed system:**
- Use Twitter Snowflake ID generator (avoid DB bottleneck)
- OR: Use DynamoDB atomic counter per partition

---

### Step 6: Database Schema

**URLs Table (DynamoDB):**
```
Primary Key: short_code (String)

Attributes:
- short_code:   "x8kQ2pR"
- long_url:     "https://www.amazon.com/..."
- user_id:      "usr_123"
- created_at:   1704067200
- expires_at:   1735689600  // null if no expiry
- click_count:  15842       // approximate, async update
- is_active:    true
```

**GSI (Global Secondary Index):**
- GSI on `user_id` → enables "list all URLs by user" query

---

### Step 7: Caching Strategy

```
Read path:
1. Check Redis: GET short_code
2. HIT → return long_url (set redirect)
3. MISS → query DynamoDB → cache in Redis (TTL 24h) → return

Cache eviction: LRU + TTL
Cache size estimate: 20M hot URLs × 200 bytes = ~4 GB Redis
```

**Cache write-through for writes:**
- When new URL created → immediately cache in Redis
- Prevents cache miss on first read

---

### Step 8: Scaling Strategy

| Traffic Level | Strategy |
|--------------|----------|
| 1K req/sec | Single app server + MySQL |
| 10K req/sec | Add Redis cache, read replicas |
| 100K req/sec | Horizontal app scaling, DynamoDB |
| 1M req/sec | CDN for redirect responses, multi-region |

**Multi-region:**
- Active-active: Users in Asia hit Asia region, US users hit US region
- DynamoDB Global Tables: Automatic cross-region replication

---

### Step 9: Bottlenecks & Trade-offs

| Decision | Trade-off |
|----------|----------|
| 301 (permanent redirect) vs 302 (temporary) | 301: browser caches → less analytics accuracy. 302: every click hits server → accurate analytics |
| Cache TTL 24h | Stale if URL deleted/deactivated. Fix: explicitly invalidate cache on deactivation |
| Base62 vs custom alias | Base62 is predictable (sequential IDs). Consider adding random salt |
| Auto-increment ID | Sequential → guessable. Use shuffled ID or random suffix |
| Expiry check in cache | Cache may serve expired URL for up to TTL duration. Fix: store expires_at in cache and validate |

---

### Step 10: How Interviewers Extend This Problem

1. "How do you detect malicious URLs?" → Block list + Google Safe Browsing API scan on creation
2. "How do you prevent scraping all URLs?" → Rate limit, non-sequential codes, auth for creation
3. "How do you handle custom domain support?" → Map custom domain to tenant, route accordingly
4. "How do you implement URL analytics in real-time?" → Kafka click events → ClickHouse → Grafana

---

---

## Solution 2: Design WhatsApp (Chat at Scale)

---

### Step 1: Requirements

**Functional:**
- 1-to-1 messaging (text, media)
- Group messaging (up to 1000 members)
- Message delivery status: Sent ✓ → Delivered ✓✓ → Read ✓✓ (blue)
- Online/last seen presence
- Push notifications for offline users

**Non-Functional:**
- 2B users, 100M DAU
- 100B messages/day
- Message delivery < 500ms (p99)
- Availability: 99.99%
- End-to-end encryption

---

### Step 2: Capacity Estimation

**Messages:**
- 100B messages/day ÷ 86,400 = ~1.15M messages/sec
- Peak: ~3M messages/sec

**Users:**
- 100M DAU, assume average 1 active connection per user
- 100M WebSocket connections maintained

**Storage:**
- Average message size: 100 bytes (text) + metadata
- 100B messages/day × 100 bytes = 10 TB/day
- 7-year retention: 25 PB+ → use tiered storage

**Media:**
- 10% of messages have media (10B/day)
- Average media: 100 KB compressed
- 10B × 100 KB = 1 PB/day media → aggressive CDN caching needed

---

### Step 3: High-Level Architecture

```
           WebSocket              WebSocket
+--------+  /ws/connect  +----------+  /ws/connect  +--------+
| Alice  |<=============>| Chat     |<=============>|  Bob   |
| Device |               | Server A |               | Device |
+--------+               +----+-----+               +--------+
                              |
                    +---------+---------+
                    |                   |
              +-----+------+    +-------+------+
              |  Message   |    |   Presence   |
              |  Service   |    |   Service    |
              +-----+------+    +------+-------+
                    |                  |
          +---------+--------+   +-----+-------+
          | Message Store    |   |   Redis     |
          | (Cassandra)      |   |  (Online)   |
          +------------------+   +-------------+
                    |
             +------+------+
             | Push        |
             | Notification|
             | (APNS/FCM)  |
             +-------------+
```

---

### Step 4: Message Delivery Flow

**Online user (happy path):**
```
Alice sends message to Bob:
1. Alice → WebSocket → Chat Server A
2. Chat Server A → Message Service → store in Cassandra (async)
3. Chat Server A → look up Bob's connection in Redis connection registry
4. Bob's connection: Chat Server B, connection ID "conn_xyz"
5. Chat Server A → internal RPC → Chat Server B
6. Chat Server B → WebSocket → Bob's device
7. Bob's device ACKs delivery
8. Chat Server B → Message Service → update status = DELIVERED
9. Chat Server B → Chat Server A → Alice (✓✓ delivered)
```

**Offline user:**
```
Alice sends to Bob (offline):
1. Same as above until step 3
2. Bob's connection: NOT in Redis (offline)
3. Store message in Bob's message queue (Cassandra)
4. Trigger push notification → APNS/FCM
5. Bob comes online → pulls pending messages from queue
6. Bob's app ACKs receipt → update status = DELIVERED
```

---

### Step 5: Database Schema

**Messages Table (Cassandra — write-optimized, time-series):**
```sql
CREATE TABLE messages (
    conversation_id UUID,    -- chat_id for 1-to-1, group_id for group
    message_id      TIMEUUID,-- time-ordered UUID (natural sorting)
    sender_id       UUID,
    content         BLOB,    -- encrypted content
    content_type    TEXT,    -- 'text', 'image', 'video', 'audio'
    media_url       TEXT,    -- S3 URL if media
    status          TEXT,    -- 'sent', 'delivered', 'read'
    created_at      TIMESTAMP,
    PRIMARY KEY (conversation_id, message_id)
) WITH CLUSTERING ORDER BY (message_id DESC);
-- Partition by conversation_id → all messages in a chat together
-- Cluster by message_id → newest first
```

**User Connections (Redis — ephemeral):**
```
Key: user_connection:{user_id}
Value: {server_id, connection_id, last_heartbeat}
TTL: 60 seconds (refreshed by heartbeat)
```

**User Presence (Redis):**
```
Key: user_presence:{user_id}
Value: {status: "online"|"offline", last_seen: timestamp}
```

---

### Step 6: Group Message Fan-Out

**Challenge:** Group with 1000 members. One message = 1000 deliveries.

**Strategy 1: Fan-out on write (small groups < 100 members)**
```
Alice sends to group:
1. Message stored once in messages table
2. Fan-out service creates delivery record per group member
3. Each member's device fetches their delivery queue
```

**Strategy 2: Fan-out on read (large groups 100–1000 members)**
```
Alice sends to group:
1. Message stored once in messages table
2. Each member's client knows group_id
3. On reconnect, client fetches messages by group_id WHERE message_id > last_seen_id
4. No fan-out needed
```

**Hybrid:** < 100 members → fan-out on write; > 100 → fan-out on read

---

### Step 7: Scaling & Trade-offs

| Component | Scale Strategy |
|-----------|---------------|
| WebSocket servers | Horizontal scaling (sticky sessions by user_id using consistent hashing) |
| Message storage | Cassandra sharded by conversation_id |
| Connection registry | Redis cluster with consistent hashing |
| Media storage | S3 + CloudFront CDN (client uploads directly to S3, message contains CDN URL) |

**Trade-off: End-to-end encryption vs server-side search**
> With E2E encryption, server cannot read message content. Cannot do server-side spam filtering or message search. WhatsApp compromise: search is client-side only.

---

---

## Solution 3: Design Amazon E-commerce Platform (Order Placement)

---

### Step 1: Requirements

**Functional:**
- Browse product catalog
- Search products
- Add to cart / wishlist
- Place order with payment
- Order tracking
- Seller can manage inventory

**Non-Functional:**
- 300M DAU
- Product catalog: 500M products
- Orders: 10M orders/day (peak: 50M on Prime Day)
- Checkout latency: < 2 seconds
- Availability: 99.99%
- No overselling (inventory must be accurate)

---

### Step 2: High-Level Architecture

```
+----------+   +------+   +----------+   +----------+
| Web/App  |-->| CDN  |-->| API      |-->| Auth     |
| Client   |   |      |   | Gateway  |   | Service  |
+----------+   +------+   +----+-----+   +----------+
                               |
        +----------------------+------------------------+
        |                      |                        |
+-------+-------+    +---------+------+    +------------+------+
| Product       |    | Search         |    |  Cart             |
| Catalog       |    | Service        |    |  Service          |
| Service       |    | (Elasticsearch)|    |  (DynamoDB)       |
+-------+-------+    +----------------+    +------+------------+
        |                                         |
+-------+-------+                      +----------+---------+
| Product DB    |                      |  Order Service     |
| (DynamoDB)    |                      |  (PostgreSQL)      |
| + S3 (images) |                      +----------+---------+
+---------------+                                 |
                                       +----------+---------+
                                       | Payment  |Inventory|
                                       | Service  |Service  |
                                       +----------+---------+
                                                  |
                                       +----------+---------+
                                       |  Fulfillment       |
                                       |  Service           |
                                       +--------------------+
```

---

### Step 3: Order Placement Flow (Critical Path)

```
User clicks "Place Order":

1. POST /v1/orders
   - Idempotency key in header: X-Idempotency-Key: client-uuid

2. Order Service validates:
   - Items exist and are active
   - User is authenticated
   - Shipping address is valid

3. Inventory Service: soft-reserve items
   UPDATE inventory SET reserved = reserved + qty
   WHERE sku = ? AND (available - reserved) >= qty
   -- Optimistic locking: fails if insufficient stock

4. Payment Service: charge credit card
   - Create payment intent (authorize)
   - Capture on success

5. Order Service: create order record (PostgreSQL)
   - Status: CONFIRMED

6. Release inventory reservation → deduct from available
   UPDATE inventory SET available = available - qty, reserved = reserved - qty

7. Publish event: order_placed → Kafka

8. Fulfillment Service: create pick-pack-ship job

9. Notification Service: send order confirmation email/SMS

Response to user: Order ID + estimated delivery
```

**Saga Pattern for distributed transaction:**
```
Steps: Reserve Inventory → Charge Payment → Confirm Order

On payment failure:
- Compensating transaction: Release inventory reservation
- Order status: PAYMENT_FAILED

On fulfillment failure:
- Compensating transaction: Refund payment, release inventory
- Order status: CANCELLED
```

---

### Step 4: Database Schema

**Products Table (DynamoDB):**
```
PK: product_id
SK: "METADATA"

Attributes: title, description, brand, category, price, 
            seller_id, images[], rating_avg, review_count
```

**Inventory Table (DynamoDB — separate from product):**
```
PK: sku_id (product + variant)
Attributes: 
    warehouse_id: "WH-BLR-01"
    available:    150
    reserved:     10
    version:      45  // optimistic locking
```

**Orders Table (PostgreSQL — ACID required):**
```sql
CREATE TABLE orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL,
    status          order_status NOT NULL,  -- ENUM
    idempotency_key VARCHAR(255) UNIQUE,    -- prevent duplicate orders
    subtotal        DECIMAL(10,2),
    tax             DECIMAL(10,2),
    shipping        DECIMAL(10,2),
    total           DECIMAL(10,2),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE TABLE order_items (
    id          UUID PRIMARY KEY,
    order_id    UUID REFERENCES orders(id),
    sku_id      VARCHAR(50),
    product_id  UUID,
    quantity    INT,
    unit_price  DECIMAL(10,2),
    snapshot    JSONB  -- product details at time of order (price may change)
);
```

---

### Step 5: Inventory Concurrency (Preventing Overselling)

**Optimistic locking approach:**
```sql
-- Attempt to reserve
UPDATE inventory 
SET reserved = reserved + ?, version = version + 1
WHERE sku_id = ? 
  AND (available - reserved) >= ?
  AND version = ?;  -- version check

-- If 0 rows affected → conflict or insufficient stock → retry or fail
```

**DynamoDB approach (conditional write):**
```
UpdateItem with ConditionExpression:
"available - reserved >= :qty AND version = :expected_version"

If condition fails → ConditionalCheckFailedException → inventory insufficient or conflict
```

---

### Step 6: Product Search

```
Product Catalog (DynamoDB) → Change Data Capture → Kafka
                                                       ↓
                                            Search Indexer
                                                       ↓
                                            Elasticsearch
                                                       ↓
                                            GET /search?q=laptop&category=electronics&
                                                price_min=500&price_max=2000&sort=relevance
```

**Elasticsearch index mapping:**
```json
{
    "title": "text (analyzed)",
    "description": "text (analyzed)",
    "brand": "keyword (exact match)",
    "category": "keyword",
    "price": "float",
    "rating_avg": "float",
    "in_stock": "boolean",
    "created_at": "date"
}
```

---

### Step 7: Scaling for Prime Day (5x normal traffic)

1. **Pre-scale:** Auto Scaling Groups warmed up 2 hours before event
2. **Cache warming:** Pre-cache top 100K products
3. **Rate limiting:** Per-user checkout rate limit (prevent bots)
4. **Queue incoming orders:** If order service overloaded, queue orders in SQS, process async
5. **Feature flags:** Disable non-critical features (recommendations, reviews) under load
6. **Database:** DynamoDB auto-scaling, RDS read replicas scaled up
7. **CDN:** Pre-warm CloudFront edge caches with promotional images

---

---

## Solution 4: Design a Notification System

---

### Step 1: Requirements

**Functional:**
- Send notifications via multiple channels: Push (mobile), Email, SMS, In-app
- User can configure preferences (disable email, only push for certain types)
- Notification types: transactional (order update) + marketing (promotions)
- Scheduled notifications (send at 9 AM user's local time)
- Notification history

**Non-Functional:**
- 1B users
- 10B notifications/day across all channels
- Transactional delivery: < 5 seconds
- Marketing delivery: best-effort (minutes acceptable)
- Idempotent delivery (no duplicate notifications)

---

### Step 2: High-Level Architecture

```
Event Sources (Order, Payment, Marketing)
           |
           v
      Kafka Topics
      (event streams)
           |
           v
    Notification Service
    (fan-out + routing)
           |
    +------+-------+
    |      |       |
    v      v       v
  Push   Email    SMS
  Worker Worker  Worker
    |      |       |
  APNS   SendGrid Twilio
  FCM    SES
```

---

### Step 3: Fan-Out Architecture

**Problem:** 1 event → 1 notification per user. At 10B/day = 115K notifications/sec.

```
Step 1: Event arrives (e.g., ORDER_SHIPPED)
Step 2: Notification Service:
    - Look up user preferences (Redis cache, 5-min TTL)
    - If user has push enabled → enqueue to Push queue
    - If user has email enabled → enqueue to Email queue
    - If user opted out of marketing → skip

Step 3: Per-channel workers process independently:
    - Push Worker: Batch 1000 notifications → APNS/FCM batch API
    - Email Worker: Batch 100 emails → SES batch send
    - SMS Worker: Single sends (Twilio) with per-number rate limiting
```

**Kafka topic structure:**
```
notifications.push     → Push worker consumer group
notifications.email    → Email worker consumer group
notifications.sms      → SMS worker consumer group
notifications.inapp    → In-app worker consumer group
```

---

### Step 4: User Preference Lookup Optimization

**Problem:** 1B users, 115K notifications/sec → 115K DB reads/sec just for preferences.

**Solution:** Cache preferences aggressively
```
Redis key: user_preferences:{user_id}
Value: {push: true, email: false, sms: true, marketing: false, timezone: "Asia/Kolkata"}
TTL: 5 minutes

Miss rate: ~5% (preference changes are rare)
Cache DB reads: 5% × 115K = 5,750 DB reads/sec → manageable
```

---

### Step 5: Idempotent Delivery

**Problem:** Network retry → duplicate notification sent.

**Solution:**
```
notification_id = hash(event_id + user_id + channel)

Before sending:
1. SET NX notification_sent:{notification_id} "1" EX 86400
   -- Set only if Not eXists, expire after 24h
2. If Redis returns 1 → first delivery → proceed
3. If Redis returns 0 → already sent → skip (idempotent)
```

---

### Step 6: Scheduled Notifications

**Use case:** Marketing campaign at 9 AM local time across 1B users.

**Architecture:**
```
Campaign creation → store in campaign DB with target_time (per timezone)
Scheduler (cron): Every minute → query campaigns WHERE next_send_time <= NOW()
               → enqueue to notification queue
               → update next_send_time

Per-timezone batching:
- 9 AM IST → all users in IST timezone get scheduled
- 9 AM SGT → 2.5 hours later → all users in SGT timezone get scheduled
```

**Scale challenge:** 1B users × 1 daily marketing notification = 1B events in 24h = 11.5K/sec. Manageable if distributed across timezones.

---

---

## Solution 5: Design Amazon S3 (Distributed Object Storage)

---

### Step 1: Requirements

**Functional:**
- Store and retrieve objects (files) of arbitrary size (up to 5 TB)
- Object addressed by: bucket + key
- Upload, download, delete objects
- Versioning support
- Access control (public/private, bucket policies)

**Non-Functional:**
- 11 nines of durability (99.999999999%)
- 4 nines availability (99.99%)
- Exabytes of total storage
- Millions of requests/sec
- Objects are immutable after write (no partial updates)

---

### Step 2: High-Level Architecture

```
+--------+     +----------+     +-------------+     +------------------+
| Client |---->| API      |---->| Metadata    |---->| Placement        |
|        |     | Layer    |     | Service     |     | Service          |
+--------+     +----+-----+     +------+------+     +-------+----------+
                    |                  |                     |
                    |            +-----+------+     +--------+----------+
                    |            | Metadata   |     | Storage Nodes     |
                    |            | DB         |     | (Chunk Servers)   |
                    |            | (DynamoDB) |     | 3 AZs × N nodes   |
                    |                         +----------------------------+
                    |
             +------+------+
             | Data        |  (for large uploads: client streams directly)
             | Transfer    |
             +-------------+
```

---

### Step 3: Object Upload Flow

**Small objects (< 5 MB): Single PUT**
```
1. Client: PUT /bucket-name/object-key
   Headers: Content-Length, Content-Type, x-amz-checksum-sha256
   Body: object data

2. API Layer: authenticate, authorize bucket access

3. Metadata Service: 
   - Generate object_id
   - Determine chunk placement (which storage nodes)
   
4. Data written to 3 storage nodes (1 per AZ) synchronously before ACK
   - Rack-aware placement: different racks within each AZ

5. Metadata stored: {bucket, key, object_id, size, checksum, storage_nodes, version_id}

6. Response: 200 OK with ETag (MD5 of object)
```

**Large objects (> 5 MB): Multipart Upload**
```
1. Initiate: POST /bucket/key?uploads → returns upload_id

2. Upload parts in parallel:
   PUT /bucket/key?partNumber=1&uploadId=xxx → PartETag
   PUT /bucket/key?partNumber=2&uploadId=xxx → PartETag
   PUT /bucket/key?partNumber=3&uploadId=xxx → PartETag

3. Complete: POST /bucket/key?uploadId=xxx
   Body: list of (partNumber, ETag) pairs
   
4. Server assembles parts → single object

Benefits: 
- Resume on failure (re-upload only failed part)
- Parallel upload (faster for large files)
- 5 TB max object size (10,000 parts × 500 MB each)
```

---

### Step 4: Durability via Erasure Coding

**Replication (simpler, used for small objects):**
- Store 3 copies across 3 AZs
- Survives 2 AZ failures
- Storage overhead: 3× (200% overhead)

**Erasure Coding (used for large objects, S3's actual approach):**
```
Reed-Solomon (6+3) encoding:
- Split object into 6 data chunks + 3 parity chunks
- Distribute 9 chunks across 9 different storage nodes (different AZs/racks)
- Can reconstruct from any 6 of 9 chunks (tolerate 3 node failures)
- Storage overhead: 9/6 = 1.5× (50% overhead vs 200% for 3-copy replication)
```

**11 nines durability math:**
- Individual disk failure rate: ~0.1%/year
- Erasure coding + redundancy → probability of data loss: 10^-11/year

---

### Step 5: Metadata Service

**What metadata stores:**
```sql
-- Object metadata
bucket_name:     "my-bucket"
object_key:      "photos/vacation.jpg"
object_id:       "obj_f8a2c9..."
version_id:      "v1_20240101..."
size:            2048576
content_type:    "image/jpeg"
checksum:        "sha256:abc123..."
created_at:      2024-01-01T00:00:00Z
is_delete_marker: false

-- Storage location
chunk_locations: ["node-az1-rack2", "node-az2-rack5", "node-az3-rack1"]
```

**Metadata DB choice:** DynamoDB
- Key: `{bucket}/{key}` → maps to object metadata
- Partitioned by bucket (even distribution)
- GSI for listing: `bucket` → all keys in bucket (with pagination)

---

### Step 6: Versioning

```
Without versioning:
bucket/photo.jpg → always latest version

With versioning enabled:
bucket/photo.jpg → version_id: "ver_v3" (latest)
bucket/photo.jpg → version_id: "ver_v2"
bucket/photo.jpg → version_id: "ver_v1"

DELETE without version_id → creates delete marker (soft delete)
DELETE with version_id → permanent delete of that version

GET /bucket/photo.jpg → returns latest non-deleted version
GET /bucket/photo.jpg?versionId=ver_v1 → returns specific version
```

---

### Step 7: Access Control

```
Bucket policy (JSON):
{
    "Effect": "Allow",
    "Principal": {"AWS": "arn:aws:iam::123456789:user/alice"},
    "Action": ["s3:GetObject"],
    "Resource": "arn:aws:s3:::my-bucket/*"
}

Evaluation order:
1. Explicit Deny → always deny
2. Organization SCP → boundary
3. Bucket policy → allow/deny
4. IAM policy → allow/deny
5. Default → deny
```

**Pre-signed URLs (for time-limited access without credentials):**
```
Generate:
url = presign(bucket, key, expiry=3600, operation=GET)
→ URL contains signature and expiry timestamp

Client uses URL within 1 hour → no auth headers needed
After 1 hour → signature invalid → 403
```

---

### Step 8: Scaling Strategy

| Component | Scale Strategy |
|-----------|---------------|
| API Layer | Horizontal, stateless, behind NLB |
| Metadata Service | DynamoDB auto-scaling, DAX for hot metadata |
| Storage nodes | Add nodes per AZ, consistent hashing distributes load |
| Upload throughput | S3 Transfer Acceleration: CloudFront → optimized backbone → S3 |
| Download | CloudFront CDN for frequently accessed objects |
| Listing | DynamoDB pagination, cursor-based |

---

### Step 9: Trade-offs

| Decision | Trade-off |
|----------|----------|
| Eventual vs strong consistency | S3 chose strong read-after-write (2020) at slight latency cost |
| Erasure coding vs replication | EC: cheaper storage, higher CPU for encoding/decoding. Replication: simpler, fast reads |
| Flat namespace (bucket/key) | Simple to shard; cannot do efficient directory-listing (LIST is expensive) |
| Immutable objects | No in-place update. Versioning adds storage. Simplifies caching. |

---

*Next: [Section 6 — Capacity Estimation Practice](./Section-6-Capacity-Estimation.md)*
