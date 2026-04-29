# B8 — Consistent Hashing

> **Section:** Core Infrastructure | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Consistent hashing is a way to distribute data across servers such that when you add or remove a server, only a small fraction of the data needs to move — not everything.

**Technical:** Consistent hashing maps both keys and nodes to positions on a circular hash ring (0 to 2³²-1). Each key is assigned to the first node clockwise from its position. When a node is added or removed, only keys between the new/removed node and its predecessor need remapping — O(K/N) keys vs O(K) with naive modulo hashing.

---

## 2. Real-World Analogy

**Restaurant Delivery Zones:**
- Naive approach: Driver A covers restaurants 1–10, Driver B covers 11–20, Driver C covers 21–30. If Driver C quits: reassign ALL 10 restaurants (30% of all deliveries disrupted).
- Consistent hashing: Each driver is positioned on a clock face. Each restaurant goes to the next driver clockwise. If Driver C quits: only the restaurants between B and C are reassigned to A. ~33% disruption → ~10% disruption.
- Virtual nodes: Each driver has 3 clock positions (instead of 1) → more even distribution.

---

## 3. Visual Diagram

```
NAIVE MODULO HASHING — Adding a node causes massive remapping:
3 nodes: key → server = hash(key) % 3
4 nodes: key → server = hash(key) % 4
→ MOST keys change servers (3/4 = 75% remapped)

CONSISTENT HASH RING:
        0
       /  \
    330    30
     |      |
    300   Server A (hash=45)
     |      |
    270   90
     |    |
    240  Server B (hash=130)
     |      |
    210   150
       \  /
       180
    Server C (hash=250)

Key K (hash=80) → clockwise → Server B (hash=130) ✓
Key K (hash=200) → clockwise → Server C (hash=250) ✓
Key K (hash=300) → clockwise → Server A (hash=45, wraps around) ✓

ADD Server D (hash=100):
- Keys from 45 to 100 (previously going to B) → now go to D
- All other keys: UNCHANGED
- Only ~1/N keys remapped (O(K/N) vs O(K))
```

---

## 4. Deep Technical Explanation

### The Problem with Naive Hashing
```
3 servers: hash(key) % 3 → server 0, 1, or 2
Add 4th server: hash(key) % 4 → 75% of keys map to different servers

Consequence for distributed cache:
- Clients suddenly can't find their cached data (cache misses)
- DB overloaded as cache misses cascade
- Cache stampede
```

### Consistent Hashing Algorithm
1. Map each server to a position on the ring: `position = hash(server_id) % RING_SIZE`
2. Map each key to a position on the ring: `position = hash(key) % RING_SIZE`
3. Key belongs to the first server clockwise from its position
4. Server removal: keys of removed server → next clockwise server
5. Server addition: keys between predecessor and new server → new server

### Virtual Nodes (vnodes)
**Problem:** With few physical nodes, hash function may place them unevenly → one node holds 60% of keys, another 10%.

**Solution:** Each physical node gets V virtual nodes at different ring positions:
```
Physical Node A → VNode A1 (pos 45), VNode A2 (pos 180), VNode A3 (pos 320)
Physical Node B → VNode B1 (pos 130), VNode B2 (pos 240), VNode B3 (pos 30)
Physical Node C → VNode C1 (pos 90), VNode C2 (pos 200), VNode C3 (pos 280)
```
Each physical node owns ~1/N of the ring = even distribution regardless of hash function variance.

Typical vnodes per server: 150–300 (Cassandra default: 256)

### Used In
| System | Purpose |
|--------|---------|
| Redis Cluster | Distributes 16,384 hash slots across nodes |
| Cassandra | Token-based partitioning across nodes |
| Memcached | Client-side consistent hashing |
| CDNs (Akamai) | Route requests to edge servers |
| DynamoDB | Internal shard assignment |

---

## 5. Code Example

```php
class ConsistentHashRing {
    private array $ring    = [];
    private array $nodes   = [];
    private int   $vnodes  = 150;
    
    public function addNode(string $nodeId): void {
        $this->nodes[] = $nodeId;
        for ($i = 0; $i < $this->vnodes; $i++) {
            $hash = crc32("{$nodeId}:{$i}");
            // Normalize to unsigned 32-bit
            $hash = $hash < 0 ? $hash + 4294967296 : $hash;
            $this->ring[$hash] = $nodeId;
        }
        ksort($this->ring);
    }
    
    public function removeNode(string $nodeId): void {
        for ($i = 0; $i < $this->vnodes; $i++) {
            $hash = crc32("{$nodeId}:{$i}");
            $hash = $hash < 0 ? $hash + 4294967296 : $hash;
            unset($this->ring[$hash]);
        }
        $this->nodes = array_filter($this->nodes, fn($n) => $n !== $nodeId);
    }
    
    public function getNode(string $key): string {
        if (empty($this->ring)) {
            throw new RuntimeException('No nodes in ring');
        }
        
        $hash = crc32($key);
        $hash = $hash < 0 ? $hash + 4294967296 : $hash;
        
        // Find first node clockwise from hash position
        foreach ($this->ring as $nodeHash => $nodeId) {
            if ($hash <= $nodeHash) {
                return $nodeId;
            }
        }
        
        // Wrap around: return first node in ring
        return reset($this->ring);
    }
    
    // Get N nodes for replication
    public function getNodes(string $key, int $count): array {
        $startNode = $this->getNode($key);
        $nodes     = array_unique(array_values($this->ring));
        
        // Find start position and return N nodes
        $startIndex = array_search($startNode, $nodes);
        $result     = [];
        for ($i = 0; $i < min($count, count($nodes)); $i++) {
            $result[] = $nodes[($startIndex + $i) % count($nodes)];
        }
        return $result;
    }
}

// Usage
$ring = new ConsistentHashRing();
$ring->addNode('cache-1');
$ring->addNode('cache-2');
$ring->addNode('cache-3');

$key      = 'user:profile:12345';
$cacheNode = $ring->getNode($key);
// $cacheNode = 'cache-2' (deterministic based on key hash)

$ring->addNode('cache-4');
// Only ~25% of keys are remapped to cache-4
// cache-1, cache-2, cache-3 still serve ~75% of their original keys
```

---

## 6. Trade-offs

| Approach | Keys Remapped on Add/Remove | Distribution | Complexity |
|----------|--------------------------|-------------|-----------|
| Modulo hashing | ~(N-1)/N (most keys) | Perfect | Trivial |
| Consistent hashing | ~1/N (minimal) | Potentially uneven | Medium |
| Consistent hashing + vnodes | ~1/N | Even | Medium |

---

## 7. Interview Q&A

**Q1: Why is consistent hashing better than modulo hashing for distributed caches?**
> With modulo hashing (`hash(key) % N`), adding or removing one server causes ~(N-1)/N of all keys to be remapped to different servers — effectively a cache wipe. With consistent hashing, only ~1/N of keys are remapped (only the keys "owned" by the added/removed node). For a 100GB cache with 10 servers, naive hashing when adding 1 server causes ~90GB of cache misses; consistent hashing causes only ~10GB of misses.

**Q2: What is the purpose of virtual nodes (vnodes)?**
> Without virtual nodes, with few servers the hash function may place them unevenly on the ring — one server might own 60% of the key space, another 10%. Virtual nodes solve this by giving each physical server multiple positions on the ring (typically 150-300). This distributes each server's "share" of the ring across many segments, averaging out to ~1/N per server regardless of hash function variance.

**Q3: Where is consistent hashing used in real systems?**
> Redis Cluster uses 16,384 hash slots distributed across nodes (each slot is like a virtual node). Cassandra uses consistent hashing with token ranges (each node owns a token range on the ring; vnodes = 256 by default). Memcached uses client-side consistent hashing. CDNs use it to route requests to the optimal edge server. DynamoDB uses consistent hashing internally for partition management.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Consistent hashing: O(K/N) keys remapped vs O(K) with modulo    │
│ ✓ Virtual nodes (150-300 per server) = even distribution          │
│ ✓ Key → clockwise → first node on ring                            │
│ ✓ Used in: Redis Cluster, Cassandra, Memcached, CDN routing       │
│ ✓ crc32(node:i) for vnode positions, ksort for ring structure     │
└────────────────────────────────────────────────────────────────────┘
```
