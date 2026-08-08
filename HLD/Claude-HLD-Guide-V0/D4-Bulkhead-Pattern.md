# D4 — Bulkhead Pattern

> **Section:** Architecture Patterns | **Level:** Intermediate | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** A bulkhead is a wall in a ship that divides it into compartments. If one compartment floods, the others stay dry — the ship doesn't sink. In software, the bulkhead pattern isolates failures to one part of the system so the rest keeps working.

**Technical:** The Bulkhead pattern allocates separate, dedicated resource pools (thread pools, connection pools, process slots) for each dependent service. A failure or overload in one dependency can't exhaust resources needed by other dependencies. Prevents cascading failure through resource exhaustion.

---

## 2. Real-World Analogy

**Hospital Emergency Room triage:**
- Separate rooms for trauma, burns, pediatrics, general medicine
- If trauma room is overwhelmed with patients, burns patients still get treated in their room
- Without bulkheads: all patients in one queue → one emergency floods entire ER

**Ocean liner compartments:**
- Titanic sank because seawater entered multiple compartments
- Modern ships: if one compartment floods, watertight doors close → ship stays afloat

---

## 3. Visual Diagram

```
WITHOUT BULKHEAD (all services share one thread pool):
Thread Pool: [T1, T2, T3, T4, T5, T6, T7, T8] (8 threads)

Slow ServiceA hogs threads:
[T1→A, T2→A, T3→A, T4→A, T5→A, T6→A, T7→A, T8→A]
                              ↑
All threads busy waiting on slow ServiceA!
ServiceB requests: REJECTED — no threads available!
ServiceC requests: REJECTED — no threads available!
→ Cascading failure from ONE slow dependency

WITH BULKHEAD (separate thread pools):
ServiceA pool: [T1, T2, T3] — max 3 threads
ServiceB pool: [T4, T5]     — max 2 threads
ServiceC pool: [T6, T7, T8] — max 3 threads

ServiceA is slow → its 3 threads are full
ServiceA requests: REJECTED (fail fast — good!)
ServiceB/C unaffected — have their own pools
```

---

## 4. Deep Technical Explanation

### Types of Bulkhead

**1. Thread Pool Isolation (Hystrix-style):**
- Each dependency gets a dedicated thread pool
- App server thread: submits task to dependency-specific pool
- If dependency is slow → only its thread pool fills up → fail fast
- Other dependencies: unaffected

**2. Connection Pool Isolation:**
- Database connections are expensive (usually max 100-200 per DB)
- Shared pool: slow analytical queries can exhaust connections for fast transactional queries
- Bulkhead: separate pool for OLTP (max 50 connections) vs OLAP queries (max 10 connections)
- Even separate DB replicas: replica for analytics, master for writes

**3. Process/Container Isolation:**
- Run each service in separate container/VM
- A memory leak in one container doesn't affect others
- Kubernetes resource limits: `requests.memory: 256Mi, limits.memory: 512Mi` per pod

### Bulkhead + Circuit Breaker Combination
- **Circuit Breaker:** Stops calling a failing service (temporal isolation)
- **Bulkhead:** Limits resources a service can consume (resource isolation)
- Together: detect failure quickly (circuit breaker) AND prevent resource exhaustion (bulkhead)

| Pattern | Prevents | Mechanism |
|---------|---------|-----------|
| Circuit Breaker | Calling a failing service | Open circuit after N failures |
| Bulkhead | Resource exhaustion from slow service | Fixed pool per dependency |
| Timeout | Waiting too long | Hard deadline per call |
| Retry | Transient failures | Try again with backoff |

---

## 5. Code Example

```php
// Bulkhead: separate connection pools per dependency
class BulkheadConnectionManager {
    private array $pools = [];
    
    public function __construct() {
        // Separate pools for different dependencies
        $this->pools['primary_db'] = new ConnectionPool(
            factory: fn() => new PDO('mysql:host=db-primary;dbname=app', ...),
            maxConnections: 50,  // OLTP: many small, fast queries
        );
        
        $this->pools['analytics_db'] = new ConnectionPool(
            factory: fn() => new PDO('mysql:host=db-replica;dbname=app', ...),
            maxConnections: 10,  // OLAP: few large, slow queries
        );
        
        $this->pools['payment_service'] = new ConnectionPool(
            factory: fn() => new GuzzleHttp\Client(['base_uri' => 'https://payments.example.com']),
            maxConnections: 5,   // Payment API: rare, controlled
        );
        
        $this->pools['cache'] = new ConnectionPool(
            factory: fn() => new Redis(),
            maxConnections: 20,
        );
    }
    
    public function getConnection(string $dependency): mixed {
        if (!isset($this->pools[$dependency])) {
            throw new \InvalidArgumentException("Unknown dependency: {$dependency}");
        }
        
        $conn = $this->pools[$dependency]->acquire(timeout: 100); // 100ms max wait
        
        if ($conn === null) {
            throw new BulkheadException("Bulkhead full: {$dependency} pool exhausted");
        }
        
        return $conn;
    }
}

// Thread pool isolation (conceptual PHP — real implementation via process pool or queue workers)
class IsolatedServiceCaller {
    private array $semaphores;
    
    public function __construct() {
        // Semaphore = max concurrent calls per dependency
        $this->semaphores['payment']   = new \SplFixedArray(5);  // max 5 concurrent payment calls
        $this->semaphores['inventory'] = new \SplFixedArray(10); // max 10 concurrent
        $this->semaphores['email']     = new \SplFixedArray(3);  // max 3 concurrent
    }
    
    public function call(string $service, callable $fn): mixed {
        if (!$this->semaphores[$service]->tryAcquire()) {
            throw new BulkheadException("{$service} bulkhead full — fail fast");
        }
        
        try {
            return $fn();
        } finally {
            $this->semaphores[$service]->release();
        }
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you prevent a slow third-party API from bringing down your entire service?**
> Use bulkhead + circuit breaker + timeout. (1) Bulkhead: allocate a separate, small thread/connection pool for the third-party API — max 5 concurrent calls. If all 5 slots are busy, reject immediately (fail fast) rather than queuing indefinitely. (2) Timeout: max 2 seconds per API call. Never allow it to hold resources indefinitely. (3) Circuit breaker: if >50% of calls in last 60 seconds failed → open circuit, return cached/fallback response for 30 seconds. (4) Fallback: degrade gracefully — show cached data or "feature temporarily unavailable" instead of error page.

**Q2: In a monolith, can bulkheads still apply?**
> Yes. Separate database connection pools for different query types (OLTP vs OLAP). Separate thread pools for different request types (payment processing vs browsing). In Tomcat/PHP-FPM: separate process pools for different endpoint groups — "admin" pool and "customer-facing" pool. If admin queries are slow, the customer-facing pool is unaffected. In Laravel Horizon: separate queues with separate workers for critical vs background jobs.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Bulkhead = isolated resource pools per dependency               │
│ ✓ Prevents resource exhaustion from one slow/failing dependency   │
│ ✓ Thread pool isolation: each dependency has fixed thread budget  │
│ ✓ Connection pool isolation: OLTP vs OLAP separate pools          │
│ ✓ Combine with circuit breaker + timeout for full resilience      │
│ ✓ Fail fast when bulkhead full — better than slow queue buildup   │
└────────────────────────────────────────────────────────────────────┘
```
