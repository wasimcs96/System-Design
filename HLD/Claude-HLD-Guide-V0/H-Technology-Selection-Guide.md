# H — Technology Selection Guide

> **Section:** Methodology | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Overview

This guide provides decision trees and selection criteria for common technology choices in system design interviews. The right answer is always: "it depends" — but on specific, articulable factors.

---

## 2. Database Selection

```
CHOOSE DATABASE TYPE:

Do you need complex relations + ACID transactions?
  YES -> Relational DB (PostgreSQL, MySQL)
       - Multi-table transactions (order + payment + inventory)
       - Complex JOINs (reporting)
       - Strong consistency required
       - Examples: orders, payments, user accounts

  NO -> Is your access pattern key-value (lookup by ID)?
       YES -> Key-Value Store
             - Simple, ultra-fast reads: Redis, DynamoDB, Riak
             - Session storage, shopping carts, user preferences
             - Redis if you need data structures (lists, sorted sets, hashes)

       NO -> Is it wide-column (time-series or high write)?
            YES -> Wide-Column Store: Cassandra, HBase
                  - High write throughput (1M+ writes/sec)
                  - Time-series data, IoT sensor readings
                  - Read by partition key + clustering key
                  - Netflix: viewing history

            NO -> Is it document (flexible schema, nested)?
                 YES -> Document Store: MongoDB, DynamoDB
                       - Flexible schema (schema changes frequently)
                       - Nested/hierarchical data (product catalog with varied attributes)
                       - Blog posts, product listings, user profiles

                 NO -> Is it graph (relationships are the data)?
                      YES -> Graph DB: Neo4j, Neptune
                            - Social networks (friends of friends)
                            - Fraud detection (transaction graphs)
                            - Knowledge graphs, recommendation engines

                      NO -> Full-text search?
                           YES -> Search Engine: Elasticsearch, Solr
                                 - Product search, log search
                                 - Relevance-ranked results

RELATIONAL DB SELECTION:
PostgreSQL (prefer):
  - Complex queries, JOINs, window functions
  - PostGIS for geospatial data
  - JSONB for semi-structured data
  - Strong ACID, MVCC
  - Open source

MySQL (choose when):
  - Existing MySQL infrastructure
  - Simpler read-heavy workloads
  - Wide hosting support (shared hosting)

When to use BOTH (polyglot persistence):
  Primary store: PostgreSQL (source of truth)
  + Redis (cache + sessions)
  + Elasticsearch (search replica)
  + S3 (file storage)
```

---

## 3. Cache Selection

```
CHOOSE CACHE SOLUTION:

Application-level (single server, no network hop):
  - PHP APCu, in-memory array
  - Use for: expensive computation results, config
  - Invalidated on server restart

Distributed cache (shared across servers):
  Redis vs Memcached:

  Redis: CHOOSE when you need:
    + Data structures: lists, sorted sets, hashes, sets
    + Pub/Sub messaging
    + Lua scripts (atomic operations)
    + Persistence (optional: RDB snapshots, AOF)
    + Cluster/Sentinel for HA
    + Sorted sets for leaderboards, rate limiting
    + 95% of the time: choose Redis

  Memcached: CHOOSE only when:
    + Simpler key-value only (no data structures needed)
    + Need multi-threading for CPU-bound cache operations
    + Very large values (Memcached can cache larger objects)
    + Existing Memcached infrastructure

Cache for database reads (most common):
  - Cache-aside: app checks cache first, loads from DB on miss, writes to cache
  - TTL: 1 minute to 1 hour depending on data freshness requirement
  - Key: entity_type:id (e.g., "user:123", "product:456")

CDN (edge cache):
  - Static assets: CSS, JS, images -- TTL: 1 year (with cache-busting)
  - Semi-static API responses -- TTL: 1-5 minutes
  - Dynamic content: NOT cacheable (user-specific, real-time)
```

---

## 4. Message Queue / Event Streaming Selection

```
CHOOSE MESSAGING SOLUTION:

Simple task queue (background jobs):
  - Redis + Sidekiq/Horizon: simplest, PHP-native
  - SQS (AWS): managed, reliable, at-least-once delivery
  - Use for: email sending, image processing, webhooks

High-throughput event streaming:
  - Kafka: 1M+ messages/sec, persistent, consumer groups, replay
  - Use for: event sourcing, CDC, analytics pipeline, microservice events
  - Kinesis (AWS): managed Kafka alternative

Real-time fan-out (pub/sub):
  - Redis Pub/Sub: simple, in-memory, not persistent
  - Use for: live notifications, chat (ephemeral)
  - Kafka: persistent fan-out with consumer groups

DECISION:
  Need replay/persistence? -> Kafka or Kinesis
  Need simplicity + AWS? -> SQS
  Need lowest latency (push not poll)? -> Redis Pub/Sub
  Need background jobs with retry? -> Redis Queue (Horizon/Sidekiq)
  Need 1M+ events/sec with consumer groups? -> Kafka
```

---

## 5. Compute Selection

```
CHOOSE COMPUTE:

Always running, predictable load:
  -> Virtual Machines (EC2) or Containers (ECS/K8s)
  -> Better: Kubernetes (K8s) for microservices
  -> Consider: RDS, ElastiCache as managed services

Event-driven, unpredictable/spiky load:
  -> Serverless (Lambda, Cloud Functions)
  -> Max 15 min execution, no persistent state
  -> Great for: image processing, webhooks, cron jobs

CPU-intensive (ML inference, video transcoding):
  -> GPU instances (EC2 p3/p4, g4)
  -> Or: managed ML inference (SageMaker, Vertex AI)

Batch processing:
  -> Managed: EMR (Spark), AWS Batch
  -> Serverless batch: Lambda with SQS trigger

LOAD BALANCER:
  L4 (TCP): fastest, can't see HTTP content, for TCP/UDP protocols
  L7 (HTTP): sees URLs, headers, cookies -- sticky sessions, path routing
  -> Usually: L7 (ALB on AWS) for HTTP services
  -> L4 (NLB) for: low latency, non-HTTP, gaming, VoIP
```

---

## 6. Storage Selection

```
CHOOSE STORAGE:

Files/Blobs (images, videos, backups, logs):
  -> Object Storage: S3, GCS, Azure Blob
  -> Cheap, unlimited scale, CDN-friendly

Relational/transactional data:
  -> Block Storage (EBS) attached to DB server
  -> Or: managed DB (RDS, CloudSQL)

Shared filesystem between multiple servers:
  -> Network File System: EFS (AWS), NFS, CIFS
  -> Use for: legacy apps needing shared file access

Temporary working storage (Lambda, containers):
  -> Ephemeral: /tmp (Lambda: 10 GB, containers: configurable)

Long-term archives (compliance, cold data):
  -> S3 Glacier, S3 Deep Archive
  -> 100x cheaper than S3 Standard, hours to retrieve
```

---

## 7. Interview Q&A

**Q1: Cassandra vs DynamoDB vs PostgreSQL -- when to use each?**
> PostgreSQL: ACID, complex queries, joins, strong consistency -- user accounts, payments, orders. Cassandra: open-source, wide-column, tunable consistency, high write throughput (1M+/sec), multi-region -- IoT time-series, viewing history, messaging. DynamoDB: managed, serverless-friendly, predictable performance with provisioned capacity, simple key-value or single-table design -- e-commerce catalog, gaming leaderboards, session storage. Key question: do you need transactions and complex queries? -> PostgreSQL. Do you need massive write throughput with simple access patterns? -> Cassandra or DynamoDB.

**Q2: When would you NOT use Redis as a cache?**
> Redis may not be ideal when: (1) Data is too large -- Redis stores all data in memory; if you need to cache TBs, use Memcached or a disk-backed cache; (2) You need strong consistency -- Redis is eventually consistent in cluster mode; (3) Data is already fast enough -- if your DB query takes 2ms, the overhead of Redis serialization/network may not justify the cache; (4) Security sensitive data -- keys visible in memory, use encrypted Redis with AUTH; (5) Ephemeral environment -- Redis data can be lost on restart (without persistence), so don't use as primary store.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| Relational: ACID, JOINs, complex queries -- PostgreSQL preferred  |
| Key-Value: simple lookup, sessions -- Redis (95% of cases)        |
| Wide-column: high write throughput, time-series -- Cassandra      |
| Document: flexible schema, nested data -- MongoDB/DynamoDB        |
| Kafka: persistent event streaming, replay, 1M+ msg/sec            |
| Object storage: unlimited files -- S3 (cheap, CDN-friendly)      |
| Serverless: spiky load, short tasks, no idle cost -- Lambda       |
+--------------------------------------------------------------------+
```
