# A5 — PACELC Theorem

> **Section:** Foundational Concepts | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** CAP theorem only describes what happens when a network failure occurs. PACELC goes further: it also asks, "Even when everything is working fine, do you want faster responses (low latency) or guaranteed correct data (consistency)?"

**Technical:** PACELC (Daniel Abadi, 2012) extends CAP:
- **If P (Partition):** choose between **A** (Availability) and **C** (Consistency) — same as CAP
- **Else (E):** choose between **L** (Latency) and **C** (Consistency) — new dimension

Written as: **PA/EL** or **PC/EC** classification.

---

## 2. Real-World Analogy

**Hospital Records System:**
- **During network failure (P):** Either refuse to update patient records until all systems sync (PC) or allow updates that might diverge (PA)
- **During normal operation (E):** Either wait for all hospital branches to confirm record update before returning to doctor (EC — slower but consistent) or return immediately after writing to one server (EL — faster but replicas may lag)

PACELC forces you to think about the normal-operation trade-off, which is far more frequent than partition scenarios.

---

## 3. Visual Diagram

```
        PACELC CLASSIFICATION MATRIX:
        ┌──────────────────────────────────────────────────────┐
        │                  During Partition?                   │
        │                 YES            NO                    │
        ├──────────────┬──────────────┬────────────────────────┤
        │ Consistency  │     PC/EC    │ Wait for sync          │
        │ preferred    │  (MySQL,     │ (always slow)          │
        │              │  PostgreSQL) │                        │
        ├──────────────┼──────────────┼────────────────────────┤
        │ Latency /    │     PA/EL    │ Return fast            │
        │ Availability │  (DynamoDB,  │ (may be stale)         │
        │ preferred    │  Cassandra)  │                        │
        └──────────────┴──────────────┴────────────────────────┘

SYSTEM CLASSIFICATION:
System           │ Partition      │ Normal Operation
─────────────────┼────────────────┼──────────────────
DynamoDB         │ PA             │ EL (default)
Cassandra        │ PA             │ EL (tunable to EC)
MySQL (InnoDB)   │ PC             │ EC
PostgreSQL       │ PC             │ EC  
MongoDB          │ PC             │ EC (default)
Spanner          │ PC             │ EC
Riak             │ PA             │ EL
```

---

## 4. Deep Technical Explanation

### Why PACELC Matters More Than CAP

In production systems, partitions are rare (maybe hours per year). But the Else (no partition) case happens constantly. By choosing a highly available system (AP), you're also implicitly choosing higher latency vs consistency trade-offs in normal operation.

**Example — Cassandra (PA/EL):**
- Default: Write to 1 node, return immediately (EL — very low latency)
- Eventual consistency in normal operation: replicas sync asynchronously
- During partition: continue serving (AP)

**Example — PostgreSQL (PC/EC):**
- Write must be committed to WAL and optionally synced to replicas before returning
- Higher latency in normal operation (EC)
- During partition: primary rejects writes if can't reach quorum

### Tunable Systems
Cassandra bridges the gap:
```
ConsistencyLevel.ONE   → PA/EL (fastest, eventual)
ConsistencyLevel.QUORUM → PA/EC (majority must agree)
ConsistencyLevel.ALL   → PC/EC (all nodes must agree, slowest)
```

### PACELC and Replication Lag

**EL systems** (DynamoDB, Cassandra default):
- Write returns after committing to primary/coordinator
- Replicas receive update asynchronously (milliseconds to seconds later)
- Read from replica = may return stale value

**EC systems** (MySQL with sync replication, Spanner):
- Write blocks until all replicas confirm
- Adds network round-trip latency to every write
- Read from any replica = always fresh

---

## 5. Code Example

```php
// PACELC in practice — DynamoDB PA/EL vs PC/EC

// Default (PA/EL): fast write, eventual read
$dynamodb->putItem([
    'TableName' => 'Orders',
    'Item' => ['order_id' => ['S' => 'ORD-123'], 'status' => ['S' => 'placed']],
    // No ReturnConsumedCapacity — just fast, no wait
]);

// PA/EL read (default, may be stale)
$order = $dynamodb->getItem([
    'TableName' => 'Orders',
    'Key' => ['order_id' => ['S' => 'ORD-123']],
    'ConsistentRead' => false,  // eventual (default) — EL
]);

// PC/EC read (always fresh, costs 2x RCU, slightly slower)
$order = $dynamodb->getItem([
    'TableName' => 'Orders',
    'Key' => ['order_id' => ['S' => 'ORD-123']],
    'ConsistentRead' => true,   // strong — EC
]);
```

```php
// When to choose EC over EL:
// EL (default): social feed, product catalog reads, analytics
// EC (strong): order status after payment, inventory check before deduction

class OrderService {
    public function checkAndDeductInventory(string $productId, int $qty): bool {
        // MUST use strong consistency — concurrent deductions could cause oversell
        $item = $this->dynamodb->getItem([
            'TableName' => 'Inventory',
            'Key' => ['product_id' => ['S' => $productId]],
            'ConsistentRead' => true,  // EC — get latest inventory level
        ]);
        
        $available = $item['Item']['quantity']['N'];
        if ($available < $qty) return false;
        
        // Conditional update — only deduct if quantity hasn't changed (optimistic lock)
        $this->dynamodb->updateItem([
            'TableName' => 'Inventory',
            'Key' => ['product_id' => ['S' => $productId]],
            'UpdateExpression' => 'SET quantity = quantity - :qty',
            'ConditionExpression' => 'quantity >= :qty',
            'ExpressionAttributeValues' => [':qty' => ['N' => (string)$qty]],
        ]);
        return true;
    }
}
```

---

## 6. Trade-offs

| | PA/EL | PC/EC |
|--|-------|-------|
| **Latency** | Low (local write) | Higher (wait for replicas) |
| **Availability** | High (partition tolerant) | Lower (rejects on partition) |
| **Consistency** | Eventual | Strong |
| **Best for** | Read-heavy, tolerate stale | Write-critical, financial |
| **Examples** | DynamoDB, Cassandra | MySQL, PostgreSQL, Spanner |

---

## 7. Interview Q&A

**Q1: How does PACELC improve on CAP theorem?**
> CAP only addresses partition scenarios, which are rare in practice. PACELC adds the Else case: when the system is healthy (no partition), do you prioritize latency (return fast with potential stale data) or consistency (wait for replica acknowledgment)? This is a far more frequent and impactful trade-off. PACELC gives a more complete picture for choosing databases.

**Q2: What is DynamoDB's PACELC classification?**
> DynamoDB is PA/EL: during a partition, it stays Available (AP); in normal operation, it defaults to EL (low latency, eventual reads). You can override to EC on specific reads using `ConsistentRead: true`, which routes to the primary and ensures you get the latest write — at 2x read cost and slightly higher latency.

**Q3: When would you specifically choose a PC/EC system?**
> When your use case absolutely requires that reads always see the latest writes, even in normal operation. Examples: financial ledgers (balance must be current), distributed locks (must see if lock is held), inventory deduction (prevent overselling). Systems like PostgreSQL with synchronous replication, Google Spanner, or CockroachDB give you PC/EC guarantees.

---

## 8. Key Takeaways

```
┌─────────────────────────────────────────────────────────────────┐
│ ✓ PACELC extends CAP with latency vs consistency trade-off      │
│ ✓ PA/EL: fast + available, may return stale (Cassandra, Dynamo) │
│ ✓ PC/EC: consistent, higher latency (MySQL, Spanner, etcd)      │
│ ✓ Most reads can use EL; use EC for critical post-write reads   │
│ ✓ Cassandra lets you tune per-query (ONE → QUORUM → ALL)        │
└─────────────────────────────────────────────────────────────────┘
```
