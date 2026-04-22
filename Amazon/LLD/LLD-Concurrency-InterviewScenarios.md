# LLD Concurrency — Interview Scenarios & Deep Dive

> Thread safety patterns, classic problems with full Java solutions, and 15 mock Amazon interview Q&A pairs.
> Study this file last — it answers every "what happens under load?" follow-up question.

---

## PART 1 — JAVA CONCURRENCY FUNDAMENTALS

---

### 1.1 Synchronization Primitives — Comparison Table

| Feature | `synchronized` | `ReentrantLock` | `ReadWriteLock` | `volatile` | `Atomic*` |
|---------|--------------|----------------|-----------------|-----------|-----------|
| **Ease of use** | ★★★★★ | ★★★ | ★★ | ★★★★★ | ★★★★ |
| **Fairness** | No | Optional (fair=true) | Optional | N/A | N/A |
| **Interruptible wait** | No | Yes (lockInterruptibly) | Yes | N/A | N/A |
| **Try-lock (no block)** | No | Yes (tryLock) | Yes | N/A | N/A |
| **Multiple conditions** | One (wait/notify) | Unlimited (newCondition) | Two built-in | N/A | N/A |
| **Read concurrency** | No (exclusive) | No (exclusive) | YES (concurrent reads) | Yes | Yes |
| **Visibility guarantee** | Yes | Yes | Yes | Yes | Yes |
| **Atomicity** | Yes (block) | Yes (block) | Yes (block) | No | Yes (single op) |
| **Best for** | Simple mutual exclusion | Complex locking scenarios | Read-heavy data | Single flag/counter | Counters, refs |

---

### 1.2 `volatile` — Deep Dive

**What it guarantees:**
1. **Visibility** — Writes to a `volatile` variable are immediately visible to all threads (no CPU cache — goes straight to main memory)
2. **Ordering** — Acts as a memory barrier; no instruction reordering across a volatile read/write

**What it does NOT guarantee:**
- **Atomicity** for compound operations like `i++` (read-modify-write is still 3 separate operations)

```java
// CORRECT USE OF VOLATILE
class ServiceStatus {
    private volatile boolean running = true;  // simple flag, one writer
    private volatile int version = 0;          // simple counter read by many threads

    public void stop() {
        running = false; // one write, visibility needed
    }

    public boolean isRunning() {
        return running; // read from main memory, not cached value
    }
}

// WRONG: volatile does not protect i++ (read-modify-write)
class BrokenCounter {
    private volatile int count = 0;
    public void increment() {
        count++; // NOT thread-safe! Three operations: read, add 1, write
    }
}

// CORRECT: use AtomicInteger for compound operations
class SafeCounter {
    private final AtomicInteger count = new AtomicInteger(0);
    public void increment() { count.incrementAndGet(); } // atomic
    public int get()        { return count.get(); }
}
```

---

### 1.3 `synchronized` vs `ReentrantLock` — When to Use Which

```java
// ── synchronized: simple, automatic unlock ───────────────────────────────────
class SimpleCache {
    private final Map<String, String> map = new HashMap<>();

    public synchronized String get(String key) { return map.get(key); }
    public synchronized void put(String key, String value) { map.put(key, value); }
}
// ✓ Use when: simple mutual exclusion, always lock/unlock as a pair

// ── ReentrantLock: when you need more control ─────────────────────────────────
class AdvancedCache {
    private final Map<String, String> map = new HashMap<>();
    private final ReentrantLock lock = new ReentrantLock(true); // fair ordering

    public String getOrCompute(String key, Supplier<String> loader) throws InterruptedException {
        // Try to acquire lock; give up after 500ms rather than blocking forever
        if (!lock.tryLock(500, TimeUnit.MILLISECONDS)) {
            throw new TimeoutException("Could not acquire lock");
        }
        try {
            if (!map.containsKey(key)) {
                map.put(key, loader.get());
            }
            return map.get(key);
        } finally {
            lock.unlock(); // MUST be in finally — always releases
        }
    }
}
// ✓ Use when: timeout needed, interruptible wait needed, fairness needed

// ── ReadWriteLock: read-heavy workloads ───────────────────────────────────────
class ReadHeavyCache {
    private final Map<String, String> map = new HashMap<>();
    private final ReadWriteLock rwLock = new ReentrantReadWriteLock();

    public String get(String key) {
        rwLock.readLock().lock();
        try {
            return map.get(key);
        } finally {
            rwLock.readLock().unlock();
        }
    }

    public void put(String key, String value) {
        rwLock.writeLock().lock();
        try {
            map.put(key, value);
        } finally {
            rwLock.writeLock().unlock();
        }
    }
}
// ✓ Use when: many concurrent readers, rare writes (e.g., config cache, product catalog)
// Throughput improvement: 10x if 95% operations are reads
```

---

### 1.4 `Atomic` Classes — When and How

```java
// ── AtomicInteger: counters, sequences ───────────────────────────────────────
class RequestCounter {
    private final AtomicInteger count = new AtomicInteger(0);
    private final AtomicInteger errors = new AtomicInteger(0);

    public void recordRequest() { count.incrementAndGet(); }
    public void recordError()   { errors.incrementAndGet(); }
    public double getErrorRate() {
        int total = count.get();
        return total == 0 ? 0 : (double) errors.get() / total;
    }
}

// ── AtomicLong: for larger counters (total bytes, total revenue) ──────────────
class RevenueTracker {
    private final AtomicLong totalRevenueCents = new AtomicLong(0);
    public void addRevenue(double amount) {
        totalRevenueCents.addAndGet((long)(amount * 100));
    }
}

// ── AtomicReference: atomic updates to object references ─────────────────────
class ConfigHolder {
    private final AtomicReference<Config> current = new AtomicReference<>(Config.defaultConfig());

    public void updateConfig(Config newConfig) {
        current.set(newConfig); // atomic reference swap
    }

    public Config getConfig() { return current.get(); } // always sees latest
}

// ── AtomicInteger CAS (compare-and-swap) pattern ─────────────────────────────
class OptimisticLockingCounter {
    private final AtomicInteger value = new AtomicInteger(0);

    // Increment only if current value equals expected
    public boolean incrementIfEquals(int expected) {
        return value.compareAndSet(expected, expected + 1);
        // Returns true if swap succeeded, false if another thread modified it
    }

    // Safe conditional update (retry loop)
    public void incrementByAmountSafely(int delta) {
        int current;
        do {
            current = value.get();
        } while (!value.compareAndSet(current, current + delta));
        // Retries until our CAS wins the race
    }
}
```

---

## PART 2 — CLASSIC CONCURRENCY PROBLEMS WITH SOLUTIONS

---

### 2.1 Producer-Consumer (BlockingQueue)

```java
// ── The Problem ───────────────────────────────────────────────────────────────
// Producers generate work faster than consumers can handle it.
// Need: bounded buffer, consumers sleep when empty, producers sleep when full.

class OrderProcessingPipeline {
    private final BlockingQueue<Order> queue;
    private final ExecutorService producers;
    private final ExecutorService consumers;
    private volatile boolean running = true;

    OrderProcessingPipeline(int capacity, int numProducers, int numConsumers) {
        this.queue     = new ArrayBlockingQueue<>(capacity);
        this.producers = Executors.newFixedThreadPool(numProducers);
        this.consumers = Executors.newFixedThreadPool(numConsumers);
    }

    // ── Producer ───────────────────────────────────────────────────────────────
    class OrderProducer implements Runnable {
        private final String source;
        OrderProducer(String source) { this.source = source; }

        @Override
        public void run() {
            while (running) {
                Order order = fetchNextOrder(source);
                if (order == null) break;
                try {
                    // put() BLOCKS if queue is full (back-pressure!)
                    queue.put(order);
                    System.out.printf("Produced order %s (queue size: %d)%n",
                        order.id(), queue.size());
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                    break;
                }
            }
        }

        private Order fetchNextOrder(String source) {
            return new Order(UUID.randomUUID().toString(), "PENDING");
        }
    }

    // ── Consumer ───────────────────────────────────────────────────────────────
    class OrderConsumer implements Runnable {
        @Override
        public void run() {
            while (running || !queue.isEmpty()) {
                try {
                    // poll() with timeout — don't block forever when shutting down
                    Order order = queue.poll(100, TimeUnit.MILLISECONDS);
                    if (order != null) processOrder(order);
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                    break;
                }
            }
        }

        private void processOrder(Order order) {
            System.out.printf("Processing order %s%n", order.id());
            // Business logic here
        }
    }

    public void start(int numProd, int numCons) {
        for (int i = 0; i < numProd; i++) producers.submit(new OrderProducer("source-" + i));
        for (int i = 0; i < numCons; i++) consumers.submit(new OrderConsumer());
    }

    public void shutdown() throws InterruptedException {
        running = false;
        producers.shutdown();
        producers.awaitTermination(5, TimeUnit.SECONDS);
        consumers.shutdown();
        consumers.awaitTermination(5, TimeUnit.SECONDS);
    }
}

record Order(String id, String status) {}
```

---

### 2.2 Reader-Writer Problem

```java
// The Problem: Multiple readers OK simultaneously, but a writer needs exclusive access.

class UserSessionStore {
    private final Map<String, UserSession> sessions = new HashMap<>();
    private final ReadWriteLock lock = new ReentrantReadWriteLock();

    // Many threads can read simultaneously
    public UserSession getSession(String sessionId) {
        lock.readLock().lock();
        try {
            return sessions.get(sessionId);
        } finally {
            lock.readLock().unlock();
        }
    }

    public boolean sessionExists(String sessionId) {
        lock.readLock().lock();
        try {
            return sessions.containsKey(sessionId);
        } finally {
            lock.readLock().unlock();
        }
    }

    // Only one writer at a time, blocks ALL readers
    public void createSession(UserSession session) {
        lock.writeLock().lock();
        try {
            sessions.put(session.id(), session);
        } finally {
            lock.writeLock().unlock();
        }
    }

    public void invalidateSession(String sessionId) {
        lock.writeLock().lock();
        try {
            sessions.remove(sessionId);
        } finally {
            lock.writeLock().unlock();
        }
    }

    // Read-then-write (upgrade) — must acquire write lock, then re-check
    public boolean refreshSession(String sessionId, long newExpiry) {
        // First check without write lock
        lock.readLock().lock();
        boolean exists;
        try {
            exists = sessions.containsKey(sessionId);
        } finally {
            lock.readLock().unlock();
        }

        if (!exists) return false;

        // Now upgrade to write lock (cannot upgrade directly — release read first)
        lock.writeLock().lock();
        try {
            // Re-check after acquiring write lock (session may have been removed)
            UserSession session = sessions.get(sessionId);
            if (session == null) return false;
            sessions.put(sessionId, session.withExpiry(newExpiry));
            return true;
        } finally {
            lock.writeLock().unlock();
        }
    }
}

record UserSession(String id, String userId, long expiry) {
    UserSession withExpiry(long newExpiry) {
        return new UserSession(id, userId, newExpiry);
    }
}
```

---

### 2.3 Deadlock — Prevention Patterns

```java
// ── Deadlock Scenario ─────────────────────────────────────────────────────────
// Thread 1: lock(AccountA) → wants lock(AccountB)
// Thread 2: lock(AccountB) → wants lock(AccountA)
// → DEADLOCK

// ── Prevention 1: Lock Ordering (always acquire locks in same order) ──────────
class SafeBankTransfer {
    private final ReentrantLock lock = new ReentrantLock();

    public void transfer(BankAccount from, BankAccount to, double amount) {
        // Always lock lower ID first — prevents circular dependency
        BankAccount first  = from.getId().compareTo(to.getId()) < 0 ? from : to;
        BankAccount second = first == from ? to : from;

        first.getLock().lock();
        try {
            second.getLock().lock();
            try {
                if (from.getBalance() < amount)
                    throw new IllegalStateException("Insufficient funds");
                from.debit(amount);
                to.credit(amount);
            } finally {
                second.getLock().unlock();
            }
        } finally {
            first.getLock().unlock();
        }
    }
}

// ── Prevention 2: tryLock with timeout ───────────────────────────────────────
class TimeoutBankTransfer {
    public boolean transfer(BankAccount from, BankAccount to, double amount)
            throws InterruptedException {
        long deadline = System.currentTimeMillis() + 1000; // 1 second timeout

        while (System.currentTimeMillis() < deadline) {
            if (from.getLock().tryLock(50, TimeUnit.MILLISECONDS)) {
                try {
                    if (to.getLock().tryLock(50, TimeUnit.MILLISECONDS)) {
                        try {
                            from.debit(amount);
                            to.credit(amount);
                            return true; // success
                        } finally {
                            to.getLock().unlock();
                        }
                    }
                } finally {
                    from.getLock().unlock();
                }
            }
            // Random backoff before retry — reduces livelock
            Thread.sleep((long)(Math.random() * 10));
        }
        throw new DeadlineExceededException("Transfer timed out");
    }
}

class BankAccount {
    private final String id;
    private double balance;
    private final ReentrantLock lock = new ReentrantLock();

    BankAccount(String id, double balance) {
        this.id = id;
        this.balance = balance;
    }

    public void debit(double amount)  { balance -= amount; }
    public void credit(double amount) { balance += amount; }
    public double getBalance()        { return balance; }
    public String getId()             { return id; }
    public ReentrantLock getLock()    { return lock; }
}

class DeadlineExceededException extends RuntimeException {
    DeadlineExceededException(String msg) { super(msg); }
}
```

---

### 2.4 Thread-Safe Singleton Connection Pool

```java
// One of the most common Amazon interview follow-ups: "How do you manage DB connections?"

public class ConnectionPool {
    private static volatile ConnectionPool instance;
    private final BlockingQueue<Connection> pool;
    private final int maxSize;
    private final AtomicInteger activeConnections = new AtomicInteger(0);

    private ConnectionPool(int maxSize) {
        this.maxSize = maxSize;
        this.pool    = new ArrayBlockingQueue<>(maxSize);
        // Pre-warm the pool
        for (int i = 0; i < maxSize; i++) {
            pool.offer(createConnection());
        }
    }

    public static ConnectionPool getInstance(int maxSize) {
        if (instance == null) {
            synchronized (ConnectionPool.class) {
                if (instance == null) {
                    instance = new ConnectionPool(maxSize);
                }
            }
        }
        return instance;
    }

    // ── Borrow a connection ───────────────────────────────────────────────────
    public Connection acquire(long timeoutMs) throws Exception {
        Connection conn = pool.poll(timeoutMs, TimeUnit.MILLISECONDS);
        if (conn == null) throw new Exception("Connection pool exhausted after " + timeoutMs + "ms");

        if (!isValid(conn)) {
            conn = createConnection(); // replace stale connection
        }
        activeConnections.incrementAndGet();
        return conn;
    }

    // ── Return a connection ───────────────────────────────────────────────────
    public void release(Connection conn) {
        if (conn != null) {
            activeConnections.decrementAndGet();
            pool.offer(conn); // Return to pool (never blocks — pool can't be over-full)
        }
    }

    // ── Try-with-resources friendly wrapper ───────────────────────────────────
    public <T> T withConnection(long timeoutMs, java.util.function.Function<Connection, T> action) throws Exception {
        Connection conn = acquire(timeoutMs);
        try {
            return action.apply(conn);
        } finally {
            release(conn); // Always returns connection even on exception
        }
    }

    public int getActiveConnections() { return activeConnections.get(); }
    public int getIdleConnections()   { return pool.size(); }

    private Connection createConnection() { return new Connection("conn-" + UUID.randomUUID()); }
    private boolean isValid(Connection c) { return c != null; }
}

record Connection(String id) {}
```

---

## PART 3 — CONCURRENT INVENTORY (Preventing Overselling)

This is THE most common Amazon-specific concurrency question.

```java
// ── The Problem ───────────────────────────────────────────────────────────────
// 1000 users simultaneously try to buy the last 1 unit of a product.
// Only ONE should succeed. The other 999 should see "Out of Stock".

// ── Solution 1: synchronized block (single JVM) ───────────────────────────────
class SynchronizedInventory {
    private final ConcurrentHashMap<String, Integer> stock = new ConcurrentHashMap<>();

    public boolean reserve(String skuId, int quantity) {
        // ConcurrentHashMap.compute() is atomic per-key — no extra lock needed
        boolean[] success = { false };
        stock.compute(skuId, (key, current) -> {
            if (current != null && current >= quantity) {
                success[0] = true;
                return current - quantity;
            }
            return current; // unchanged
        });
        return success[0];
    }

    public void restock(String skuId, int quantity) {
        stock.merge(skuId, quantity, Integer::sum);
    }
}

// ── Solution 2: Optimistic Locking with version number ───────────────────────
class VersionedInventoryItem {
    private final String skuId;
    private volatile int quantity;
    private final AtomicInteger version = new AtomicInteger(0);

    VersionedInventoryItem(String skuId, int quantity) {
        this.skuId    = skuId;
        this.quantity = quantity;
    }

    public boolean reserve(int requested) {
        while (true) {
            int currentVersion  = version.get();
            int currentQuantity = quantity;

            if (currentQuantity < requested) return false; // out of stock

            // Only update if version hasn't changed (no other thread modified it)
            if (version.compareAndSet(currentVersion, currentVersion + 1)) {
                quantity -= requested;
                return true; // we won the CAS race
            }
            // Another thread modified it — retry with fresh values
        }
    }
}

// ── Solution 3: Database-level (most production systems) ─────────────────────
/*
SQL approach (prevents race condition even across multiple app servers):

UPDATE inventory 
SET quantity = quantity - :requested, version = version + 1
WHERE sku_id = :skuId 
  AND quantity >= :requested
  AND version = :expectedVersion;

If rows_affected == 0 → either out of stock or version conflict → retry or reject.
This is optimistic locking at the database level.
*/

// ── Solution 4: Redis DECR + Lua script (distributed) ────────────────────────
/*
REDIS APPROACH:
Lua script (runs atomically in Redis):
    local qty = tonumber(redis.call('GET', KEYS[1]))
    if qty and qty >= tonumber(ARGV[1]) then
        redis.call('DECRBY', KEYS[1], ARGV[1])
        return 1  -- success
    end
    return 0  -- failure

Guarantee: Redis processes Lua scripts atomically — no TOCTOU race.
Scales to millions of concurrent requests.
*/
```

---

## PART 4 — 15 MOCK AMAZON INTERVIEW Q&A SCENARIOS

---

### Q1: "1000 threads hit your LRU cache simultaneously. What happens?"

**Bad answer:** "It works because ConcurrentHashMap is thread-safe."

**Strong answer:**
> "In my implementation, `LinkedHashMap` in access-order mode mutates on every `get()` call — it moves the accessed entry to the tail. `LinkedHashMap` is not thread-safe at all. So I protect both reads and writes with a `WriteLock` from `ReentrantReadWriteLock`. Reads need write lock too (unusual but necessary) because `get()` mutates internal order.
>
> Under 1000 concurrent threads: requests queue behind the write lock. Latency increases linearly with contention. For production with extremely high read QPS, I would switch to Caffeine library which uses a ring buffer + periodic async ordering update — allows concurrent reads with O(1) amortized overhead.
>
> Alternative: segment the cache into 16 shards by `key.hashCode() % 16`. Each shard has its own lock — reduces contention 16x."

---

### Q2: "How do you prevent overselling when you have 1 item left and 10000 concurrent buy requests?"

**Strong answer:**
> "Three layers of defense:
> 1. **Application layer**: `ConcurrentHashMap.compute()` is atomic per-key — wraps check-and-decrement in a single atomic operation. Correct for single-instance, zero extra locking.
> 2. **Database layer**: `UPDATE inventory SET qty = qty - 1 WHERE sku_id = ? AND qty >= 1`. The WHERE clause is the guard. Only the first transaction to execute this wins. All others get `rows_affected = 0`.
> 3. **Redis layer** (for high QPS): Lua script `DECR` with check — atomic, 10x faster than DB for inventory checks. Reserve in Redis, async-sync to DB.
>
> I would use all three: Redis as fast check, DB as source of truth, app-level as defense-in-depth."

---

### Q3: "What's the difference between sleep() and wait()?"

| | `Thread.sleep(ms)` | `Object.wait()` |
|--|--|--|
| **Releases lock?** | NO | YES |
| **Woken by?** | Timer only | `notify()` / `notifyAll()` / timeout |
| **Call from?** | Anywhere | Synchronized block only |
| **Use case** | Delay execution | Coordination between threads |

**Code comparison:**
```java
// sleep() — holds the lock while sleeping (other threads cannot enter synchronized block)
synchronized void badWait() throws InterruptedException {
    Thread.sleep(1000); // lock held! other threads blocked
}

// wait() — releases the lock while waiting (other threads can proceed)
synchronized void goodWait() throws InterruptedException {
    while (!conditionMet()) {
        wait(); // releases lock; thread suspended; woken by notify()
    }
}
```

---

### Q4: "When would you use a CountDownLatch vs CyclicBarrier?"

**`CountDownLatch`**: One-time gate — wait for N events to happen, then proceed.
```java
// Example: Start processing only after all 3 services are ready
CountDownLatch readyLatch = new CountDownLatch(3);

// Thread 1: DatabaseService
databaseService.start();
readyLatch.countDown();  // signals "DB ready"

// Thread 2: CacheService
cacheService.start();
readyLatch.countDown();  // signals "Cache ready"

// Thread 3: MessageQueue
mqService.start();
readyLatch.countDown();  // signals "MQ ready"

// Main thread waits until all 3 are ready
readyLatch.await(30, TimeUnit.SECONDS);
startAcceptingRequests(); // safe to start now
```

**`CyclicBarrier`**: Reusable barrier — N threads synchronize at a meeting point, repeatedly.
```java
// Example: Batch processing — each worker processes a chunk, then all merge results
CyclicBarrier barrier = new CyclicBarrier(4, () -> {
    // This runs when all 4 reach the barrier
    mergeResults();
    System.out.println("Batch complete, starting next batch");
});

// Each of 4 worker threads:
for (int i = 0; i < 10; i++) {
    processChunk(i);
    barrier.await(); // Wait for all 4 workers to finish this batch before starting next
}
```

---

### Q5: "Your order service has a deadlock in production. How do you diagnose it?"

**Strong answer:**
> "1. **Thread dump**: `kill -3 <pid>` or `jstack <pid>`. Look for `BLOCKED` threads and `waiting to lock` patterns. JVM reports circular dependencies explicitly as `DEADLOCK detected`.
> 2. **VisualVM / JConsole**: Live thread dump with deadlock detection button.
> 3. **Root cause analysis**: Identify the lock acquisition order. If Thread A holds lock X → wants Y, and Thread B holds lock Y → wants X, that's your deadlock.
> 4. **Fix**: Enforce consistent lock ordering (always acquire by ID), or replace with `tryLock(timeout)` which breaks deadlock by backing off."

---

### Q6: "How do you design a thread-safe in-memory queue that supports priority?"

```java
// PriorityBlockingQueue: thread-safe, unbounded, elements ordered by natural order or Comparator
class PriorityTaskQueue {
    private final PriorityBlockingQueue<Task> queue = new PriorityBlockingQueue<>(
        100,
        Comparator.comparingInt(Task::getPriority).reversed() // higher priority first
    );

    public void submit(Task task) { queue.offer(task); }

    public Task poll() throws InterruptedException {
        return queue.take(); // blocks if empty
    }

    public int size() { return queue.size(); }
}

class Task implements Comparable<Task> {
    private final String id;
    private final int priority; // higher = more important
    private final Runnable work;

    Task(String id, int priority, Runnable work) {
        this.id = id; this.priority = priority; this.work = work;
    }

    public int getPriority() { return priority; }
    public void execute()    { work.run(); }

    @Override
    public int compareTo(Task other) {
        return Integer.compare(other.priority, this.priority); // descending
    }
}
```

---

### Q7: "What is ThreadLocal and when would you use it?"

```java
// ThreadLocal: each thread has its own isolated copy of the variable — no sharing, no locks needed

class RequestContext {
    // Each thread (each request handler) gets its own RequestContext
    private static final ThreadLocal<RequestContext> CONTEXT = new ThreadLocal<>();

    private String requestId;
    private String userId;
    private long startTimeMs;

    public static void set(String requestId, String userId) {
        RequestContext ctx = new RequestContext();
        ctx.requestId   = requestId;
        ctx.userId      = userId;
        ctx.startTimeMs = System.currentTimeMillis();
        CONTEXT.set(ctx);
    }

    public static RequestContext get() { return CONTEXT.get(); }

    // CRITICAL: Always clear in finally block to prevent memory leaks in thread pools
    public static void clear() { CONTEXT.remove(); }
}

// Usage in servlet filter / Spring interceptor:
void handleRequest(HttpRequest req) {
    RequestContext.set(req.getHeader("X-Request-Id"), req.getUserId());
    try {
        processRequest(req);
    } finally {
        RequestContext.clear(); // MUST clear — thread returns to pool, next request reuses it
    }
}
```

**Amazon interview follow-up:** "What's the risk with ThreadLocal in thread pools?"
> "Thread pool threads are reused. If you don't call `ThreadLocal.remove()` in a `finally` block, the next request running on the same thread will inherit the previous request's context. This is a security/correctness bug AND a memory leak."

---

### Q8: "1 million tasks arrive in 1 hour. How do you size your thread pool?"

**Formula: Thread pool size = N_cpu × (1 + Wait_time / Compute_time)**

```java
// For CPU-bound tasks (image processing, encryption):
int cpuCores = Runtime.getRuntime().availableProcessors();
ExecutorService cpuPool = Executors.newFixedThreadPool(cpuCores);
// Reasoning: More threads than cores just causes context switching overhead

// For I/O-bound tasks (DB calls, HTTP calls — mostly waiting):
// If task is 90% waiting, 10% computing:
// threads = 8 cores × (1 + 9) = 80 threads
ExecutorService ioPool = Executors.newFixedThreadPool(cpuCores * 10);

// Production approach: Use queue with backpressure
ExecutorService boundedPool = new ThreadPoolExecutor(
    8,                              // core threads (always alive)
    32,                             // max threads (created under load)
    60L, TimeUnit.SECONDS,          // idle thread timeout
    new ArrayBlockingQueue<>(1000), // bounded queue (backpressure)
    new ThreadPoolExecutor.CallerRunsPolicy() // if queue full: calling thread runs task
);
```

---

### Q9: "What is a race condition? Give an example from an e-commerce system."

**Definition:** A race condition occurs when the correctness of a program depends on the relative ordering of thread executions — and that ordering is not controlled.

```java
// RACE CONDITION: Flash sale inventory check
class RacyInventory {
    private int stock = 1; // Last item!

    // BUG: Two threads can both read stock=1, both pass the check, both decrement
    // Result: stock = -1 (oversold!)
    public boolean buy(String userId) {
        if (stock > 0) {         // Thread A checks: stock=1 → pass
                                 // Thread B checks: stock=1 → pass (context switch between check and decrement!)
            stock--;             // Thread A decrements: stock=0
                                 // Thread B decrements: stock=-1 ← BUG
            System.out.println(userId + " bought last item");
            return true;
        }
        return false;
    }
}

// FIX: Make check-and-decrement atomic
class SafeInventory {
    private final AtomicInteger stock = new AtomicInteger(1);

    public boolean buy(String userId) {
        // CAS: only decrements if current value is > 0
        int current;
        do {
            current = stock.get();
            if (current <= 0) return false; // out of stock
        } while (!stock.compareAndSet(current, current - 1));
        // If CAS fails (another thread changed it), retry with fresh value
        System.out.println(userId + " bought last item");
        return true;
    }
}
```

---

### Q10: "How do you implement a thread-safe cache that handles concurrent misses efficiently?"

The "stampede" problem: 1000 threads all miss the cache simultaneously, all go to DB — overwhelming it.

```java
class StampedeProofCache<K, V> {
    private final ConcurrentHashMap<K, V> cache = new ConcurrentHashMap<>();
    // One lock per key — prevents multiple threads loading same key simultaneously
    private final ConcurrentHashMap<K, CompletableFuture<V>> inflight = new ConcurrentHashMap<>();
    private final java.util.function.Function<K, V> loader;

    StampedeProofCache(java.util.function.Function<K, V> loader) {
        this.loader = loader;
    }

    public V get(K key) throws Exception {
        V cached = cache.get(key);
        if (cached != null) return cached; // fast path: cache hit

        // Cache miss — ensure only ONE thread loads this key
        CompletableFuture<V> myFuture  = new CompletableFuture<>();
        CompletableFuture<V> existing  = inflight.putIfAbsent(key, myFuture);

        if (existing != null) {
            // Another thread is already loading this key — wait for its result
            return existing.get(); // blocks until the other thread's load completes
        }

        // We are the designated loader for this key
        try {
            V value = loader.apply(key); // call DB/API — only ONE thread does this per key
            cache.put(key, value);
            myFuture.complete(value);    // wake all waiting threads
            return value;
        } catch (Exception e) {
            myFuture.completeExceptionally(e);
            throw e;
        } finally {
            inflight.remove(key); // clear inflight entry after completion
        }
    }
}
```

---

### Q11: "What is a memory barrier and why does Java need them?"

**Strong answer:**
> "Modern CPUs and compilers reorder instructions for performance — a write to variable X might not be visible to another CPU core immediately because they have separate L1/L2 caches. A memory barrier is a CPU instruction that flushes/synchronises the caches.
>
> In Java, `volatile`, `synchronized`, and `java.util.concurrent.locks` all emit memory barriers automatically. Without them:
> - Thread 1 writes `initialized = true` but the CPU hasn't flushed its cache
> - Thread 2 reads `initialized == false` even though Thread 1 thinks it wrote true
>
> This is why the `volatile` keyword is critical in the DCL Singleton — without it, Thread 2 might see a partially constructed object even after the null check passes."

---

### Q12: "How do you test concurrent code for correctness?"

**Strong answer:**
> "Three approaches:
> 1. **Load test with latch**: Use `CountDownLatch` to release N threads simultaneously — maximises contention. Assert invariants (e.g., `stock >= 0`) after all threads complete.
> 2. **Model checker**: Java PathFinder, or property-based tests with `jqwik` — exhaustively explores thread interleavings
> 3. **Jcstress**: JVM concurrency stress testing framework — runs tests billions of times with different memory models
>
> Minimal test pattern:"
```java
@Test
void testConcurrentInventoryReserve() throws Exception {
    SafeInventory inv = new SafeInventory();
    inv.setStock("SKU-1", 100);
    
    int numThreads = 200;
    CountDownLatch startGate = new CountDownLatch(1);
    CountDownLatch finishGate = new CountDownLatch(numThreads);
    AtomicInteger successCount = new AtomicInteger(0);
    
    for (int i = 0; i < numThreads; i++) {
        new Thread(() -> {
            try {
                startGate.await(); // all threads wait at the gate
                if (inv.reserve("SKU-1", 1)) successCount.incrementAndGet();
            } catch (Exception e) { /* ignore */ }
            finally { finishGate.countDown(); }
        }).start();
    }
    
    startGate.countDown(); // release all threads simultaneously
    finishGate.await();    // wait for all to finish
    
    assertEquals(100, successCount.get()); // exactly 100 of 200 should succeed
    assertEquals(0, inv.getStock("SKU-1")); // no negative stock
}
```

---

### Q13: "What's the difference between `notify()` and `notifyAll()`?"

| | `notify()` | `notifyAll()` |
|--|--|--|
| **Wakes** | One random waiting thread | All waiting threads |
| **Efficiency** | Better (less context switching) | Worse (many threads wake, only one wins) |
| **Safety** | Only if ALL waiting threads wait for the same condition | When threads wait for different conditions |

**Rule of thumb:** Use `notifyAll()` unless you are absolutely certain all waiters are interchangeable.

```java
// SAFE to use notify(): all waiters are interchangeable (any one can proceed)
class WorkQueue {
    private final Queue<Runnable> tasks = new LinkedList<>();

    synchronized void addTask(Runnable task) {
        tasks.add(task);
        notify(); // wake one worker — any worker can handle any task
    }

    synchronized Runnable take() throws InterruptedException {
        while (tasks.isEmpty()) wait();
        return tasks.poll();
    }
}

// MUST use notifyAll(): different threads wait for different conditions
class FairSemaphore {
    private int permits;
    FairSemaphore(int permits) { this.permits = permits; }

    synchronized void acquire(int n) throws InterruptedException {
        while (permits < n) wait(); // different threads need different amounts
        permits -= n;
    }

    synchronized void release(int n) {
        permits += n;
        notifyAll(); // must wake ALL — a thread waiting for 3 permits won't help a thread needing 1
    }
}
```

---

### Q14: "Your API has a circuit breaker. How do you implement it with concurrency?"

```java
enum CircuitState { CLOSED, OPEN, HALF_OPEN }

class CircuitBreaker {
    private volatile CircuitState state = CircuitState.CLOSED;
    private final AtomicInteger failureCount    = new AtomicInteger(0);
    private volatile long lastFailureTime       = 0;

    private final int failureThreshold;      // trip after this many failures
    private final long recoverTimeMs;         // try again after this duration

    CircuitBreaker(int failureThreshold, long recoverTimeMs) {
        this.failureThreshold = failureThreshold;
        this.recoverTimeMs    = recoverTimeMs;
    }

    public <T> T call(Supplier<T> operation) throws Exception {
        if (state == CircuitState.OPEN) {
            if (System.currentTimeMillis() - lastFailureTime > recoverTimeMs) {
                state = CircuitState.HALF_OPEN; // try one probe request
            } else {
                throw new CircuitOpenException("Circuit is OPEN — not calling downstream");
            }
        }

        try {
            T result = operation.get();
            onSuccess();
            return result;
        } catch (Exception e) {
            onFailure();
            throw e;
        }
    }

    private synchronized void onSuccess() {
        failureCount.set(0);
        state = CircuitState.CLOSED;
    }

    private synchronized void onFailure() {
        lastFailureTime = System.currentTimeMillis();
        if (failureCount.incrementAndGet() >= failureThreshold) {
            state = CircuitState.OPEN;
            System.out.println("Circuit TRIPPED after " + failureThreshold + " failures");
        }
    }

    public CircuitState getState() { return state; }
}

class CircuitOpenException extends RuntimeException {
    CircuitOpenException(String msg) { super(msg); }
}
```

---

### Q15: "How does `ConcurrentHashMap` differ from `HashMap` and `Hashtable`?"

| | `HashMap` | `Hashtable` | `ConcurrentHashMap` |
|--|--|--|--|
| **Thread-safe** | No | Yes (full sync) | Yes (segment-level) |
| **Null keys** | One null key | Not allowed | Not allowed |
| **Lock granularity** | None | Entire map | Per-bucket (Java 8: CAS) |
| **Read performance** | Fastest | Slow (always locks) | Fast (reads rarely block) |
| **Write performance** | Fastest | Slow | Good (fine-grained) |
| **Use case** | Single-thread | Legacy code | Multi-threaded production |

**Deep dive question:** "How does ConcurrentHashMap achieve O(1) concurrent reads?"
> "Java 8+ `ConcurrentHashMap` uses a lock-free read path. The table array is `volatile`, so reads always see the latest state. Writes use CAS for empty buckets and `synchronized` on the bucket's head node only — other buckets are completely unaffected. Reads check the `volatile` array and traverse the linked list / tree without any locking."

---

## PART 5 — CONCURRENCY INTERVIEW CHEAT SHEET

```
SCENARIO → TOOL

Counter incremented by multiple threads?
→ AtomicInteger.incrementAndGet()

Flag read by many threads, written by one?
→ volatile boolean

Cache with mostly reads, rare writes?
→ ReadWriteLock (many readers concurrent, exclusive writer)

Need to prevent duplicate processing?
→ ConcurrentHashMap.putIfAbsent() or compute()

Multiple threads must all reach a point before continuing?
→ CyclicBarrier (reusable) or CountDownLatch (one-time)

Thread-local state (request ID, user context)?
→ ThreadLocal (MUST remove() in finally)

Bounded queue with backpressure?
→ ArrayBlockingQueue (blocks producer when full)

Priority processing?
→ PriorityBlockingQueue

Need to try acquiring a lock without blocking forever?
→ ReentrantLock.tryLock(timeout)

Preventing deadlock?
→ Lock ordering by ID + tryLock with timeout + random backoff

Overselling prevention?
→ ConcurrentHashMap.compute() for in-memory
→ SQL: UPDATE ... WHERE qty >= requested (DB enforces)
→ Redis Lua script for distributed

Connection pool?
→ ArrayBlockingQueue<Connection> with fixed size

Singleton?
→ volatile + DCL or Enum singleton

Fire-and-forget async tasks?
→ CompletableFuture.runAsync(task, executor)

Multiple async tasks, wait for all?
→ CompletableFuture.allOf(f1, f2, f3).join()

Cache stampede?
→ ConcurrentHashMap<K, CompletableFuture<V>> inflight map
```

---

*Companion files: [LLD-Design-Patterns-MasterRef.md](LLD-Design-Patterns-MasterRef.md) | [LLD-Advanced-Solutions.md](LLD-Advanced-Solutions.md) | [Amazon-LLD-Complete-Guide.md](Amazon-LLD-Complete-Guide.md)*
