# Section 4: Top 50 Amazon System Design Questions

> Each question includes: difficulty, key concepts tested, and important components.

---

## Category A: Beginner (10 Questions)

These are foundational questions. You must answer these fluently before any Amazon interview.

---

### A1. Design a URL Shortener (TinyURL)
- **Difficulty:** Beginner
- **Key Concepts:** Hashing, Base62 encoding, redirection, caching, scalability
- **Core Components:**
  - REST API: `POST /shorten` → returns short URL; `GET /{code}` → 301 redirect
  - ID generation: Auto-increment DB ID → Base62 encode (7 chars = 62^7 = 3.5 trillion URLs)
  - Storage: Key-value store (DynamoDB) — `short_code → original_url`
  - Cache: Redis — cache hot short codes (80% reads hit cache)
  - Analytics: Async click counter via Kafka
- **Critical trade-off:** 301 (permanent, browser caches) vs 302 (temporary, always hits server — better for analytics)
- **Scale considerations:** 100M URLs created, 10B reads/day = ~115K reads/sec

---

### A2. Design a Rate Limiter
- **Difficulty:** Beginner
- **Key Concepts:** Token bucket, sliding window, Redis, distributed counters
- **Core Components:**
  - Redis INCR + EXPIRE for counter-based limits
  - Token bucket in Redis Lua script (atomic check-and-decrement)
  - Rate limit at API Gateway level + service level
  - Return `429 Too Many Requests` with `Retry-After` header
- **Algorithm comparison:** Token bucket (allows burst) vs Fixed window (simpler) vs Sliding window (accurate)
- **Distributed challenge:** Synchronize counters across multiple rate limiter instances

---

### A3. Design a Key-Value Store (like Redis)
- **Difficulty:** Beginner
- **Key Concepts:** Hash tables, consistent hashing, replication, eviction policies
- **Core Components:**
  - In-memory hash map as primary data structure
  - Persistent storage: AOF (Append Only File) or RDB snapshots
  - Replication: Primary-replica setup
  - Eviction: LRU, LFU, or TTL-based
  - Cluster: Consistent hashing for key distribution across nodes
- **Trade-off:** Memory vs disk (hot data in memory, cold data can be evicted)

---

### A4. Design a Parking Lot System
- **Difficulty:** Beginner
- **Key Concepts:** OOP + HLD (hybrid), concurrency, database design
- **Core Components:**
  - Entities: ParkingLot, Floor, Spot, Vehicle, Ticket
  - Spot types: Compact, Large, Handicapped, Motorcycle
  - Check-in: Find nearest available spot, create ticket, mark spot occupied
  - Check-out: Calculate fee, process payment, free spot
  - Concurrency: Optimistic locking to prevent double-booking
- **Database schema:** spots (id, floor, type, occupied), tickets (id, spot_id, vehicle_id, entry_time, exit_time, fee)

---

### A5. Design a Cache System
- **Difficulty:** Beginner
- **Key Concepts:** LRU implementation, write policies, cache invalidation
- **Core Components:**
  - LRU implemented with: HashMap + Doubly Linked List
  - API: `get(key)`, `put(key, value)`, `evict()`
  - Distributed cache: Consistent hashing across nodes
  - Cache warming: Preload on startup
  - Invalidation strategies: TTL, event-based, cache-aside
- **Follow-up:** "How do you handle cache invalidation?" — The hardest problem in CS

---

### A6. Design a Simple Chat Application (1-to-1)
- **Difficulty:** Beginner
- **Key Concepts:** WebSockets, message storage, delivery status
- **Core Components:**
  - WebSocket connection between client and chat server
  - Message storage: DynamoDB (chatId + timestamp as composite key)
  - Presence service: Redis pub/sub for online/offline status
  - Push notification: APNS/FCM when user is offline
- **Message status:** Sent → Delivered → Read (update via WebSocket ACK)

---

### A7. Design a Hotel Booking System
- **Difficulty:** Beginner
- **Key Concepts:** Transactions, idempotency, concurrency, inventory management
- **Core Components:**
  - Search: Elasticsearch for hotel search by location, date, amenities
  - Inventory: Room availability with date-range blocking
  - Booking: Distributed transaction — reserve room + charge payment atomically
  - Idempotency: Prevent double bookings on retry
- **Concurrency problem:** Two users booking last room simultaneously → optimistic locking

---

### A8. Design a Traffic Light Controller System
- **Difficulty:** Beginner
- **Key Concepts:** State machines, timers, sensors, distributed coordination
- **Core Components:**
  - State machine: GREEN → YELLOW → RED → GREEN (each direction)
  - Sensors: Vehicle detection to dynamically adjust timing
  - Priority: Emergency vehicles override normal cycle
  - Coordination: Intersections share state to create "green wave"

---

### A9. Design a Leaderboard
- **Difficulty:** Beginner
- **Key Concepts:** Sorted sets, Redis, real-time ranking, time-windowed leaderboards
- **Core Components:**
  - Redis Sorted Set: `ZADD leaderboard score userId`
  - Rank lookup: `ZREVRANK leaderboard userId` → O(log N)
  - Range query: `ZREVRANGE leaderboard 0 99` → top 100
  - Time-windowed: Daily/weekly leaderboard using separate Redis keys with TTL
  - Persistence: Snapshot to DynamoDB periodically

---

### A10. Design a Task Scheduler / Job Queue
- **Difficulty:** Beginner
- **Key Concepts:** Queues, cron jobs, distributed locking, retry logic
- **Core Components:**
  - Job storage: DB table with `next_run_at`, `status`, `retry_count`
  - Scheduler: Cron-like loop polls `WHERE next_run_at <= NOW() AND status = 'pending'`
  - Worker pool: Multiple workers consume jobs
  - Distributed lock: Prevent two workers picking same job
  - Retry: Exponential backoff on failure
- **Advanced:** Kafka-based job queue with partition-per-job-type for parallelism

---

## Category B: Intermediate (20 Questions)

---

### B1. Design WhatsApp / Group Chat System
- **Difficulty:** Intermediate
- **Key Concepts:** Fan-out, message delivery guarantees, offline delivery, group chats
- **Core Components:**
  - WebSocket servers (stateful) with connection registry
  - Message queue per user for offline delivery
  - Group chat: Fan-out on write (small groups) vs fan-out on read (large groups)
  - End-to-end encryption key exchange
  - Media storage: S3 + CDN with client-side compression
- **Scale challenge:** Group with 1000 members, message = 1000 writes to individual queues

---

### B2. Design Instagram / Photo Sharing
- **Difficulty:** Intermediate
- **Key Concepts:** News feed, CDN, media storage, social graph
- **Core Components:**
  - Upload: Client → CDN edge → S3 (async thumbnail generation via Lambda)
  - News feed generation: Push model (fan-out on write) for regular users; Pull model for celebrities
  - Social graph: Neo4j or DynamoDB with follow relationships
  - Feed cache: Redis list per user, precomputed feed
- **Hybrid approach:** Push for <1M followers, pull for >1M followers

---

### B3. Design a Ride-Sharing App (Uber/Ola)
- **Difficulty:** Intermediate
- **Key Concepts:** Geospatial indexing, real-time location, matching algorithm, pricing
- **Core Components:**
  - Driver location: Redis Geo (GEOADD, GEORADIUS) — updated every 5 seconds
  - Matching: Find nearby drivers within 5km radius, rank by ETA
  - Trip state machine: REQUESTED → DRIVER_ASSIGNED → PICKUP → IN_TRIP → COMPLETED
  - Surge pricing: Lambda function monitoring demand/supply ratio
  - WebSocket: Real-time driver location to rider during trip
- **Database:** PostgreSQL for trips (ACID needed), Redis for live location

---

### B4. Design an E-commerce Shopping Cart
- **Difficulty:** Intermediate
- **Key Concepts:** Session vs persistent cart, merge on login, consistency
- **Core Components:**
  - Guest cart: Session-based (Redis, keyed by session ID)
  - Logged-in cart: DynamoDB (userId → cart items)
  - Merge on login: Combine guest cart + user cart
  - Price validation: Re-validate prices at checkout (prices change)
  - Inventory check: Soft reserve at add-to-cart, hard reserve at checkout
- **Eventual consistency OK** for cart, **strong consistency required** at checkout

---

### B5. Design a Distributed Message Queue (like SQS)
- **Difficulty:** Intermediate
- **Key Concepts:** Message persistence, visibility timeout, dead letter queue, ordering
- **Core Components:**
  - Message storage: Sharded across partitions by queue hash
  - Visibility timeout: Message hidden after dequeue, re-appears if not ACKed
  - Dead Letter Queue: Messages exceeding max receive count
  - FIFO guarantee: Per-message-group ordering
  - Durability: Write to N replicas before acknowledging
- **Scale challenge:** 100K messages/sec at 1KB = 100MB/sec ingestion

---

### B6. Design a Search Autocomplete System
- **Difficulty:** Intermediate
- **Key Concepts:** Trie, prefix matching, ranking, caching, real-time updates
- **Core Components:**
  - Trie data structure for prefix matching
  - For web scale: Trie stored in distributed cache (Redis)
  - Top-K suggestions per prefix (pre-computed, stored in hash)
  - Ranking: CTR (click-through rate) + recency + personalization
  - Update frequency: Batch update trie every 1–2 minutes (not real-time)
- **API:** `GET /autocomplete?q=amaz&limit=10` → returns ranked suggestions

---

### B7. Design a Video Streaming Platform (like Netflix/Prime)
- **Difficulty:** Intermediate
- **Key Concepts:** Video encoding, CDN, adaptive bitrate, content delivery
- **Core Components:**
  - Upload → Transcoding pipeline (Lambda + MediaConvert → multiple bitrates)
  - Storage: S3 for video segments (HLS: `.m3u8` manifest + `.ts` segments)
  - CDN: CloudFront edge caches segments near users
  - Adaptive Bitrate (ABR): Client selects bitrate based on network speed
  - Recommendation: Collaborative filtering (users who watched X also watched Y)
- **Manifest file:** `master.m3u8` → lists quality options → client selects based on bandwidth

---

### B8. Design a Web Crawler
- **Difficulty:** Intermediate
- **Key Concepts:** BFS/DFS traversal, deduplication, politeness, distributed crawling
- **Core Components:**
  - URL frontier: Priority queue of URLs to crawl
  - Fetcher: Download HTML, respect robots.txt, rate-limit per domain
  - Parser: Extract new URLs, text content
  - Deduplication: Bloom filter for URL deduplication (space-efficient)
  - Content dedup: SimHash to detect near-duplicate pages
- **Politeness:** Per-domain crawl delay, respect `Crawl-Delay` in robots.txt

---

### B9. Design a News Feed System (Facebook/Twitter Timeline)
- **Difficulty:** Intermediate
- **Key Concepts:** Fan-out models, caching, ranking, real-time updates
- **Core Components:**
  - Post creation → Kafka event
  - Fan-out service: Write post to followers' feed caches (fan-out on write)
  - Feed cache: Redis list per user (max 200 items)
  - Ranking: ML model scores posts (EdgeRank-like)
  - Hybrid: Push model for users with <1000 followers; pull from DB for celebrities
- **Challenge:** Celebrity with 10M followers posts → 10M cache writes needed

---

### B10. Design a Food Delivery App (Swiggy/DoorDash)
- **Difficulty:** Intermediate
- **Key Concepts:** Multi-party coordination, real-time tracking, ETA, payments
- **Core Components:**
  - Order service: Creates order, assigns to restaurant
  - Delivery assignment: Match available delivery partner using geospatial index
  - Real-time tracking: WebSocket updates for order status and delivery location
  - ETA: Machine learning model based on distance, traffic, restaurant prep time
  - Payment: Pre-authorize on order, capture on delivery

---

### B11. Design an API Gateway
- **Difficulty:** Intermediate
- **Key Concepts:** Routing, auth, rate limiting, load balancing, request transformation
- **Core Components:**
  - Routing: URL pattern → microservice mapping (config-driven)
  - Auth: JWT validation, OAuth2 token introspection
  - Rate limiting: Redis-backed per-client counters
  - Load balancing: Round-robin or least-connection to service instances
  - Request/response transformation: Header injection, payload modification
  - Circuit breaker: Fail fast when downstream service is down

---

### B12. Design a Distributed Logging System
- **Difficulty:** Intermediate
- **Key Concepts:** Log aggregation, indexing, search, retention
- **Core Components:**
  - Log agents: Filebeat/Fluentd on each host → Kafka
  - Stream processing: Flink/Kinesis process logs (filter, enrich, route)
  - Storage: Elasticsearch (hot: recent 7 days), S3 (cold: 90+ days)
  - Search: Kibana / custom UI on Elasticsearch
  - Retention: Data lifecycle policies (delete after 90 days)
- **Scale:** 100K services × 1000 logs/sec = 100M logs/sec

---

### B13. Design a Password Manager
- **Difficulty:** Intermediate
- **Key Concepts:** Encryption, zero-knowledge architecture, sync, security
- **Core Components:**
  - Client-side encryption: AES-256 with master password (PBKDF2 key derivation)
  - Zero-knowledge: Server stores only encrypted blobs, never plaintext
  - Sync: Version-based conflict resolution across devices
  - Sharing: Asymmetric encryption for vault sharing
- **Security critical:** Brute-force protection on master password, 2FA mandatory

---

### B14. Design an Email System (Gmail-like)
- **Difficulty:** Intermediate
- **Key Concepts:** SMTP, IMAP, inbox storage, search, spam filtering
- **Core Components:**
  - SMTP server: Receive inbound email, relay outbound
  - Inbox storage: Per-user message storage (blob + metadata in DB)
  - Search: Elasticsearch for full-text email search
  - Spam: ML model + reputation-based filtering (SpamAssassin-like)
  - Attachments: S3 + virus scan before delivery

---

### B15. Design a Recommendation Engine
- **Difficulty:** Intermediate
- **Key Concepts:** Collaborative filtering, content-based filtering, real-time vs batch
- **Core Components:**
  - Batch: ALS collaborative filtering on Spark (overnight model training)
  - Real-time: User recent interactions → feature vector → nearest neighbor lookup
  - Data: User interaction events (click, purchase, rate) → Kafka → feature store
  - Serving: Precomputed recommendations per user cached in Redis
- **Cold start:** New users → content-based recommendations based on profile

---

### B16. Design a Distributed File Storage (Dropbox-like)
- **Difficulty:** Intermediate
- **Key Concepts:** Chunking, deduplication, sync, versioning, conflict resolution
- **Core Components:**
  - Client: Chunk files into 4MB blocks, hash each chunk (SHA-256)
  - Deduplication: If chunk hash already stored, skip upload (just reference it)
  - Metadata DB: File tree, version history, chunk references
  - Sync: Event-based (changes → Kafka → notify other devices)
  - Conflict resolution: Last-write-wins or fork (create conflict copy)
- **Bandwidth optimization:** Only changed chunks are uploaded on update

---

### B17. Design a Subscription/Billing System
- **Difficulty:** Intermediate
- **Key Concepts:** Recurring payments, idempotency, proration, dunning
- **Core Components:**
  - Subscription state machine: ACTIVE → PAST_DUE → CANCELLED
  - Billing engine: Scheduled job triggers charges at cycle end
  - Idempotency: Idempotency key per billing attempt
  - Proration: Calculate partial-period credits/charges on plan change
  - Dunning: Retry failed payments with escalating delays (1, 3, 7 days)
- **Audit trail:** Every charge attempt logged with outcome

---

### B18. Design a Social Media Trending Topics System
- **Difficulty:** Intermediate
- **Key Concepts:** Stream processing, approximate counting, time windows
- **Core Components:**
  - Events: Tweet/post creation → Kafka
  - Stream processing: Apache Flink counts hashtag frequency in 5-minute windows
  - Approximate counting: Count-Min Sketch for space-efficient counting
  - Ranking: Score = frequency × recency_decay_factor
  - Output: Top 10 trends, refreshed every 2 minutes
- **Challenge:** Distinguish organically trending from coordinated manipulation

---

### B19. Design an Online Code Judge (LeetCode-like)
- **Difficulty:** Intermediate
- **Key Concepts:** Sandboxing, resource limits, job queuing, multi-language
- **Core Components:**
  - Submission API: Code + language + problem ID → job queue
  - Sandbox: Docker container per submission (CPU/memory limits, no network)
  - Execution: Run code against test cases, compare output
  - Result delivery: WebSocket or polling
  - Test case storage: S3 (large inputs), metadata in DB
- **Security critical:** Strict sandboxing to prevent malicious code execution

---

### B20. Design an Inventory Management System (Amazon Warehouse)
- **Difficulty:** Intermediate
- **Key Concepts:** Real-time inventory, reservations, multi-warehouse, consistency
- **Core Components:**
  - Inventory record: SKU × warehouse location × quantity
  - Reservation: Soft-reserve on add-to-cart, hard-reserve on order confirm
  - Multi-warehouse: Route order to nearest warehouse with stock
  - Consistency: Optimistic locking on inventory deduction
  - Events: `inventory_updated` → Kafka → search indexer, replenishment service
- **Critical:** Prevent overselling while maintaining high throughput

---

## Category C: Advanced (20 Questions)

---

### C1. Design a Distributed Cache (like Memcached / Redis Cluster)
- **Difficulty:** Advanced
- **Key Concepts:** Consistent hashing, gossip protocol, replication, eviction, cluster management
- **Core Components:**
  - Cluster topology: Consistent hashing ring with virtual nodes
  - Replication: N replicas per key, quorum reads/writes
  - Failure detection: Gossip protocol — nodes share health state
  - Eviction: LRU with clock algorithm
  - Persistence: AOF for durability
  - Hot key handling: Local L1 cache + distributed L2

---

### C2. Design a Distributed Database (like DynamoDB / Cassandra)
- **Difficulty:** Advanced
- **Key Concepts:** LSM tree, consistent hashing, tunable consistency, compaction
- **Core Components:**
  - Partitioning: Consistent hashing assigns key ranges to nodes
  - Replication: Synchronous to quorum, async to remaining replicas
  - Write path: Memtable → WAL → SSTable (LSM tree)
  - Read path: Memtable → SSTable bloom filter → SSTable block
  - Compaction: Merge SSTables, remove tombstones
  - Consistency: Configurable (ONE, QUORUM, ALL)

---

### C3. Design Google Maps / Location Service
- **Difficulty:** Advanced
- **Key Concepts:** Geospatial indexing, routing algorithms, map tiles, real-time traffic
- **Core Components:**
  - Map tiles: Pre-rendered tile images stored in S3, served via CDN (zoom levels 1-20)
  - Routing: Road graph (nodes + edges) stored in specialized graph DB
  - Shortest path: A* or Dijkstra on road graph with traffic weights
  - Traffic data: Real-time GPS signals from users → aggregate → update edge weights
  - Geospatial index: S2 geometry (Google) or H3 (Uber) for location queries

---

### C4. Design a Global Content Delivery Network (CDN)
- **Difficulty:** Advanced
- **Key Concepts:** Edge caching, anycast routing, cache invalidation, origin pull
- **Core Components:**
  - PoP (Points of Presence): 200+ global edge locations
  - DNS routing: Anycast returns nearest PoP IP
  - Cache hierarchy: Edge → Regional → Origin
  - Cache invalidation: Versioned URLs (immutable) vs TTL-based vs explicit purge
  - TLS termination: At edge to reduce origin load
  - DDOS protection: Absorb attacks at edge before reaching origin

---

### C5. Design Twitter at Scale (1B users)
- **Difficulty:** Advanced
- **Key Concepts:** Celebrity fan-out, timeline aggregation, global distribution
- **Core Components:**
  - Tweet storage: Distributed storage partitioned by tweet_id (snowflake ID)
  - Home timeline: Hybrid push-pull (push to <1M followers, pull for celebrities)
  - Social graph: Separate graph service (users + follow relationships)
  - Trends: Stream processing on tweet stream
  - Search: Real-time indexing of tweets via Kafka → Elasticsearch
  - Global: Active-active multi-region with conflict-free replication

---

### C6. Design a Payment Processing System (like Stripe)
- **Difficulty:** Advanced
- **Key Concepts:** ACID transactions, idempotency, PCI compliance, fraud detection
- **Core Components:**
  - Payment intent: Pre-validate, authorize without capturing
  - Charge: Idempotent capture with payment gateway
  - Ledger: Double-entry accounting system (immutable)
  - Fraud: Real-time ML scoring before charge
  - Retry: Exponential backoff with jitter, idempotency key
  - PCI scope: Minimize PCI scope — never store raw card data, use tokenization

---

### C7. Design a Search Engine (like Google Search)
- **Difficulty:** Advanced
- **Key Concepts:** Web crawling, inverted index, PageRank, query processing
- **Core Components:**
  - Crawler: Distributed crawling with URL frontier
  - Indexer: HTML parsing → tokenization → inverted index
  - Ranking: TF-IDF + PageRank + ML ranking signals
  - Query processor: Parse query → boolean retrieval → ranking → top-K
  - Index storage: Distributed inverted index sharded by term hash
  - Freshness: Crawl popular pages hourly, rare pages weekly

---

### C8. Design Amazon Prime Video (Video on Demand at Scale)
- **Difficulty:** Advanced
- **Key Concepts:** DRM, multi-bitrate encoding, global distribution, licensing
- **Core Components:**
  - Content ingestion: Studio delivery → transcoding pipeline (multi-bitrate HLS/DASH)
  - DRM: Widevine/FairPlay license server, encrypted segments
  - Content licensing: Per-region availability stored in license DB
  - Streaming: CloudFront delivers HLS segments, adaptive bitrate client
  - Analytics: Playback events (start, buffering, quality switch) → real-time dashboards

---

### C9. Design a Fraud Detection System
- **Difficulty:** Advanced
- **Key Concepts:** Real-time ML, feature engineering, low latency scoring, feedback loop
- **Core Components:**
  - Real-time scoring: <100ms decision required (synchronous path)
  - Feature store: Pre-computed user features (spend patterns, location history) in Redis
  - ML model: Gradient boosting or neural network served via TensorFlow Serving
  - Rules engine: Hard rules (velocity check, blacklist) + ML soft score combined
  - Feedback loop: Dispute outcomes → retrain model daily

---

### C10. Design Amazon S3 (Object Storage)
- **Difficulty:** Advanced
- **Key Concepts:** Distributed storage, erasure coding, metadata service, durability
- **Core Components:**
  - Metadata: Separate metadata layer (key-value: object path → storage location)
  - Data storage: Objects split into chunks, stored with erasure coding (11 9s durability)
  - Replication: Stored across 3+ AZs
  - Consistency: Strong read-after-write consistency (as of 2020)
  - Multipart upload: Split large files, upload parts in parallel
  - Lifecycle: Transition to S3-IA → S3 Glacier based on age

---

### C11. Design a Real-Time Analytics Platform (like Clickhouse / Druid)
- **Difficulty:** Advanced
- **Key Concepts:** OLAP, columnar storage, time-series aggregation, pre-aggregation
- **Core Components:**
  - Ingestion: Kafka → batch micro-aggregation → columnar storage
  - Storage: Column-oriented for fast aggregation (Parquet-like)
  - Query engine: Vectorized execution, pre-aggregated rollups
  - Compaction: Merge small files into larger segments
  - Retention: Hot (recent) in SSD, cold (historical) in S3

---

### C12. Design a Distributed Tracing System (like Jaeger / AWS X-Ray)
- **Difficulty:** Advanced
- **Key Concepts:** Trace context propagation, sampling, span correlation, storage
- **Core Components:**
  - Trace context: W3C Trace Context header propagated through all services
  - Sampling: Head-based (decide at start) or tail-based (sample after seeing full trace)
  - Span storage: Elasticsearch or Cassandra (high write throughput)
  - Query: Search by trace ID, service name, latency range
  - Visualization: Flame graph, waterfall view of spans

---

### C13. Design a Multi-Region Database Synchronization System
- **Difficulty:** Advanced
- **Key Concepts:** Conflict resolution, CRDT, replication lag, regional consistency
- **Core Components:**
  - Replication: Async replication with logical replication log
  - Conflict resolution: Last-write-wins (LWW) or CRDT for commutative operations
  - Consistency: Eventual consistency across regions, strong within region
  - Failover: DNS-based automatic failover to secondary region
  - Data gravity: User data pinned to home region, replicated globally

---

### C14. Design a Kubernetes-like Container Orchestration System
- **Difficulty:** Advanced
- **Key Concepts:** Scheduler, service discovery, health checking, resource management
- **Core Components:**
  - API server: REST API for cluster management
  - Scheduler: Place pods on nodes based on resource requirements + constraints
  - Controller: Desired state reconciliation (scale, restart failed pods)
  - etcd: Distributed KV store for cluster state
  - Networking: CNI plugin for pod networking
  - Service discovery: DNS-based (kube-dns)

---

### C15. Design Amazon Dynamo (Key-Value Store)
- **Difficulty:** Advanced
- **Key Concepts:** Consistent hashing, vector clocks, quorum, gossip, Merkle trees
- **Core Components:**
  - Partitioning: Consistent hashing with virtual nodes
  - Replication: Coordinator replicates to N-1 nodes
  - Consistency: Quorum-based (W + R > N guarantees overlap)
  - Conflict resolution: Vector clocks for version tracking, application-level merge
  - Failure detection: Gossip protocol
  - Anti-entropy: Merkle tree comparison for replica reconciliation

---

### C16. Design a Ride-Hailing Surge Pricing System
- **Difficulty:** Advanced
- **Key Concepts:** Real-time aggregation, geohashing, ML pricing, feedback loops
- **Core Components:**
  - Demand signal: Active ride requests per geohash cell (Redis sorted set)
  - Supply signal: Available drivers per geohash cell
  - Surge multiplier: demand/supply ratio → multiplier lookup table
  - ML model: Historical patterns + weather + events → predicted surge
  - Update frequency: Recompute every 30 seconds per cell

---

### C17. Design a Distributed Transaction System (Saga Pattern)
- **Difficulty:** Advanced
- **Key Concepts:** Saga choreography vs orchestration, compensating transactions
- **Core Components:**
  - Choreography: Services emit events, each service reacts and emits next event
  - Orchestration: Central saga coordinator calls each service in sequence
  - Compensating transactions: On failure, reverse previous steps
  - Idempotency: Each step must be idempotent (retried on failure)
  - State tracking: Saga state stored in DB (which steps completed)
- **Example:** Book flight → reserve hotel → charge card → (if any fails → cancel all)

---

### C18. Design a Global E-commerce Platform (Amazon at Scale)
- **Difficulty:** Advanced
- **Key Concepts:** Multi-region, microservices, eventual consistency, catalog vs order
- **Core Components:**
  - Product catalog: Global CDN-cached, eventually consistent, search via Elasticsearch
  - Orders: Strongly consistent, regional primary with global replication
  - Payments: Isolated service, ACID guaranteed, regionally compliant
  - Fulfillment: Warehouse selection, shipping partner integration
  - Personalization: Real-time recommendations per user per session

---

### C19. Design a Machine Learning Feature Store
- **Difficulty:** Advanced
- **Key Concepts:** Online vs offline features, low-latency serving, feature freshness
- **Core Components:**
  - Offline store: S3 + Spark for batch feature computation
  - Online store: Redis / DynamoDB for low-latency feature retrieval (<10ms)
  - Feature pipeline: Kafka → stream processing → online store update
  - Point-in-time correctness: Avoid data leakage — features must reflect state at prediction time
  - Versioning: Feature versions for model reproducibility

---

### C20. Design a Distributed ID Generator (like Twitter Snowflake)
- **Difficulty:** Advanced
- **Key Concepts:** Monotonic IDs, clock skew, uniqueness, throughput
- **Core Components:**
  - 64-bit ID structure: `[41-bit timestamp][10-bit machine ID][12-bit sequence]`
  - Timestamp: Milliseconds since epoch
  - Machine ID: Unique per service instance (from ZooKeeper)
  - Sequence: Per-millisecond counter (4096 IDs/ms per machine)
  - Clock skew: Wait if current time < last generated time
  - Throughput: 4096 × 1000 = 4M IDs/sec per machine

---

*Next: [Section 5 — Detailed Design Solutions](./Section-5-Detailed-Design-Solutions.md)*
