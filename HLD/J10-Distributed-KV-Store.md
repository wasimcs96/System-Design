# J10 — Design a Distributed Key-Value Store

> **Section:** Case Studies | **Difficulty:** Very Hard | **Interview Frequency:** ★★★★☆

---

## 1. Problem Statement

Design a distributed key-value store like Redis, DynamoDB, or Cassandra.

**Functional Requirements:**
- `put(key, value)` — store or update a key-value pair
- `get(key)` — retrieve value by key
- `delete(key)` — remove a key
- Keys and values can be arbitrary bytes (up to 1 MB)

**Non-Functional Requirements:**
- 1B keys, terabytes of data (doesn't fit on one machine)
- < 10ms p99 for reads and writes
- Highly available (AP system — prioritize availability over consistency)
- Eventually consistent (tunable: client can request strong consistency)
- Fault tolerant: survive up to N-1 node failures in a group of N nodes

---

## 2. High-Level Architecture

```
Client
  |
  v
[Coordinator Node] (any node can be coordinator -- like Dynamo)
  |
  +-- Hashes key to find responsible nodes (consistent hashing)
  |
  +-- Replicates to N nodes (e.g., N=3)
  |
  +-- Returns success when W nodes ack write (W=2 for quorum write)
  |
  +-- Reads from R nodes, returns latest value (R=2 for quorum read)
  (R + W > N guarantees reading your own writes)

Nodes: each stores a partition of the key space
Data: stored in LSM-Tree (MemTable + SSTables) on disk
```

---

## 3. Data Distribution: Consistent Hashing

```php
// See C8 for full consistent hashing implementation
// Key points:
//   - Hash ring: each node occupies a range on a 0..2^32 ring
//   - Virtual nodes (150 per physical node): even distribution despite heterogeneous nodes
//   - On node add/remove: only adjacent range data needs to move (not all data)

class ConsistentHashRing {
    private array $ring    = [];  // sorted list of [hash => nodeId]
    private int   $vnodes  = 150; // virtual nodes per physical node

    public function getNodes(string $key, int $n = 3): array {
        $hash        = $this->hash($key);
        $startIdx    = $this->findPosition($hash);
        $nodes       = [];
        $seen        = [];

        for ($i = $startIdx; count($nodes) < $n; $i = ($i + 1) % count($this->ring)) {
            $nodeId = $this->ring[$i]['nodeId'];
            if (!in_array($nodeId, $seen)) {
                $nodes[] = $nodeId;
                $seen[]  = $nodeId;
            }
        }
        return $nodes;  // N nodes responsible for this key
    }
}
```

---

## 4. Write Path (Quorum Write)

```php
// N=3, W=2: write to 3 nodes, wait for 2 acks -> return success
// Third write happens async -> eventual consistency

class KVCoordinator {
    public function put(string $key, string $value, array $options = []): bool {
        $nodes   = $this->ring->getNodes($key, $this->N);  // e.g., [Node1, Node2, Node3]
        $version = $this->generateVersion();  // Vector clock or timestamp

        $acks = 0;
        $futures = [];
        foreach ($nodes as $node) {
            $futures[] = $this->async(fn() => $node->put($key, $value, $version));
        }

        // Wait for W acks (quorum)
        foreach ($futures as $future) {
            if ($future->await(timeout: 100)) {  // 100ms timeout per node
                $acks++;
                if ($acks >= $this->W) {
                    return true;  // Quorum reached -- return success immediately
                    // Remaining async writes complete in background
                }
            }
        }

        throw new QuorumNotReachedException("Only {$acks}/{$this->W} acks");
    }
}

// Per-node storage (LSM-Tree):
class StorageNode {
    public function put(string $key, string $value, int $version): void {
        // 1. Write to WAL (Write-Ahead Log) for durability
        $this->wal->append(['op' => 'PUT', 'key' => $key, 'value' => $value, 'ver' => $version]);

        // 2. Write to MemTable (sorted in-memory structure)
        $this->memTable->put($key, ['value' => $value, 'version' => $version]);

        // 3. If MemTable too large (>64MB): flush to SSTable on disk
        if ($this->memTable->size() > 64 * 1024 * 1024) {
            $this->flushMemTableToSSTable();
        }
    }
}
```

---

## 5. Read Path (Quorum Read + Read Repair)

```php
public function get(string $key): ?string {
    $nodes = $this->ring->getNodes($key, $this->N);

    $responses = [];
    foreach ($nodes as $node) {
        $resp = $node->get($key);
        if ($resp !== null) {
            $responses[] = $resp;
        }
        if (count($responses) >= $this->R) {
            break;  // Quorum reached
        }
    }

    if (empty($responses)) {
        return null;
    }

    // Pick latest version (highest vector clock / timestamp)
    $latest = $this->pickLatest($responses);

    // Read Repair: if some nodes returned stale version, update them async
    foreach ($responses as $resp) {
        if ($resp['version'] < $latest['version']) {
            $this->async(fn() => $resp['node']->put($key, $latest['value'], $latest['version']));
        }
    }

    return $latest['value'];
}
```

---

## 6. Conflict Resolution

```
Problem: Two clients write to same key on different nodes simultaneously
         (network partition caused the nodes to diverge)

Value of key "user:1" after partition heals:
  Node A: {"name": "Alice", "version": [A:2, B:1]}
  Node B: {"name": "Alice Smith", "version": [A:1, B:2]}
  -> Vector clocks diverge -- CONFLICT

Resolution strategies:
  1. Last Write Wins (LWW): pick entry with highest timestamp
     - Simple, but risks data loss (whichever write arrived "last" wins)
     - Used by: Cassandra (default)

  2. Merge (CRDT): if value is a counter, add both increments
     - Works for specific data types: counters, sets, registers
     - Used by: Riak, Redis CRDT

  3. Return both + let client resolve:
     - Return both conflicting versions to client
     - Client merges and sends merged value back
     - Used by: Amazon Dynamo (shopping cart = union of items)

  4. Application-level: last write wins within a session (read-your-writes)
     - Use sticky routing: same client always talks to same replica for a session
```

---

## 7. Anti-Entropy (Gossip + Merkle Trees)

```
Problem: After network partitions, how do nodes sync missing/stale data?

Gossip Protocol (for node membership):
  - Every second, each node picks 2 random peers, exchanges node state
  - Within O(log N) rounds, all nodes know about membership changes

Merkle Tree (for data sync):
  - Each node builds a Merkle tree of its key-value data (hash of ranges)
  - Nodes periodically exchange Merkle tree roots
  - If roots differ: walk tree to find divergent subtree -> sync that range only
  - Used by: Cassandra, DynamoDB for repair operations

Hinted Handoff:
  If target node is temporarily down during write:
    Coordinator stores write hint on a healthy node ("deliver to Node3 when it recovers")
    When Node3 recovers, the hinting node sends all pending hints
    Node3 catches up without full Merkle tree sync
```

---

## 8. Tunable Consistency

```
N=3, W+R configurations:
  W=3, R=1: Strong consistency writes, fast reads. Write latency = slowest of 3.
  W=1, R=3: Fast writes, slow reads. Risk: read may see stale data during write.
  W=2, R=2: Quorum (R+W=4 > N=3). Balance. Default recommendation.
  W=1, R=1: Fastest, weakest. May read stale data. Use for: ephemeral sessions.

In PHP client:
$kv->put($key, $value, consistency: 'QUORUM');   // W=2 of 3
$kv->put($key, $value, consistency: 'ALL');       // W=3 of 3 (strong)
$kv->put($key, $value, consistency: 'ONE');       // W=1 of 3 (fast)
```

---

## 9. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Distribution | Consistent hashing + virtual nodes | Even distribution, minimal data movement on resize |
| Storage | LSM-Tree (MemTable + SSTable) | Write-optimized, fast sequential disk writes |
| Consistency | Tunable quorum (N=3, W=2, R=2) | Configurable per operation |
| Conflict | LWW default + CRDT option | Simple default; CRDT for specific types |
| Repair | Merkle tree + hinted handoff | Efficient sync; minimize network |

---

## 10. Interview Q&A

**Q: Why use LSM-Tree instead of B-Tree for storage?**
> B-Trees do in-place updates: to update a key, seek to its location on disk and overwrite. This causes random I/O (expensive). LSM-Trees write sequentially: all writes go to an in-memory MemTable, then flushed as immutable SSTable files in order. Sequential writes are ~10x faster than random writes on HDD, and even on SSD (avoids write amplification). Trade-off: reads can be slower (may need to check multiple SSTables), mitigated by Bloom Filters (quickly check if SSTable contains a key) and compaction (merge SSTables to reduce their number).

**Q: What happens if the coordinator node fails during a write?**
> The client has a timeout (e.g., 1 second). On timeout, the client retries with an idempotency key. Because writes are idempotent (re-applying same write with same version = safe), retrying is safe. The new coordinator node re-executes the same write (deduplication via version number). This is the same "at-least-once delivery + idempotency" pattern used across distributed systems. For the partial write case (W-1 nodes received write before coordinator crashed): the next quorum read will trigger read repair to propagate the write to remaining nodes.

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| Consistent hashing + virtual nodes = even data distribution      |
| Tunable quorum (N, W, R): W+R > N = read-your-writes guarantee  |
| LSM-Tree: write-optimized, sequential I/O, compaction            |
| Read repair: lazy consistency healing on every quorum read       |
| Merkle tree: efficient anti-entropy sync after partition          |
| Hinted handoff: catch up fast after node recovery                |
+--------------------------------------------------------------------+
```
