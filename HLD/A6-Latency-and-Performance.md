# A6 — Latency & Performance

> **Section:** Foundational Concepts | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Latency is how long it takes for your system to respond to a request. Performance is how well your system works under load.

**Technical:** Latency is the time from a request being sent to the response being received (measured in nanoseconds to seconds). Performance encompasses throughput (requests per second), latency distribution (p50/p95/p99), resource utilization, and error rates under various load conditions.

---

## 2. Real-World Analogy

**Restaurant Service:**
- **Latency** = time from ordering to receiving your food
- **Throughput** = number of tables the restaurant serves per hour
- **p99 latency** = the wait time for the 1-in-100 unluckiest customers
- **Tail latency** = the slowest 1% — often caused by garbage collection, lock contention, or unlucky cache misses
- **Hedged requests** = asking two chefs to cook your dish and using whichever finishes first (reduces tail latency)

---

## 3. Visual Diagram

```
LATENCY NUMBERS EVERY ENGINEER MUST KNOW:
┌─────────────────────────────────────────────────────────────┐
│ Operation                  │ Latency       │ Relative       │
├────────────────────────────┼───────────────┼────────────────┤
│ L1 cache reference         │ 0.5 ns        │ 1x             │
│ L2 cache reference         │ 7 ns          │ 14x            │
│ Mutex lock/unlock          │ 25 ns         │ 50x            │
│ Main memory (RAM) access   │ 100 ns        │ 200x           │
│ Compress 1KB (Snappy)      │ 3,000 ns      │ 6,000x         │
│ Send 1KB over network      │ 10,000 ns     │ 20,000x        │
│ SSD random read            │ 150,000 ns    │ 300,000x       │
│ Read 1MB from memory       │ 250,000 ns    │ 500,000x       │
│ Same-datacenter round trip │ 500,000 ns    │ 1,000,000x     │
│ Read 1MB from SSD          │ 1,000,000 ns  │ 2,000,000x     │
│ HDD seek time              │ 10,000,000 ns │ 20,000,000x    │
│ Cross-region round trip    │ 150,000,000 ns│ 300,000,000x   │
└────────────────────────────┴───────────────┴────────────────┘
Key insight: RAM is 300x faster than SSD; same-DC is 300x faster than cross-region

LATENCY PERCENTILE DISTRIBUTION:
Requests (%)
100% |████████████████████████████████
 95% |██████████████████████████
 50% |█████████████
  0% |─────────────────────────────────→ Latency
     0ms    10ms    50ms   100ms  500ms
           p50      p95    p99
```

---

## 4. Deep Technical Explanation

### Percentile Latency — Why p99 Matters More Than Average

**Average is misleading:** If 99 requests take 10ms and 1 takes 1000ms, average = 19.9ms. But 1% of users wait 1 second — your most active users are likely in that 1%.

| Percentile | Meaning | Who it impacts |
|-----------|---------|----------------|
| p50 (median) | 50% of requests are faster | "Typical" user experience |
| p95 | 95% of requests are faster | Frequent users see this |
| p99 | 99% of requests are faster | Power users, most impactful |
| p999 | 99.9% of requests are faster | Rare, but indicates worst-case |

**Rule of thumb:** Design for p99, not average. Amazon found that 100ms increase in latency = 1% decrease in sales.

### Tail Latency Causes
1. **Garbage collection pauses** (JVM, PHP memory collection)
2. **Lock contention** — waiting for exclusive lock
3. **Hot cache keys** — thundering herd after expiry
4. **Network jitter** — packet loss triggering TCP retransmit
5. **CPU scheduling** — OS preempts thread mid-request
6. **DB query plan variation** — same query, different execution plan

### Tail Latency Solutions
- **Hedged requests:** Send same request to 2 servers simultaneously, use whichever responds first (costs 2x resources but cuts p99 significantly)
- **Timeout + retry:** Set aggressive timeout, retry on a different server
- **Pre-warming:** Keep connections warm, pre-populate caches
- **Reduce GC pauses:** Use GC-optimized languages (Go, C++), tune JVM heap

### Throughput vs Latency Trade-off
- **Batching increases throughput, increases latency:** Instead of processing each request immediately, batch 100 requests → 100x higher throughput, but each request waits up to batch timeout
- **Caching reduces latency, may reduce throughput burden:** Serve from cache in 1ms instead of querying DB in 50ms → allows same hardware to handle more load

### Amdahl's Law (Parallelization Limit)
- If 5% of code is serial (cannot parallelize), max speedup from parallelization = 1/0.05 = 20x
- Even with infinite CPUs, you can't go faster than your serial bottleneck
- Applies to: DB queries, external API calls, file I/O

---

## 5. Code Example

```php
// Measuring latency percentiles in application code
class LatencyTracker {
    private array $measurements = [];
    
    public function record(float $latencyMs): void {
        $this->measurements[] = $latencyMs;
    }
    
    public function percentile(float $p): float {
        sort($this->measurements);
        $index = (int) ceil($p / 100 * count($this->measurements)) - 1;
        return $this->measurements[max(0, $index)];
    }
    
    public function report(): array {
        return [
            'p50'  => $this->percentile(50),
            'p95'  => $this->percentile(95),
            'p99'  => $this->percentile(99),
            'p999' => $this->percentile(99.9),
            'max'  => max($this->measurements),
        ];
    }
}

// Usage
$tracker = new LatencyTracker();
$start = microtime(true);
$result = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
$tracker->record((microtime(true) - $start) * 1000);

// Output: ['p50' => 2.1, 'p95' => 15.3, 'p99' => 87.2, 'p999' => 450.0]
```

```php
// Hedged requests — reduce tail latency
class HedgedHttpClient {
    private float $hedgeAfterMs;
    
    public function __construct(float $hedgeAfterMs = 50.0) {
        $this->hedgeAfterMs = $hedgeAfterMs;
    }
    
    public function get(string $url): Response {
        // Start first request
        $promise1 = $this->asyncGet($url);
        
        // If first request takes > hedgeAfterMs, send second request
        // whichever finishes first wins
        usleep((int)($this->hedgeAfterMs * 1000));
        
        if ($promise1->isPending()) {
            $promise2 = $this->asyncGet($url);
            return race([$promise1, $promise2]); // first to complete wins
        }
        
        return $promise1->wait();
    }
}
```

---

## 6. Trade-offs

| Optimization | Latency Gain | Cost |
|-------------|-------------|------|
| L1/L2 cache hit | Sub-nanosecond | Limited size |
| In-memory cache (Redis) | 1–5ms → 0.5ms | Memory cost |
| Read replica | 50ms → 5ms (read) | Replication lag |
| CDN edge | 150ms → 5ms | Cache invalidation complexity |
| Connection pooling | 50ms → 5ms (connection overhead) | Memory |
| Hedged requests | p99: 200ms → 50ms | 2x resource usage |
| Async processing | 0ms (immediate ack) | Eventual processing |

---

## 7. Interview Q&A

**Q1: Why do we care about p99 latency instead of average?**
> Average latency hides outliers. If 99% of requests take 10ms but 1% take 2 seconds, the average looks great (~30ms) but 1-in-100 users has a terrible experience. For services with millions of users, 1% means thousands of people per minute. Amazon uses p99/p999 as SLO targets because high-value users (who use the service most) are more likely to hit tail latency cases.

**Q2: What causes tail latency and how do you reduce it?**
> Major causes: GC pauses (stop-the-world collection), lock contention (waiting for exclusive locks), hot cache invalidation (thundering herd), network jitter (TCP retransmit on packet loss). Solutions: hedged requests (send to two servers, use fastest response), aggressive timeouts with retry, GC tuning, circuit breakers to fail fast rather than queue, and keeping connections warm to avoid cold-start costs.

**Q3: If a client makes 5 serial network calls, each at 50ms p99, what's the p99 of the whole request?**
> In serial calls, latencies add: 5 × 50ms = 250ms. But if any single call hits its p99, the whole request is slow. At p99, each call has 1% chance of being slow — with 5 serial calls, the probability that ALL are fast is 0.99^5 ≈ 95%. So the combined p99 is roughly 95%. Use parallel calls where possible, and aggregate only what's needed.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ RAM (100ns) is 300x faster than SSD (150µs)                     │
│ ✓ Same-DC round trip (500µs) is 300x faster than cross-region     │
│ ✓ Use p99, not average, as your latency target                    │
│ ✓ Serial calls multiply latency — parallelize where possible      │
│ ✓ Hedged requests reduce tail latency at cost of extra resources  │
│ ✓ Every network hop adds latency — minimize them with caching     │
└────────────────────────────────────────────────────────────────────┘
```
