# C14 — Top-K & Heavy Hitters

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** "Top-K" answers: "What are the 10 most-viewed videos on YouTube right now?" A naive approach counts all views in a database — too slow for real-time. Approximate algorithms (Count-Min Sketch, Space-Saving) do this with very little memory.

**Technical:** Top-K and Heavy Hitters problems find the most frequent elements in a data stream. Exact solutions require O(N) memory; approximate solutions (Count-Min Sketch, Space-Saving, Misra-Gries) use O(K × log(1/ε)) space with bounded error.

---

## 2. Real-World Analogy

**Radio station "top 10 songs":**
- Naive: count every play for every song ever → massive storage
- Approximation: maintain a "hit counter" for only the top 10 candidates, estimate the rest
- New songs knock out old ones when their count exceeds a current top-10 member

---

## 3. Visual Diagram

```
COUNT-MIN SKETCH:
2D array of counters (d rows × w columns)
d = log(1/δ) hash functions (δ = confidence)
w = e/ε columns (ε = error rate)

ADD element "video:123":
  hash1("video:123") → col 7  → row1[7]++
  hash2("video:123") → col 2  → row2[2]++
  hash3("video:123") → col 11 → row3[11]++

QUERY frequency of "video:123":
  min(row1[7], row2[2], row3[11]) → estimated count
  (minimum because hash collisions only overcount, never undercount)

For Top-K: maintain a Min-Heap of K elements
  When estimated count > min in heap → replace

STREAM PROCESSING (real-time Top-K):
                  ┌─────────────────┐
Event stream ───→ │ Count-Min Sketch│ ──→ Min-Heap (top-K candidates)
(view events)     │ (O(1) update)   │     (O(log K) update)
                  └─────────────────┘
```

---

## 4. Deep Technical Explanation

### Count-Min Sketch
- **Space:** O(d × w) = O(log(1/δ) × e/ε) — independent of N (stream size!)
- **Update:** O(d) — increment d cells
- **Query:** O(d) — return minimum of d cells
- **Error:** Frequency estimate ≤ true frequency + ε × N with probability 1-δ
- **Over-estimate only:** Counts can be higher than actual (hash collisions), never lower

### Misra-Gries Algorithm (exact heavy hitters, memory proportional to K)
For finding elements appearing > N/K times:
1. Maintain a table of K (element, count) pairs
2. For each incoming element:
   - If in table: increment count
   - If not in table and table not full: add with count=1
   - If not in table and table full: decrement all counts by 1, remove zeros
3. After processing all N elements: table contains all elements that appear > N/(K+1) times

### Space-Saving Algorithm
Improvement over Misra-Gries — more accurate estimates:
- Always maintain exactly K counters
- When new element has no counter: replace min-count counter with new element (but keep the old count + 1 as the "error" estimate)

### Practical Architectures

**YouTube Top Videos (real-world):**
1. View events → Kafka
2. Real-time layer: Flink/Spark Streaming → Count-Min Sketch per 1-min window
3. Batch layer: Hadoop/Spark job counts exact views per video per day
4. Serving layer: Redis sorted set with top 1000 videos (refreshed every minute)

**Redis ZADD approach (for moderate scale):**
- `ZINCRBY trending:videos 1 "video:123"` (sorted set with score = view count)
- `ZREVRANGE trending:videos 0 9` (top 10 by score)
- Works perfectly for up to ~1M unique items
- For billions of items per second: use Count-Min Sketch

---

## 5. Code Example

```php
class CountMinSketch {
    private array $table;
    private array $hashSeeds;
    private int   $width;
    private int   $depth;
    
    public function __construct(float $epsilon = 0.01, float $delta = 0.01) {
        $this->width     = (int) ceil(M_E / $epsilon);    // e / ε
        $this->depth     = (int) ceil(log(1 / $delta));   // ln(1/δ)
        $this->table     = array_fill(0, $this->depth, array_fill(0, $this->width, 0));
        $this->hashSeeds = array_map(fn($i) => $i * 2654435761, range(1, $this->depth));
    }
    
    public function add(string $item, int $count = 1): void {
        for ($i = 0; $i < $this->depth; $i++) {
            $col = $this->hash($item, $this->hashSeeds[$i]) % $this->width;
            $this->table[$i][$col] += $count;
        }
    }
    
    public function estimate(string $item): int {
        $min = PHP_INT_MAX;
        for ($i = 0; $i < $this->depth; $i++) {
            $col = $this->hash($item, $this->hashSeeds[$i]) % $this->width;
            $min = min($min, $this->table[$i][$col]);
        }
        return $min;
    }
    
    private function hash(string $item, int $seed): int {
        $hash = $seed;
        for ($i = 0; $i < strlen($item); $i++) {
            $hash = ($hash ^ ord($item[$i])) * 2246822519;
            $hash &= 0x7FFFFFFF;
        }
        return $hash;
    }
}

// Real-time trending with Redis sorted set
class TrendingService {
    private \Redis $redis;
    
    public function recordView(string $videoId): void {
        $key = "trending:" . date('YmdH');  // per-hour key
        $this->redis->zIncrBy($key, 1, $videoId);
        $this->redis->expire($key, 7200);  // 2-hour TTL
    }
    
    public function getTopK(int $k = 10): array {
        $key = "trending:" . date('YmdH');
        return $this->redis->zRevRange($key, 0, $k - 1, true);
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you design a real-time trending topics system for Twitter?**
> Tweets come in as a stream. Each tweet's hashtags increment a Count-Min Sketch. A Min-Heap maintains the current top-K estimated heavy hitters. Every minute, emit the top-K to a Redis sorted set for serving. For personalization: maintain per-region Count-Min Sketches. For burst detection: compare count velocity over past 5 minutes vs 1-hour average (z-score). Tools: Kafka for ingestion, Flink for real-time aggregation, Redis for serving.

**Q2: What is the error bound of Count-Min Sketch?**
> With width w = e/ε and depth d = ln(1/δ): the estimated frequency of any element is within ε × N of the true frequency, with probability at least 1-δ. Example: ε=0.01, δ=0.01 → 1% error of total stream size, 99% confidence. For 1 billion events, estimated count is within 10 million of true count. The estimate is always an overcount — never undercount (hash collisions can only add, not subtract).

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Count-Min Sketch: O(1) update, O(1) query, bounded error       │
│ ✓ Always over-estimates (never under): safe for "probably frequent"│
│ ✓ Combine with Min-Heap to maintain top-K candidates              │
│ ✓ Redis ZADD/ZINCRBY: simple Top-K for moderate cardinality       │
│ ✓ Use for: trending topics, hot products, DDoS IP detection       │
│ ✓ Space independent of stream size — works for infinite streams   │
└────────────────────────────────────────────────────────────────────┘
```
