# B3 — Databases: SQL vs NoSQL

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A SQL database stores data in tables with rows and columns (like Excel). A NoSQL database stores data in other formats — documents, key-value pairs, graphs — optimized for different use cases.

**Technical:** SQL (Relational) databases enforce a fixed schema, support ACID transactions and complex joins, and scale vertically. NoSQL databases sacrifice some ACID guarantees for horizontal scalability, flexible schemas, and specialized data models optimized for specific access patterns.

---

## 2. Real-World Analogy

**SQL = organized filing cabinet** with labeled folders, strict rules, and references between folders.
**MongoDB (Document)** = a collection of self-contained envelopes — each has everything in it, no strict format.
**Redis (Key-Value)** = a dictionary/hashmap — extremely fast lookups by key.
**Cassandra (Wide-Column)** = a spreadsheet optimized for writing millions of rows per second.
**Neo4j (Graph)** = a mind-map — connections between items are first-class citizens.

---

## 3. Visual Diagram

```
DATABASE TYPES AND USE CASES:

┌─────────────────────────────────────────────────────────────────┐
│                    RELATIONAL (SQL)                              │
│  PostgreSQL, MySQL, MariaDB, SQLite                             │
│  ┌────────┐  ┌────────────┐  ┌─────────┐                       │
│  │ Users  │  │  Orders    │  │Products │                       │
│  │ id     ├──┤ user_id FK ├──┤ id      │                       │
│  │ name   │  │ product_id │  │ name    │                       │
│  └────────┘  └────────────┘  └─────────┘                       │
│  JOIN-based, ACID, complex queries                              │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    DOCUMENT (NoSQL)                              │
│  MongoDB, CouchDB, Firestore                                    │
│  { "user": "Alice", "orders": [{"id":1, "total":500}],         │
│    "address": {"city": "Mumbai", "pin": "400001"} }            │
│  Denormalized, flexible schema, horizontal scale               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│              KEY-VALUE (NoSQL)                                   │
│  Redis, DynamoDB, Memcached, Riak                               │
│  "session:abc123" → {user_id:1, cart:[...]}                    │
│  O(1) lookups, fastest, simple data model                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│              WIDE-COLUMN (NoSQL)                                 │
│  Cassandra, HBase, ScyllaDB                                     │
│  Row key: "user123:2024-01" → {msg1: ..., msg2: ..., ...}      │
│  Write-optimized, time-series, horizontal scale                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│              GRAPH (NoSQL)                                       │
│  Neo4j, Amazon Neptune, TigerGraph                              │
│  (Alice) --[FOLLOWS]--> (Bob) --[FRIENDS_WITH]--> (Carol)       │
│  Relationship traversal, fraud detection, recommendations      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Deep Technical Explanation

### ACID vs BASE

| | ACID (SQL) | BASE (NoSQL) |
|--|-----------|-------------|
| **A** | Atomicity (all or nothing) | **B**asically Available |
| **C** | Consistency (DB rules enforced) | **S**oft state |
| **I** | Isolation (concurrent transactions don't interfere) | **E**ventually consistent |
| **D** | Durability (committed data persists) | |

### Relational Databases — When to Use
**Strengths:**
- Complex queries with JOINs across multiple tables
- ACID transactions (multi-row, multi-table atomic operations)
- Strong data integrity (foreign keys, constraints, triggers)
- Ad-hoc queries without knowing access patterns upfront
- Mature tooling: ORMs, migration tools, query analyzers

**Weaknesses:**
- Horizontal scaling is hard (sharding breaks JOIN and foreign keys)
- Schema changes require migrations (ALTER TABLE on large tables = hours)
- Object-relational impedance mismatch

**Use when:** E-commerce transactions, financial systems, ERPs, any system requiring complex JOINs and ACID guarantees.

### MongoDB (Document) — When to Use
**Strengths:**
- Flexible schema — add fields without migration
- Data co-located in document (product + variants + images in one JSON)
- Horizontal sharding built-in
- Rich query language, aggregation pipeline

**Weaknesses:**
- No server-side joins (must embed or do application-level joins)
- ACID only within single document by default (multi-document transactions since v4)
- Can lead to data duplication

**Use when:** CMS, catalogs, user profiles, real-time analytics, any data with nested structure.

### Cassandra (Wide-Column) — When to Use
**Strengths:**
- Extremely high write throughput (append-only LSM tree)
- Linear horizontal scaling (add nodes = add throughput)
- No single point of failure (every node is equal)
- Time-series, log, event data

**Weaknesses:**
- No JOINs, no GROUP BY
- Data model must be designed around query patterns upfront
- Eventual consistency by default
- No ad-hoc queries

**Use when:** IoT sensor data, time-series metrics, messaging (WhatsApp uses it), activity feeds, user event logs.

### DynamoDB (Key-Value / Wide-Column) — When to Use
**Strengths:**
- Fully managed by AWS — no ops overhead
- Auto-scaling, multi-region replication (Global Tables)
- Single-digit millisecond latency
- Pay-per-request pricing

**Weaknesses:**
- No complex queries; must know access patterns upfront
- Expensive for high-read workloads without careful design
- 400KB item size limit

**Use when:** Serverless applications, AWS-native architectures, e-commerce carts, sessions, any high-scale KV lookups.

### OLTP vs OLAP

| | OLTP (Operational) | OLAP (Analytical) |
|--|-------------------|-------------------|
| Purpose | Transactions (CRUD) | Analytics (aggregations) |
| Queries | Simple, indexed, fast | Complex, full-table scans |
| Storage | Row-oriented | Column-oriented |
| Scale | Thousands of rows/sec | Petabytes |
| Examples | PostgreSQL, MySQL | Redshift, BigQuery, ClickHouse |
| Latency | Milliseconds | Seconds to minutes |

---

## 5. Database Decision Matrix

```
USE CASE                          → RECOMMENDED DATABASE
──────────────────────────────────────────────────────────
Financial transactions, inventory → PostgreSQL, MySQL (ACID)
User profiles, product catalog    → MongoDB (flexible schema)
Session storage, caching          → Redis (K/V, in-memory)
Shopping cart (AWS)               → DynamoDB (managed KV)
Chat messages, activity feed      → Cassandra (write-heavy, time-series)
Friend relationships, fraud graph → Neo4j, Amazon Neptune
Full-text search                  → Elasticsearch
Metrics, IoT time-series          → InfluxDB, TimescaleDB
Reporting, business analytics     → Redshift, BigQuery, ClickHouse
Global scale + SQL                → Google Spanner, CockroachDB
```

---

## 6. Code Example

```php
// When to denormalize in NoSQL (MongoDB)
// Instead of joining User + Address + Orders tables:

// SQL approach (3 tables, JOIN required):
// SELECT u.name, a.city, COUNT(o.id) FROM users u
// JOIN addresses a ON a.user_id = u.id
// JOIN orders o ON o.user_id = u.id
// GROUP BY u.id

// MongoDB approach (single document, no join):
$userDocument = [
    '_id'     => new MongoDB\BSON\ObjectId(),
    'name'    => 'Alice',
    'email'   => 'alice@example.com',
    // Embedded address (1:1 — always accessed together)
    'address' => ['city' => 'Mumbai', 'pin' => '400001'],
    // Embedded recent orders (1:few — limited count)
    'recent_orders' => [
        ['id' => 'ORD-1', 'total' => 500, 'date' => '2024-01-15'],
        ['id' => 'ORD-2', 'total' => 1200, 'date' => '2024-01-16'],
    ],
    'order_count' => 2,  // pre-aggregated counter
];
// One read = all user data, zero joins
```

```php
// Cassandra data model — design for query patterns
// Query: "Get all messages for user X in conversation Y, ordered by time"

// Table designed for this exact query:
// CREATE TABLE messages (
//   user_id    UUID,
//   conv_id    UUID,
//   created_at TIMESTAMP,
//   content    TEXT,
//   PRIMARY KEY ((user_id, conv_id), created_at)
// ) WITH CLUSTERING ORDER BY (created_at DESC);

$messages = $cassandra->execute(
    "SELECT * FROM messages WHERE user_id = ? AND conv_id = ? LIMIT 50",
    ['params' => [$userId, $convId]]
);
// This query is O(1) — data is pre-sorted on disk by (user_id, conv_id, created_at)
```

---

## 7. Interview Q&A

**Q1: When would you choose NoSQL over SQL?**
> Choose NoSQL when: (1) data is hierarchical and naturally fits a document model (product catalog with variants); (2) you need horizontal scaling beyond what SQL can offer (Cassandra for billions of writes/day); (3) schema is unknown or evolving rapidly (early-stage startups); (4) specific access patterns are known upfront and can be optimized for (Cassandra, DynamoDB). Stick with SQL when ACID transactions across multiple entities are required (e-commerce checkout, banking).

**Q2: Can you do transactions in NoSQL databases?**
> It depends on the database. MongoDB supports multi-document ACID transactions since v4.0. DynamoDB supports transactions across up to 25 items. Cassandra supports lightweight transactions with PAXOS (but very slow — avoid). Redis supports atomic operations via Lua scripts. Generally, NoSQL databases have limited transaction support compared to SQL — they're designed for single-entity operations.

**Q3: How does Cassandra's write performance work?**
> Cassandra uses an LSM-tree (Log-Structured Merge tree) storage engine. Writes go to an in-memory MemTable first (microsecond latency), then are flushed to immutable SSTables on disk periodically. There are no in-place updates — every write is an append. This makes writes extremely fast. Reads are slower because they may need to check multiple SSTables and merge results. Compaction periodically merges SSTables to improve read performance.

**Q4: What is the N+1 query problem?**
> N+1 occurs when code fetches 1 parent record, then makes N separate queries for each child. Example: fetch 100 orders, then for each order fetch its product details = 101 queries. Fix with: SQL JOIN, Eloquent `with()` eager loading, or DataLoader batching. N+1 is a common performance killer in ORMs.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ SQL: ACID, JOINs, complex queries; NoSQL: scale, flexibility    │
│ ✓ OLTP (transactions) → PostgreSQL; OLAP (analytics) → BigQuery   │
│ ✓ Cassandra = write-heavy, time-series, no JOINs                 │
│ ✓ MongoDB = flexible schema, document-oriented, sharding          │
│ ✓ DynamoDB = AWS-native, managed, predictable KV/document         │
│ ✓ Design Cassandra table per query — access pattern first         │
│ ✓ Embed for 1:1; reference for 1:many with large counts           │
└────────────────────────────────────────────────────────────────────┘
```
