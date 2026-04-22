# Section 2: Complete Preparation Roadmap (Beginner → Advanced)

> Timeline: 6–10 weeks | Daily commitment: 2–3 hours

---

## Overview of Phases

| Phase | Duration | Focus |
|-------|----------|-------|
| Phase 1 | Week 1–2 | Fundamentals (Networking, OS, DB basics) |
| Phase 2 | Week 3–4 | Core System Design Concepts |
| Phase 3 | Week 5–6 | Distributed Systems Deep Dive |
| Phase 4 | Week 7 | Scalability Patterns |
| Phase 5 | Week 8 | End-to-End Design Framework |
| Phase 6 | Week 9–10 | Mock Interviews & Optimization |

---

## Phase 1: Fundamentals (Week 1–2)

### 1A. Networking Basics

**HTTP / HTTPS:**
- HTTP is stateless, text-based protocol over TCP
- HTTPS = HTTP + TLS encryption
- Know: request/response cycle, headers, status codes
- HTTP/1.1 vs HTTP/2 (multiplexing, header compression, server push)
- HTTP/3 (QUIC, UDP-based, reduced latency)

**Key HTTP concepts for interviews:**
```
GET    /users/123       → Idempotent, cacheable, no body
POST   /orders          → Creates resource, NOT idempotent
PUT    /users/123       → Full replace, idempotent
PATCH  /users/123       → Partial update, may not be idempotent
DELETE /users/123       → Idempotent
```

**TCP/IP:**
- TCP: connection-oriented, reliable, ordered delivery (3-way handshake)
- UDP: connectionless, fast, no guarantee (used for video streaming, DNS)
- Know the OSI/TCP layers conceptually:
  - Application (HTTP, DNS, FTP)
  - Transport (TCP, UDP)
  - Network (IP, routing)
  - Data Link / Physical

**DNS:**
- Domain → IP resolution
- DNS hierarchy: Root → TLD → Authoritative
- TTL controls caching duration
- Record types: A (IPv4), AAAA (IPv6), CNAME (alias), MX (mail), TXT
- Anycast DNS: request routed to nearest DNS server (used by Cloudflare, AWS Route 53)
- **Interview relevance:** Understand how CDN and global routing uses DNS-based load balancing

**WebSockets vs Long Polling vs SSE:**
| Method | Use Case | Connection |
|--------|----------|-----------|
| HTTP Polling | Simple, infrequent updates | New connection each time |
| Long Polling | Near-real-time, simpler setup | Held open until response |
| WebSockets | Chat, live notifications, gaming | Persistent bidirectional |
| Server-Sent Events | One-way stream (news feed) | Persistent, server → client |

---

### 1B. OS Basics (Relevant to System Design)

**Processes vs Threads:**
- Process: isolated memory space, heavier to create
- Thread: shared memory within a process, lighter weight
- Context switching cost: threads cheaper than processes
- **Relevance:** Multi-threaded servers (Nginx uses event loop, Apache uses thread-per-connection)

**Memory concepts:**
- Stack vs Heap memory
- Memory leaks in long-running services
- Virtual memory / paging

**Concurrency concepts:**
- Race conditions, deadlocks, mutex, semaphores
- Lock-free data structures
- **Interview relevance:** "How do you handle concurrent writes to the same user's cart?"

**File I/O:**
- Buffered vs unbuffered I/O
- Memory-mapped files (used in Kafka for log storage)
- Write-ahead logging (WAL) in databases

---

### 1C. Database Fundamentals

**SQL vs NoSQL — Decision Matrix:**

| Factor | SQL (PostgreSQL, MySQL) | NoSQL (DynamoDB, MongoDB, Cassandra) |
|--------|------------------------|--------------------------------------|
| Data structure | Structured, relational | Semi-structured, flexible schema |
| Query flexibility | High (ad-hoc SQL) | Limited (must know access patterns) |
| ACID compliance | Full | Varies (MongoDB: ACID per doc; Cassandra: eventual) |
| Scalability | Vertical + limited horizontal | Horizontal natively |
| Consistency | Strong | Configurable (eventual to strong) |
| Best for | Financial, ERP, complex joins | High-volume, low-latency, global scale |

**When to choose which:**
- E-commerce orders, financial transactions → PostgreSQL (ACID needed)
- User sessions, shopping cart → DynamoDB (high throughput, simple K-V)
- Time-series metrics, logs → Cassandra / InfluxDB (write-optimized)
- Search → Elasticsearch (full-text, relevance scoring)
- Relationships/graphs → Neo4j (social networks, recommendation engines)
- Cache → Redis (in-memory, sub-millisecond)

**ACID Properties (must know):**
- **A**tomicity: All or nothing transaction
- **C**onsistency: Database remains in valid state
- **I**solation: Transactions don't interfere with each other
- **D**urability: Committed data persists even after crashes

**Isolation Levels (important for concurrency):**
| Level | Dirty Read | Non-repeatable Read | Phantom Read |
|-------|-----------|--------------------|----|
| Read Uncommitted | ✓ possible | ✓ possible | ✓ possible |
| Read Committed | ✗ | ✓ possible | ✓ possible |
| Repeatable Read | ✗ | ✗ | ✓ possible |
| Serializable | ✗ | ✗ | ✗ |

---

### 1D. CAP Theorem (Deep Understanding)

```
         C (Consistency)
        / \
       /   \
      /     \
     P-------A
(Partition)  (Availability)
```

**In a network partition, you MUST choose:**

| Choice | Examples | Behavior During Partition |
|--------|---------|--------------------------|
| CP | HBase, ZooKeeper, Redis (cluster mode) | Returns error if can't guarantee consistency |
| AP | DynamoDB (default), Cassandra, CouchDB | Returns stale data rather than error |
| CA | Traditional RDBMS (single node) | Not possible in distributed system |

**PACELC Theorem** (extension of CAP — more realistic):
- When Partition: choose between A and C
- **Else** (normal operation): choose between L (Latency) and C (Consistency)
- DynamoDB: PA/EL (high availability + low latency)
- Aurora: PC/EC (consistency + consistency)

---

### 1E. Load Balancing Basics

**Algorithms:**
| Algorithm | How it Works | Best For |
|-----------|-------------|----------|
| Round Robin | Rotate through servers sequentially | Equal capacity servers |
| Weighted Round Robin | More requests to higher-capacity servers | Mixed capacity |
| Least Connections | Route to server with fewest active connections | Long-lived connections |
| IP Hash | Hash client IP → consistent server | Session stickiness |
| Random | Random server selection | Stateless services at scale |

**Layer 4 vs Layer 7 Load Balancers:**
- L4 (TCP level): Fast, no content inspection, used for raw TCP/UDP
- L7 (HTTP level): Can route by URL path, host header, cookie — used for microservices

**Health checks:**
- Active: LB pings servers periodically
- Passive: LB monitors response success rates
- Grace period: New instances get time to warm up before receiving traffic

---

## Phase 2: Core System Design Concepts (Week 3–4)

### 2A. Caching

**Caching layers:**
```
Client Browser Cache
        ↓
    CDN Cache (CloudFront)
        ↓
    API Gateway Cache
        ↓
    Application-level Cache (Redis/Memcached)
        ↓
    Database Query Cache
        ↓
    Database (Source of Truth)
```

**Caching strategies:**

| Strategy | Write Flow | Read Flow | Risk |
|----------|-----------|-----------|------|
| Cache-Aside (Lazy Loading) | Write to DB only | Read cache → miss → DB → populate cache | Stale data on DB updates |
| Write-Through | Write to cache + DB simultaneously | Read from cache | Higher write latency |
| Write-Behind (Write-Back) | Write to cache → async write to DB | Read from cache | Data loss if cache crashes |
| Read-Through | Cache fetches from DB on miss | Read → cache handles miss | Cache library complexity |

**Cache Eviction Policies:**
- **LRU** (Least Recently Used): Evict least recently accessed → best for temporal locality
- **LFU** (Least Frequently Used): Evict least frequently accessed → best for popularity-based
- **FIFO**: Simple, but ignores access patterns
- **TTL-based**: Entries expire after fixed time → good for time-sensitive data

**Cache problems and solutions:**

| Problem | Description | Solution |
|---------|-------------|---------|
| Cache Stampede | Many requests hit DB simultaneously on cache miss | Mutex/lock on cache miss, background refresh |
| Cache Avalanche | Many keys expire simultaneously | Stagger TTLs with random jitter |
| Cache Penetration | Queries for non-existent keys bypass cache | Bloom filter, cache null results |
| Hot Key Problem | Single key receives disproportionate traffic | Local replica caches, key sharding |

**Redis Data Structures (know for interviews):**
- String: Simple K-V, counters
- Hash: User profile fields
- List: Message queues, activity feed
- Set: Unique visitors, tags
- Sorted Set: Leaderboard, priority queue
- Bitmap: Feature flags, online users
- HyperLogLog: Approximate unique count

---

### 2B. Database Scaling

**Replication:**
```
Primary (Write)
    ├── Replica 1 (Read)
    ├── Replica 2 (Read)
    └── Replica 3 (Read)
```
- Synchronous replication: Write confirmed only after all replicas acknowledge → strong consistency, higher latency
- Asynchronous replication: Write confirmed after primary write → eventual consistency, lower latency
- **Replication lag:** Replicas may be seconds behind — design for this

**Read/Write Splitting:**
- Route `SELECT` queries to read replicas
- Route `INSERT/UPDATE/DELETE` to primary
- Problem: Read-your-writes consistency — user writes then immediately reads their own write from a lagging replica

**Sharding (Horizontal Partitioning):**
| Sharding Strategy | How | Pros | Cons |
|------------------|-----|------|------|
| Range-based | Users A-M → Shard 1, N-Z → Shard 2 | Simple queries | Hot partitions (all new users go to latest shard) |
| Hash-based | hash(userId) % N | Even distribution | No range queries, resharding is expensive |
| Directory-based | Lookup table: userId → shard | Flexible | Lookup table is SPOF |
| Geo-based | users in US → US shard | Data locality | Uneven distribution |

**Consistent Hashing for sharding:**
- Add nodes without reassigning all keys
- Only K/N keys need to move when adding a node (K = keys, N = nodes)
- Virtual nodes (vnodes) improve distribution

---

### 2C. Indexing Strategies

**B-Tree Index (default in PostgreSQL, MySQL):**
- Good for: equality, range, sorting, prefix matching
- Bad for: writes (index must be updated), high-cardinality columns with frequent updates

**Hash Index:**
- Perfect O(1) for equality queries
- Cannot do range queries
- Used in: Redis, hash partitions in PostgreSQL

**Composite Index:**
- `INDEX(user_id, created_at)` — query must use left-most prefix
- `WHERE user_id = 5 AND created_at > '2024-01-01'` → uses index
- `WHERE created_at > '2024-01-01'` → does NOT use above composite index

**Covering Index:**
- Index includes all columns needed by the query
- Query satisfied entirely from index without touching base table
- Eliminates "index scan + table fetch" → pure index scan

**Partial Index:**
- `CREATE INDEX ON orders(user_id) WHERE status = 'pending'`
- Smaller index, faster for specific queries

**Full-Text Index:**
- Used for LIKE '%keyword%' type searches
- PostgreSQL: `tsvector` and `tsquery`
- Better: use Elasticsearch for production-scale full-text search

**When NOT to add an index:**
- Small tables (full scan is faster)
- High-write-frequency columns (index maintenance slows writes)
- Low-cardinality columns (e.g., `gender` with only M/F — not worth it)

---

### 2D. Message Queues

**Why message queues?**
- Decoupling: Producer doesn't wait for consumer
- Buffering: Handle traffic spikes without dropping requests
- Retry: Failed messages can be retried
- Fan-out: One message → many consumers

**Kafka vs SQS vs RabbitMQ:**

| Feature | Kafka | Amazon SQS | RabbitMQ |
|---------|-------|-----------|----------|
| Type | Distributed log (pull) | Managed queue (pull) | Traditional queue (push/pull) |
| Durability | Persisted to disk, replay capable | Messages deleted after consumption | Messages deleted after ACK |
| Throughput | Millions/sec | 3000 msg/sec (standard) | Tens of thousands/sec |
| Ordering | Per partition | FIFO queues only | Per queue |
| Use case | Event streaming, audit log, CDC | Async task processing, decoupling | Complex routing, RPC patterns |
| Retention | Configurable (days/weeks) | Up to 14 days | Until consumed |

**Kafka internals (know for SDE-3):**
- Topic → Partitions → Segments (log files)
- Producer writes to partition by key hash
- Consumer group: each partition assigned to one consumer in group
- Offset: position in partition, consumer tracks its own offset
- Replication factor: each partition has N replicas (one leader, N-1 followers)

**Dead Letter Queue (DLQ):**
- Messages that fail processing N times go to DLQ
- Prevents poison pill messages from blocking queue
- Always mention DLQ when designing with queues

---

### 2E. API Design

**RESTful API Best Practices:**
```
Collection:     GET    /v1/products           → list products
Single item:    GET    /v1/products/{id}      → get product
Create:         POST   /v1/products           → create product
Full update:    PUT    /v1/products/{id}      → replace product
Partial update: PATCH  /v1/products/{id}      → update fields
Delete:         DELETE /v1/products/{id}      → delete product
Nested:         GET    /v1/orders/{id}/items  → items in order
Action:         POST   /v1/orders/{id}/cancel → cancel order
```

**Idempotency (critical for Amazon payment systems):**
- Idempotency key: client generates unique key per request
- Server stores: `idempotency_key → response`
- Same key → return cached response without re-processing
- Prevents double charges on network retry

**API Versioning strategies:**
- URL versioning: `/v1/`, `/v2/` → simplest, most visible
- Header versioning: `Accept: application/vnd.api+json;version=2` → cleaner URLs
- Query param: `?version=2` → easy to test in browser

**Pagination:**
- Offset-based: `?page=3&per_page=20` → simple but slow for deep pages
- Cursor-based: `?after=eyJpZCI6MTAwfQ==` → consistent for real-time feeds
- Keyset: `?after_id=100` → performant with proper index

**Rate Limiting:**
- Token Bucket: Allows burst up to bucket size, refills at constant rate
- Leaky Bucket: Smooth output rate, handles bursts by queuing
- Fixed Window Counter: Count requests per time window (simple, edge case at window boundary)
- Sliding Window Log: Track all timestamps, accurate but memory-intensive
- Sliding Window Counter: Weighted average of two fixed windows, good balance

**Rate limit headers:**
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 750
X-RateLimit-Reset: 1714569600
Retry-After: 30
```

---

## Phase 3: Distributed Systems Deep Dive (Week 5–6)

### 3A. Consistent Hashing

**Problem it solves:** Adding/removing servers in a distributed cache without remapping all keys.

**How it works:**
1. Map servers to positions on a circular ring using hash(serverIP)
2. Map keys to positions using hash(key)
3. A key belongs to the first server clockwise from its position
4. Add server → only keys between new server and its predecessor move
5. Remove server → only its keys move to successor

**Virtual nodes (vnodes):**
- Each physical server maps to multiple positions on the ring
- More even distribution
- Better fault tolerance (when one server fails, load distributes among all, not just one)

---

### 3B. Leader Election

**Why needed:** Distributed systems need one authoritative node to coordinate (avoid split-brain).

**Approaches:**
| Approach | Used In | How |
|----------|---------|-----|
| Bully Algorithm | Simple systems | Highest ID node wins election |
| Raft Consensus | etcd, CockroachDB | Majority vote, term-based |
| ZooKeeper Ephemeral Nodes | Kafka, Hadoop | First to create ephemeral node is leader |
| External coordinator | Most practical | Use ZooKeeper or etcd for election |

**Practical answer in interviews:** "I'd use ZooKeeper's ephemeral nodes or etcd for leader election. Each candidate tries to create `/service/leader` node. First success is the leader. On crash, node disappears, triggering new election."

---

### 3C. Distributed Locking

**When needed:** Multiple service instances competing to update the same resource.

**Redis SETNX-based lock:**
```
SET lock:resource_id unique_id EX 30 NX
# NX = only set if Not eXists
# EX = expire in 30 seconds (auto-release if crash)
```

**Problems with basic Redis lock:**
- Single Redis node failure → lock lost
- **Redlock algorithm:** Acquire lock on majority of N Redis nodes

**Database-based lock:**
```sql
INSERT INTO distributed_locks (lock_key, locked_at, expires_at)
VALUES ('invoice_123', NOW(), NOW() + INTERVAL 30 SECOND)
ON CONFLICT DO NOTHING;
-- If INSERT succeeds → you have the lock
```

**Optimistic Locking (preferred for low-contention):**
```sql
UPDATE accounts 
SET balance = balance - 100, version = version + 1
WHERE id = 123 AND version = 5;
-- If 0 rows updated → version conflict, retry
```

---

### 3D. Event-Driven Architecture

**Event-driven vs Request-driven:**
| Aspect | Request-Driven | Event-Driven |
|--------|---------------|-------------|
| Coupling | Tight (caller knows callee) | Loose (producer doesn't know consumers) |
| Failure impact | Cascade failure risk | Producer unaffected by consumer failure |
| Latency | Synchronous | Asynchronous |
| Complexity | Simple | More infrastructure |

**Event sourcing:**
- Store events (facts that happened) rather than current state
- State = replay of all events
- Benefits: Full audit trail, time-travel debugging, easy event replay
- Used in: Financial systems, order management

**CQRS (Command Query Responsibility Segregation):**
- Separate read model from write model
- Write side: commands → events → event store
- Read side: events → projections → read-optimized views
- Benefits: Optimize reads and writes independently

---

### 3E. Microservices vs Monolith

| Aspect | Monolith | Microservices |
|--------|---------|--------------|
| Deployment | Deploy everything at once | Deploy services independently |
| Scaling | Scale entire app | Scale individual services |
| Development speed | Fast initially | Faster at scale |
| Operational complexity | Low | High (service discovery, distributed tracing) |
| Data isolation | Shared database | Each service owns its data |
| Failure isolation | One bug crashes all | Contained failure |
| Best for | Early stage, small team | Large org, established domain model |

**Service communication patterns:**
- Synchronous: REST, gRPC (when you need immediate response)
- Asynchronous: Message queue (when you can tolerate delay)
- Service mesh: Istio, Linkerd (for complex microservice communication control)

**Key microservice patterns:**
- API Gateway: Single entry point, handles auth, rate limiting, routing
- Circuit Breaker: Stop calling failing service to prevent cascade
- Saga Pattern: Distributed transactions without 2PC
- Service Discovery: Services register/discover each other (Consul, Eureka)

---

### 3F. Idempotency (Critical for Amazon)

**Definition:** An operation that produces the same result whether called once or multiple times.

**Why critical:** Networks fail, clients retry → your APIs MUST be safe to retry.

**Making operations idempotent:**
```
Payment API:
POST /payments
{
    "idempotency_key": "client-uuid-here",
    "amount": 100,
    "currency": "USD"
}

Server stores: idempotency_key → response
On retry: return stored response without re-processing
```

**Database-level idempotency:**
```sql
-- Use unique constraint to prevent duplicates
INSERT INTO payments (idempotency_key, amount)
VALUES ('uuid-123', 100)
ON CONFLICT (idempotency_key) DO NOTHING;
```

---

## Phase 4: Scalability Patterns (Week 7)

### 4A. Read-Heavy vs Write-Heavy Optimization

**Read-Heavy System:**
- Add read replicas (scale reads horizontally)
- Aggressive caching (Redis, CDN)
- Denormalize data (precompute joins)
- CQRS read model
- Example: Product catalog, news feed, search

**Write-Heavy System:**
- Write buffering / batching
- Kafka for write buffering
- LSM-tree storage (Cassandra, RocksDB) — optimized for writes
- Async writes where possible
- Sharding to distribute write load
- Example: IoT telemetry, analytics events, audit logs

---

### 4B. Backpressure Handling

**Problem:** Producer generates data faster than consumer can process.

**Solutions:**
1. **Queue with bounded capacity:** Drop new messages or block producer when full
2. **Rate limiting at ingestion:** Reject requests above threshold (429 Too Many Requests)
3. **Consumer feedback loop:** Consumer signals producer to slow down
4. **Priority queues:** Process critical messages first
5. **Load shedding:** Drop low-priority requests under extreme load

---

### 4C. Circuit Breaker Pattern

**States:**
```
CLOSED → (failure threshold exceeded) → OPEN → (timeout) → HALF-OPEN → (success) → CLOSED
                                                                        → (failure) → OPEN
```

- **CLOSED:** Normal operation, requests flow through
- **OPEN:** Fail fast, don't call downstream service
- **HALF-OPEN:** Allow one request to test if service recovered

**Why it matters:** Without circuit breaker, cascade failures can take down your entire system when one dependency fails.

---

### 4D. Async Processing Patterns

| Pattern | Use Case |
|---------|----------|
| Fire and Forget | Email sending, non-critical notifications |
| Request-Reply with Callback | Webhook-based async workflows |
| Event-Driven | Order processing, inventory update |
| Scheduled/Batch | Nightly reports, bulk processing |

---

## Phase 5: End-to-End System Design Framework (Week 8)

This is your **interview script**. Follow this every time.

### Step 1: Requirement Clarification (5 min)

**Functional requirements:**
- What are the core features? (don't assume)
- What are the user-facing APIs?
- What's out of scope?

**Non-functional requirements:**
- How many users? DAU/MAU?
- What's the expected QPS (reads/writes)?
- What's the acceptable latency? (p50, p99)
- What's the required availability? (99.9%, 99.99%?)
- Is the system read-heavy or write-heavy?
- Any geographic constraints? Global or regional?
- Data retention requirements?

**Sample clarifications for "Design Twitter":**
> "Before I start, I want to clarify scope. I'll focus on tweet creation, home timeline, and follow relationships. Out of scope: direct messages, ads, trending topics. Is that okay?"
>
> "For scale: 300M DAU, 100M tweets/day, timeline reads 10x more than writes. Globally distributed — users in US, India, Europe. Latency for timeline: under 200ms at p99. Availability: 99.99%."

---

### Step 2: Capacity Estimation (5 min)

Always estimate:
- QPS (queries per second)
- Storage requirements
- Bandwidth requirements
- Memory requirements for caching

See Section 6 for detailed examples.

---

### Step 3: High-Level Architecture (10 min)

Draw the system in components:
```
Client → CDN → API Gateway → Load Balancer → Service Layer → [Cache + DB + Queue]
```

Name every component and explain its role. Don't skip the CDN and API Gateway.

---

### Step 4: Component-Level Deep Dive (15 min)

Pick 2-3 most interesting/critical components and go deep:
- Database schema
- API contracts
- Internal logic
- Scaling strategy for that component

---

### Step 5: Data Flow Explanation (5 min)

Walk through the happy path:
> "User places an order: 1) POST /orders hits API Gateway → 2) Auth middleware validates JWT → 3) Order service validates inventory → 4) Payment service charges card → 5) Publishes order_placed event to Kafka → 6) Inventory service consumes event → updates stock → 7) Email service sends confirmation."

---

### Step 6: Bottleneck Identification (5 min)

Proactively identify:
- Database write bottleneck → add caching, queue writes
- Single point of failure → add redundancy
- Hot partition in Kafka → increase partition count, better key selection
- N+1 query problem → batching, eager loading

---

### Step 7: Scaling Strategy (5 min)

From 1K users → 10M → 100M → 1B:
- Phase 1: Single region, monolith with DB
- Phase 2: Add read replicas, cache layer
- Phase 3: Microservices, horizontal scaling
- Phase 4: Multi-region, global CDN, geo-routing

---

### Step 8: Trade-offs Discussion (5 min)

Always bring up trade-offs you made:
> "I chose eventual consistency for the cart because the added latency of synchronous replication isn't worth it for a shopping cart. The trade-off is users may briefly see stale quantity, but we re-validate at checkout."

---

## Phase 6: Mock Interviews & Optimization (Week 9–10)

### Daily Practice Schedule

**Weekdays (2 hours):**
- 30 min: Review one concept from Phase 1–4
- 60 min: Design one system end-to-end (timed)
- 30 min: Review someone else's solution (Grokking, YouTube)

**Weekends (3–4 hours):**
- 90 min: Mock interview (with peer or self-timed)
- 60 min: Deep-dive one component (e.g., Kafka internals)
- 30 min: Read real-world engineering blog (AWS, Uber, Netflix)

---

### Common Mistakes to Avoid

| Mistake | Why Bad | Fix |
|---------|---------|-----|
| Jumping into design without clarifying | You solve the wrong problem | Spend 5 min on requirements |
| Only drawing boxes without explaining | Looks like memorization | Narrate your thinking continuously |
| No capacity estimation | Skips scalability reasoning | Always do rough numbers |
| Choosing buzzwords without justification | "I'd use Kafka" — why? | Every choice needs a reason |
| Ignoring failure scenarios | Shows lack of maturity | Ask "what happens if X fails?" |
| Designing for 10 users | Misses the point | Always design for stated scale |
| Never mentioning trade-offs | Shows overconfidence | Proactively trade-off |
| Going too deep too early | Run out of time on full design | Breadth first, depth on request |

---

### Time Allocation for 45-Minute Interview

| Phase | Time | Notes |
|-------|------|-------|
| Requirements clarification | 5 min | Don't skip, don't overdo |
| Capacity estimation | 3–5 min | Quick rough numbers |
| High-level architecture | 10 min | Full system block diagram |
| Deep dive on 2 components | 15 min | Pick hardest/most interesting |
| Bottlenecks + scaling | 5 min | Show forward thinking |
| Trade-offs + Q&A | 5 min | Let interviewer drive |

---

### Communication Strategy

**Think out loud:** Never go silent. Say what you're thinking.
> "I'm considering two options here — Redis for caching or a CDN. Since this is static product data that doesn't change often, a CDN makes more sense and is cheaper at scale."

**Drive the conversation:** Don't wait for the interviewer to guide you.
> "I've covered the read path. Let me now focus on the write path, specifically how we handle order creation under high load."

**Acknowledge limitations:** Shows maturity.
> "I'm simplifying the payment integration here — in reality I'd also handle chargebacks, refunds, and multi-currency, but those are out of scope for today."

---

*Next: [Section 3 — Amazon-Specific Strategy](./Section-3-Amazon-Specific-Strategy.md)*
