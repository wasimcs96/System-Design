# B2 — Caching

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Caching is storing a copy of frequently used data in a fast storage location (like memory) so you don't have to fetch it from slow storage (like a database) every time.

**Technical:** A cache is a high-speed data storage layer that stores a subset of data — typically transient — so future requests for that data are served faster than accessing the primary storage. Caches trade memory for speed and introduce consistency challenges.

**Core rule:** Cache is always a copy of data that lives elsewhere. The "real" data is in the primary store (DB, API).

---

## 2. Real-World Analogy

**Office Desk Analogy:**
- **L1 CPU Cache** = papers on your desk (fastest, tiniest)
- **L2 CPU Cache** = drawer in your desk
- **Redis/Memcached** = filing cabinet in your room
- **Database** = archive room in the basement (slowest, largest)
- **CDN** = branch office in another city that has copies of the most popular documents

When you need a document:
1. Check your desk (L1 cache) — instant
2. Check your drawer (L2 cache) — seconds
3. Go to filing cabinet (distributed cache) — minutes
4. Go to archive room (database) — long trip
5. File it in your drawer/desk for next time (cache population)

---

## 3. Visual Diagrams

```
CACHE PLACEMENT HIERARCHY:
Browser Cache → CDN Edge → API Gateway Cache → App Cache → Redis → Database
(nearest)                                                           (furthest)
   1ms          5ms              10ms              15ms      20ms    100ms

CACHE-ASIDE (LAZY LOADING) — Most common:
Client ──→ App Server ──→ Redis Cache ──→ HIT: return cached value
                              │
                              └── MISS ──→ Database ──→ Store in Redis ──→ return to client

WRITE-THROUGH:
Client ──→ App Server ──→ Cache ──→ Database (synchronous, both updated)

WRITE-BACK (WRITE-BEHIND):
Client ──→ App Server ──→ Cache (update immediately, return)
                              └── async queue ──→ Database (update later)

READ-THROUGH:
Client ──→ Cache Layer ──→ HIT: return
                       └── MISS: Cache fetches from DB, stores, returns
```

---

## 4. Deep Technical Explanation

### Cache Strategies

**1. Cache-Aside (Lazy Loading) — DEFAULT CHOICE**
```
Read: Check cache → miss → read from DB → write to cache → return
Write: Update DB only; cache entry either expires or is deleted
```
- ✓ Only caches what's actually requested
- ✓ DB failure doesn't break cache
- ✗ Cache miss = extra DB roundtrip (first request is slow)
- ✗ Data can be stale until TTL expiry

**2. Read-Through**
```
Read: App asks cache → cache checks itself → miss → cache fetches DB → cache stores → returns to app
```
- ✓ App code simpler (only talks to cache)
- ✗ Cache miss still slow (same penalty as cache-aside)
- Used by: AWS ElastiCache with RDS, some ORM-level caches

**3. Write-Through**
```
Write: App writes to cache → cache synchronously writes to DB → both updated
```
- ✓ Cache always has latest data (strong consistency)
- ✗ Write latency = cache + DB (both must complete)
- ✗ Writes for unread data waste cache space
- Use when: read-after-write consistency required (payments, inventory)

**4. Write-Back (Write-Behind)**
```
Write: App writes to cache → immediately returns → background job writes to DB
```
- ✓ Lowest write latency
- ✗ Risk of data loss if cache fails before DB write
- Use when: high write throughput acceptable with durability risk (gaming scores, IoT)

**5. Write-Around**
```
Write: App writes directly to DB (bypasses cache)
Read: Cache-aside for reads
```
- ✓ Cache not polluted with write data that may not be read
- Use when: write-once, read-rarely data (log files, backups)

### Cache Eviction Policies
| Policy | How | Best For |
|--------|-----|----------|
| **LRU** (Least Recently Used) | Evict item unused longest | General workloads |
| **LFU** (Least Frequently Used) | Evict item accessed fewest times | Popular vs unpopular content |
| **FIFO** | Evict oldest inserted | Simple, order-dependent data |
| **TTL** | Evict after time-to-live | Time-sensitive data |
| **Random** | Random eviction | Simple, good enough |

**Redis default:** allkeys-lru (when maxmemory reached, evict least recently used)

### Cache Stampede / Thundering Herd
**Problem:** 10,000 users request the same expired cache key simultaneously. All miss, all hit DB at once → DB crashes.

**Solutions:**
1. **Mutex lock:** First request gets lock to rebuild cache, others wait
2. **Probabilistic early expiry:** Before TTL expires, randomly refresh with probability that increases as expiry approaches
3. **Background refresh:** Before cache expires, proactively refresh in background thread
4. **Cache warming:** Pre-populate cache before high traffic event

```php
// Mutex solution for cache stampede
public function getPopularData(string $key): mixed {
    $cached = Redis::get($key);
    if ($cached !== null) return json_decode($cached, true);
    
    // Only one process rebuilds the cache
    $lockKey = "lock:{$key}";
    $locked  = Redis::set($lockKey, 1, 'NX', 'EX', 10); // NX = only if not exists
    
    if ($locked) {
        $data = $this->expensiveDbQuery();
        Redis::setex($key, 3600, json_encode($data));
        Redis::del($lockKey);
        return $data;
    }
    
    // Others wait and retry
    usleep(100000); // 100ms
    return $this->getPopularData($key);  // retry
}
```

### Redis vs Memcached

| Feature | Redis | Memcached |
|---------|-------|-----------|
| Data structures | Strings, Lists, Sets, Sorted Sets, Hashes, Streams | Strings only |
| Persistence | RDB snapshots + AOF logs | None |
| Pub/Sub | Yes | No |
| Lua scripting | Yes | No |
| Clustering | Redis Cluster | Consistent hashing client-side |
| Multi-threading | Single-threaded (6.0+ I/O multi-thread) | Multi-threaded |
| **Choose when** | Need data structures, pub/sub, persistence | Pure cache, very high throughput |

**Default choice: Redis** (richer features, better ecosystem)

### Cache Invalidation Strategies
1. **TTL-based:** Entries expire after N seconds. Simple but may serve stale data until expiry.
2. **Event-driven:** When DB record changes, explicitly delete/update cache entry. Complex but accurate.
3. **Write-through:** DB + cache always in sync (but slower writes).
4. **Cache versioning:** Change cache key when data changes (e.g., `user:123:v2`). Old entries orphaned.

---

## 5. Code Example

```php
// Complete Cache-Aside implementation
class ProductRepository {
    private const TTL = 3600; // 1 hour
    
    public function findById(int $id): ?Product {
        $cacheKey = "product:{$id}";
        
        // 1. Try cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return Product::fromArray(json_decode($cached, true));
        }
        
        // 2. Cache miss — query database
        $product = Product::find($id);
        
        // 3. Store in cache (only if found)
        if ($product) {
            Cache::put($cacheKey, json_encode($product->toArray()), self::TTL);
        }
        
        return $product;
    }
    
    public function update(int $id, array $data): Product {
        $product = Product::findOrFail($id)->update($data);
        
        // Invalidate cache — force fresh read next time
        Cache::forget("product:{$id}");
        
        return $product;
    }
    
    public function getTopProducts(int $limit = 10): array {
        return Cache::remember("top_products:{$limit}", 300, function() use ($limit) {
            return Product::orderByDesc('sales_count')->limit($limit)->get()->toArray();
        });
    }
}
```

```php
// Redis data structures for advanced caching
class LeaderboardCache {
    // Sorted Set: key=user_id, score=points — O(log N) insert/read
    public function addScore(string $userId, float $score): void {
        Redis::zadd('leaderboard', $score, $userId);
    }
    
    public function getTopN(int $n): array {
        // Returns top N in descending order: [userId => score]
        return Redis::zrevrangebyscore('leaderboard', '+inf', '-inf', [
            'LIMIT' => [0, $n],
            'WITHSCORES' => true,
        ]);
    }
    
    public function getRank(string $userId): int {
        return Redis::zrevrank('leaderboard', $userId);  // 0-indexed
    }
}
```

---

## 6. Trade-offs

| Strategy | Read Perf | Write Perf | Consistency | Complexity |
|----------|-----------|-----------|-------------|-----------|
| Cache-Aside | High | Normal | Eventual | Low |
| Read-Through | High | Normal | Eventual | Medium |
| Write-Through | High | Lower | Strong | Medium |
| Write-Back | High | Highest | Eventual | High |
| Write-Around | Normal | Normal | N/A | Low |

---

## 7. Interview Q&A

**Q1: What caching strategy would you use for an e-commerce product catalog?**
> Cache-Aside with TTL of 5–15 minutes. Product catalog is read-heavy (thousands of reads per write), and minor stale data (showing old price for a few minutes) is acceptable. On product update, explicitly invalidate the cache key. For product listings/search results, use shorter TTL (60–300s). For inventory counts, don't cache (too critical).

**Q2: What is a cache stampede and how do you prevent it?**
> Cache stampede occurs when many concurrent requests all miss the same expired cache key and simultaneously query the database — potentially overwhelming it. Prevention: (1) mutex lock — only one request rebuilds the cache, others wait; (2) probabilistic early expiry — start refreshing before TTL expires based on probability; (3) background refresh — a background job proactively refreshes cache before expiry.

**Q3: How would you implement a distributed cache with Redis Cluster?**
> Redis Cluster shards data across 16,384 hash slots distributed across N master nodes (each with replicas). A key is assigned to a slot using CRC16(key) mod 16384. The client library (predis, phpredis) handles routing — it knows which node owns which slots. Adding nodes redistributes slots with minimal data movement. Set maxmemory and eviction policy (allkeys-lru) to handle memory pressure.

**Q4: What's the difference between Redis persistence (RDB vs AOF)?**
> RDB (Redis Database Backup) takes periodic point-in-time snapshots — fast to restore but may lose data since last snapshot. AOF (Append Only File) logs every write command — can lose at most 1 second of data (with fsync every second). Production recommendation: enable both — RDB for fast restart, AOF for data safety. Trade-off: AOF files grow large and need periodic rewrite (BGREWRITEAOF).

---

## 8. Key Takeaways

```
┌───────────────────────────────────────────────────────────────────┐
│ ✓ Cache-Aside (Lazy Loading) is the default strategy             │
│ ✓ Write-Through for read-after-write consistency                  │
│ ✓ Write-Back for high-write throughput (risk: data loss)         │
│ ✓ LRU eviction by default; TTL for time-sensitive data           │
│ ✓ Use mutex or background refresh to prevent cache stampede      │
│ ✓ Redis > Memcached for most use cases (richer data structures)  │
│ ✓ Cache invalidation is hard — prefer TTL + explicit invalidation│
└───────────────────────────────────────────────────────────────────┘
```
