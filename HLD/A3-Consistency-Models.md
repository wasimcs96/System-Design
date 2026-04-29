# A3 — Consistency Models

> **Section:** Foundational Concepts | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Consistency means all users see the same data at the same time. But in distributed systems, there's a spectrum — from "always see latest" to "eventually see latest."

**Technical:** A consistency model defines the guarantees a distributed system provides about the order and visibility of writes across multiple nodes. Different models trade consistency for availability and latency.

---

## 2. Real-World Analogy

**Google Docs:**
- **Strong consistency:** You type a letter, your co-author instantly sees it (within microseconds). Requires network round-trip before confirming write.
- **Eventual consistency:** You type offline, your changes sync when you reconnect. Others see it "eventually." Used by most mobile apps.
- **Causal consistency:** "React to what you've seen" — if you reply to a comment, your reply appears after the comment you replied to (preserves cause-effect), but other unrelated comments may appear in any order.

---

## 3. Visual Diagram

```
CONSISTENCY SPECTRUM (weakest → strongest):
─────────────────────────────────────────────────────────────────────
Eventual → Causal → Read-Your-Writes → Monotonic Read → Strong
─────────────────────────────────────────────────────────────────────
(most available,                              (least available,
 lowest latency)                               highest latency)

STRONG CONSISTENCY:
Client writes 5 → [Primary] → sync replicate → [Replica]
                                ↑ waits for ack
Client reads  5 ← [Replica]   (guaranteed latest value)

EVENTUAL CONSISTENCY:
Client writes 5 → [Primary] → async replicate → [Replica] (queue)
Client reads  3 ← [Replica]   (may return stale value until sync)
... later ...
Client reads  5 ← [Replica]   (eventually consistent)
```

---

## 4. Deep Technical Explanation

### Strong Consistency (Linearizability)
- Every read sees the most recent write
- Operations appear to execute atomically at a single point in time
- **Cost:** Every write must be replicated synchronously — higher latency (network round-trip to all replicas before confirming write)
- **Systems:** ZooKeeper, etcd, Spanner, HBase

### Eventual Consistency
- Writes are propagated asynchronously — replicas catch up eventually
- Reads may return stale data during propagation window
- **Cost:** Data may be temporarily inconsistent (replication lag)
- **Systems:** Cassandra, DynamoDB (default), DNS, CDN cache
- **When acceptable:** Shopping cart, view counts, social media likes

### Causal Consistency
- Preserves cause-and-effect relationships
- If write B is caused by (depends on) write A, all nodes see A before B
- Unrelated operations can be seen in different orders
- **Systems:** MongoDB, Cosmos DB
- **Example:** Reply to a post always appears after the post itself

### Read-Your-Writes Consistency
- After a client writes a value, it always reads its own write
- Other clients may still see old values
- **How:** Route reads to the replica that just processed the write, or use version tokens
- **Example:** After updating profile, you see your own updates immediately

### Monotonic Read Consistency
- Once you read a value, you never read an older value
- "Time only moves forward" — you won't see X, then see a stale version of X
- **How:** Route client reads to same replica via sticky sessions or consistent hashing

### Linearizability vs Serializability
| Concept | Applies to | Guarantee |
|---------|-----------|-----------|
| **Linearizability** | Single object | Operations appear atomic in real-time order |
| **Serializability** | Transactions (multi-object) | Transactions appear to execute one at a time |
| **Strict Serializability** | Both | Serializable + linearizable (strongest) |

PostgreSQL with `SERIALIZABLE` isolation = strict serializability.

---

## 5. Code Example

```php
// Eventual consistency — DynamoDB default
$dynamodb->putItem([
    'TableName' => 'Users',
    'Item' => ['id' => ['S' => '123'], 'name' => ['S' => 'Alice']],
]);

// Immediately reading may return old value (eventual consistency)
$result = $dynamodb->getItem([
    'TableName' => 'Users',
    'Key' => ['id' => ['S' => '123']],
    // ConsistentRead: false = eventual (default, cheaper, lower latency)
]);

// Strong consistency read — always returns latest write
$result = $dynamodb->getItem([
    'TableName' => 'Users',
    'Key' => ['id' => ['S' => '123']],
    'ConsistentRead' => true,  // Reads from primary node
]);
```

```php
// Read-Your-Writes: use version token / write timestamp
class UserService {
    public function updateProfile(int $userId, array $data): string {
        $version = DB::table('users')
            ->where('id', $userId)
            ->update($data);  // returns timestamp/version
        
        // Store last-write version in user's session
        session(['user_write_version' => time()]);
        return 'Profile updated';
    }
    
    public function getProfile(int $userId): array {
        $writeTime = session('user_write_version', 0);
        
        // If write was recent, read from primary (strong)
        // Otherwise read from replica (eventual, faster)
        $connection = (time() - $writeTime < 5) ? 'mysql' : 'mysql_replica';
        
        return DB::connection($connection)
            ->table('users')
            ->find($userId);
    }
}
```

---

## 6. Trade-offs

| Model | Availability | Latency | Complexity | Use Case |
|-------|-------------|---------|-----------|----------|
| **Strong** | Low | High | High | Banking, inventory, booking seats |
| **Eventual** | High | Low | Low | Social likes, view counters, DNS |
| **Causal** | Medium | Medium | Medium | Comments, message ordering |
| **Read-Your-Writes** | Medium | Medium | Medium | Profile updates, settings |
| **Monotonic Read** | High | Low | Medium | Feeds, dashboards |

---

## 7. Interview Q&A

**Q1: When would you choose eventual consistency over strong consistency?**
> Eventual consistency is appropriate when temporary stale data is acceptable and you prioritize availability and performance. Examples: social media like counts (showing 999 vs 1000 likes is fine), shopping cart (add to cart, sync later), DNS propagation, CDN cache. It's unacceptable for financial transactions, inventory deduction (booking the last seat), or anywhere incorrect data causes business harm.

**Q2: How does DynamoDB handle consistency?**
> DynamoDB offers two read modes: eventually consistent reads (default) that may return data up to 1 second stale, routed to any replica, and strongly consistent reads that always read from the primary node. Strong reads cost 2x the read capacity units. For most reads, eventual is fine; for reads after a critical write (like checking if payment processed), use strong consistency.

**Q3: What is the replication lag problem?**
> In async replication, after a write to the primary, there's a window (milliseconds to seconds) where replicas haven't received the update. If a read is routed to a replica during this window, it returns stale data. Solutions: (1) Route reads to primary for critical operations (strong consistency); (2) Read-your-writes routing based on write timestamp; (3) Synchronous replication (blocks write until replica confirms).

**Q4: How do CRDTs relate to consistency?**
> CRDTs (Conflict-free Replicated Data Types) are data structures designed for eventual consistency — they can be merged from multiple concurrent writers without coordination. Examples: G-counter (only increments), OR-set (add/remove with unique tags). Used in Riak, Redis CRDT modules. They enable eventual consistency without conflicts by design.

---

## 8. Key Takeaways

```
┌─────────────────────────────────────────────────────────────────────┐
│ ✓ Strong consistency = all nodes agree on latest write immediately  │
│ ✓ Eventual consistency = will agree, but not immediately            │
│ ✓ Causal = preserves cause-effect ordering                          │
│ ✓ Read-Your-Writes = you see your own writes immediately            │
│ ✓ Strong reads cost 2x in DynamoDB — use deliberately               │
│ ✓ Default to eventual consistency for reads unless correctness      │
│   requires the latest value (inventory, payments, seats)            │
└─────────────────────────────────────────────────────────────────────┘
```
