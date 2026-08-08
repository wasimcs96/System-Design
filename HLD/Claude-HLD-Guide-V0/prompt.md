Act as a Senior Principal Engineer / Architect with 15+ years of experience designing large-scale distributed systems and conducting System Design (HLD) interviews at top product companies (Amazon, Google, Flipkart, Swiggy, Uber).

Generate a COMPLETE, STRUCTURED, INTERVIEW-READY guide covering ALL High-Level Design (HLD) concepts for system design interviews targeting SDE-2, SDE-3, and Staff Engineer roles.

For EVERY concept and every system design problem, follow this exact structure:

════════════════════════════════════════════
STRUCTURE PER CONCEPT / TOPIC
════════════════════════════════════════════

1. CONCEPT DEFINITION
   - One-line beginner definition (no jargon)
   - Technical depth definition
   - When to use it / why it matters in a real system

2. REAL-WORLD ANALOGY
   - One relatable analogy (restaurant, postal system, bank, etc.)
   - Map analogy components back to technical components

3. VISUAL ASCII DIAGRAM
   - Show the concept visually using boxes and arrows
   - Show before/after or comparison where applicable

4. DEEP TECHNICAL EXPLANATION
   - How it works internally
   - Step-by-step flow
   - Variants and sub-types

5. CODE / PSEUDOCODE EXAMPLE (where applicable)
   - Concrete example in PHP or pseudocode

6. TRADE-OFFS
   - Pros and cons table
   - When to choose this vs alternatives

7. INTERVIEW Q&A
   - 3–5 common interview questions on this topic
   - Model answers (2–3 sentences each)

8. KEY TAKEAWAYS BOX
   ┌─────────────────────────────────┐
   │ ✓ Point 1                       │
   │ ✓ Point 2                       │
   │ ✓ Point 3                       │
   └─────────────────────────────────┘

════════════════════════════════════════════
SECTION A — FOUNDATIONAL CONCEPTS
════════════════════════════════════════════

Cover ALL of the following in depth:

A1. SCALABILITY
   - Horizontal Scaling (scale-out) vs Vertical Scaling (scale-up)
   - Elasticity vs Scalability distinction
   - Stateless vs Stateful scaling
   - Auto-scaling strategies
   - Limits of each approach

A2. AVAILABILITY & RELIABILITY
   - SLA, SLO, SLI — definitions, differences, examples
   - Nines of availability (99.9% = 8.7h downtime/year, 99.99% = 52min, 99.999% = 5min)
   - MTTR (Mean Time to Recover), MTBF (Mean Time Between Failures)
   - Active-Active vs Active-Passive failover
   - Chaos Engineering — why and how

A3. CONSISTENCY MODELS
   - Strong Consistency
   - Eventual Consistency
   - Causal Consistency
   - Read-Your-Writes Consistency
   - Linearizability vs Serializability
   - When to choose which model

A4. CAP THEOREM
   - Consistency, Availability, Partition Tolerance
   - Why only 2 of 3 — with proof intuition
   - CP systems: HBase, ZooKeeper, MongoDB (default)
   - AP systems: Cassandra, DynamoDB, CouchDB
   - CA systems: RDBMS (no partition tolerance in practice)
   - Real-world nuance: CAP is not binary

A5. PACELC THEOREM (Extension of CAP)
   - Even without Partition: Latency vs Consistency trade-off
   - PA/EL systems: DynamoDB, Cassandra
   - PC/EC systems: MySQL, PostgreSQL
   - Why PACELC is more practical than CAP

A6. LATENCY & PERFORMANCE
   - Latency numbers every engineer MUST memorize:
     • L1 cache: 0.5 ns
     • L2 cache: 7 ns
     • RAM access: 100 ns
     • SSD random read: 150 µs
     • HDD seek: 10 ms
     • Same-datacenter round trip: 500 µs
     • Cross-region round trip: 150 ms
   - p50, p95, p99 latency — why percentiles matter
   - Tail latency and hedged requests

════════════════════════════════════════════
SECTION B — CORE INFRASTRUCTURE COMPONENTS
════════════════════════════════════════════

B1. LOAD BALANCING
   - L4 (Transport) vs L7 (Application) load balancers
   - Algorithms: Round-Robin, Weighted, Least Connections, IP Hash, Consistent Hash
   - Health checks and circuit breaking
   - Global Load Balancing (GeoDNS, Anycast)
   - Tools: NGINX, HAProxy, AWS ALB/NLB, Cloudflare

B2. CACHING
   - Why cache? Speed (RAM vs Disk), reduce DB load
   - Cache placement:
     • Client-side (browser cache, service worker)
     • CDN (edge cache)
     • Application-level (in-process: Guava, PHP array)
     • Distributed cache (Redis, Memcached)
     • Database query cache
   - Cache strategies (detailed with diagrams):
     • Cache-Aside (Lazy Loading)
     • Read-Through
     • Write-Through
     • Write-Back (Write-Behind)
     • Write-Around
   - Cache eviction policies: LRU, LFU, FIFO, TTL, Random
   - Cache invalidation — the "hardest problem in CS"
   - Cache stampede / thundering herd — solutions (mutex, probabilistic early expiry)
   - Redis vs Memcached — when to choose which
   - Distributed cache: Consistent hashing, replication, Twemproxy

B3. DATABASES — SQL vs NoSQL
   - Relational (PostgreSQL, MySQL): ACID, joins, schema-on-write
   - Document (MongoDB): flexible schema, JSON, horizontal scaling
   - Key-Value (DynamoDB, Redis): fastest lookups, simple model
   - Wide-Column (Cassandra, HBase): time-series, write-heavy
   - Graph (Neo4j): relationships, fraud detection, social
   - Search (Elasticsearch): full-text, inverted index, faceting
   - Time-Series (InfluxDB, TimescaleDB): metrics, monitoring
   - NewSQL (Spanner, CockroachDB): SQL + horizontal scale
   - Decision matrix: when to choose which
   - OLTP vs OLAP — row-oriented vs column-oriented

B4. DATABASE INDEXING
   - B-Tree index (range queries, sorted order)
   - Hash index (exact-match only)
   - Composite index — column order matters
   - Covering index — avoid table lookup
   - Partial index — index subset of rows
   - Full-text index — Elasticsearch / MySQL FULLTEXT
   - Index cardinality — why high-cardinality columns first
   - N+1 query problem — detection and fix
   - Slow query analysis — EXPLAIN ANALYZE

B5. SHARDING & PARTITIONING
   - Vertical vs Horizontal partitioning
   - Sharding strategies:
     • Range-based sharding
     • Hash-based sharding
     • Directory-based sharding
     • Geo-based sharding
   - Consistent Hashing — algorithm with virtual nodes
   - Hotspots — how to detect and resolve
   - Cross-shard joins and transactions — why to avoid
   - Resharding — data migration strategies

B6. REPLICATION
   - Why replicate: fault tolerance + read scaling
   - Single-Leader (Master-Slave): sync vs async replication
   - Multi-Leader: conflict resolution (LWW, CRDTs, operational transform)
   - Leaderless (Dynamo-style): quorum reads/writes (R + W > N)
   - Replication lag — read-your-writes consistency
   - Change Data Capture (CDC) — Debezium, DynamoDB Streams

B7. MESSAGE QUEUES & EVENT STREAMING
   - Message Queue vs Event Stream — fundamental difference
   - RabbitMQ: AMQP, work queues, topic/fanout/direct exchanges, DLQ
   - Apache Kafka: partitions, consumer groups, offsets, retention, compaction
   - AWS SQS/SNS: managed queue and pub-sub
   - Delivery guarantees: At-most-once / At-least-once / Exactly-once
   - Idempotency — making at-least-once safe
   - Dead Letter Queues (DLQ)
   - Backpressure — slow consumer problem and solutions
   - Outbox Pattern — reliable event publishing

B8. CONSISTENT HASHING
   - Problem it solves (naive modulo: massive remapping on node add/remove)
   - Hash ring concept
   - Virtual nodes (vnodes) — why needed for even distribution
   - Algorithm step-by-step with example
   - Used in: Redis Cluster, Cassandra, Memcached, CDN
   - Implementation in code (pseudocode)

B9. CDN & EDGE COMPUTING
   - Pull CDN vs Push CDN
   - Edge caching: TTL, Cache-Control headers, cache busting
   - CDN for dynamic content: edge compute (Lambda@Edge, Cloudflare Workers)
   - Origin shield
   - Tools: Cloudflare, AWS CloudFront, Fastly, Akamai
   - When NOT to use CDN

B10. API DESIGN & GATEWAY
    - REST vs GraphQL vs gRPC — detailed comparison
      • REST: stateless, HTTP verbs, JSON, caching
      • GraphQL: single endpoint, flexible queries, N+1 problem
      • gRPC: binary (Protobuf), bi-directional streaming, lower latency
    - REST API best practices: versioning, pagination, error codes, idempotency keys
    - Pagination strategies:
      • Offset-based (simple, inconsistent under writes)
      • Cursor-based (stable, scales well — preferred for feeds)
      • Keyset-based (fast for sorted large datasets)
    - API Gateway responsibilities:
      • Auth/JWT validation
      • Rate limiting
      • Request routing
      • SSL termination
      • Request/response transformation
      • Circuit breaking
    - Tools: AWS API Gateway, Kong, NGINX, Traefik

════════════════════════════════════════════
SECTION C — DISTRIBUTED SYSTEMS PATTERNS
════════════════════════════════════════════

C1. RATE LIMITING
    - Why: protect from abuse, ensure fair usage, prevent DDoS
    - Algorithms (detailed with diagrams):
      • Fixed Window Counter
      • Sliding Window Log
      • Sliding Window Counter (hybrid — best trade-off)
      • Token Bucket (smooth bursts)
      • Leaky Bucket (constant output rate)
    - Distributed rate limiting with Redis (atomic Lua scripts)
    - Rate limiting at different layers (client, gateway, service)
    - Headers: X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After

C2. CIRCUIT BREAKER PATTERN
    - States: Closed → Open → Half-Open
    - Why needed: cascading failures in microservices
    - Configuration: failure threshold, timeout window, success threshold
    - Tools: Resilience4j, Hystrix
    - Circuit breaker vs Retry vs Timeout — combined strategy

C3. SAGA PATTERN (DISTRIBUTED TRANSACTIONS)
    - Why 2PC fails in microservices
    - Choreography-based Saga (events, no central coordinator)
    - Orchestration-based Saga (central orchestrator process)
    - Compensating transactions — how to rollback
    - Real example: Order placement (Order → Inventory → Payment → Delivery)

C4. CQRS (COMMAND QUERY RESPONSIBILITY SEGREGATION)
    - Separate read model from write model
    - Why: read and write scaling requirements differ
    - Event Sourcing + CQRS — natural combination
    - Eventual consistency between command and query side
    - When to use vs when NOT to use

C5. EVENT SOURCING
    - Store events, not state
    - Event log as the source of truth
    - Replay to reconstruct state
    - Snapshots for performance
    - Trade-offs: audit trail ✓, storage growth ✓, eventual consistency ✗

C6. DISTRIBUTED LOCKS
    - Why needed: mutual exclusion in distributed environment
    - Redis RedLock algorithm
    - Fencing tokens — protecting against GC pauses
    - ZooKeeper ephemeral nodes as locks
    - Database-level locking (advisory locks)

C7. SERVICE DISCOVERY
    - Client-side discovery vs Server-side discovery
    - Service registry: Consul, Eureka, ZooKeeper, etcd
    - DNS-based discovery
    - Health check integration
    - Tools in cloud: AWS Cloud Map, Kubernetes kube-dns

C8. DISTRIBUTED CONSENSUS & LEADER ELECTION
    - Why needed: single-leader decisions in distributed systems
    - Paxos (conceptual — complex)
    - Raft (practical — used in etcd, CockroachDB)
    - Leader Election with ZooKeeper ephemeral sequential nodes
    - When to use: database primary, cron job coordinator, lock manager

C9. GOSSIP PROTOCOL
    - Epidemic-style information spreading
    - O(log N) convergence
    - Used in: Cassandra (node discovery), DynamoDB, Redis Cluster
    - Failure detection via gossip + heartbeats
    - SWIM protocol

C10. VECTOR CLOCKS & CONFLICT RESOLUTION
    - Problem: multi-leader write conflicts
    - Vector clocks — causality tracking
    - Last-Write-Wins (LWW) — simple but lossy
    - CRDTs (Conflict-free Replicated Data Types) — merge without conflicts
    - Operational Transform — Google Docs approach

C11. BLOOM FILTERS
    - Probabilistic set membership test
    - False positives possible, false negatives impossible
    - Space-efficient — bits, not actual values
    - Use cases: URL dedup (webcrawler), cache miss optimization, spam filter
    - Counting Bloom Filter (supports delete)

C12. CONSISTENT ID GENERATION
    - UUID v4: random, no coordination needed, 128-bit
    - Auto-increment: simple but single-point bottleneck
    - Twitter Snowflake: 64-bit = timestamp(41) + datacenter(5) + machine(5) + sequence(12)
    - Instagram ID: epoch ms + shard ID + sequence
    - UUID v7 (time-ordered) — new standard
    - When to use each approach

C13. GEOSPATIAL INDEXING
    - Geohash: encode lat/lng as string, nearby = common prefix
    - QuadTree: recursive spatial partitioning
    - S2 (Google): spherical geometry, hierarchical cells
    - H3 (Uber): hexagonal grid
    - PostGIS, DynamoDB Geo Library
    - Use case: Uber driver matching, restaurant search

C14. TOP-K & HEAVY HITTERS
    - Count-Min Sketch: approximate frequency with bounded error
    - HyperLogLog: approximate count-distinct
    - Lossy Counting algorithm
    - Use cases: trending topics, top searches, fraud detection

════════════════════════════════════════════
SECTION D — MICROSERVICES & ARCHITECTURE PATTERNS
════════════════════════════════════════════

D1. MICROSERVICES vs MONOLITH
    - Monolith: simple to build, hard to scale independently
    - Microservices: independent deploy, polyglot, complex ops
    - Strangler Fig Pattern: migrate monolith → microservices
    - Service granularity — how fine-grained to go
    - Data isolation: each service owns its data

D2. SERVICE MESH
    - What it is: infrastructure layer for service-to-service communication
    - Sidecar proxy pattern (Envoy, Linkerd)
    - Features: mTLS, observability, traffic management, circuit breaking
    - Control plane (Istio) vs Data plane
    - When to adopt: 20+ services, compliance/security requirements

D3. EVENT-DRIVEN ARCHITECTURE
    - Events vs Commands vs Queries
    - Event Bus: pub-sub (Kafka, SNS)
    - Event-carried state transfer
    - Event notification pattern
    - CQRS + Event Sourcing combination
    - Choreography vs Orchestration

D4. BULKHEAD PATTERN
    - Isolate failures to one "compartment" — don't let them spread
    - Thread pool isolation (Hystrix Bulkhead)
    - Connection pool partitioning
    - Resource isolation per tenant in multi-tenant systems

D5. DEPLOYMENT PATTERNS
    - Blue-Green Deployment: two identical environments, instant switch
    - Canary Release: route 5% traffic to new version first
    - Rolling Update: replace instances gradually
    - A/B Testing: route by user segment
    - Feature Flags (LaunchDarkly): decouple deploy from release

D6. CONTAINERS & ORCHESTRATION
    - Docker: containerization, image layers, Dockerfile
    - Kubernetes (K8s): pod, deployment, service, ingress, HPA
    - Service mesh with K8s
    - Sidecar injection
    - How orchestration enables zero-downtime deploys

D7. SERVERLESS & FUNCTIONS-AS-A-SERVICE
    - When to use: sporadic workloads, event triggers, no server management
    - Cold start problem — mitigation strategies
    - AWS Lambda, Google Cloud Functions
    - Limitations: timeout (15 min), stateless, cold start latency
    - Cost model: pay per execution

════════════════════════════════════════════
SECTION E — DATA PROCESSING & STORAGE
════════════════════════════════════════════

E1. BATCH vs STREAM PROCESSING
    - Batch: process large datasets periodically (Hadoop MapReduce, Apache Spark)
    - Stream: process events as they arrive (Apache Flink, Kafka Streams, Spark Streaming)
    - Lambda Architecture: batch layer + speed layer + serving layer
    - Kappa Architecture: streams only (simpler)
    - Use cases: recommendation engine (batch), fraud detection (stream)

E2. OBJECT STORAGE
    - S3-like architecture: bucket → object → key
    - Metadata service + chunk storage
    - Erasure coding (vs replication) — space efficiency
    - Presigned URLs — direct client upload
    - Multipart upload for large files
    - Lifecycle policies: S3 → Glacier transition

E3. DATABASE INTERNALS
    - LSM Tree (Log-Structured Merge): write-optimized (used in Cassandra, RocksDB)
      • Memtable → Immutable memtable → SSTable → Compaction
      • Write amplification vs Read amplification trade-off
    - B-Tree: read-optimized (used in PostgreSQL, MySQL)
    - Write-Ahead Log (WAL): durability guarantee before data written
    - MVCC (Multi-Version Concurrency Control): snapshot isolation, no read locks
    - Connection Pooling: PgBouncer, HikariCP — why needed

E4. DATA SERIALIZATION
    - JSON: human-readable, verbose, slow
    - Protocol Buffers (Protobuf): binary, schema-defined, 3–10x faster/smaller
    - Apache Avro: schema in header, good for Kafka
    - MessagePack: binary JSON
    - When to use each: internal APIs → Protobuf, external/public → JSON

E5. SEARCH SYSTEMS
    - Inverted index: word → list of document IDs (with positions)
    - TF-IDF scoring
    - Elasticsearch architecture: shards, replicas, segments, refresh interval
    - Write pipeline: ingest → analyze → tokenize → index
    - Full-text search vs Vector search (semantic/embedding-based)
    - Autocomplete (Trie in memory, Elasticsearch completion suggester)

E6. TIME-SERIES DATA
    - Challenges: high write rate, ordered by time, frequent aggregation
    - InfluxDB, TimescaleDB (PostgreSQL extension), Amazon Timestream
    - Downsampling — old data at lower resolution
    - Rollup tables for pre-aggregated metrics
    - Retention policies

════════════════════════════════════════════
SECTION F — OBSERVABILITY, SECURITY & RELIABILITY
════════════════════════════════════════════

F1. OBSERVABILITY (THE THREE PILLARS)
    - Metrics: counters, gauges, histograms, summaries
      • Prometheus + Grafana — scrape model
      • Push model: StatsD, CloudWatch
      • p99, p999 latency tracking
    - Logs: structured logging (JSON), log levels, correlation IDs
      • ELK Stack (Elasticsearch + Logstash + Kibana)
      • Fluentd, AWS CloudWatch Logs
    - Distributed Tracing: trace → spans → context propagation
      • OpenTelemetry (standard), Jaeger, Zipkin, AWS X-Ray
      • Trace ID across all services — request correlation
    - Alerting: PagerDuty, OpsGenie — alert fatigue management

F2. FAULT TOLERANCE & RESILIENCE PATTERNS
    - Retry with Exponential Backoff + Jitter
    - Timeout — every external call must have a timeout
    - Circuit Breaker (see C2)
    - Bulkhead (see D4)
    - Idempotency — retry-safe operations
    - Graceful Degradation — serve partial results
    - Fallback strategy — cached response, default value
    - Health checks: liveness probe vs readiness probe

F3. DISASTER RECOVERY
    - RPO (Recovery Point Objective): maximum acceptable data loss
    - RTO (Recovery Time Objective): maximum acceptable downtime
    - Backup strategies: full, incremental, differential
    - Multi-region active-active vs active-passive
    - Database failover: automatic (Aurora) vs manual (RDS)
    - Runbook / playbook — documented recovery procedures
    - Chaos Engineering (Netflix Chaos Monkey)

F4. SECURITY
    - Authentication: JWT, OAuth 2.0, OpenID Connect, API Keys
    - Authorization: RBAC (Role-Based), ABAC (Attribute-Based), ACL
    - mTLS — mutual authentication in microservices
    - Encryption: in-transit (TLS 1.3), at-rest (AES-256)
    - Secrets management: AWS Secrets Manager, HashiCorp Vault
    - OWASP Top 10: SQLi, XSS, CSRF, IDOR, rate limiting bypass
    - DDoS protection: Cloudflare, AWS Shield, WAF
    - Input validation and sanitization at every boundary

F5. NETWORKING ESSENTIALS
    - DNS resolution: A record, CNAME, TTL
    - TCP vs UDP — when each is appropriate
    - HTTP/1.1 vs HTTP/2 (multiplexing) vs HTTP/3 (QUIC/UDP)
    - Long Polling vs WebSockets vs SSE (Server-Sent Events)
      • Long Polling: simple, inefficient (repeated connections)
      • WebSocket: full-duplex, low latency (chat, gaming)
      • SSE: server→client only, simple (live dashboard, notifications)
    - Connection pooling, keep-alive
    - Anycast routing

════════════════════════════════════════════
SECTION G — CAPACITY ESTIMATION (MANDATORY SECTION)
════════════════════════════════════════════

G1. ESTIMATION FORMULAS
    - 1 million req/day = ~12 req/sec (÷ 86,400)
    - 100M DAU, 5 actions/day = 500M req/day = ~6,000 QPS
    - 1 photo (1 MB) × 10M uploads/day = 10 TB/day
    - 1 character = 1 byte, 1 tweet = 280 bytes
    - Read:Write ratio — assume 10:1 unless told otherwise

G2. STORAGE ESTIMATION TEMPLATE
    - Per-entity size × number of entities × retention period
    - Image: ~1 MB, Video: ~100 MB/min, Text tweet: 280B
    - Add 20% overhead for metadata, indexes, replication

G3. BANDWIDTH ESTIMATION
    - QPS × average payload size = bandwidth
    - Inbound vs Outbound (CDN handles outbound)

G4. SERVERS ESTIMATION
    - Assume 1 server handles ~1,000 concurrent connections (synchronous)
    - Async (Node.js, Go): 1 server handles ~10,000+ concurrent
    - Formula: QPS ÷ requests_per_server_per_second = number of servers

G5. PRACTICE PROBLEMS (Work through each):
    - URL Shortener: 100M URLs, 10:1 read:write, storage for 5 years
    - WhatsApp: 500M DAU, 50 messages/user/day, media messages 5%
    - Netflix: 200M users, 2h video/day, 10 Gbps bandwidth per CDN node
    - Instagram: 1B users, 50M photos/day, 500M feed reads/day
    - Twitter: 300M DAU, 100 tweets/day, 1000 follows/user (fan-out problem)

════════════════════════════════════════════
SECTION H — TECHNOLOGY SELECTION GUIDE
════════════════════════════════════════════

H1. DATABASE DECISION MATRIX
    ┌─────────────────┬──────────────────────────────────────────┐
    │ Use Case         │ Best Choice                              │
    ├─────────────────┼──────────────────────────────────────────┤
    │ ACID transactions│ PostgreSQL, MySQL                        │
    │ Flexible schema  │ MongoDB                                  │
    │ High write       │ Cassandra, DynamoDB                     │
    │ Key-value lookup │ Redis, DynamoDB                         │
    │ Social graph     │ Neo4j                                    │
    │ Full-text search │ Elasticsearch                           │
    │ Time-series      │ InfluxDB, TimescaleDB                   │
    │ Global scale     │ DynamoDB, Spanner, CockroachDB          │
    │ Analytics (OLAP) │ Redshift, BigQuery, ClickHouse          │
    └─────────────────┴──────────────────────────────────────────┘

H2. CACHE DECISION MATRIX
    - Redis vs Memcached:
      • Redis: data structures, persistence, pub/sub, Lua scripts → PREFERRED
      • Memcached: pure cache, multi-threaded, simpler → only for very high throughput
    - Cache-Aside vs Write-Through:
      • Cache-Aside: tolerate stale data, read-heavy → USE THIS BY DEFAULT
      • Write-Through: require fresh data (payment, inventory)

H3. MESSAGE QUEUE DECISION MATRIX
    - Kafka: event streaming, replay, audit log, high throughput (millions/sec)
    - RabbitMQ: task queues, routing, low-latency, DLQ, complex routing rules
    - AWS SQS: simple managed queue, at-least-once, auto-scale
    - AWS SNS: fan-out pub-sub, push notifications

H4. AWS SERVICES REFERENCE
    - Compute: EC2, Lambda, ECS, EKS, Fargate
    - Storage: S3, EBS, EFS, Glacier
    - Database: RDS (Aurora), DynamoDB, ElastiCache, Redshift
    - Queue/Events: SQS, SNS, Kinesis, EventBridge
    - Networking: API Gateway, CloudFront, Route53, VPC, ALB/NLB
    - Observability: CloudWatch, X-Ray, OpenSearch (ELK)
    - Security: IAM, Secrets Manager, KMS, WAF, Shield

════════════════════════════════════════════
SECTION I — SYSTEM DESIGN METHODOLOGY
════════════════════════════════════════════

I1. THE 8-STEP FRAMEWORK (Use in every interview)

    Step 1 — Clarify Requirements (5 min)
      • "What are the 3–5 core use cases?"
      • "How many users? Daily Active Users? Peak QPS?"
      • "Any latency / SLA requirements?"
      • "Globally distributed or single region?"
      • "What's OUT of scope?"

    Step 2 — Functional Requirements
      • List 4–6 key features: "Users can X, Y, Z"
      • Prioritize: core vs nice-to-have

    Step 3 — Non-Functional Requirements
      • Scale: 100M users, 50K QPS
      • Latency: p99 < 100ms for reads
      • Availability: 99.99%
      • Consistency: eventual acceptable? or strong required?
      • Durability: no data loss?

    Step 4 — Capacity Estimation (3 min)
      • QPS (read + write), Storage, Bandwidth
      • State assumptions out loud

    Step 5 — High-Level Architecture (5 min)
      • Draw: Client → CDN → LB → App Servers → DB → Cache → Queue
      • Name the technologies at each component

    Step 6 — API Design (3 min)
      • Core endpoints: POST /short-url, GET /:code
      • Request/response structure
      • Auth headers

    Step 7 — Deep Dive (15 min)
      • Pick 2–3 critical components
      • Explain algorithms (consistent hashing, ID generation)
      • Show data models / schemas
      • Handle edge cases

    Step 8 — Bottlenecks & Trade-offs (5 min)
      • Single points of failure → how addressed
      • Scaling bottlenecks → solution
      • What you'd change at 10x scale
      • Cost vs performance trade-offs

I2. SDE-2 vs SDE-3 vs STAFF EXPECTATIONS

    │ Dimension            │ SDE-2                 │ SDE-3                  │ Staff/Principal       │
    ├──────────────────────┼───────────────────────┼────────────────────────┼───────────────────────┤
    │ Scope                │ Single service        │ Full platform          │ Multi-system strategy │
    │ Trade-offs           │ When prompted         │ Proactively            │ Business impact too   │
    │ Failure handling     │ Node failures         │ Region failures        │ Multi-region chaos    │
    │ Cost awareness       │ Not required          │ Mentioned              │ Quantified ($/month)  │
    │ Ambiguity            │ Needs prompting       │ Asks right questions   │ Shapes requirements   │
    │ Data modeling        │ For stated req        │ Anticipates patterns   │ Access pattern-driven │
    │ Tech choices         │ Common options        │ Justified with nuance  │ Custom solutions too  │

════════════════════════════════════════════
SECTION J — EXAMPLE SYSTEM DESIGNS (MANDATORY — 15 SYSTEMS)
════════════════════════════════════════════

For EACH of the following 15 systems, provide:
  (a) Problem statement + core use cases
  (b) Functional + Non-Functional requirements
  (c) Capacity estimation (numbers + formulas)
  (d) High-Level Architecture Diagram (ASCII)
  (e) API Design (key endpoints)
  (f) Data Model / Schema
  (g) Critical Component Deep-Dive
  (h) Scaling Strategy
  (i) Trade-offs
  (j) Common follow-up interview questions

SYSTEMS TO DESIGN:

J1.  URL Shortener (TinyURL) — ID generation, redirect, analytics
J2.  WhatsApp / Chat System — WebSocket, Kafka, Cassandra, presence
J3.  Instagram / Photo Feed — CDN, S3, fan-out on write vs read, Redis
J4.  Twitter / News Feed — Fan-out on write (celeb problem), timeline
J5.  Uber / Ride-Sharing — Geohash matching, surge pricing, real-time GPS
J6.  Netflix / YouTube — Video upload pipeline, transcoding, CDN, adaptive bitrate
J7.  Amazon E-Commerce — Catalog, inventory, cart, order, payment (Saga)
J8.  Food Delivery (Swiggy/Zomato) — Restaurant search, order tracking
J9.  Google Search / Web Crawler — BFS crawler, inverted index, PageRank
J10. Notification System — Multi-channel (email/SMS/push), priority queue
J11. Rate Limiter — Token bucket, distributed Redis, gateway integration
J12. Distributed Cache (Redis-like) — Consistent hashing, eviction, replication
J13. Distributed Key-Value Store — LSM-tree, WAL, gossip, quorum
J14. Google Drive / Dropbox — File chunking, dedup, sync, versioning
J15. Payment System — Idempotency, reconciliation, double-entry ledger, Saga

════════════════════════════════════════════
SECTION K — TRADE-OFF DECISION FRAMEWORK
════════════════════════════════════════════

K1. CLASSIC TRADE-OFFS (Know all of these cold)
    - Consistency vs Availability (CAP)
    - Latency vs Consistency (PACELC)
    - Read performance vs Write performance (indexes, denormalization)
    - Storage cost vs Query speed (pre-aggregation, materialized views)
    - Simplicity vs Flexibility (SQL vs NoSQL)
    - Strong typing vs Schema flexibility (Protobuf vs JSON)
    - Fan-out on write vs Fan-out on read (social feed)
    - Synchronous vs Asynchronous processing
    - Monolith vs Microservices operational complexity

K2. DECISION PHRASES (Use in interviews)
    - "I'm choosing X here because [reason]. The trade-off is [Y], which is acceptable because [Z]."
    - "At this scale, [bottleneck] becomes a problem. I'd address it by [solution]."
    - "This violates strong consistency, but eventual consistency is acceptable here because [reason]."
    - "I'm using denormalization here to optimize for the read path at the cost of write complexity."

════════════════════════════════════════════
SECTION L — BOTTLENECKS & SCALING PLAYBOOK
════════════════════════════════════════════

L1. READ BOTTLENECK SOLUTIONS
    - Add read replicas
    - Add caching layer (Redis)
    - CDN for static/semi-static content
    - Denormalize / pre-compute
    - Async data loading

L2. WRITE BOTTLENECK SOLUTIONS
    - Shard the database (horizontal partitioning)
    - Write to queue (async processing)
    - Batch writes
    - Write-behind caching
    - Event sourcing (append-only log)

L3. SINGLE POINT OF FAILURE (SPOF) ELIMINATION
    - Every component must have a replica or failover
    - DNS failover (Route53 health-check routing)
    - Database: primary/replica with automatic failover (Aurora)
    - Cache: Redis Sentinel or Redis Cluster
    - Queue: Kafka with multiple brokers + ZooKeeper
    - App servers: behind load balancer, auto-scaling group

L4. HOT SPOT RESOLUTION
    - Hot partition in DB: add random suffix to partition key
    - Hot cache key: local cache + distributed cache (two-tier)
    - Celebrity problem in feed: fan-out on read for high-follower accounts

════════════════════════════════════════════
SECTION M — COMMON INTERVIEW RED FLAGS
════════════════════════════════════════════

INTERVIEWER DEDUCTIONS (Don't do these):
  🚨 Jump to coding without clarifying requirements
  🚨 No capacity estimation
  🚨 Single database, no sharding plan
  🚨 No caching layer mentioned
  🚨 No mention of failure handling
  🚨 Over-engineering for stated scale
  🚨 Using buzzwords without knowing internals (e.g., "just use Kafka")
  🚨 No trade-off discussion — only one option considered
  🚨 Ignoring the read/write ratio
  🚨 Monolith for clearly distributed problem without justification

WHAT IMPRESSES INTERVIEWERS:
  ✅ Clarify first, then design
  ✅ Name the trade-off before being asked
  ✅ "At 10x scale, this becomes a bottleneck because..."
  ✅ Justify every technology choice
  ✅ Think about failure modes proactively
  ✅ Quantify: "This adds ~₹X/month, acceptable because Y"
  ✅ Draw as you speak
  ✅ Reference real-world patterns: "Similar to how Uber does..."

════════════════════════════════════════════
SECTION N — QUICK REVISION REFERENCE
════════════════════════════════════════════

N1. CONCEPT → USE CASE CHEATSHEET (one-liner each)
N2. ALGORITHM CHEATSHEET (consistent hashing, token bucket, Snowflake ID)
N3. DATABASE CHOICE CHEATSHEET (one-liner decision per use case)
N4. AWS SERVICE CHEATSHEET
N5. "WHAT TO SAY" PHRASE BANK for each concept

════════════════════════════════════════════
OUTPUT REQUIREMENTS
════════════════════════════════════════════

- Begin with a Table of Contents (all 14 sections)
- Beginner → Intermediate → Advanced flow within each section
- Every concept has: definition, diagram, trade-off, interview Q&A
- Every system design has: estimation, diagram, schema, deep-dive, trade-offs
- All diagrams are ASCII text-based
- PHP code examples where algorithm implementation helps understanding
- Highlight interview phrases and model answers separately
- End each major section with a "Quick Revision Box"

════════════════════════════════════════════
GOAL
════════════════════════════════════════════

Prepare a candidate to confidently walk into any SDE-2, SDE-3, or Staff Engineer system design interview at:
  → Amazon, Google, Meta, Flipkart, Swiggy, Zomato, Uber, Paytm, PhonePe, Razorpay, CRED

And design any system from scratch in 45 minutes with:
  ✓ Correct estimation
  ✓ Right technology choices with justification
  ✓ Proactive trade-off discussion
  ✓ Failure mode awareness
  ✓ Scalable, production-grade architecture