# B5 — Sharding & Partitioning

> **Section:** Core Infrastructure | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Sharding splits a large database into smaller pieces (shards), each stored on a different server. It's like splitting a phone book into A-G, H-P, Q-Z — each book is smaller and easier to search.

**Technical:** Horizontal partitioning (sharding) distributes data rows across multiple database nodes based on a shard key. Each shard contains a subset of the total rows and operates as an independent database. Vertical partitioning splits a table by columns across different servers.

---

## 2. Real-World Analogy

**Library Branches:**
- **Vertical partitioning** = Reference books in one branch, fiction in another, children's in another (split by type/column)
- **Horizontal sharding (Range)** = Branch 1 has books by authors A-G, Branch 2 has H-P, Branch 3 has Q-Z
- **Hash sharding** = Hash the author's name to determine which of 5 branches holds the book (uniform distribution)
- **Directory sharding** = A central lookup book tells you which branch has which book
- **Hotspot problem** = If all popular authors are in Branch 2 (H-P), it's overwhelmed while others are idle

---

## 3. Visual Diagram

```
HORIZONTAL SHARDING:
                     ┌──────────────────┐
                     │   Shard Router   │
                     │ (decides shard)  │
                     └────────┬─────────┘
          ┌──────────────────┼──────────────────┐
          ▼                  ▼                  ▼
   ┌────────────┐    ┌────────────┐    ┌────────────┐
   │  Shard 1   │    │  Shard 2   │    │  Shard 3   │
   │user_id 1-M │    │user_id M-2M│    │user_id 2M+ │
   │(PostgreSQL)│    │(PostgreSQL)│    │(PostgreSQL)│
   └────────────┘    └────────────┘    └────────────┘

VERTICAL PARTITIONING:
Original Table (users): id, name, email, bio, avatar, settings, last_login
           ↓ Split into:
Users Core: id, name, email, last_login  (frequently accessed)
Users Profile: id, bio, avatar, settings  (rarely accessed, large)

SHARDING STRATEGIES:
1. Range:   user_id 1–1M → Shard1, 1M–2M → Shard2
2. Hash:    hash(user_id) % 3 → Shard 0/1/2
3. Directory: lookup table: user_id 123 → Shard 2
4. Geo:     users in India → Shard-IN, users in US → Shard-US
```

---

## 4. Deep Technical Explanation

### Sharding Strategies

**1. Range-Based Sharding**
- Shard key: user_id 1–1M on Shard1, 1M–2M on Shard2
- ✓ Easy range queries (get all users created this month)
- ✗ Hotspots: new users (high IDs) always go to the newest shard

**2. Hash-Based Sharding**
- Shard key: `shard = hash(user_id) % num_shards`
- ✓ Uniform distribution — no hotspots
- ✗ Range queries span all shards (WHERE user_id BETWEEN X AND Y)
- ✗ Adding a shard requires remapping all data (use consistent hashing)

**3. Directory-Based Sharding**
- Central lookup service: "user_id 123 lives on Shard 2"
- ✓ Most flexible — migrate individual users between shards
- ✗ Lookup service is a single point of failure + extra latency

**4. Geo-Based Sharding**
- Users in India → India shard, US users → US shard
- ✓ Reduced latency (data co-located with users)
- ✓ Regulatory compliance (data sovereignty: GDPR, etc.)
- ✗ Uneven shard sizes if geo distribution uneven

### Hotspots
**Problem:** A shard key that concentrates traffic:
- Sequential ID → all new writes go to highest shard (range-based)
- Celebrity user → one user's data gets millions of reads/writes
- Time-based key → "today's shard" gets all traffic

**Solutions:**
- Add random prefix to shard key: `shard_key = user_id + rand(0, 10)` → spread across 10 shards
- Consistent hashing with virtual nodes
- Dedicated hot-shard handling: replicate hot user's data across all shards

### Cross-Shard Queries — The Problem
- `SELECT * FROM orders JOIN users ON orders.user_id = users.id WHERE users.country = 'India'`
- users may be on Shard1, orders on Shard3 — JOIN cannot happen at DB level
- **Solutions:**
  1. Denormalize: store `user_country` in orders table (copy needed columns)
  2. Application-level join: fetch from multiple shards, join in application code
  3. Scatter-gather: broadcast query to all shards, aggregate results (slow)
  4. Co-locate: ensure related data always on same shard (users + their orders on same shard)

### Resharding — The Painful Operation
When a shard fills up or traffic outgrows capacity:
1. Create new shard
2. Migrate subset of data from old shard to new
3. Update shard routing rules
4. Remove migrated data from old shard

**Problem:** During migration, reads/writes to migrating data must be handled carefully (dual-write or read-from-both).
**Solution:** Consistent hashing minimizes data movement when nodes are added/removed (only adjacent range remaps).

---

## 5. Code Example

```php
class ShardRouter {
    private array $shards;
    private int   $numShards;
    
    public function __construct(array $shardConnections) {
        $this->shards    = $shardConnections;
        $this->numShards = count($shardConnections);
    }
    
    // Hash-based sharding
    public function getShardForUser(int $userId): PDO {
        $shardIndex = $userId % $this->numShards;
        return $this->shards[$shardIndex];
    }
    
    // Range-based sharding
    public function getShardForRange(int $userId): PDO {
        $rangesPerShard = 1_000_000;
        $shardIndex     = (int) floor($userId / $rangesPerShard);
        $shardIndex     = min($shardIndex, $this->numShards - 1);
        return $this->shards[$shardIndex];
    }
    
    // Cross-shard query — scatter-gather pattern
    public function queryAllShards(string $sql, array $params = []): array {
        $results = [];
        foreach ($this->shards as $shard) {
            $stmt = $shard->prepare($sql);
            $stmt->execute($params);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        return $results;
    }
}

// Usage
$router = new ShardRouter([
    new PDO('mysql:host=shard1.db;dbname=app', $user, $pass),
    new PDO('mysql:host=shard2.db;dbname=app', $user, $pass),
    new PDO('mysql:host=shard3.db;dbname=app', $user, $pass),
]);

$shard = $router->getShardForUser(123456);
$user  = $shard->query("SELECT * FROM users WHERE id = 123456")->fetch();
```

```php
// Consistent Hashing for Sharding
class ConsistentHashRing {
    private array $ring    = [];
    private int   $vnodes  = 150;  // virtual nodes per physical node
    
    public function addNode(string $nodeId): void {
        for ($i = 0; $i < $this->vnodes; $i++) {
            $hash = crc32("{$nodeId}:{$i}");
            $this->ring[$hash] = $nodeId;
        }
        ksort($this->ring);
    }
    
    public function removeNode(string $nodeId): void {
        $this->ring = array_filter($this->ring, fn($n) => $n !== $nodeId);
    }
    
    public function getNode(string $key): string {
        $hash = crc32($key);
        foreach ($this->ring as $nodeHash => $nodeId) {
            if ($hash <= $nodeHash) return $nodeId;
        }
        // Wrap around
        return reset($this->ring);
    }
}
```

---

## 6. Trade-offs

| Strategy | Distribution | Range Queries | Resharding | Hotspot Risk |
|----------|-------------|--------------|-----------|-------------|
| Range | Uneven | Easy | Hard | High (sequential IDs) |
| Hash | Uniform | Hard (all shards) | Hard (full remap) | Low |
| Consistent Hash | Near-uniform | Hard | Easy (O(1/N) remapping) | Low |
| Directory | Flexible | Depends | Easy | Low |
| Geo | Geo-uniform | Within region | Easy | If geo-uneven |

---

## 7. Interview Q&A

**Q1: How would you design sharding for a user database with 1 billion users?**
> Use hash-based sharding on user_id with consistent hashing to allow adding shards. Start with 10 shards, each handling 100M users. Co-locate user data (profile, orders, sessions) on the same shard by user_id to avoid cross-shard joins. Use a shard routing service that caches the shard map. For resharding, use a "double-write" migration: write to both old and new shard for a period, then flip reads to new shard, then stop old shard writes.

**Q2: What are the problems with sharding and how do you mitigate them?**
> Problems: (1) Cross-shard joins — mitigate by denormalizing or co-locating related data; (2) Hotspots — mitigate with consistent hashing + virtual nodes or random key suffixes; (3) Transactions across shards — mitigate with Saga pattern or avoid multi-shard transactions by design; (4) Schema changes — run migrations on all shards sequentially; (5) Resharding complexity — use consistent hashing and automated migration tools.

**Q3: What is the difference between partitioning and sharding?**
> Partitioning (in SQL databases like PostgreSQL) splits a table into multiple physical storage segments within the same database server — improves query performance by pruning partitions. Sharding distributes data across multiple, separate database servers — enables horizontal scaling beyond what a single server can handle. Partitioning is within one node; sharding is across multiple nodes.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Hash sharding = uniform distribution; range = hotspot risk      │
│ ✓ Consistent hashing minimizes data movement when adding shards   │
│ ✓ Co-locate related data on same shard to avoid cross-shard joins │
│ ✓ Cross-shard joins: denormalize or application-level join        │
│ ✓ Resharding is painful — design shard key carefully upfront      │
│ ✓ Vertical partitioning: split rarely-used columns to separate DB │
└────────────────────────────────────────────────────────────────────┘
```
