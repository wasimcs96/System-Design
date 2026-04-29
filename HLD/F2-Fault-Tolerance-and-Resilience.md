# F2 — Fault Tolerance and Resilience

> **Section:** Observability and Security | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Fault tolerance is designing your system so that when parts fail (and they will), the system continues working. Resilience is recovering quickly from failures. Together they prevent small problems from becoming catastrophic outages.

**Technical:** Fault tolerance patterns: timeouts, retries with exponential backoff + jitter, circuit breakers, bulkheads, fallbacks, hedged requests. Cascading failure prevention: if ServiceA depends on ServiceB which is slow/failing, ServiceA must not become slow/failing too.

---

## 2. Real-World Analogy

**Electrical circuit breaker:** Your home circuit breaker protects appliances. If one appliance shorts, the breaker trips — protecting the rest of the house. Without it, one faulty appliance could cause a house fire.

**Retry = trying a vending machine again.** First try fails (jammed). Wait, try again. But don't hammer it 100 times instantly — wait a bit each time (backoff). And don't try forever — give up after N attempts (timeout).

---

## 3. Visual Diagram

```
TIMEOUT + RETRY + CIRCUIT BREAKER + FALLBACK:

Request ──→ [Timeout: 2s] ──→ [Service B] ──→ Response
                ↓ timeout!
           [Retry logic]
           Attempt 1: wait 100ms, retry
           Attempt 2: wait 200ms + jitter, retry
           Attempt 3: wait 400ms + jitter, retry
                ↓ still failing
           [Circuit Breaker: OPEN]
                ↓ all future requests
           [Fallback: cached response / degraded response]
           (don't call Service B while circuit is open)

CASCADING FAILURE (without protections):
User ──→ ServiceA ──→ ServiceB ──→ ServiceC [SLOW/DOWN]
All 100 ServiceA threads hang waiting for ServiceB
ServiceA is now down too
User ──→ ServiceA [DOWN]
ServiceD ──→ ServiceA [DOWN]
Entire system collapses from one slow service

PROTECTION STACK:
Timeout:         2s max wait per call
Retry:           3 attempts with exponential backoff + jitter
Circuit breaker: open after 5 failures in 60s, rest 30s
Bulkhead:        max 10 concurrent calls to ServiceB
Fallback:        return cached/degraded response when circuit open
```

---

## 4. Deep Technical Explanation

### Timeout
- Every network call MUST have a timeout — TCP connections can hang indefinitely without one
- Set timeout lower than the caller's timeout: if user timeout is 5s, your dependency timeout should be 2s (leaves time for retries or fallback)
- Timeout budget: distribute available time across service call chain

### Retry with Exponential Backoff + Jitter
- **Exponential backoff:** wait 2^attempt seconds: 1s, 2s, 4s, 8s...
- **Without jitter:** all retrying clients retry at same time — thundering herd
- **With jitter:** add random(0, wait) to spread retries: 1.3s, 2.8s, 3.1s...
- **Full jitter:** `wait = random(0, base * 2^attempt)` — most spread out
- Only retry on: transient errors (5xx, network timeout). Never retry: 4xx (client errors)

### Circuit Breaker States
- **CLOSED:** normal operation — requests pass through
- **OPEN:** failure threshold exceeded — requests fail fast (no call made)
- **HALF-OPEN:** after sleep window — allow one request to test recovery
  - Success: -> CLOSED
  - Failure: -> OPEN again

### Fallback Strategies
1. **Cache fallback:** return last known good response
2. **Default fallback:** return empty/default ("no recommendations available")
3. **Static fallback:** return hardcoded safe response
4. **Fail silent:** return empty rather than error (for non-critical features)
5. **Fail fast:** propagate error immediately (for critical path)

### Hedged Requests
- Send same request to two backends simultaneously
- Use response from whichever replies first, cancel the other
- Eliminates tail latency (slow p99 instances)
- Cost: 2x requests to backend (use sparingly for critical paths)

---

## 5. Code Example

```php
class ResilientHttpClient {
    private array $circuitBreakers = [];
    
    public function get(string $url, array $options = []): array {
        $host = parse_url($url, PHP_URL_HOST);
        $cb   = $this->getCircuitBreaker($host);
        
        if ($cb->isOpen()) {
            // Circuit is open -- return fallback immediately
            return $this->fallback($url, $options);
        }
        
        $maxAttempts = $options['max_attempts'] ?? 3;
        $timeout     = $options['timeout']      ?? 2.0;
        $lastError   = null;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                // Exponential backoff with full jitter
                $base  = 0.1 * (2 ** $attempt);  // 0.2s, 0.4s, 0.8s
                $jitter = mt_rand(0, (int)($base * 1000)) / 1000;
                usleep((int)(($base + $jitter) * 1000000));
            }
            
            try {
                $response = $this->httpCall($url, $timeout);
                $cb->recordSuccess();
                return $response;
            } catch (TimeoutException $e) {
                $lastError = $e;
                $cb->recordFailure();
                // Retry on timeout
            } catch (ServerErrorException $e) {
                $lastError = $e;
                $cb->recordFailure();
                // Retry on 5xx
            } catch (ClientErrorException $e) {
                // Don't retry 4xx errors
                throw $e;
            }
        }
        
        if ($cb->isOpen()) {
            return $this->fallback($url, $options);
        }
        
        throw $lastError;
    }
    
    private function fallback(string $url, array $options): array {
        // Return cached response if available
        $cacheKey = 'fallback:' . md5($url);
        $cached   = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return array_merge(json_decode($cached, true), ['_from_cache' => true]);
        }
        
        // Return empty/default response
        return $options['fallback'] ?? [];
    }
}

class CircuitBreaker {
    private int    $failureCount  = 0;
    private int    $successCount  = 0;
    private string $state         = 'CLOSED';  // CLOSED, OPEN, HALF_OPEN
    private float  $openedAt      = 0;
    
    public function __construct(
        private int   $failureThreshold = 5,
        private int   $sleepWindowSec   = 30,
    ) {}
    
    public function isOpen(): bool {
        if ($this->state === 'OPEN') {
            if (microtime(true) - $this->openedAt > $this->sleepWindowSec) {
                $this->state = 'HALF_OPEN';  // allow one test request
                return false;
            }
            return true;
        }
        return false;
    }
    
    public function recordSuccess(): void {
        $this->failureCount = 0;
        $this->state        = 'CLOSED';
    }
    
    public function recordFailure(): void {
        $this->failureCount++;
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state    = 'OPEN';
            $this->openedAt = microtime(true);
        }
    }
}
```

---

## 7. Interview Q&A

**Q1: How do you prevent cascading failures in a microservices system?**
> Four layers of defense: (1) Timeouts: every outbound call has a max wait time — threads don't hang indefinitely. (2) Bulkhead: separate thread/connection pool per dependency — slow ServiceB can't exhaust threads needed for ServiceC. (3) Circuit breaker: after N failures, stop calling the failing service (fail fast), return fallback. (4) Fallback: have a degraded response ready (cached data, empty result, default). These patterns prevent a slow/failing dependency from propagating failure to your service and upstream callers.

**Q2: Why do you need jitter in exponential backoff?**
> Without jitter: if 1000 clients all get a timeout at the same time, they all wait the same amount (e.g., 1 second) then retry simultaneously -> thundering herd -> overload the recovering server -> it fails again -> all retry at 2 seconds -> again. Adding random jitter (random(0, backoff_time)) spreads retries across the sleep window, reducing peak load on the recovering server and allowing it to recover gracefully.

**Q3: What is a hedged request?**
> Send the same request to two (or more) backend instances simultaneously. Use the first response, cancel the other. Eliminates tail latency: if 1% of requests are slow (p99 = 500ms but p50 = 10ms), hedging at p50 delay means you only pay 2x the fast case, never the slow case. Used by Google Spanner, AWS DynamoDB. Cost: 2x load on backends. Use sparingly: only for read-only, idempotent requests on the critical path.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| Every network call needs: timeout + retry + circuit breaker       |
| Retry: exponential backoff + jitter (prevents thundering herd)    |
| Circuit breaker: fail fast when dependency is failing             |
| Bulkhead: limit concurrent calls per dependency (resource isolation)|
| Fallback: cached response or graceful degradation                 |
| Only retry 5xx/timeout; never retry 4xx (client errors)           |
+--------------------------------------------------------------------+
```
