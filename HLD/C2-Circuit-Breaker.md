# C2 — Circuit Breaker Pattern

> **Section:** Distributed Systems Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A circuit breaker is like the electrical circuit breaker in your home — when something goes wrong (too much current = too many failures), it trips open to prevent further damage. After a cooldown period, it resets and tries again.

**Technical:** The Circuit Breaker pattern prevents a service from repeatedly calling a failing downstream service, giving the failing service time to recover and preventing cascading failures. It maintains three states: Closed (normal), Open (failing, reject all calls), Half-Open (testing recovery).

---

## 2. Real-World Analogy

**Water pipe burst:**
- Without circuit breaker: Burst pipe → water keeps flowing → flooding everywhere
- With circuit breaker: Burst pipe → valve closes (OPEN state) → flooding stops → after 30s, valve cracks open (HALF-OPEN) to test → if pressure normal, valve opens (CLOSED) → if burst continues, valve closes again

---

## 3. Visual Diagram

```
CIRCUIT BREAKER STATE MACHINE:

              Failures >= threshold
CLOSED ─────────────────────────────→ OPEN
(normal, calls pass)              (all calls rejected,
        ▲                          fast fail with error)
        │                               │
        │ Success                       │ Timeout (30s)
        │                               ▼
        └─────────────────────── HALF-OPEN
                                (let 1 probe request through)
                                        │
                                        ├─→ FAIL → back to OPEN
                                        └─→ SUCCESS → back to CLOSED

CASCADING FAILURE WITHOUT CIRCUIT BREAKER:
ServiceA → ServiceB (slow/failing)
         ↑ threads pile up (waiting for B to respond)
ServiceA runs out of thread pool
ServiceA starts failing too
ServiceC → ServiceA (now A is failing)
→ Total cascade failure

WITH CIRCUIT BREAKER:
ServiceB failing → CircuitBreaker OPENS
ServiceA → CircuitBreaker (OPEN) → immediate error
ServiceA fails fast → releases threads immediately → ServiceA stays healthy
```

---

## 4. Deep Technical Explanation

### States

**CLOSED (Normal):**
- All requests pass through to the downstream service
- Track failure count in a sliding window
- If failures exceed threshold (e.g., 50% failure rate over 60s) → trip to OPEN

**OPEN (Failing):**
- All requests immediately return error (fail fast)
- No actual calls to downstream service
- After timeout period (e.g., 30s), transition to HALF-OPEN

**HALF-OPEN (Recovery probe):**
- Let a single probe request through
- If success → transition to CLOSED (recovery confirmed)
- If failure → transition back to OPEN (still broken)

### Failure Detection
- **Count-based:** Trip if N consecutive failures (e.g., 5 failures → OPEN)
- **Rate-based:** Trip if failure rate > X% over a time window (e.g., 50% over 60s, with minimum 10 requests)
- **Timeout-based:** Slow responses count as failures (time out > 2s = failure)

### Fallback Strategies
When circuit is open, what does the calling service return?
1. **Cached value:** Return last known good response
2. **Default value:** Return empty/null with graceful degradation
3. **Error response:** Return descriptive error to client
4. **Alternative service:** Route to backup service or read-only mode

### Circuit Breaker Libraries
- **PHP:** None native — implement manually or use Guzzle with retry middleware
- **Java:** Resilience4j, Hystrix (deprecated)
- **Node.js:** opossum
- **Go:** gobreaker, sony/gobreaker
- **Infrastructure level:** Istio service mesh, AWS App Mesh (handles circuit breaking at network layer)

---

## 5. Code Example

```php
class CircuitBreaker {
    private const CLOSED    = 'CLOSED';
    private const OPEN      = 'OPEN';
    private const HALF_OPEN = 'HALF_OPEN';
    
    private string $serviceName;
    private int    $failureThreshold;  // e.g., 5 failures
    private int    $successThreshold;  // e.g., 2 successes to close
    private int    $timeout;           // seconds in OPEN state
    private Redis  $redis;
    
    public function call(callable $fn, callable $fallback = null): mixed {
        $state = $this->getState();
        
        if ($state === self::OPEN) {
            if ($this->shouldAttemptReset()) {
                $this->transitionTo(self::HALF_OPEN);
            } else {
                return $fallback ? $fallback() : throw new CircuitOpenException();
            }
        }
        
        try {
            $result = $fn();
            $this->onSuccess();
            return $result;
        } catch (Throwable $e) {
            $this->onFailure();
            if ($fallback) return $fallback();
            throw $e;
        }
    }
    
    private function onSuccess(): void {
        $state = $this->getState();
        if ($state === self::HALF_OPEN) {
            $successes = $this->redis->incr("cb:{$this->serviceName}:successes");
            if ($successes >= $this->successThreshold) {
                $this->transitionTo(self::CLOSED);
            }
        } elseif ($state === self::CLOSED) {
            $this->redis->del("cb:{$this->serviceName}:failures");
        }
    }
    
    private function onFailure(): void {
        $failures = $this->redis->incr("cb:{$this->serviceName}:failures");
        $this->redis->expire("cb:{$this->serviceName}:failures", 60);
        
        if ($failures >= $this->failureThreshold) {
            $this->transitionTo(self::OPEN);
        }
    }
    
    private function shouldAttemptReset(): bool {
        $openedAt = $this->redis->get("cb:{$this->serviceName}:opened_at");
        return $openedAt && (time() - (int)$openedAt) >= $this->timeout;
    }
    
    private function transitionTo(string $state): void {
        $this->redis->set("cb:{$this->serviceName}:state", $state);
        if ($state === self::OPEN) {
            $this->redis->set("cb:{$this->serviceName}:opened_at", time());
        }
        Log::warning("CircuitBreaker [{$this->serviceName}]: $state");
    }
}

// Usage
$cb = new CircuitBreaker('payment-service', failureThreshold: 5, timeout: 30);

$result = $cb->call(
    fn() => $this->paymentClient->charge($amount),
    fn() => ['status' => 'queued', 'message' => 'Payment will be processed shortly']
);
```

---

## 6. Trade-offs

| Aspect | Without Circuit Breaker | With Circuit Breaker |
|--------|------------------------|---------------------|
| Cascading failure | High risk | Prevented |
| Resource usage | Threads pile up | Released fast |
| User experience | Hanging requests | Immediate error/fallback |
| Recovery | Manual intervention | Automatic (HALF-OPEN probe) |
| Complexity | Low | Medium |

---

## 7. Interview Q&A

**Q1: How does a circuit breaker prevent cascading failures?**
> Without a circuit breaker, if ServiceB is slow, ServiceA's thread pool fills up waiting for B. Eventually A runs out of threads and can't serve any requests — even ones that don't need B. With a circuit breaker, after N failures it trips OPEN and immediately returns errors for all calls to B — no threads held waiting. ServiceA's thread pool remains available for other requests. This isolates the failure to the B dependency rather than cascading to A.

**Q2: What is the difference between a circuit breaker and retry logic?**
> Retries are appropriate for transient failures (network blip, temporary overload). Circuit breakers are for sustained failures. Naively combining them creates a problem: if 10 services each retry 3 times against a failing service, you get 30 requests instead of 10 — making the failing service worse. Circuit breaker + retry: retry for transient errors, but once the circuit trips open, stop retrying until the service recovers.

**Q3: How do you monitor circuit breaker state in production?**
> Emit metrics on every state transition: `circuit_breaker_state{service="payment"}` gauge (0=closed, 1=half-open, 2=open). Alert on OPEN state. Track failure rate per service as a dashboard metric. Log every state transition with context (failure count, last error). Expose a `/health` endpoint that shows circuit state for each dependency.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ 3 states: CLOSED (normal) → OPEN (fail fast) → HALF-OPEN (probe)│
│ ✓ Prevents cascading failures by releasing threads immediately    │
│ ✓ Provide meaningful fallback (cached value, degraded response)   │
│ ✓ Rate-based tripping > count-based (handles variable traffic)    │
│ ✓ Monitor state transitions and alert on OPEN state              │
│ ✓ Service mesh (Istio) handles CB at infrastructure level         │
└────────────────────────────────────────────────────────────────────┘
```
