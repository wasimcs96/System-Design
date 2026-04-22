# LLD Advanced Solutions — 5 Fully Coded Systems

> Complete class diagrams + full Java code + extensions for: LRU Cache, Rate Limiter, Elevator System, Splitwise, Notification System.
> Each solution is interview-ready — clean, compilable, narrated.

---

## HOW TO USE THIS FILE

1. **Attempt the problem first** — Cover the solution, set a 45-min timer, design from scratch
2. **Compare your solution** — Check classes, patterns, trade-offs
3. **Study the extension questions** — That is where Bar Raisers separate SDE-2 from SDE-3

---

## SOLUTION 1: LRU CACHE (with Thread Safety)

---

### Class Diagram
```
+---------------------------+
|      LRUCache<K,V>        |
+---------------------------+
| - capacity: int           |
| - cache: LinkedHashMap    |
| - lock: ReadWriteLock     |
+---------------------------+
| + get(key): V             |   → O(1)
| + put(key, val): void     |   → O(1)
| + remove(key): void       |   → O(1)
| + size(): int             |
| + clear(): void           |
+---------------------------+

Internal structure:
LinkedHashMap (access-ordered) maintains LRU order
removeEldestEntry() evicts LRU automatically when over capacity
ReadWriteLock: multiple concurrent reads, exclusive writes
```

---

### Full Code

```java
import java.util.*;
import java.util.concurrent.locks.*;

public class LRUCache<K, V> {
    private final int capacity;
    private final LinkedHashMap<K, V> cache;
    private final ReadWriteLock lock = new ReentrantReadWriteLock();
    private final Lock readLock      = lock.readLock();
    private final Lock writeLock     = lock.writeLock();

    // Metrics
    private long hits   = 0;
    private long misses = 0;

    public LRUCache(int capacity) {
        if (capacity <= 0) throw new IllegalArgumentException("Capacity must be > 0");
        this.capacity = capacity;
        // accessOrder=true: moves accessed entries to tail (most recent)
        this.cache = new LinkedHashMap<>(capacity, 0.75f, true) {
            @Override
            protected boolean removeEldestEntry(Map.Entry<K, V> eldest) {
                return size() > LRUCache.this.capacity;
            }
        };
    }

    // ── Get ──────────────────────────────────────────────────────────────────
    public V get(K key) {
        Objects.requireNonNull(key, "Key cannot be null");
        writeLock.lock(); // write lock needed: get() reorders the map in access-order mode
        try {
            V value = cache.get(key);
            if (value != null) hits++; else misses++;
            return value;
        } finally {
            writeLock.unlock();
        }
    }

    // ── Put ──────────────────────────────────────────────────────────────────
    public void put(K key, V value) {
        Objects.requireNonNull(key, "Key cannot be null");
        Objects.requireNonNull(value, "Value cannot be null");
        writeLock.lock();
        try {
            cache.put(key, value);
        } finally {
            writeLock.unlock();
        }
    }

    // ── Remove ───────────────────────────────────────────────────────────────
    public V remove(K key) {
        writeLock.lock();
        try {
            return cache.remove(key);
        } finally {
            writeLock.unlock();
        }
    }

    // ── Contains ─────────────────────────────────────────────────────────────
    public boolean containsKey(K key) {
        readLock.lock();
        try {
            return cache.containsKey(key);
        } finally {
            readLock.unlock();
        }
    }

    public int size() {
        readLock.lock();
        try { return cache.size(); }
        finally { readLock.unlock(); }
    }

    public void clear() {
        writeLock.lock();
        try { cache.clear(); }
        finally { writeLock.unlock(); }
    }

    // ── Metrics ───────────────────────────────────────────────────────────────
    public double getHitRate() {
        long total = hits + misses;
        return total == 0 ? 0 : (double) hits / total;
    }

    public String getStats() {
        return String.format("LRUCache[capacity=%d, size=%d, hits=%d, misses=%d, hitRate=%.2f%%]",
            capacity, size(), hits, misses, getHitRate() * 100);
    }
}
```

---

### LRU Cache with TTL (Extension)

```java
public class TTLLRUCache<K, V> {
    private final int capacity;
    private final long defaultTtlMs;
    private final Map<K, V> values    = new LinkedHashMap<>(16, 0.75f, true);
    private final Map<K, Long> expiry = new HashMap<>();
    private final ReadWriteLock lock  = new ReentrantReadWriteLock();

    public TTLLRUCache(int capacity, long defaultTtlMs) {
        this.capacity = capacity;
        this.defaultTtlMs = defaultTtlMs;
    }

    public V get(K key) {
        lock.writeLock().lock();
        try {
            if (!values.containsKey(key)) return null;

            Long expiresAt = expiry.get(key);
            if (expiresAt != null && System.currentTimeMillis() > expiresAt) {
                values.remove(key);
                expiry.remove(key);
                return null; // expired
            }

            return values.get(key); // updates access order
        } finally {
            lock.writeLock().unlock();
        }
    }

    public void put(K key, V value) {
        put(key, value, defaultTtlMs);
    }

    public void put(K key, V value, long ttlMs) {
        lock.writeLock().lock();
        try {
            if (values.size() >= capacity && !values.containsKey(key)) {
                // Evict LRU (first entry in access-order LinkedHashMap)
                K lruKey = values.keySet().iterator().next();
                values.remove(lruKey);
                expiry.remove(lruKey);
            }
            values.put(key, value);
            expiry.put(key, System.currentTimeMillis() + ttlMs);
        } finally {
            lock.writeLock().unlock();
        }
    }
}
```

---

### Amazon Extension Questions + Answers

**Q: "How would you make this distributed?"**
> Consistent hashing maps keys to specific Redis nodes. Each node runs its own LRU eviction. A client-side library handles routing. For hot key problem: replicate hot keys to multiple nodes; client randomly selects one for reads.

**Q: "How would you handle cache stampede (thundering herd)?"**
> Use mutex per key on cache miss. Only one thread fetches from DB; others wait for the result. Redis has `SET NX PX` for this. Alternative: probabilistic early expiration — refresh cache slightly before TTL to avoid simultaneous misses.

**Q: "What if you need LFU (Least Frequently Used) instead of LRU?"**
> LFU needs a frequency counter per key. Use `TreeMap<frequency, LinkedHashSet<key>>` for O(log n) min-frequency lookup. More complex, worse for bursty access patterns. LRU is better default.

---

---

## SOLUTION 2: TOKEN BUCKET RATE LIMITER

---

### Class Diagram
```
+---------------------------+          +---------------------------+
|     RateLimiter           |          |   RateLimiterConfig       |
+---------------------------+          +---------------------------+
| (interface)               |          | - maxTokens: int          |
| + allowRequest(clientId)  |          | - refillRatePerSec: double|
+---------------------------+          +---------------------------+
        ▲
        |
+---------------------------+          +---------------------------+
|  TokenBucketRateLimiter   |◆-------->|    TokenBucket            |
+---------------------------+          +---------------------------+
| - buckets: ConcurrentMap  |          | - tokens: double          |
| - config: Config          |          | - lastRefillTime: long    |
| - lock: per-bucket        |          | - maxTokens: int          |
+---------------------------+          +---------------------------+
| + allowRequest(clientId)  |          | + tryConsume(): boolean   |
| + getRemainingTokens()    |          | + refill()                |
+---------------------------+          +---------------------------+

+---------------------------+
|  SlidingWindowRateLimiter |
+---------------------------+
| - requests: Map<id,Deque> |
| + allowRequest(clientId)  |
+---------------------------+
```

---

### Full Code

```java
import java.util.*;
import java.util.concurrent.*;
import java.util.concurrent.atomic.*;

// ── Rate Limiter Interface ────────────────────────────────────────────────────
interface RateLimiter {
    boolean allowRequest(String clientId);
    int getRemainingTokens(String clientId);
    RateLimitResult tryAcquire(String clientId);
}

// ── Result Object ─────────────────────────────────────────────────────────────
record RateLimitResult(boolean allowed, int remainingTokens, long retryAfterMs) {
    public static RateLimitResult allowed(int remaining) {
        return new RateLimitResult(true, remaining, 0);
    }
    public static RateLimitResult rejected(long retryAfterMs) {
        return new RateLimitResult(false, 0, retryAfterMs);
    }
}

// ── Token Bucket ──────────────────────────────────────────────────────────────
class TokenBucket {
    private double tokens;
    private long lastRefillTimeNanos;
    private final int maxTokens;
    private final double refillRatePerNano; // tokens per nanosecond

    TokenBucket(int maxTokens, double refillRatePerSecond) {
        this.maxTokens           = maxTokens;
        this.tokens              = maxTokens;  // start full
        this.lastRefillTimeNanos = System.nanoTime();
        this.refillRatePerNano   = refillRatePerSecond / 1_000_000_000.0;
    }

    synchronized boolean tryConsume(int requested) {
        refill();
        if (tokens >= requested) {
            tokens -= requested;
            return true;
        }
        return false;
    }

    synchronized int getAvailableTokens() {
        refill();
        return (int) Math.floor(tokens);
    }

    synchronized long nanosUntilNextToken() {
        refill();
        if (tokens >= 1) return 0;
        double tokensNeeded = 1 - tokens;
        return (long)(tokensNeeded / refillRatePerNano);
    }

    private void refill() {
        long now   = System.nanoTime();
        double add = (now - lastRefillTimeNanos) * refillRatePerNano;
        tokens     = Math.min(maxTokens, tokens + add);
        lastRefillTimeNanos = now;
    }
}

// ── Token Bucket Rate Limiter ─────────────────────────────────────────────────
public class TokenBucketRateLimiter implements RateLimiter {
    private final ConcurrentHashMap<String, TokenBucket> buckets = new ConcurrentHashMap<>();
    private final int maxTokens;
    private final double refillRatePerSecond;

    // Constructor: e.g., 100 max burst, refill 10 tokens/sec
    public TokenBucketRateLimiter(int maxTokens, double refillRatePerSecond) {
        this.maxTokens           = maxTokens;
        this.refillRatePerSecond = refillRatePerSecond;
    }

    @Override
    public boolean allowRequest(String clientId) {
        return tryAcquire(clientId).allowed();
    }

    @Override
    public RateLimitResult tryAcquire(String clientId) {
        TokenBucket bucket = buckets.computeIfAbsent(
            clientId,
            id -> new TokenBucket(maxTokens, refillRatePerSecond)
        );

        boolean allowed = bucket.tryConsume(1);
        int remaining   = bucket.getAvailableTokens();

        if (allowed) {
            return RateLimitResult.allowed(remaining);
        } else {
            long retryAfterMs = bucket.nanosUntilNextToken() / 1_000_000;
            return RateLimitResult.rejected(retryAfterMs);
        }
    }

    @Override
    public int getRemainingTokens(String clientId) {
        TokenBucket bucket = buckets.get(clientId);
        return bucket == null ? maxTokens : bucket.getAvailableTokens();
    }
}

// ── Sliding Window Log Rate Limiter (accurate but memory-heavy) ───────────────
class SlidingWindowLogRateLimiter implements RateLimiter {
    private final ConcurrentHashMap<String, Deque<Long>> requestLogs = new ConcurrentHashMap<>();
    private final int maxRequests;
    private final long windowMs;

    SlidingWindowLogRateLimiter(int maxRequests, long windowMs) {
        this.maxRequests = maxRequests;
        this.windowMs    = windowMs;
    }

    @Override
    public boolean allowRequest(String clientId) {
        return tryAcquire(clientId).allowed();
    }

    @Override
    public synchronized RateLimitResult tryAcquire(String clientId) {
        long now = System.currentTimeMillis();
        Deque<Long> log = requestLogs.computeIfAbsent(clientId, k -> new ArrayDeque<>());

        // Remove timestamps outside the window
        while (!log.isEmpty() && (now - log.peekFirst()) > windowMs) {
            log.pollFirst();
        }

        if (log.size() < maxRequests) {
            log.addLast(now);
            return RateLimitResult.allowed(maxRequests - log.size());
        } else {
            long oldestInWindow = log.peekFirst();
            long retryAfterMs   = windowMs - (now - oldestInWindow);
            return RateLimitResult.rejected(retryAfterMs);
        }
    }

    @Override
    public int getRemainingTokens(String clientId) {
        long now = System.currentTimeMillis();
        Deque<Long> log = requestLogs.getOrDefault(clientId, new ArrayDeque<>());
        long recentCount = log.stream().filter(t -> (now - t) <= windowMs).count();
        return (int) Math.max(0, maxRequests - recentCount);
    }
}
```

---

### Amazon Extension Questions + Answers

**Q: "How do you make the rate limiter distributed (across multiple API servers)?"**
> Use Redis as shared state. Token bucket stored as Redis hash per clientId. Use Lua script for atomic check-and-decrement (Redis processes Lua atomically). Each API server calls Redis before processing. Latency: ~1ms Redis round trip — acceptable.

**Q: "How do you handle per-user vs per-IP vs per-API-key rate limiting?"**
> Rate limiter key = `{dimension}:{identifier}` — e.g., `user:usr_123` or `ip:192.168.1.1` or `apikey:key_abc`. Compose multiple dimensions: `RateLimiter.allowRequest("user:" + userId)` AND `RateLimiter.allowRequest("ip:" + ip)`.

**Q: "What if the rate limiter itself becomes a bottleneck?"**
> L1 local cache (approximate, per server) + L2 Redis (precise, shared). Local cache absorbs most traffic. Sync to Redis every 100ms. Slight over-counting is acceptable for most use cases.

---

---

## SOLUTION 3: ELEVATOR SYSTEM

---

### Class Diagram
```
+---------------------------+          +---------------------------+
|   ElevatorController     |◆-------->|   Elevator               |
+---------------------------+          +---------------------------+
| - elevators: List         |          | - id: int                |
| - scheduler: Scheduler    |          | - currentFloor: int      |
+---------------------------+          | - state: ElevatorState   |
| + requestElevator(floor,  |          | - direction: Direction   |
|     direction): void      |          | - destinationQueue: PQ   |
| + selectFloor(elevId,     |          +---------------------------+
|     floor): void          |          | + moveToFloor(floor)     |
+---------------------------+          | + openDoors()            |
        |                              | + addDestination(floor)  |
        ▼                              | + getOptimalScore(floor) |
+---------------------------+          +---------------------------+
|  ElevatorScheduler        |
+---------------------------+
| (interface)               |
| + selectElevator(request) |
+---------------------------+
        ▲
        |
+-----------------------+  +---------------------+
|  NearestElevatorSched |  |  LOOK Scheduler     |
+-----------------------+  +---------------------+
| Picks nearest idle /  |  | SCAN algorithm:     |
| same-direction car    |  | elevator sweeps     |
+-----------------------+  | like disk I/O       |
                           +---------------------+
```

---

### Full Code

```java
import java.util.*;
import java.util.concurrent.*;
import java.util.concurrent.atomic.*;

// ── Enums ─────────────────────────────────────────────────────────────────────
enum Direction     { UP, DOWN, IDLE }
enum ElevatorState { MOVING, DOORS_OPEN, IDLE, MAINTENANCE }

// ── External Request (button pressed in hallway) ───────────────────────────────
record ExternalRequest(int floor, Direction direction) {}

// ── Elevator (internal state machine) ────────────────────────────────────────
class Elevator {
    private final int id;
    private final int minFloor;
    private final int maxFloor;

    private int currentFloor = 1;
    private Direction direction = Direction.IDLE;
    private ElevatorState state = ElevatorState.IDLE;

    // PriorityQueue: ascending for UP trips, descending for DOWN trips
    private final TreeSet<Integer> upQueue   = new TreeSet<>();   // ascending
    private final TreeSet<Integer> downQueue = new TreeSet<>(Comparator.reverseOrder());

    Elevator(int id, int minFloor, int maxFloor) {
        this.id = id;
        this.minFloor = minFloor;
        this.maxFloor = maxFloor;
    }

    public synchronized void addDestination(int floor) {
        if (floor == currentFloor) {
            openDoors();
            return;
        }
        if (floor > currentFloor) upQueue.add(floor);
        else                      downQueue.add(floor);
    }

    public synchronized void step() {
        if (state == ElevatorState.MAINTENANCE) return;

        if (direction == Direction.UP || direction == Direction.IDLE) {
            if (!upQueue.isEmpty()) {
                int next = upQueue.first();
                move(next);
                if (currentFloor == next) {
                    upQueue.remove(next);
                    openDoors();
                    if (upQueue.isEmpty()) {
                        direction = downQueue.isEmpty() ? Direction.IDLE : Direction.DOWN;
                    }
                }
                return;
            }
        }

        if (direction == Direction.DOWN || direction == Direction.IDLE) {
            if (!downQueue.isEmpty()) {
                int next = downQueue.first();
                move(next);
                if (currentFloor == next) {
                    downQueue.remove(next);
                    openDoors();
                    if (downQueue.isEmpty()) {
                        direction = upQueue.isEmpty() ? Direction.IDLE : Direction.UP;
                    }
                }
            }
        }
    }

    private void move(int targetFloor) {
        state = ElevatorState.MOVING;
        if (targetFloor > currentFloor) {
            currentFloor++;
            direction = Direction.UP;
        } else if (targetFloor < currentFloor) {
            currentFloor--;
            direction = Direction.DOWN;
        }
        System.out.printf("  Elevator %d → Floor %d (going %s)%n", id, currentFloor, direction);
    }

    private void openDoors() {
        state = ElevatorState.DOORS_OPEN;
        System.out.printf("  Elevator %d: DOORS OPEN at floor %d%n", id, currentFloor);
        // In real system: wait for door close timer / sensor
        state = direction == Direction.IDLE ? ElevatorState.IDLE : ElevatorState.MOVING;
    }

    // ── Scoring for scheduler ──────────────────────────────────────────────────
    public int score(ExternalRequest req) {
        int distance = Math.abs(currentFloor - req.floor());

        if (state == ElevatorState.IDLE)
            return distance;  // idle elevator: just distance

        // Moving in same direction AND request is ahead → very good
        if (direction == Direction.UP && req.direction() == Direction.UP && req.floor() >= currentFloor)
            return distance;

        if (direction == Direction.DOWN && req.direction() == Direction.DOWN && req.floor() <= currentFloor)
            return distance;

        // Moving opposite or request behind → penalty
        return distance + 20;
    }

    // ── Maintenance mode ───────────────────────────────────────────────────────
    public void setMaintenance(boolean inMaintenance) {
        state = inMaintenance ? ElevatorState.MAINTENANCE : ElevatorState.IDLE;
    }

    public int getId()           { return id; }
    public int getCurrentFloor() { return currentFloor; }
    public Direction getDirection() { return direction; }
    public ElevatorState getState() { return state; }

    public boolean isAvailable() {
        return state != ElevatorState.MAINTENANCE;
    }
}

// ── Scheduler Interface ───────────────────────────────────────────────────────
interface ElevatorScheduler {
    Optional<Elevator> selectElevator(List<Elevator> elevators, ExternalRequest request);
}

// ── Nearest Scheduler ─────────────────────────────────────────────────────────
class NearestElevatorScheduler implements ElevatorScheduler {
    @Override
    public Optional<Elevator> selectElevator(List<Elevator> elevators, ExternalRequest req) {
        return elevators.stream()
            .filter(Elevator::isAvailable)
            .min(Comparator.comparingInt(e -> e.score(req)));
    }
}

// ── Elevator Controller ───────────────────────────────────────────────────────
public class ElevatorController {
    private final List<Elevator> elevators;
    private final ElevatorScheduler scheduler;
    private final ScheduledExecutorService ticker = Executors.newSingleThreadScheduledExecutor();

    public ElevatorController(int numElevators, int minFloor, int maxFloor) {
        this.elevators = new ArrayList<>();
        for (int i = 1; i <= numElevators; i++) {
            elevators.add(new Elevator(i, minFloor, maxFloor));
        }
        this.scheduler = new NearestElevatorScheduler();
        // Tick the simulation every 500ms
        ticker.scheduleAtFixedRate(this::tick, 0, 500, TimeUnit.MILLISECONDS);
    }

    // ── External request (hallway button) ─────────────────────────────────────
    public void requestElevator(int floor, Direction direction) {
        ExternalRequest req = new ExternalRequest(floor, direction);
        Optional<Elevator> selected = scheduler.selectElevator(elevators, req);
        selected.ifPresentOrElse(
            e -> {
                System.out.printf("Request floor=%d dir=%s → Assigned to Elevator %d%n",
                    floor, direction, e.getId());
                e.addDestination(floor);
            },
            () -> System.out.println("No elevator available — request queued")
        );
    }

    // ── Internal request (floor button inside elevator) ───────────────────────
    public void selectFloor(int elevatorId, int floor) {
        elevators.stream()
            .filter(e -> e.getId() == elevatorId)
            .findFirst()
            .ifPresent(e -> e.addDestination(floor));
    }

    public void setMaintenance(int elevatorId, boolean inMaintenance) {
        elevators.stream()
            .filter(e -> e.getId() == elevatorId)
            .findFirst()
            .ifPresent(e -> e.setMaintenance(inMaintenance));
    }

    private void tick() {
        elevators.forEach(Elevator::step);
    }

    public void shutdown() {
        ticker.shutdown();
    }
}
```

---

### Amazon Extension Questions + Answers

**Q: "How would you handle emergency / fire evacuation mode?"**
> New state `EMERGENCY` in `ElevatorState`. On emergency signal: clear all queues, set direction DOWN, destination = ground floor for all elevators. Disable external calls, only go to ground floor and stay there.

**Q: "How would you optimize for energy efficiency?"**
> Score function includes current load (fewer passengers → prefer for nearby requests) and penalize elevators that would need to reverse direction. Group nearby requests to same elevator via time-window batching.

---

---

## SOLUTION 4: SPLITWISE (Expense Sharing)

---

### Class Diagram
```
+------------------+     +------------------+     +------------------+
|    Group         |◆--->|    User          |◆--->|    Balance       |
+------------------+     +------------------+     +------------------+
| - id: String     |     | - id: String     |     | - fromUser: User |
| - name: String   |     | - name: String   |     | - toUser: User   |
| - members: List  |     | - email: String  |     | - amount: double |
| - expenses: List |     +------------------+     +------------------+
+------------------+
| + addExpense()   |
| + settleUp()     |
| + getBalances()  |
+------------------+
         |
         ▼
+------------------+
|    Expense       |
+------------------+
| - id: String     |
| - paidBy: User   |
| - amount: double |
| - description    |
| - splits: List   |
| - splitStrategy  |
+------------------+

Split Strategies:
+------------------+
| SplitStrategy    |<<interface>>
+------------------+
| + split(amount,  |
|   participants)  |
+------------------+
        ▲
  ______|______
 |      |      |
Equal Exact Percent
Split Split  Split
```

---

### Full Code

```java
import java.util.*;
import java.util.stream.*;

// ── Domain Objects ────────────────────────────────────────────────────────────
record User(String id, String name, String email) {
    public String getId() { return id; }
    public String getName() { return name; }
}

record Split(User user, double amount) {
    public User getUser()   { return user; }
    public double getAmount() { return amount; }
}

// ── Split Strategies (Strategy Pattern) ──────────────────────────────────────
interface SplitStrategy {
    List<Split> split(double totalAmount, List<User> participants, Map<String, Object> params);
    void validate(double totalAmount, List<User> participants, Map<String, Object> params);
}

class EqualSplitStrategy implements SplitStrategy {
    @Override
    public List<Split> split(double totalAmount, List<User> participants, Map<String, Object> params) {
        validate(totalAmount, participants, params);
        double perPerson = totalAmount / participants.size();
        // Round to 2 decimal places; add remainder to first person
        double rounded = Math.floor(perPerson * 100) / 100.0;
        double remainder = totalAmount - rounded * participants.size();

        List<Split> splits = new ArrayList<>();
        for (int i = 0; i < participants.size(); i++) {
            double amount = i == 0 ? rounded + remainder : rounded;
            splits.add(new Split(participants.get(i), amount));
        }
        return splits;
    }

    @Override
    public void validate(double totalAmount, List<User> participants, Map<String, Object> params) {
        if (participants.isEmpty()) throw new IllegalArgumentException("No participants");
    }
}

class ExactSplitStrategy implements SplitStrategy {
    @Override
    @SuppressWarnings("unchecked")
    public List<Split> split(double totalAmount, List<User> participants, Map<String, Object> params) {
        validate(totalAmount, participants, params);
        Map<String, Double> exactAmounts = (Map<String, Double>) params.get("exactAmounts");
        return participants.stream()
            .map(u -> new Split(u, exactAmounts.get(u.getId())))
            .collect(Collectors.toList());
    }

    @Override
    @SuppressWarnings("unchecked")
    public void validate(double totalAmount, List<User> participants, Map<String, Object> params) {
        Map<String, Double> exactAmounts = (Map<String, Double>) params.getOrDefault("exactAmounts", Map.of());
        double sum = exactAmounts.values().stream().mapToDouble(Double::doubleValue).sum();
        if (Math.abs(sum - totalAmount) > 0.01)
            throw new IllegalArgumentException(
                String.format("Exact amounts sum (%.2f) does not match total (%.2f)", sum, totalAmount));
    }
}

class PercentSplitStrategy implements SplitStrategy {
    @Override
    @SuppressWarnings("unchecked")
    public List<Split> split(double totalAmount, List<User> participants, Map<String, Object> params) {
        validate(totalAmount, participants, params);
        Map<String, Double> percentages = (Map<String, Double>) params.get("percentages");
        return participants.stream()
            .map(u -> new Split(u, totalAmount * percentages.get(u.getId()) / 100.0))
            .collect(Collectors.toList());
    }

    @Override
    @SuppressWarnings("unchecked")
    public void validate(double totalAmount, List<User> participants, Map<String, Object> params) {
        Map<String, Double> percentages = (Map<String, Double>) params.getOrDefault("percentages", Map.of());
        double total = percentages.values().stream().mapToDouble(Double::doubleValue).sum();
        if (Math.abs(total - 100.0) > 0.01)
            throw new IllegalArgumentException("Percentages must sum to 100, got: " + total);
    }
}

// ── Expense ───────────────────────────────────────────────────────────────────
class Expense {
    private final String id;
    private final User paidBy;
    private final double amount;
    private final String description;
    private final List<Split> splits;
    private final long createdAt;

    Expense(User paidBy, double amount, String description,
            List<User> participants, SplitStrategy strategy, Map<String, Object> params) {
        this.id          = UUID.randomUUID().toString();
        this.paidBy      = paidBy;
        this.amount      = amount;
        this.description = description;
        this.splits      = strategy.split(amount, participants, params);
        this.createdAt   = System.currentTimeMillis();
    }

    public User getPaidBy()         { return paidBy; }
    public double getAmount()       { return amount; }
    public List<Split> getSplits()  { return Collections.unmodifiableList(splits); }
    public String getId()           { return id; }
}

// ── Balance Ledger ────────────────────────────────────────────────────────────
class BalanceLedger {
    // net[A][B] = amount A owes B (positive) or B owes A (negative)
    private final Map<String, Map<String, Double>> net = new HashMap<>();

    public void recordExpense(Expense expense) {
        String payerId = expense.getPaidBy().getId();

        for (Split split : expense.getSplits()) {
            String userId = split.getUser().getId();
            if (userId.equals(payerId)) continue;

            // userId owes payerId amount split.getAmount()
            updateBalance(userId, payerId, split.getAmount());
        }
    }

    public void settleDebt(String fromId, String toId, double amount) {
        // fromId is paying toId — reduce their debt
        updateBalance(fromId, toId, -amount);
    }

    private void updateBalance(String debtorId, String creditorId, double delta) {
        net.computeIfAbsent(debtorId, k -> new HashMap<>()).merge(creditorId, delta, Double::sum);
        net.computeIfAbsent(creditorId, k -> new HashMap<>()).merge(debtorId, -delta, Double::sum);
    }

    public Map<String, Double> getBalancesFor(String userId) {
        return Collections.unmodifiableMap(net.getOrDefault(userId, Map.of()));
    }

    // ── Simplify Debts (minimize number of transactions) ─────────────────────
    public List<String> simplifyDebts(Map<String, User> users) {
        // Compute net balance per person: positive = owed money, negative = owes money
        Map<String, Double> netBalance = new HashMap<>();
        for (var outerEntry : net.entrySet()) {
            for (var innerEntry : outerEntry.getValue().entrySet()) {
                netBalance.merge(outerEntry.getKey(), -innerEntry.getValue(), Double::sum);
            }
        }

        // Separate creditors and debtors
        PriorityQueue<Map.Entry<String, Double>> creditors = new PriorityQueue<>(
            (a, b) -> Double.compare(b.getValue(), a.getValue()));
        PriorityQueue<Map.Entry<String, Double>> debtors = new PriorityQueue<>(
            Comparator.comparingDouble(Map.Entry::getValue));

        for (var entry : netBalance.entrySet()) {
            if (entry.getValue() > 0.01)       creditors.offer(entry);
            else if (entry.getValue() < -0.01) debtors.offer(entry);
        }

        List<String> transactions = new ArrayList<>();
        while (!creditors.isEmpty() && !debtors.isEmpty()) {
            var creditor = creditors.poll();
            var debtor   = debtors.poll();

            double amount = Math.min(creditor.getValue(), -debtor.getValue());
            String creditorName = users.get(creditor.getKey()).getName();
            String debtorName   = users.get(debtor.getKey()).getName();
            transactions.add(String.format("%s pays %s ₹%.2f", debtorName, creditorName, amount));

            creditor.setValue(creditor.getValue() - amount);
            debtor.setValue(debtor.getValue() + amount);

            if (creditor.getValue() > 0.01) creditors.offer(creditor);
            if (debtor.getValue() < -0.01)  debtors.offer(debtor);
        }
        return transactions;
    }
}

// ── Group ─────────────────────────────────────────────────────────────────────
public class Group {
    private final String id;
    private final String name;
    private final List<User> members = new ArrayList<>();
    private final List<Expense> expenses = new ArrayList<>();
    private final BalanceLedger ledger = new BalanceLedger();

    Group(String name) {
        this.id   = UUID.randomUUID().toString();
        this.name = name;
    }

    public void addMember(User user) { members.add(user); }

    public Expense addExpense(User paidBy, double amount, String description,
                              SplitStrategy strategy, Map<String, Object> params) {
        if (!members.contains(paidBy))
            throw new IllegalArgumentException("Payer must be a group member");

        Expense expense = new Expense(paidBy, amount, description, members, strategy, params);
        expenses.add(expense);
        ledger.recordExpense(expense);
        return expense;
    }

    public void settleUp(User from, User to, double amount) {
        ledger.settleDebt(from.getId(), to.getId(), amount);
        System.out.printf("Settlement: %s paid %s ₹%.2f%n", from.getName(), to.getName(), amount);
    }

    public void printBalances(User user) {
        System.out.println("Balances for " + user.getName() + ":");
        Map<String, User> userMap = members.stream().collect(Collectors.toMap(User::getId, u -> u));
        ledger.getBalancesFor(user.getId()).forEach((otherId, balance) -> {
            String otherName = userMap.getOrDefault(otherId, new User(otherId, "Unknown", "")).getName();
            if (balance > 0.01)       System.out.printf("  %s owes you ₹%.2f%n", otherName, balance);
            else if (balance < -0.01) System.out.printf("  You owe %s ₹%.2f%n", otherName, -balance);
        });
    }

    public void printSimplifiedDebts() {
        Map<String, User> userMap = members.stream().collect(Collectors.toMap(User::getId, u -> u));
        System.out.println("Simplified debts:");
        ledger.simplifyDebts(userMap).forEach(t -> System.out.println("  " + t));
    }
}
```

---

---

## SOLUTION 5: NOTIFICATION SYSTEM (Multi-channel with Retry)

---

### Class Diagram
```
+------------------+     +------------------+     +------------------+
|  NotificationSvc |---->|  NotificationReq |     |  UserPreference  |
+------------------+     +------------------+     +------------------+
| - channels: Map  |     | - userId: String |     | - email: boolean |
| - prefService    |     | - type: Type     |     | - sms: boolean   |
| - retryPolicy    |     | - title: String  |     | - push: boolean  |
+------------------+     | - body: String   |     | - marketing: bool|
| + send(request)  |     | - priority: int  |     +------------------+
| + sendBatch()    |     +------------------+
+------------------+
         |
    _____↓_____________________
   |            |              |
+------+    +------+    +----------+
|Email |    |  SMS |    |   Push   |
|Chan  |    | Chan |    |  Channel |
+------+    +------+    +----------+
```

---

### Full Code

```java
import java.util.*;
import java.util.concurrent.*;

// ── Notification Request ──────────────────────────────────────────────────────
enum NotificationType   { ORDER_UPDATE, PROMO, SYSTEM_ALERT, PAYMENT }
enum NotificationChannel { EMAIL, SMS, PUSH, IN_APP }
enum NotificationPriority { LOW, MEDIUM, HIGH, CRITICAL }

class NotificationRequest {
    private final String id;
    private final String userId;
    private final NotificationType type;
    private final NotificationPriority priority;
    private final String title;
    private final String body;
    private final Map<String, String> data;
    private final List<NotificationChannel> preferredChannels;

    NotificationRequest(String userId, NotificationType type, NotificationPriority priority,
                        String title, String body) {
        this.id                = UUID.randomUUID().toString();
        this.userId            = userId;
        this.type              = type;
        this.priority          = priority;
        this.title             = title;
        this.body              = body;
        this.data              = new HashMap<>();
        this.preferredChannels = new ArrayList<>();
    }

    // Getters
    public String getId()                         { return id; }
    public String getUserId()                     { return userId; }
    public NotificationType getType()             { return type; }
    public NotificationPriority getPriority()     { return priority; }
    public String getTitle()                      { return title; }
    public String getBody()                       { return body; }
    public Map<String, String> getData()          { return data; }
}

// ── User Preferences ──────────────────────────────────────────────────────────
class UserPreference {
    private final String userId;
    private boolean emailEnabled  = true;
    private boolean smsEnabled    = true;
    private boolean pushEnabled   = true;
    private boolean marketingEnabled = true;

    UserPreference(String userId) { this.userId = userId; }

    public boolean isChannelEnabled(NotificationChannel channel) {
        return switch (channel) {
            case EMAIL  -> emailEnabled;
            case SMS    -> smsEnabled;
            case PUSH   -> pushEnabled;
            case IN_APP -> true; // always enabled
        };
    }

    // Setters for preferences
    public void setEmailEnabled(boolean v)    { emailEnabled = v; }
    public void setSmsEnabled(boolean v)      { smsEnabled = v; }
    public void setPushEnabled(boolean v)     { pushEnabled = v; }
    public void setMarketingEnabled(boolean v){ marketingEnabled = v; }
    public boolean isMarketingEnabled()       { return marketingEnabled; }
}

// ── Delivery Result ───────────────────────────────────────────────────────────
enum DeliveryStatus { SENT, FAILED, SKIPPED_PREFERENCE, SKIPPED_OPT_OUT }

record DeliveryResult(NotificationChannel channel, DeliveryStatus status, String errorMessage) {
    public static DeliveryResult success(NotificationChannel ch) {
        return new DeliveryResult(ch, DeliveryStatus.SENT, null);
    }
    public static DeliveryResult failure(NotificationChannel ch, String error) {
        return new DeliveryResult(ch, DeliveryStatus.FAILED, error);
    }
    public static DeliveryResult skipped(NotificationChannel ch) {
        return new DeliveryResult(ch, DeliveryStatus.SKIPPED_PREFERENCE, null);
    }
}

// ── Channel Interface ─────────────────────────────────────────────────────────
interface NotificationChannel_ {
    NotificationChannel getChannelType();
    DeliveryResult deliver(String userId, NotificationRequest request);
    boolean isAvailable();
}

class EmailChannel implements NotificationChannel_ {
    private final String smtpHost;

    EmailChannel(String smtpHost) { this.smtpHost = smtpHost; }

    public NotificationChannel getChannelType() { return NotificationChannel.EMAIL; }

    public DeliveryResult deliver(String userId, NotificationRequest request) {
        try {
            // In real impl: send via AWS SES, SendGrid, etc.
            System.out.printf("[EMAIL] → %s: %s%n", userId, request.getTitle());
            return DeliveryResult.success(NotificationChannel.EMAIL);
        } catch (Exception e) {
            return DeliveryResult.failure(NotificationChannel.EMAIL, e.getMessage());
        }
    }

    public boolean isAvailable() { return true; }
}

class SmsChannel implements NotificationChannel_ {
    public NotificationChannel getChannelType() { return NotificationChannel.SMS; }

    public DeliveryResult deliver(String userId, NotificationRequest request) {
        try {
            // In real impl: Twilio, AWS SNS SMS, etc.
            System.out.printf("[SMS] → %s: %s%n", userId, request.getBody());
            return DeliveryResult.success(NotificationChannel.SMS);
        } catch (Exception e) {
            return DeliveryResult.failure(NotificationChannel.SMS, e.getMessage());
        }
    }

    public boolean isAvailable() { return true; }
}

class PushChannel implements NotificationChannel_ {
    public NotificationChannel getChannelType() { return NotificationChannel.PUSH; }

    public DeliveryResult deliver(String userId, NotificationRequest request) {
        try {
            // In real impl: APNS (iOS), FCM (Android)
            System.out.printf("[PUSH] → %s: %s%n", userId, request.getTitle());
            return DeliveryResult.success(NotificationChannel.PUSH);
        } catch (Exception e) {
            return DeliveryResult.failure(NotificationChannel.PUSH, e.getMessage());
        }
    }

    public boolean isAvailable() { return true; }
}

// ── Retry Policy (Strategy Pattern) ──────────────────────────────────────────
interface RetryPolicy {
    boolean shouldRetry(int attemptNumber, DeliveryResult lastResult);
    long getRetryDelayMs(int attemptNumber);
}

class ExponentialBackoffRetryPolicy implements RetryPolicy {
    private final int maxAttempts;
    private final long baseDelayMs;

    ExponentialBackoffRetryPolicy(int maxAttempts, long baseDelayMs) {
        this.maxAttempts = maxAttempts;
        this.baseDelayMs = baseDelayMs;
    }

    public boolean shouldRetry(int attempt, DeliveryResult last) {
        return attempt < maxAttempts && last.status() == DeliveryStatus.FAILED;
    }

    public long getRetryDelayMs(int attempt) {
        return (long)(Math.pow(2, attempt) * baseDelayMs);
    }
}

// ── Preference Service ─────────────────────────────────────────────────────────
class UserPreferenceService {
    private final Map<String, UserPreference> preferences = new ConcurrentHashMap<>();

    public UserPreference getPreference(String userId) {
        return preferences.computeIfAbsent(userId, UserPreference::new);
    }

    public void setPreference(String userId, UserPreference pref) {
        preferences.put(userId, pref);
    }
}

// ── Notification Service (Orchestrator) ──────────────────────────────────────
public class NotificationService {
    private final Map<NotificationChannel, NotificationChannel_> channels = new EnumMap<>(NotificationChannel.class);
    private final UserPreferenceService preferenceService;
    private final RetryPolicy retryPolicy;
    private final ExecutorService executor;

    // Default channels to try in order of priority
    private static final List<NotificationChannel> DEFAULT_CHANNEL_ORDER = List.of(
        NotificationChannel.PUSH,
        NotificationChannel.EMAIL,
        NotificationChannel.SMS
    );

    public NotificationService(UserPreferenceService prefs, RetryPolicy retry, int threadPoolSize) {
        this.preferenceService = prefs;
        this.retryPolicy       = retry;
        this.executor          = Executors.newFixedThreadPool(threadPoolSize);

        // Register channels
        registerChannel(new PushChannel());
        registerChannel(new EmailChannel("smtp.amazon.com"));
        registerChannel(new SmsChannel());
    }

    public void registerChannel(NotificationChannel_ channel) {
        channels.put(channel.getChannelType(), channel);
    }

    // ── Send to all enabled channels ──────────────────────────────────────────
    public CompletableFuture<Map<NotificationChannel, DeliveryResult>> send(NotificationRequest request) {
        return CompletableFuture.supplyAsync(() -> {
            UserPreference pref = preferenceService.getPreference(request.getUserId());
            Map<NotificationChannel, DeliveryResult> results = new EnumMap<>(NotificationChannel.class);

            // Skip marketing if user opted out
            if (request.getType() == NotificationType.PROMO && !pref.isMarketingEnabled()) {
                DEFAULT_CHANNEL_ORDER.forEach(ch ->
                    results.put(ch, new DeliveryResult(ch, DeliveryStatus.SKIPPED_OPT_OUT, "Marketing opt-out")));
                return results;
            }

            for (NotificationChannel channelType : DEFAULT_CHANNEL_ORDER) {
                if (!pref.isChannelEnabled(channelType)) {
                    results.put(channelType, DeliveryResult.skipped(channelType));
                    continue;
                }

                NotificationChannel_ channel = channels.get(channelType);
                if (channel == null || !channel.isAvailable()) continue;

                DeliveryResult result = sendWithRetry(channel, request);
                results.put(channelType, result);
            }
            return results;
        }, executor);
    }

    private DeliveryResult sendWithRetry(NotificationChannel_ channel, NotificationRequest request) {
        DeliveryResult result = DeliveryResult.failure(channel.getChannelType(), "Not attempted");
        int attempt = 0;

        do {
            if (attempt > 0) {
                try {
                    Thread.sleep(retryPolicy.getRetryDelayMs(attempt));
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                    break;
                }
            }
            result = channel.deliver(request.getUserId(), request);
            attempt++;
        } while (retryPolicy.shouldRetry(attempt, result));

        return result;
    }

    // ── Batch send ────────────────────────────────────────────────────────────
    public List<CompletableFuture<Map<NotificationChannel, DeliveryResult>>> sendBatch(
            List<NotificationRequest> requests) {
        return requests.stream()
            .map(this::send)
            .collect(java.util.stream.Collectors.toList());
    }

    public void shutdown() { executor.shutdown(); }
}
```

---

### Amazon Extension Questions + Answers

**Q: "How do you add priority queuing so CRITICAL notifications are never delayed by PROMO ones?"**
> Use separate thread pools (or `PriorityBlockingQueue`) per priority tier. `CRITICAL` → dedicated pool of 20 threads (always available). `PROMO` → shared pool of 5 threads. SLA: CRITICAL < 1s, PROMO < 5min.

**Q: "How do you prevent duplicate notifications?"**
> Idempotency key = `hash(userId + notificationType + referenceId)`. Store in Redis with 24h TTL: `SET NX notif_sent:{key}`. If already set → skip. On retry: same key → same result returned without re-sending.

**Q: "How do you handle user timezone for scheduled notifications?"**
> Store `scheduledAt` as UTC. Scheduler runs every minute: `SELECT WHERE scheduledAt <= NOW()`. Per-user timezone stored in preferences. Compute: `scheduledAt = userLocalTime + timezone_offset`.

---

*Companion files: [LLD-Design-Patterns-MasterRef.md](LLD-Design-Patterns-MasterRef.md) | [LLD-Concurrency-InterviewScenarios.md](LLD-Concurrency-InterviewScenarios.md)*
