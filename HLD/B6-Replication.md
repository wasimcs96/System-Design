# B6 — Replication

> **Section:** Core Infrastructure | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Replication means keeping multiple copies of your data on different servers. If one server fails, others still have the data. It also lets multiple servers answer read queries simultaneously.

**Technical:** Database replication is the process of synchronizing data from a primary (leader) node to one or more replica (follower) nodes. It provides high availability, fault tolerance, and read scalability. Replication can be synchronous or asynchronous and follows different topologies: single-leader, multi-leader, or leaderless.

---

## 2. Real-World Analogy

**Single-Leader:** A head chef (primary) prepares all dishes, and trainees (replicas) copy each recipe in their notebooks. Customers can ask trainees to read recipes, but only the head chef can create/modify dishes.

**Multi-Leader:** Two head chefs at different restaurants both create dishes. When Chef A creates a new dish, Chef B gets a copy, and vice versa. If both create the same dish with slightly different ingredients simultaneously — conflict!

**Leaderless (Dynamo-style):** A committee of 5 people where any 3 must agree on a decision. No single leader; decisions are made by quorum.

---

## 3. Visual Diagram

```
SINGLE-LEADER REPLICATION:
┌─────────────────────────────────────────────────────────────┐
│  All WRITES ──→ Leader (Primary)                            │
│                     │                                       │
│        ┌────────────┼────────────┐                          │
│        ▼ replication             ▼                          │
│   Replica 1                Replica 2                        │
│  (Read traffic)            (Standby / failover)             │
│                                                             │
│  Async: Leader returns immediately; replicas lag (ms)       │
│  Sync:  Leader waits for at least 1 replica to confirm      │
└─────────────────────────────────────────────────────────────┘

MULTI-LEADER REPLICATION:
DataCenter A                    DataCenter B
Leader A  ←──── conflict? ────→ Leader B
    │                               │
 Replica A1                     Replica B1
 Replica A2                     Replica B2

LEADERLESS (DYNAMO):
           Write(value)  Read(value)
Client ──→ Node 1  ✓         Node 1  ✓
       ──→ Node 2  ✓         Node 2  (stale)
       ──→ Node 3  ✓         Node 3  ✓
W=2 (write to 2 of 3), R=2 (read from 2 of 3)
Quorum satisfied: R + W > N (2 + 2 > 3) → consistent reads
```

---

## 4. Deep Technical Explanation

### Single-Leader Replication

**Synchronous vs Asynchronous:**

| | Synchronous | Asynchronous |
|--|-------------|-------------|
| Leader returns | After replica confirms write | Immediately after own write |
| Replication lag | Zero | Milliseconds to seconds |
| Durability | High (replica confirms) | Risk: data loss if leader fails before replication |
| Latency | Higher (waits for replica) | Lower |
| Replica failure | Blocks writes | Does NOT block writes |

**Semi-synchronous:** 1 replica is synchronous, rest are async — balance between durability and availability.

**MySQL binlog replication:** Leader writes binary log → replica reads binlog → replica applies changes. Lag can be monitored via `SHOW SLAVE STATUS`.

**Replication lag problems:**
1. **Read-your-writes:** User writes profile, immediately reads it → request goes to stale replica → sees old data. Fix: route user's own reads to leader for 1 minute after write.
2. **Monotonic reads:** User refreshes page → each refresh hits different replica → sometimes sees newer data, sometimes older. Fix: sticky sessions (same replica for same user).
3. **Consistent prefix reads:** User sees reply before the original question (messages reordered). Fix: causally related writes on same partition.

### Multi-Leader Replication

**Use cases:** Multi-datacenter deployments, offline mobile apps (each device is a leader), collaborative editing.

**Conflict resolution:**
1. **Last Write Wins (LWW):** Whichever write has the latest timestamp wins. Simple but loses concurrent writes; requires synchronized clocks.
2. **Version vectors / CRDTs:** Track causality; merge concurrent changes mathematically.
3. **Application-level conflict resolution:** Surface conflict to user ("which version would you like to keep?") — Google Docs model.
4. **Operational Transformation:** Used by collaborative editing tools to merge concurrent edits.

### Leaderless Replication (Dynamo-style)

**Quorum:** With N replicas, W writes confirmed, R reads required:
- **Strong consistency:** R + W > N (read set and write set overlap)
  - W=3, R=2, N=3: every read sees the latest write
- **High availability:** W=1, R=1 (fast, but reads may be stale)
- **Typical:** W=2, R=2, N=3 (balanced)

**Read repair:** When a client reads from multiple nodes and gets stale value on one, it sends the latest value back to the stale node.

**Anti-entropy:** Background process comparing replica data and fixing divergences.

### Change Data Capture (CDC)
Transform DB replication stream into events that other services can consume:
- **MySQL binlog / PostgreSQL WAL** → Debezium → Kafka topic
- Downstream services (search index, cache invalidation, analytics) react to changes
- Guarantees: at-least-once delivery (events may be replayed; use idempotency)

---

## 5. Code Example

```php
// Read-your-writes consistency — route to leader after own write
class UserRepository {
    public function updateProfile(int $userId, array $data): void {
        // Write to leader
        $this->leader->update('users', $data, ['id' => $userId]);
        
        // Mark user as recently wrote — route reads to leader for 1 minute
        Cache::put("user_wrote:{$userId}", true, 60);
    }
    
    public function getProfile(int $userId): array {
        // If this user recently wrote, use leader for read
        if (Cache::has("user_wrote:{$userId}")) {
            return $this->leader->find('users', $userId);
        }
        
        // Otherwise, load-balance across replicas
        return $this->getReadReplica()->find('users', $userId);
    }
    
    private function getReadReplica(): DB {
        $replicas = [$this->replica1, $this->replica2];
        return $replicas[array_rand($replicas)];
    }
}
```

```php
// Conflict-free Replicated Data Type (CRDT) — increment counter
class DistributedCounter {
    // G-Counter (Grow-Only): each node tracks its own increment
    // Merge = take max of each node's counter
    
    private array $counts;  // nodeId => count
    private string $nodeId;
    
    public function increment(): void {
        $this->counts[$this->nodeId]++;
    }
    
    public function value(): int {
        return array_sum($this->counts);
    }
    
    // Merge two replicas — commutative, associative, idempotent
    public function merge(self $other): self {
        $merged = clone $this;
        foreach ($other->counts as $nodeId => $count) {
            $merged->counts[$nodeId] = max($merged->counts[$nodeId] ?? 0, $count);
        }
        return $merged;
    }
}
```

---

## 6. Trade-offs

| Model | Consistency | Availability | Complexity | Use Case |
|-------|-------------|-------------|-----------|----------|
| Single-leader sync | Strong | Lower (replica failure blocks) | Low | Financial systems |
| Single-leader async | Eventual | High | Low | Most web apps |
| Multi-leader | Eventual + conflict | High | High | Multi-datacenter |
| Leaderless | Tunable (quorum) | High | Medium | DynamoDB, Cassandra |

---

## 7. Interview Q&A

**Q1: What is replication lag and how do you handle it?**
> Replication lag is the delay between a write on the leader and that write appearing on replicas. It's typically milliseconds but can grow to seconds/minutes under load. Problems: read-your-writes (user doesn't see own update), monotonic reads (flip-flopping between stale/fresh data). Solutions: (1) route user's own reads to leader after writes; (2) sticky replica sessions per user; (3) vector clocks / version numbers to detect staleness.

**Q2: How does leader election work when the primary fails?**
> The remaining nodes hold an election. In Raft/PAXOS consensus: a candidate node requests votes from other nodes, and wins if it gets majority votes. The new leader must have the most up-to-date log. Problem: split-brain — if network partition causes two nodes to both think they're leader, you get two primaries. Fix: require majority quorum (in a 3-node cluster, need 2 votes — so only one "side" of a partition can elect a leader). PostgreSQL uses patroni + etcd/ZooKeeper for this.

**Q3: What is the difference between synchronous and asynchronous replication?**
> Synchronous: the leader waits for at least one replica to confirm the write before returning to the client. Guarantees durability — no data loss if leader fails. But slower writes and if the replica is unavailable, writes are blocked. Asynchronous: leader returns to client immediately after writing to its own storage. Faster writes but risk of data loss if leader fails before replication completes. Semi-synchronous is the pragmatic middle ground.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Single-leader: simple, common; replicas for reads                │
│ ✓ Async replication = lower latency but risk of data loss         │
│ ✓ Read-your-writes: route to leader for 1 minute after write      │
│ ✓ Leaderless (Dynamo): R+W>N for strong consistency              │
│ ✓ Multi-leader: conflict resolution is hard (use CRDTs/LWW)       │
│ ✓ CDC (Debezium) = replicate DB changes to Kafka for other services│
└────────────────────────────────────────────────────────────────────┘
```
