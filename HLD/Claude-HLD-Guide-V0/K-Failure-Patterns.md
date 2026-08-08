# K — Common Failure Patterns & Anti-Patterns

> **Section:** Methodology | **Level:** Advanced | **Interview Frequency:** ★★★★★

---

## 1. Overview

Senior engineers are distinguished by their knowledge of failure modes. Interviewers actively probe whether you can predict what goes wrong and why. This file covers the most common distributed system failure patterns, how they manifest, and how to fix them.

---

## 2. Thundering Herd (Cache Stampede)

**What it is:** Cache key expires. Simultaneously, thousands of requests find the cache empty and all query the database at once, overwhelming it.

**Trigger:** Popular cache key with short TTL expires under heavy load.

```
Normal:
  1000 req/sec -> [Redis HIT] -> fast response

Cache expires:
  1000 req/sec -> [Redis MISS] -> 1000 simultaneous DB queries -> DB overloaded -> cascade failure
```

**Fix 1: Mutex / Probabilistic Early Expiry**
```php
function getCachedUser(int $userId): array {
    $cacheKey = "user:{$userId}";
    $data     = $this->redis->get($cacheKey);

    if ($data !== null) {
        // Probabilistic early expiry: refresh before it actually expires
        $ttl = $this->redis->ttl($cacheKey);
        if ($ttl < 60 && (mt_rand(1, 100) / 100) < (1 - ($ttl / 60))) {
            // Preemptively refresh -- only one random request will do this
            // Avoids hard expiry stampede
            $this->refreshCacheAsync($userId);
        }
        return json_decode($data, true);
    }

    // Distributed mutex: only ONE request fetches from DB
    $lockKey     = "lock:user:{$userId}";
    $lockAcquired = $this->redis->set($lockKey, 1, ['NX', 'EX' => 10]);

    if ($lockAcquired) {
        // I got the lock: fetch from DB
        $user = $this->db->find($userId);
        $this->redis->setEx($cacheKey, 3600, json_encode($user));
        $this->redis->del($lockKey);
        return $user;
    } else {
        // Another request is fetching: wait briefly, then retry
        usleep(50000);  // 50ms
        return $this->getCachedUser($userId);  // retry -- will likely hit cache now
    }
}
```

**Fix 2: Staggered TTL**
```php
// Add random jitter to TTL: not all keys expire at the same time
$baseTtl     = 3600;
$jitter      = mt_rand(0, 300);  // +0 to +5 minutes random
$this->redis->setEx($cacheKey, $baseTtl + $jitter, $value);
```

---

## 3. Hot Partition

**What it is:** One shard/partition receives a disproportionate share of traffic while others are idle.

**Examples:**
- All writes for user "Justin Bieber" (100M followers) go to partition keyed by user_id
- Kafka partition for a viral event topic receives 1000x normal traffic
- Database partition for "products starting with 'A'" gets all Black Friday traffic

**Fix: Key Salting**
```php
// Problem: all writes for celebrity_id=123 go to the same shard
$shardKey = hash('xxh3', (string) $userId) % $numShards;

// Fix: add random suffix to distribute across multiple shards
$salt         = mt_rand(0, 9);  // 10 sub-shards per user
$shardKey     = hash('xxh3', "{$userId}_{$salt}") % $numShards;
$storageKey   = "user:{$userId}_{$salt}:timeline";

// Reads: must read from ALL 10 sub-shards and merge
// Only use for write-heavy hot keys where write throughput > single shard limit
```

**Fix: Adaptive Routing**
```
Monitor per-shard QPS.
If shard X > 2x average: split shard X into X.1 and X.2 (consistent hashing makes this easy).
```

---

## 4. N+1 Query Problem

**What it is:** Fetching a list of N items, then making 1 DB query per item to fetch related data = N+1 total queries.

```php
// BAD: N+1 queries
$posts = $db->query("SELECT * FROM posts LIMIT 20");   // 1 query
foreach ($posts as $post) {
    $post->author = $db->query(
        "SELECT * FROM users WHERE id = {$post->user_id}"  // N queries!
    );
}
// Total: 21 queries for 20 posts

// GOOD: 2 queries (batch load)
$posts   = $db->query("SELECT * FROM posts LIMIT 20");
$userIds = array_unique(array_column($posts, 'user_id'));
$users   = $db->query(
    "SELECT * FROM users WHERE id IN (" . implode(',', $userIds) . ")"
);  // 1 query for all users

// Index users by ID for O(1) lookup:
$usersById = array_column($users, null, 'id');
foreach ($posts as $post) {
    $post->author = $usersById[$post->user_id];
}
// Total: 2 queries regardless of N
```

---

## 5. Split-Brain

**What it is:** Network partition causes two parts of a cluster to each believe they are the leader/primary. Both accept writes, resulting in divergent state.

```
Normal:
  [Primary] <- heartbeat -> [Replica]

Network partition:
  [Primary] X X X [Replica]
  Primary: "I am leader (no heartbeat from replica)"
  Replica: "I am new leader (primary is dead!)" -- both accept writes
  Partition heals: two conflicting versions of data
```

**Fix: Fencing Tokens**
```php
// Lease-based leadership with monotonic fencing token
// Leader election gives token to winner (incrementing integer)
// Storage layer rejects writes with stale token

class FencedWrite {
    public function write(string $key, string $value, int $fencingToken): void {
        $currentToken = (int) $this->redis->get("fence:token:{$key}");
        if ($fencingToken <= $currentToken) {
            throw new StaleLeaderException(
                "Write rejected: token {$fencingToken} <= current {$currentToken}"
            );
        }
        $this->redis->set("fence:token:{$key}", $fencingToken);
        $this->redis->set($key, $value);
    }
}

// Leader election: winner gets token N+1
// Old leader's writes rejected because its token N < new leader's token N+1
```

**Fix: Quorum Writes**
```
Require W > N/2 acknowledgments.
Impossible for two "leaders" to both get quorum simultaneously (pigeonhole principle).
```

---

## 6. Retry Storm

**What it is:** A dependent service slows down. Clients timeout and retry. The retries increase load on the already-struggling service, making it worse, causing more timeouts, more retries.

```
Service B slows (95% response in 2s):
  Client: timeout at 1s -> retry immediately
  Each client retries: doubles load on B
  B now has 2x normal load -> even slower -> more timeouts -> more retries -> cascade
```

**Fix: Exponential Backoff with Jitter**
```php
function callWithRetry(callable $fn, int $maxRetries = 3): mixed {
    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        try {
            return $fn();
        } catch (TransientException $e) {
            if ($attempt === $maxRetries) {
                throw $e;
            }
            // Exponential backoff: 100ms, 200ms, 400ms base
            $baseDelay = 100 * (2 ** $attempt);
            // Full jitter: random(0, baseDelay) -- spreads clients randomly
            $jitter    = mt_rand(0, $baseDelay);
            usleep($jitter * 1000);
        }
    }
}

// Without jitter: all clients retry at exactly 100ms, 200ms, 400ms -> synchronized spikes
// With full jitter: clients spread retries randomly -> smooth load
```

---

## 7. Head-of-Line Blocking

**What it is:** A slow request blocks subsequent faster requests waiting in the same queue/connection.

```
Request queue: [slow 5s req] [fast 1ms] [fast 1ms] [fast 1ms]
All fast requests wait behind the slow one.
```

**Fixes:**
```
1. Separate queues by priority / request type
   - Separate endpoints for: read (fast) vs write (can be slow)
   - Separate thread pools per operation type (Bulkhead pattern -- see D4)

2. Timeouts: abort slow requests, don't let them block forever
   $db->query($sql, timeout: 500);  // 500ms max

3. HTTP/2 multiplexing: stream-level isolation (see F5)
   - One slow stream doesn't block other streams on same connection

4. Async processing: accept request, return job_id, process in background
   POST /reports -> { job_id: "abc123" }
   GET /reports/abc123/status -> { status: "processing" | "ready", download_url: "..." }
```

---

## 8. Cascading Failures

**What it is:** One service failure causes dependent services to fail, propagating across the system.

```
DB slow -> App servers waiting on DB -> App server thread pool exhausted ->
Load balancer health checks fail -> LB routes all traffic to remaining servers ->
Remaining servers also exhaust thread pools -> Total outage
```

**Prevention Checklist:**
```
1. Timeouts on all external calls (DB, HTTP, Redis) -- never wait forever
2. Circuit Breakers (see C2): fail fast when dependency is unhealthy
3. Bulkheads (see D4): isolate thread pools so one dependency can't starve others
4. Rate limiting inbound traffic: shed load gracefully under pressure
5. Graceful degradation: if recommendations fail, show popular items (not error)
6. Health check endpoints: /health returns 503 if dependencies unhealthy
   -> LB stops sending traffic to sick instances
```

---

## 9. Anti-Pattern: Distributed Monolith

**What it is:** Microservices in name only -- every service calls every other service synchronously. A single slow service cascades failures everywhere.

```
BAD (synchronous chain):
  User Request -> Service A -> Service B -> Service C -> Service D
  If D is slow: C waits, B waits, A waits, user times out
  If D is down: cascading 500 errors through A, B, C

GOOD (async + bulkheads):
  User Request -> Service A (returns immediately with job_id)
  Kafka event -> Service B (async)
  Kafka event -> Service C (async)
  B/C failures: isolated, queued, retried -- don't affect user response
```

---

## 10. Anti-Pattern: Missing Idempotency

**What it is:** Retries cause duplicate actions (double charge, double email, duplicate orders).

```php
// BAD: POST /payments is not idempotent
// Retry after timeout -> customer charged twice

// GOOD: Idempotency key (client generates UUID, sends in header)
// Server: INSERT ... WHERE idempotency_key = ? ON CONFLICT DO NOTHING
// If key already exists: return cached response (same status as original)

function createPayment(string $idempotencyKey, int $amountCents): PaymentResult {
    // Check for existing payment with this key
    $existing = $this->db->findWhere('payments', ['idempotency_key' => $idempotencyKey]);
    if ($existing !== null) {
        return PaymentResult::fromRow($existing);  // Return original result
    }

    // Process new payment
    $result = $this->stripe->charge($amountCents);
    $this->db->insert('payments', [
        'idempotency_key' => $idempotencyKey,
        'amount_cents'    => $amountCents,
        'stripe_id'       => $result->id,
        'status'          => $result->status,
    ]);
    return $result;
}
```

---

## 11. Anti-Pattern: Ignoring the Slow Path

**What it is:** Optimizing for the p50 (average), ignoring p99 tail latency. 1% of requests take 10x longer, but since they're rare they're ignored -- until they cause timeouts in chains of services.

```
Service A calls B: p99 = 500ms, timeout = 1s -> OK
But A chains B + C + D: 500ms + 500ms + 500ms = 1500ms > 1s timeout

Tail Latency Amplification:
  Each service: 99% requests < 100ms
  Chain of 10 services: probability ALL are fast = 0.99^10 = 90%
  -> 10% of requests hit a slow path somewhere in the chain!
```

**Fix: Hedged Requests**
```php
// Send same request to two replicas, use whichever responds first
// Cancel the slower one
function hedgedGet(string $key): string {
    $primary   = $this->async(fn() => $this->replica1->get($key));
    $secondary = null;

    // After 95th-percentile latency, send to second replica too
    usleep(50000);  // 50ms hedge delay (p95 latency)
    if (!$primary->isDone()) {
        $secondary = $this->async(fn() => $this->replica2->get($key));
    }

    return $primary->isDone()
        ? $primary->result()
        : ($secondary ?? $primary)->await();
}
// Cost: ~5% extra requests to replicas
// Benefit: p99 drops to near p50 (slow replica no longer determines your latency)
```

---

## 12. Quick Reference Cheat Sheet

```
+---------------------------+----------------------------------------+
| Failure Pattern           | Primary Fix                            |
+---------------------------+----------------------------------------+
| Thundering Herd           | Mutex lock + probabilistic early expiry |
| Hot Partition             | Key salting + shard splitting           |
| N+1 Query                 | Batch loading (WHERE id IN (...))      |
| Split-Brain               | Fencing tokens + quorum writes         |
| Retry Storm               | Exponential backoff + full jitter      |
| Head-of-Line Blocking     | Separate queues + timeouts             |
| Cascading Failure         | Circuit Breaker + Bulkhead + Timeouts  |
| Distributed Monolith      | Async messaging (Kafka) + decouple     |
| Missing Idempotency       | Idempotency key + ON CONFLICT DO NOTHING|
| Tail Latency Amplification| Hedged requests + tight p99 SLOs       |
+---------------------------+----------------------------------------+
```
