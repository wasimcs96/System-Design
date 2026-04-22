# Amazon LLD Interview — Top 50 Questions
> Categorized by difficulty. For each question: difficulty, key concepts tested, and patterns used.

---

## CATEGORY A: BEGINNER (10 Questions)

These questions test fundamental OOP and basic class design. Expected to be solved cleanly in ~30 minutes.

---

### A1. Design a Parking Lot

**Difficulty:** Beginner
**Key Concepts Tested:**
- Object modeling (Vehicle, ParkingSpot, Ticket)
- Enums for vehicle type and spot status
- Single Responsibility Principle
- Basic state management

**Patterns Used:**
- Factory (create spots by type)
- Strategy (pricing calculation)

**Core Classes:**
`ParkingLot`, `ParkingFloor`, `ParkingSpot`, `Vehicle`, `Ticket`, `ParkingAttendant`

**What Amazon Checks:**
- Do you model `ParkingSpot` as a class or just an int?
- Do you handle multiple floors?
- How do you allocate spots efficiently?

---

### A2. Design a Library Management System

**Difficulty:** Beginner
**Key Concepts Tested:**
- Entity relationships (Book, Member, Loan)
- State machines (book availability)
- Basic search functionality

**Patterns Used:**
- Observer (notify when reserved book is available)
- Strategy (search by title/author/ISBN)

**Core Classes:**
`Library`, `Book`, `BookItem`, `Member`, `Loan`, `Catalog`, `SearchService`

---

### A3. Design a Hotel Room Booking System

**Difficulty:** Beginner
**Key Concepts Tested:**
- Date-range management
- Room availability checking
- Booking lifecycle

**Patterns Used:**
- Builder (Booking construction)
- Strategy (pricing: weekend, holiday)

**Core Classes:**
`Hotel`, `Room`, `Booking`, `Guest`, `PricingStrategy`, `BookingService`

---

### A4. Design a Vending Machine

**Difficulty:** Beginner
**Key Concepts Tested:**
- State machine (Idle, HasMoney, Dispensing, OutOfStock)
- Inventory management
- Change calculation

**Patterns Used:**
- State pattern (machine states)
- Command (button presses)

**Core Classes:**
`VendingMachine`, `Product`, `Inventory`, `CoinSlot`, `MachineState`

---

### A5. Design a Chess Game

**Difficulty:** Beginner–Intermediate
**Key Concepts Tested:**
- Board representation (8x8 grid)
- Piece hierarchy (abstract Piece → Rook, Bishop, etc.)
- Move validation

**Patterns Used:**
- Template Method (validate move → abstract; move implementation → concrete)
- Strategy (AI player vs human player)

**Core Classes:**
`Game`, `Board`, `Cell`, `Piece`, `Player`, `Move`, `MoveValidator`

---

### A6. Design an ATM Machine

**Difficulty:** Beginner
**Key Concepts Tested:**
- State machine (Idle, CardInserted, PINEntered, SelectingTransaction)
- Cash dispensing logic
- Transaction rollback

**Patterns Used:**
- State
- Command (Withdraw, Deposit, CheckBalance)

**Core Classes:**
`ATM`, `ATMState`, `Card`, `Account`, `Transaction`, `CashDispenser`

---

### A7. Design a Movie Ticket Booking System (like BookMyShow)

**Difficulty:** Beginner
**Key Concepts Tested:**
- Seat selection and locking
- Show schedule
- Payment integration

**Patterns Used:**
- Observer (notify if seats become available)
- Strategy (seat pricing: normal, premium)

**Core Classes:**
`Cinema`, `Screen`, `Show`, `Seat`, `Booking`, `Payment`, `Customer`

---

### A8. Design a Stack Overflow (simplified)

**Difficulty:** Beginner
**Key Concepts Tested:**
- Content hierarchy (Question → Answer → Comment)
- Voting system
- Tag-based search

**Patterns Used:**
- Observer (notify on new answer)
- Decorator (add badges to users)

**Core Classes:**
`User`, `Question`, `Answer`, `Comment`, `Tag`, `Vote`, `Reputation`

---

### A9. Design a Tic-Tac-Toe Game

**Difficulty:** Beginner
**Key Concepts Tested:**
- Board state management
- Win condition checking
- Player turns

**Patterns Used:**
- Strategy (human vs AI player)

**Core Classes:**
`Game`, `Board`, `Player`, `Move`, `WinChecker`

---

### A10. Design a Contact Manager

**Difficulty:** Beginner
**Key Concepts Tested:**
- CRUD operations on in-memory data
- Search and filtering
- Data modeling

**Patterns Used:**
- Repository pattern
- Builder (Contact construction)

**Core Classes:**
`Contact`, `ContactRepository`, `PhoneNumber`, `Email`, `SearchFilter`

---

## CATEGORY B: INTERMEDIATE (20 Questions)

These test design patterns, SOLID, and basic concurrency. Expect full coding in 45 minutes.

---

### B1. Design an LRU Cache

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Data structure choice (HashMap + Doubly Linked List)
- O(1) get and put
- Thread safety (ReadWriteLock)
- Eviction policy

**Patterns Used:**
- Singleton (cache instance)

**What Amazon Checks:**
- Can you implement without `LinkedHashMap`?
- How do you handle concurrent access?
- Can you make it generic?

**Core Classes:**
`LRUCache<K,V>`, `Node<K,V>`, `DoublyLinkedList<K,V>`

---

### B2. Design a Rate Limiter

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Algorithm choice: Token Bucket vs Sliding Window vs Fixed Window
- Per-user vs global limiting
- Time-based state

**Patterns Used:**
- Strategy (algorithm selection)
- Decorator (add rate limiting to any service)

**Algorithms to Know:**

| Algorithm | Pros | Cons |
|-----------|------|------|
| Fixed Window | Simple | Burst at boundary |
| Sliding Window Log | Accurate | High memory |
| Token Bucket | Handles bursts | Complex state |
| Leaky Bucket | Smooth output | Drops bursts |

**Core Classes:**
`RateLimiter`, `TokenBucketRateLimiter`, `SlidingWindowRateLimiter`, `RateLimitRule`

---

### B3. Design a Notification System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Multiple channels (Email, SMS, Push, Slack)
- User preferences
- Retry logic
- Priority queues

**Patterns Used:**
- Observer
- Factory (create channel handler)
- Decorator (add retry, logging)
- Chain of Responsibility (fallback channels)

**Core Classes:**
`NotificationService`, `NotificationChannel`, `Notification`, `UserPreferences`, `NotificationQueue`

---

### B4. Design a Task Scheduler

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Priority queue for scheduling
- Cron-like recurring tasks
- Thread pool management
- Task cancellation

**Patterns Used:**
- Command (task encapsulation)
- Observer (task completion events)

**Core Classes:**
`TaskScheduler`, `Task`, `RecurringTask`, `TaskExecutor`, `ScheduledTask`

```java
// Key: Use PriorityQueue ordered by next execution time
PriorityQueue<ScheduledTask> queue = new PriorityQueue<>(
    Comparator.comparingLong(ScheduledTask::getNextExecutionTime)
);
```

---

### B5. Design an Elevator System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Multiple elevator coordination
- Direction state (UP/DOWN/IDLE)
- Floor request handling
- Scheduling algorithm (SCAN/SSTF)

**Patterns Used:**
- State (elevator state machine)
- Strategy (scheduling algorithm)
- Observer (floor arrival notification)

**Core Classes:**
`ElevatorSystem`, `Elevator`, `ElevatorState`, `FloorRequest`, `ElevatorScheduler`

---

### B6. Design an In-Memory File System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Tree structure (Directory → File/Directory)
- Path resolution
- CRUD operations

**Patterns Used:**
- Composite (File and Directory both implement `FileSystemNode`)
- Iterator (directory traversal)

**Core Classes:**
`FileSystem`, `FileSystemNode`, `File`, `Directory`, `Path`

---

### B7. Design a Shopping Cart

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Cart item management
- Discount application
- Price calculation pipeline
- Inventory validation

**Patterns Used:**
- Strategy (discount types)
- Builder (Cart construction)
- Chain of Responsibility (price calculation pipeline)

**Core Classes:**
`Cart`, `CartItem`, `DiscountStrategy`, `PriceCalculator`, `InventoryValidator`

---

### B8. Design a URL Shortener

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Hash function selection
- Collision handling
- Expiration support
- Analytics tracking

**Patterns Used:**
- Strategy (hash algorithm)
- Decorator (add analytics)

**Core Classes:**
`UrlShortener`, `ShortenedUrl`, `HashGenerator`, `UrlRepository`, `AnalyticsTracker`

---

### B9. Design a Food Delivery System (like Swiggy)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Order lifecycle management
- Restaurant and menu modeling
- Delivery assignment

**Patterns Used:**
- State (order state machine)
- Observer (order status updates to customer)
- Strategy (delivery partner assignment)

**Core Classes:**
`Order`, `OrderStatus`, `Restaurant`, `Menu`, `DeliveryPartner`, `DeliveryService`

---

### B10. Design a Text Editor with Undo/Redo

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Command pattern for undo/redo
- History stack management
- Text operations

**Patterns Used:**
- Command (Insert, Delete, Replace operations)
- Memento (alternative approach — save snapshots)

**Core Classes:**
`TextEditor`, `EditorCommand`, `InsertCommand`, `DeleteCommand`, `CommandHistory`

---

### B11. Design a Ride-Sharing System (like Uber)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Driver and rider matching
- Trip lifecycle
- Location tracking
- Surge pricing

**Patterns Used:**
- Strategy (matching algorithm, pricing)
- State (trip state machine)
- Observer (location updates)

**Core Classes:**
`RideService`, `Ride`, `Driver`, `Rider`, `MatchingService`, `PricingStrategy`

---

### B12. Design a Logging Framework

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Log levels (DEBUG, INFO, WARN, ERROR)
- Multiple appenders (Console, File, Remote)
- Log formatting
- Thread safety

**Patterns Used:**
- Singleton (Logger)
- Decorator (add timestamp, thread name)
- Chain of Responsibility (level filtering)
- Observer (alert on ERROR)

**Core Classes:**
`Logger`, `LogLevel`, `Appender`, `ConsoleAppender`, `FileAppender`, `LogFormatter`

---

### B13. Design a Cache with TTL (Time-To-Live)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- TTL expiration on get/background cleanup
- LRU + TTL combined
- Thread safety

**Patterns Used:**
- Decorator (add TTL to existing LRU cache)

```java
// On get: check if entry is expired
public V get(K key) {
    CacheEntry<V> entry = store.get(key);
    if (entry == null || entry.isExpired()) {
        store.remove(key);
        return null;
    }
    return entry.getValue();
}
```

---

### B14. Design an Event Bus (Pub-Sub System)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Topic-based subscription
- Synchronous vs async delivery
- Subscriber lifecycle

**Patterns Used:**
- Observer (core pattern)
- Command (event encapsulation)

**Core Classes:**
`EventBus`, `Event`, `EventSubscriber`, `EventPublisher`, `Topic`

---

### B15. Design a Conference Room Booking System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Time slot conflict detection
- Room features/capacity filtering
- Booking cancellation and rescheduling

**Patterns Used:**
- Strategy (room selection)
- Observer (notify participants)

---

### B16. Design a Splitwise Clone

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Expense splitting (equal, percentage, exact)
- Balance calculation
- Debt simplification algorithm

**Patterns Used:**
- Strategy (split strategy)

**Core Classes:**
`Group`, `Expense`, `SplitStrategy`, `Balance`, `Settlement`

**Debt Simplification:**
```
Build net balance map → Separate creditors and debtors
→ Greedily match max creditor with max debtor
→ Minimize transactions
```

---

### B17. Design a Coupon/Voucher System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Coupon validation (expiry, usage limits, user eligibility)
- Discount types (flat, percentage, BOGO)
- Composable discounts

**Patterns Used:**
- Strategy (discount calculation)
- Chain of Responsibility (validation pipeline)

---

### B18. Design a Social Media Feed (simplified)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Post creation and storage
- Follow relationships
- Feed generation (fan-out vs pull model)

**Patterns Used:**
- Observer (new post notification)
- Strategy (feed ranking algorithm)

---

### B19. Design a Key-Value Store (in-memory)

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Thread-safe read/write
- Namespace/bucket support
- Expiration

**Patterns Used:**
- Singleton

**Core Impl:**
```java
ConcurrentHashMap<String, ValueEntry> store;
// ValueEntry holds value + optional expiry timestamp
```

---

### B20. Design a Printer Queue System

**Difficulty:** Intermediate
**Key Concepts Tested:**
- Priority queue management
- Print job lifecycle
- Multiple printer support

**Patterns Used:**
- Command (print job)
- Observer (job completion notification)
- Strategy (printer selection)

---

## CATEGORY C: ADVANCED (20 Questions)

These require concurrency, advanced patterns, and extension-ready designs. Aimed at SDE-3 level.

---

### C1. Design a Thread-Safe Object Pool

**Difficulty:** Advanced
**Key Concepts Tested:**
- Resource reuse (DB connections, threads)
- Acquire/release lifecycle
- Max pool size enforcement
- Idle object cleanup

**Patterns Used:**
- Singleton (pool instance)
- Factory (object creation on demand)

```java
public class ConnectionPool {
    private final BlockingQueue<Connection> pool;
    private final int maxSize;
    private final AtomicInteger currentSize = new AtomicInteger(0);

    public Connection acquire(long timeout, TimeUnit unit) throws InterruptedException {
        Connection conn = pool.poll(timeout, unit);
        if (conn == null && currentSize.get() < maxSize) {
            conn = createConnection();
        }
        return conn;
    }

    public void release(Connection conn) {
        pool.offer(conn); // returns to pool
    }
}
```

---

### C2. Design Amazon Locker System

**Difficulty:** Advanced
**Key Concepts Tested:**
- Locker size matching
- Time-bound access (OTP, expiry)
- Concurrent locker assignment

**Patterns Used:**
- Strategy (locker selection algorithm)
- Observer (notify customer when locker assigned)
- Factory (locker creation)

**Core Classes:**
`LockerSystem`, `Locker`, `LockerSize`, `Package`, `Delivery`, `OTPService`

**Extension questions:**
- How do you handle a locker that malfunctions?
- How do you pick the optimal locker location?

---

### C3. Design a Distributed Rate Limiter

**Difficulty:** Advanced
**Key Concepts Tested:**
- Rate limiting across multiple nodes
- Redis-backed state
- Sliding window with atomic operations

**Patterns Used:**
- Strategy
- Decorator

**Sliding Window with Redis:**
```
MULTI
  ZADD user:123 <now> <requestId>
  ZREMRANGEBYSCORE user:123 0 <now - windowMs>
  ZCARD user:123
EXEC
→ If ZCARD > limit → REJECT
```

---

### C4. Design a Search Autocomplete System

**Difficulty:** Advanced
**Key Concepts Tested:**
- Trie data structure
- Top-k suggestions with ranking
- Thread safety for concurrent reads/writes

**Patterns Used:**
- Composite (Trie node structure)

```java
class TrieNode {
    Map<Character, TrieNode> children = new HashMap<>();
    boolean isEndOfWord;
    int frequency; // for ranking
    // Use PriorityQueue for top-k at each node for faster retrieval
}
```

---

### C5. Design a Workflow Engine (State Machine)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Generic state machine implementation
- Transition guards and actions
- Event-driven transitions

**Patterns Used:**
- State
- Command (transition actions)
- Observer (state change events)

```java
class StateMachine<S, E> {
    private S currentState;
    private Map<S, Map<E, Transition<S>>> transitions;

    public void trigger(E event) {
        Transition<S> t = transitions.get(currentState).get(event);
        if (t != null && t.guard().test()) {
            t.action().execute();
            currentState = t.targetState();
        }
    }
}
```

---

### C6. Design an Order Management System

**Difficulty:** Advanced
**Key Concepts Tested:**
- Multi-step order lifecycle
- Payment integration
- Inventory reservation and rollback
- Event-driven status updates

**Patterns Used:**
- State (order status transitions)
- Observer (status change events)
- Saga pattern (distributed transaction simulation)
- Builder (order construction)

**Core Classes:**
`Order`, `OrderItem`, `OrderStatus`, `PaymentService`, `InventoryService`, `OrderEventPublisher`

---

### C7. Design a Circuit Breaker

**Difficulty:** Advanced
**Key Concepts Tested:**
- Failure detection and threshold
- Three states: CLOSED, OPEN, HALF-OPEN
- Timeout and retry logic
- Thread safety

**Patterns Used:**
- State
- Proxy (wrap service calls)

```java
enum CircuitState { CLOSED, OPEN, HALF_OPEN }

class CircuitBreaker {
    private volatile CircuitState state = CircuitState.CLOSED;
    private final AtomicInteger failureCount = new AtomicInteger(0);
    private volatile long lastFailureTime;
    
    public <T> T execute(Supplier<T> operation) {
        if (state == CircuitState.OPEN) {
            if (shouldAttemptReset()) state = CircuitState.HALF_OPEN;
            else throw new CircuitOpenException();
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
}
```

---

### C8. Design a Message Queue (like SQS simplified)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Producer-consumer design
- Message visibility timeout
- Dead letter queue
- At-least-once delivery

**Patterns Used:**
- Observer
- Command (message as command)

**Core Classes:**
`MessageQueue`, `Message`, `Producer`, `Consumer`, `DeadLetterQueue`, `VisibilityManager`

---

### C9. Design a Leaderboard System

**Difficulty:** Advanced
**Key Concepts Tested:**
- Efficient rank computation
- Real-time updates
- Tie-breaking logic
- Thread safety with concurrent score updates

**Patterns Used:**
- Observer (rank change events)

**Data Structure:**
- Use `TreeMap<Integer, Set<String>>` (score → players) for O(log n) rank queries
- Or Redis ZADD/ZREVRANK for production

---

### C10. Design a File Storage Service (like S3 simplified)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Chunked file upload
- Versioning
- Access control (ACL)
- Metadata management

**Patterns Used:**
- Decorator (add encryption, compression)
- Strategy (storage backend selection)

---

### C11. Design a Real-Time Collaborative Document Editor (like Google Docs simplified)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Operational Transformation or CRDT basics
- Concurrent edit conflict resolution
- Lock-free design at class level

**Patterns Used:**
- Command (operations: insert, delete)
- Observer (broadcast changes)
- Memento (version history)

---

### C12. Design a Job Queue with Retry and Backoff

**Difficulty:** Advanced
**Key Concepts Tested:**
- Exponential backoff implementation
- Max retry limits
- Dead letter handling
- Priority jobs

**Patterns Used:**
- Command (job encapsulation)
- Strategy (backoff strategy: linear, exponential, jitter)

```java
class RetryPolicy {
    private final int maxRetries;
    private final BackoffStrategy backoff;
    
    public long getDelayMs(int attempt) {
        return backoff.calculate(attempt); // e.g., 2^attempt * 100 ms
    }
}
```

---

### C13. Design a Multi-Tenant Saas Permission System

**Difficulty:** Advanced
**Key Concepts Tested:**
- RBAC (Role-Based Access Control)
- Resource-level permissions
- Tenant isolation
- Permission inheritance

**Patterns Used:**
- Composite (permission hierarchy)
- Strategy (authorization evaluation)

---

### C14. Design a Content Delivery / Caching Layer

**Difficulty:** Advanced
**Key Concepts Tested:**
- Cache-aside vs write-through vs write-behind
- TTL and invalidation
- Cache stampede prevention (probabilistic early expiration)

**Patterns Used:**
- Decorator (add caching to any service)
- Proxy (transparent caching proxy)

---

### C15. Design a Fraud Detection System (rule engine)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Rule evaluation pipeline
- Rule combination (AND/OR)
- Scoring and threshold

**Patterns Used:**
- Chain of Responsibility (rule pipeline)
- Composite (combine rules)
- Strategy (individual rule implementations)

---

### C16. Design a Live Streaming Platform (like Twitch — LLD portion)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Stream lifecycle management
- Viewer session management
- Real-time event handling

**Patterns Used:**
- Observer (viewer join/leave, chat events)
- State (stream states: STARTING, LIVE, ENDED)

---

### C17. Design a Recommendation Engine (simplified)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Data ingestion pipeline
- Scoring function
- Multiple recommendation strategies

**Patterns Used:**
- Strategy (collaborative filtering, content-based)
- Decorator (add A/B testing, logging)

---

### C18. Design an API Gateway (LLD portion)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Request routing
- Rate limiting per route/client
- Authentication middleware
- Request/response transformation

**Patterns Used:**
- Chain of Responsibility (middleware pipeline)
- Decorator (add auth, rate limit, logging)
- Proxy (forward to downstream services)

---

### C19. Design a Thread Pool Executor (from scratch)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Worker thread lifecycle
- Task queue management
- Graceful shutdown
- Rejection policies

```java
class SimpleThreadPool {
    private final BlockingQueue<Runnable> taskQueue;
    private final List<WorkerThread> workers;
    private volatile boolean isShutdown = false;

    public void submit(Runnable task) {
        if (isShutdown) throw new RejectedExecutionException();
        taskQueue.offer(task);
    }

    public void shutdown() {
        isShutdown = true;
        workers.forEach(WorkerThread::interrupt);
    }
    
    class WorkerThread extends Thread {
        public void run() {
            while (!isInterrupted()) {
                try {
                    Runnable task = taskQueue.poll(1, TimeUnit.SECONDS);
                    if (task != null) task.run();
                } catch (InterruptedException e) { interrupt(); }
            }
        }
    }
}
```

---

### C20. Design a Consistent Hashing Ring (LLD portion)

**Difficulty:** Advanced
**Key Concepts Tested:**
- Hash ring data structure
- Virtual nodes
- Node add/remove with minimal reshuffling

```java
class ConsistentHashRing {
    private final TreeMap<Integer, String> ring = new TreeMap<>();
    private final int virtualNodes;

    public void addServer(String server) {
        for (int i = 0; i < virtualNodes; i++) {
            int hash = hash(server + "#" + i);
            ring.put(hash, server);
        }
    }

    public String getServer(String key) {
        if (ring.isEmpty()) return null;
        int hash = hash(key);
        Map.Entry<Integer, String> entry = ring.ceilingEntry(hash);
        return (entry != null ? entry : ring.firstEntry()).getValue();
    }
}
```

---

## QUICK REFERENCE TABLE

| # | Problem | Category | Primary Pattern | Concurrency? |
|---|---------|----------|-----------------|--------------|
| A1 | Parking Lot | Beginner | Factory, Strategy | Optional |
| A2 | Library System | Beginner | Observer | No |
| A3 | Hotel Booking | Beginner | Builder, Strategy | Optional |
| A4 | Vending Machine | Beginner | State, Command | No |
| A5 | Chess Game | Beginner | Template Method | No |
| B1 | LRU Cache | Intermediate | — | Yes (ReadWriteLock) |
| B2 | Rate Limiter | Intermediate | Strategy | Yes (AtomicLong) |
| B3 | Notification System | Intermediate | Observer, Factory | Yes (BlockingQueue) |
| B4 | Task Scheduler | Intermediate | Command | Yes (ScheduledExecutor) |
| B5 | Elevator System | Intermediate | State, Strategy | Optional |
| B16 | Splitwise | Intermediate | Strategy | No |
| C1 | Object Pool | Advanced | Singleton, Factory | Yes (BlockingQueue) |
| C2 | Amazon Locker | Advanced | Strategy, Observer | Yes |
| C7 | Circuit Breaker | Advanced | State, Proxy | Yes (AtomicInteger, volatile) |
| C19 | Thread Pool | Advanced | — | Yes (Core concept) |
| C20 | Consistent Hashing | Advanced | — | Optional |

---

*See also:*
- [Amazon-LLD-Complete-Guide.md](Amazon-LLD-Complete-Guide.md) — Full preparation roadmap
- [Amazon-LLD-Detailed-Solutions.md](Amazon-LLD-Detailed-Solutions.md) — 5 fully worked solutions
