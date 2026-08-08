# A4 — CAP Theorem

> **Section:** Foundational Concepts | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** In a distributed system (multiple computers), you can have at most 2 out of 3 guarantees: that data is always correct (Consistency), that the system is always available (Availability), and that it works even when computers can't talk to each other (Partition Tolerance).

**Technical:** CAP Theorem (Brewer's Theorem, 2000) states that a distributed system cannot simultaneously guarantee Consistency (every read returns the most recent write), Availability (every request receives a response), and Partition Tolerance (system operates despite network partitions). You must choose 2.

**Critical nuance:** Network partitions in distributed systems are inevitable. Therefore, the real choice is: **CP or AP** — how do you behave WHEN a partition occurs?

---

## 2. Real-World Analogy

**Bank Branch Analogy:**
Imagine a bank with two branches (Node A and Node B). The phone line between them breaks (network partition).

- **CP choice:** Branch A stops processing transactions until the line is fixed. Customers can't withdraw. (Consistent, but Unavailable)
- **AP choice:** Both branches keep processing independently. When line reconnects, they reconcile. (Available, but temporarily inconsistent — a customer might over-withdraw)

CA systems (no partition tolerance) = a single branch — no network to partition, but no distribution either.

---

## 3. Visual Diagram

```
                    CONSISTENCY
                   (C): All nodes
                   return latest
                        ▲
                       /│                      / │                      /  │                   CP /   │   \ CA
                   /    │                      / ────┼────                  /   NOT │                      /   POSSIBLE│                    ▼────────────────────▼
        AVAILABILITY              PARTITION
       (A): Always                TOLERANCE
          responds               (P): Works during
                                 network splits
               AP
    (partition always happens → choose CP or AP)

REAL SYSTEMS:
 CP  → HBase, ZooKeeper, MongoDB (strong), Spanner, etcd
 AP  → Cassandra, DynamoDB, CouchDB, DynamoDB, Riak
 CA  → MySQL, PostgreSQL (single-node, no distribution)
```

---

## 4. Deep Technical Explanation

### Why You Can't Have All Three

Proof intuition:
1. You have Node A and Node B, both storing key X=1
2. Client writes X=2 to Node A
3. A network partition occurs — Node B can't receive the update
4. Another client reads X from Node B

Now pick:
- **Give Consistency:** Node B refuses to respond until it receives update from A (sacrifices Availability)
- **Give Availability:** Node B responds with X=1 (sacrifices Consistency)
- You cannot give both

### CP Systems (Consistency + Partition Tolerance)
**Behavior during partition:** Reject writes and reads that cannot be confirmed across nodes. Return error rather than stale data.

| System | How |
|--------|-----|
| **ZooKeeper** | Leader election via Paxos/Zab; majority quorum required for writes |
| **HBase** | Single master coordinates all writes; replicas are read-only |
| **MongoDB** (strong) | Primary must acknowledge before write confirms |
| **etcd** | Raft consensus; majority quorum required |

**Use when:** Financial transactions, distributed locks, configuration management — where stale data causes serious problems.

### AP Systems (Availability + Partition Tolerance)
**Behavior during partition:** Continue accepting reads/writes on all nodes. Reconcile conflicts after partition heals.

| System | How |
|--------|-----|
| **Cassandra** | Tunable consistency (ANY/ONE/QUORUM/ALL); default eventual |
| **DynamoDB** | Eventually consistent reads by default |
| **CouchDB** | MVCC, merge conflicts after partition |
| **Riak** | Vector clocks for conflict detection |

**Use when:** Shopping carts, social feeds, DNS — where temporary inconsistency is acceptable.

### CA (Consistency + Availability) — Not Truly Distributed
These are single-node RDBMS. They can be consistent and available because there's no network partition to worry about. In a multi-node setup, they sacrifice partition tolerance (they just fail if a node is down).

### CAP is NOT Binary
Real systems offer tunable consistency:
- Cassandra: ONE → QUORUM → ALL (sliding scale from AP to CP)
- DynamoDB: eventual read vs consistent read per-request
- MongoDB: read concern (local/majority/linearizable) per-query

---

## 5. Code Example

```php
// Cassandra — tunable consistency demonstration
$cassandra = Cassandra::cluster()
    ->withContactPoints('127.0.0.1')
    ->build()
    ->connect('keyspace');

// AP behavior (default): fast, eventual consistency
$statement = new Cassandra\SimpleStatement(
    'SELECT * FROM users WHERE id = ?'
);
$options = new Cassandra\ExecutionOptions([
    'consistency' => Cassandra::CONSISTENCY_ONE  // AP: fastest, may return stale
]);
$result = $cassandra->execute($statement, $options);

// CP behavior: slower but consistent
$options = new Cassandra\ExecutionOptions([
    'consistency' => Cassandra::CONSISTENCY_QUORUM  // majority nodes must agree
]);
$result = $cassandra->execute($statement, $options);

// With N=3 replicas, R+W > N = strong consistency
// W=2 (QUORUM write) + R=2 (QUORUM read) = 4 > 3 ✓
```

```php
// System design decision helper
function chooseDatabaseConsistency(string $useCase): string {
    $requiresStrong = [
        'payment_processing',
        'inventory_deduction', 
        'seat_booking',
        'distributed_lock',
        'account_balance',
    ];
    
    $acceptsEventual = [
        'social_feed',
        'view_counter',
        'like_count',
        'search_index',
        'recommendation',
    ];
    
    if (in_array($useCase, $requiresStrong)) {
        return 'CP: Use PostgreSQL, HBase, or DynamoDB with ConsistentRead=true';
    }
    return 'AP: Use Cassandra, DynamoDB eventual, or Redis';
}
```

---

## 6. Trade-offs

| Choice | Availability | Consistency | Example Systems | Best For |
|--------|-------------|-------------|----------------|----------|
| **CP** | Lower | Strong | ZooKeeper, HBase, etcd | Locks, config, finance |
| **AP** | Higher | Eventual | Cassandra, DynamoDB | Social, counters, cache |

---

## 7. Interview Q&A

**Q1: Is the CAP Theorem still relevant today?**
> CAP is still conceptually important but often misapplied. The real insight is: network partitions are inevitable in distributed systems, so the practical choice is CP vs AP — how does your system behave during a partition? Modern systems like DynamoDB and Cassandra offer tunable consistency, so you're not stuck with a binary choice. PACELC extends CAP to also consider latency vs consistency even when no partition exists.

**Q2: Where does DynamoDB sit in CAP?**
> DynamoDB is AP by default — it prioritizes availability and returns eventually consistent reads. However, DynamoDB supports strongly consistent reads (CP behavior) on a per-request basis, which routes the read to the primary node. This makes DynamoDB "tunable" — AP when eventual consistency is acceptable, CP when you need guaranteed latest data.

**Q3: Can a system be CA in practice?**
> CA (Consistent + Available without Partition Tolerance) is really just a single-node system. Once you distribute across multiple nodes, network partitions become possible, and you must choose between C and A when a partition occurs. Traditional RDBMS like MySQL are CA in the sense that they don't handle partitions — if replication fails, the primary continues alone. They're not truly distributed by design.

**Q4: You're designing a shopping cart. CP or AP?**
> AP. A shopping cart benefits from availability over strict consistency. If a network partition occurs, it's better for users to keep adding items (eventual consistency) than to block the entire cart operation. The worst case is cart contents look slightly different on two devices — acceptable. We'd use DynamoDB or Cassandra with eventual consistency, and reconcile on checkout. Checkout itself (inventory deduction, payment) would use CP systems.

---

## 8. Key Takeaways

```
┌─────────────────────────────────────────────────────────────────────┐
│ ✓ Partitions are inevitable → real choice is CP vs AP              │
│ ✓ CP: reject requests during partition (no stale data)             │
│ ✓ AP: serve requests during partition (may return stale data)      │
│ ✓ CP systems: ZooKeeper, HBase, etcd, Spanner                     │
│ ✓ AP systems: Cassandra, DynamoDB (default), Riak, CouchDB         │
│ ✓ Most modern systems are tunable (not strictly CP or AP)          │
│ ✓ Finance, inventory, locks → CP; Social, counters → AP            │
└─────────────────────────────────────────────────────────────────────┘
```
