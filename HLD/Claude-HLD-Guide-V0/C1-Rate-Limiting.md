# C1 — Rate Limiting

> **Section:** Distributed Systems Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Rate limiting restricts how many requests a client can make in a given time period. Like a "only 5 coffee refills per hour" policy at a café.

**Technical:** Rate limiting is a technique to control the rate of requests a system accepts from a client, preventing resource exhaustion, DDoS, and ensuring fair usage. Implementations use in-memory or distributed counters with sliding/fixed windows.

---

## 2. Real-World Analogy

**Turnstile at a subway:** Only allows 1 person through per second — even if 100 people rush at once, the flow is controlled. Those who arrive too fast are told to wait.

**Token bucket:** You get 10 tokens per minute. Each request consumes 1 token. Unused tokens accumulate up to 10 max. Short bursts allowed (use saved tokens), but sustained rate capped.

---

## 3. Visual Diagram

```
FIXED WINDOW:
Minute 1: [req1][req2][req3]...[req10] ← LIMIT ← req11 REJECTED
Minute 2: [req1][req2]... ← counter RESET
Problem: 10 reqs at 0:59 + 10 reqs at 1:01 = 20 reqs in 2 seconds (edge burst)

SLIDING WINDOW LOG:
Track exact timestamps of recent requests
Window = last 60 seconds from now
Count = requests in that window
No edge burst problem — always accurate

TOKEN BUCKET:
Bucket capacity: 10 tokens
Refill rate: 10 tokens/minute
[●●●●●●●●●●] ← full bucket
Request → consume 1 token → [●●●●●●●●●○]
10 requests burst → [○○○○○○○○○○] → next request REJECTED until refill

LEAKY BUCKET:
Requests enter at any rate
Process 1 request/100ms (constant drip)
Buffer overflow = drop request
Smooths traffic to constant rate
```

---

## 4. Deep Technical Explanation

### Algorithms Comparison

| Algorithm | Accuracy | Memory | Burst Allow | Implementation |
|-----------|----------|--------|-------------|---------------|
| Fixed Window | Low (edge burst) | O(1) | At window boundary | Simple |
| Sliding Window Log | High (exact) | O(N requests) | No | Complex, memory-heavy |
| Sliding Window Counter | Medium-High | O(1) | Partial | Good balance |
| Token Bucket | High | O(1) | Yes (saved tokens) | Common choice |
| Leaky Bucket | High | O(1) | No (constant drain) | Smooth output |

### Sliding Window Counter (Best for most cases)
Combines fixed window simplicity with sliding window accuracy:
```
Current window count = (previous_window_count × overlap_ratio) + current_window_count

Example: limit = 10/min
At 1:15 (25% through current window):
  overlap = 1 - 0.25 = 0.75 (75% of previous window is within last 60s)
  previous window: 8 requests
  current window: 3 requests
  effective_count = (8 × 0.75) + 3 = 9 → under limit ✓
```

### Distributed Rate Limiting with Redis
Single-server rate limiting is easy (atomic counter in memory). Distributed requires coordination:
- **Redis atomic operations:** `INCR` + `EXPIRE` are atomic
- **Redis Lua script:** Execute check + increment atomically (no race conditions)
- **Redis Cluster:** Shard by rate limit key (user_id)

### Rate Limit Dimensions
- Per IP address (prevent DDoS from one IP)
- Per user ID (fair usage per account)
- Per API key (third-party developer quotas)
- Per endpoint (expensive endpoints rate limited separately)
- Per tenant (multi-tenant SaaS)

---

## 5. Code Example

```php
// Token Bucket rate limiter with Redis
class RateLimiter {
    private Redis   $redis;
    private int     $capacity;  // max tokens
    private float   $refillRate;  // tokens per second
    
    public function isAllowed(string $clientId): bool {
        $key    = "rate_limit:{$clientId}";
        $now    = microtime(true);
        
        // Lua script — atomic: no race condition between check and update
        $script = <<<'LUA'
        local key       = KEYS[1]
        local capacity  = tonumber(ARGV[1])
        local refill    = tonumber(ARGV[2])
        local now       = tonumber(ARGV[3])
        local cost      = tonumber(ARGV[4])
        
        local data = redis.call('HMGET', key, 'tokens', 'last_refill')
        local tokens     = tonumber(data[1]) or capacity
        local last_refill = tonumber(data[2]) or now
        
        -- Refill tokens based on elapsed time
        local elapsed = now - last_refill
        tokens = math.min(capacity, tokens + elapsed * refill)
        
        if tokens >= cost then
            tokens = tokens - cost
            redis.call('HMSET', key, 'tokens', tokens, 'last_refill', now)
            redis.call('EXPIRE', key, 3600)
            return 1  -- allowed
        else
            redis.call('HMSET', key, 'tokens', tokens, 'last_refill', now)
            return 0  -- rejected
        end
        LUA;
        
        $result = $this->redis->eval($script, [$key, $this->capacity, $this->refillRate, $now, 1], 1);
        return (bool) $result;
    }
}
```

```php
// Rate limit middleware — return proper 429 response
class RateLimitMiddleware {
    public function handle(Request $request, Closure $next): Response {
        $clientId = $request->user()?->id ?? $request->ip();
        $limiter  = app(RateLimiter::class);
        
        if (!$limiter->isAllowed($clientId)) {
            return response()->json(
                ['error' => 'Too many requests. Retry after 60 seconds.'],
                429,
                [
                    'Retry-After' => 60,
                    'X-RateLimit-Limit'     => 100,
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Reset'     => now()->addMinute()->timestamp,
                ]
            );
        }
        
        return $next($request);
    }
}
```

---

## 6. Trade-offs

| Algorithm | Pros | Cons |
|-----------|------|------|
| Token Bucket | Allows bursts, smooth | Slightly complex |
| Fixed Window | Simplest | Edge burst vulnerability |
| Sliding Window Counter | Accurate, O(1) space | Approximate (vs log) |
| Leaky Bucket | Constant output rate | Drops bursts, not queues |

---

## 7. Interview Q&A

**Q1: How do you implement rate limiting in a distributed system with 100 API servers?**
> Use Redis as a centralized rate limit store. Each API server sends INCR commands to Redis with TTL. Use a Lua script for atomic check + increment. Redis operates at ~100K ops/sec — sufficient for most systems. For extreme scale, use a per-server local window + a periodic global sync, accepting slight inaccuracy. AWS API Gateway has built-in rate limiting; alternatively, handle at the load balancer level.

**Q2: What HTTP headers should a rate-limited response include?**
> `X-RateLimit-Limit` (total allowed), `X-RateLimit-Remaining` (tokens left), `X-RateLimit-Reset` (Unix timestamp when limit resets), `Retry-After` (seconds until retry), `429 Too Many Requests` status code. These let clients back off gracefully and implement exponential backoff.

**Q3: What is the difference between rate limiting and throttling?**
> Rate limiting enforces a hard cap — requests over the limit are rejected (429). Throttling slows down requests — excess requests are queued and processed slower (like a leaky bucket). Rate limiting protects resources from abuse; throttling provides graceful degradation. A payment gateway might throttle (slow processing) rather than reject, since rejected payments lose revenue.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Token bucket: allows short bursts + enforces sustained rate     │
│ ✓ Redis Lua scripts: atomic check-and-decrement (no race)         │
│ ✓ Sliding window counter: best balance of accuracy/memory        │
│ ✓ Return 429 with Retry-After header for proper client backoff    │
│ ✓ Rate limit per user > per IP (more fair and accurate)           │
│ ✓ Rate limit expensive endpoints separately from cheap ones       │
└────────────────────────────────────────────────────────────────────┘
```
