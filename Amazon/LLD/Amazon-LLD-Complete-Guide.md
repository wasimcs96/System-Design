# Amazon LLD Interview — Complete Preparation Guide
> Authored from the perspective of a Senior SDE-3 / Staff Engineer at Amazon with 10+ years of interviewing experience.
> Targeting: SDE-2, SDE-3, Senior Software Engineer roles — India & Malaysia hiring bars.

---

## TABLE OF CONTENTS
1. [LLD Interview Expectations at Amazon](#section-1-lld-interview-expectations-at-amazon)
2. [Complete Preparation Roadmap (6–10 Weeks)](#section-2-complete-preparation-roadmap)
3. [Amazon-Specific Strategy](#section-3-amazon-specific-strategy)
4. [Final Preparation Strategy](#section-6-final-preparation-strategy)

---

## SECTION 1: LLD INTERVIEW EXPECTATIONS AT AMAZON

### What Amazon Actually Evaluates

Amazon LLD interviews are NOT about producing perfect code. They evaluate your **engineering thought process**, ability to **model real-world complexity**, and how you **communicate design decisions**. Here is what every interviewer scores:

---

#### 1.1 OOP Principles (SOLID)

Amazon interviewers want to see that you instinctively apply OOP — not just know the definitions.

| Principle | What Interviewers Check |
|-----------|------------------------|
| **S** – Single Responsibility | Does each class do exactly ONE thing? |
| **O** – Open/Closed | Can you extend behavior without modifying existing code? |
| **L** – Liskov Substitution | Are subclasses safely substitutable for their parent? |
| **I** – Interface Segregation | Are interfaces thin and role-specific (not fat)? |
| **D** – Dependency Inversion | Are high-level modules depending on abstractions, not concrete classes? |

**Red flag:** A candidate who defines all business logic in one God-class.

---

#### 1.2 Design Patterns Usage

Interviewers are NOT looking for pattern name-dropping. They want to see:
- You identify WHEN a pattern applies naturally
- You implement it cleanly
- You explain WHY you chose it over alternatives

Most commonly tested at Amazon:
- **Strategy** (replaces conditionals)
- **Observer** (event systems)
- **Factory/Abstract Factory** (object creation)
- **Singleton** (thread-safe resource)
- **Builder** (complex object construction)
- **Decorator** (feature layering)
- **Command** (request encapsulation, undo/redo)

---

#### 1.3 Code Quality and Readability

- Meaningful class, method, and variable names
- Methods are short (< 20 lines ideally)
- No magic numbers — use constants/enums
- Avoid deep nesting — use early returns
- Follow language conventions (Java: camelCase; Python: snake_case)

**Interviewer tip:** They will read your code left-to-right. If they have to re-read a line, you've already lost points.

---

#### 1.4 Concurrency Handling (CRITICAL for SDE-2/SDE-3)

Amazon systems are massively concurrent. Interviewers frequently add:
> "Now imagine 1000 threads accessing this simultaneously. How does your design change?"

You must be fluent in:
- Identifying shared mutable state
- Using locks (`synchronized`, `ReentrantLock`, `ReadWriteLock`)
- Using atomic operations (`AtomicInteger`, `ConcurrentHashMap`)
- Avoiding deadlocks (lock ordering, tryLock with timeout)
- Designing lock-free structures where possible

---

#### 1.5 Scalability at Class/Module Level

This is NOT HLD. But you should think about:
- Can a class be extended without rewriting?
- Are there clear boundaries (interfaces) between modules?
- Is your design cohesive and loosely coupled?
- Can components be independently unit-tested?

---

#### 1.6 Extensibility and Maintainability

Interviewers will probe: *"What if we add X feature tomorrow?"*
- Can new vehicle types be added to the parking lot without modifying existing code?
- Can new payment methods be plugged in?
- Can new notification channels be added?

Your design should absorb extensions with minimal changes to existing code.

---

### 1.2 LLD vs HLD — Key Differences

| Dimension | HLD (High-Level Design) | LLD (Low-Level Design) |
|-----------|------------------------|------------------------|
| Focus | System architecture | Class/component design |
| Output | Architecture diagram, service map | Class diagram, code skeleton |
| Tools | Load balancers, databases, queues | Classes, interfaces, methods |
| Concurrency | "Use Kafka for async" | Thread-safe queue implementation |
| Duration | 45 min | 45–60 min |
| Languages | Technology-agnostic | Language-specific |

**Example:**
- HLD: "We'll have an Order Service backed by a MySQL DB with a Redis cache."
- LLD: "The `Order` class contains `OrderItem[]`, uses a `Builder` for construction, and `OrderProcessor` interface is implemented by `CODOrderProcessor` and `OnlineOrderProcessor`."

---

### 1.3 Evaluation Criteria — Including Bar Raiser

Amazon uses a **structured scorecard**:

| Dimension | Weight | What Bar Raiser Checks |
|-----------|--------|----------------------|
| Problem Understanding | High | Did you ask the right clarifying questions? |
| Design Quality | Very High | SOLID, patterns, extensibility |
| Code Quality | High | Clean, readable, idiomatic code |
| Concurrency Awareness | High (SDE-3) | Proactively address thread safety |
| Communication | Very High | Do you narrate decisions clearly? |
| Tradeoff Discussion | High | Do you acknowledge alternatives? |
| Handling Extensions | High | How well does your design adapt? |

**Bar Raiser focus:** Raises the bar means the candidate must be BETTER than 50% of people at the same level currently at Amazon. The Bar Raiser will push you harder — adding new requirements, asking "what if?", challenging your assumptions.

---

## SECTION 2: COMPLETE PREPARATION ROADMAP

> Total Duration: **8–10 weeks** (for SDE-2), **6–8 weeks** (for SDE-3)

---

### PHASE 1: FUNDAMENTALS (Week 1–2)

#### 1A: OOP Concepts

**Encapsulation**
- Hide internal state; expose behavior through methods
- Example: `BankAccount` exposes `deposit()` / `withdraw()`, not direct field access
```java
public class BankAccount {
    private double balance; // hidden state
    
    public void deposit(double amount) {
        if (amount > 0) balance += amount;
    }
    
    public double getBalance() { return balance; }
}
```

**Inheritance**
- Model "is-a" relationships; share behavior
- Prefer **composition over inheritance** when the relationship is "has-a"
```java
// Good: Animal -> Dog, Cat
// Bad: User -> AdminUser (use composition instead)
```

**Polymorphism**
- Same interface, different behavior
- Runtime (method overriding) vs Compile-time (overloading)
```java
interface PaymentProcessor {
    void process(Payment payment);
}

class CreditCardProcessor implements PaymentProcessor { ... }
class UPIProcessor implements PaymentProcessor { ... }
```

**Abstraction**
- Expose WHAT, hide HOW
- Use abstract classes when there is shared state; use interfaces when defining contracts

---

#### 1B: SOLID Principles — Deep Dive with Amazon Examples

**S — Single Responsibility**
```java
// BAD: One class does everything
class OrderService {
    public void createOrder() { }
    public void sendEmail() { }   // Wrong — not order's responsibility
    public void generatePDF() { } // Wrong
}

// GOOD: Separate responsibilities
class OrderService { public void createOrder() { } }
class NotificationService { public void sendOrderEmail(Order o) { } }
class InvoiceService { public void generateInvoice(Order o) { } }
```

**O — Open/Closed**
```java
// BAD: Modify existing code to add new discount type
class DiscountCalculator {
    public double calculate(String type, double price) {
        if (type.equals("SEASONAL")) return price * 0.1;
        if (type.equals("PRIME")) return price * 0.2; // added later — BAD
    }
}

// GOOD: Extend without modifying
interface DiscountStrategy {
    double calculate(double price);
}
class SeasonalDiscount implements DiscountStrategy { ... }
class PrimeDiscount implements DiscountStrategy { ... }
```

**L — Liskov Substitution**
```java
// BAD: Square extends Rectangle but breaks invariant
class Rectangle {
    void setWidth(int w) { this.width = w; }
    void setHeight(int h) { this.height = h; }
}
class Square extends Rectangle {
    void setWidth(int w) { this.width = w; this.height = w; } // Breaks LSP!
}

// GOOD: Separate classes with a common Shape interface
interface Shape { double area(); }
class Rectangle implements Shape { ... }
class Square implements Shape { ... }
```

**I — Interface Segregation**
```java
// BAD: Fat interface
interface Worker {
    void work();
    void eat();   // Robots don't eat!
    void sleep();
}

// GOOD: Segregated interfaces
interface Workable { void work(); }
interface Eatable { void eat(); }
class HumanWorker implements Workable, Eatable { ... }
class RobotWorker implements Workable { ... }
```

**D — Dependency Inversion**
```java
// BAD: High-level depends on low-level
class OrderService {
    private MySQLOrderRepository repo = new MySQLOrderRepository(); // Hard dependency
}

// GOOD: Depend on abstraction
class OrderService {
    private final OrderRepository repo; // Interface
    
    public OrderService(OrderRepository repo) { // Injected
        this.repo = repo;
    }
}
```

---

#### 1C: UML Basics (Class Diagrams)

Key relationships to know:

| Relationship | Symbol | Meaning | Example |
|-------------|--------|---------|---------|
| Association | `—` | A uses B | `Order` uses `Customer` |
| Aggregation | `◇—` | A has B (B can exist without A) | `Department` has `Employee` |
| Composition | `◆—` | A owns B (B cannot exist without A) | `Order` owns `OrderItem` |
| Inheritance | `△—` | A is B | `Dog` is `Animal` |
| Realization | `..△` | A implements B | `CreditCard` implements `Payment` |
| Dependency | `..>` | A depends on B temporarily | `OrderService` uses `EmailSender` |

**Text UML Format (for interviews):**
```
+------------------+         +------------------+
|     Order        |<>-------|   OrderItem      |
+------------------+         +------------------+
| - id: String     |         | - productId:String|
| - items: List    |         | - quantity: int   |
| - status: Enum   |         | - price: double   |
+------------------+         +------------------+
| + addItem()      |         | + getTotal()      |
| + cancel()       |         +------------------+
+------------------+
```

---

### PHASE 2: CORE DESIGN PATTERNS (Week 3–4)

---

#### 2A: Creational Patterns

**Factory Method**

*When to use:* When object creation logic is complex or should be decided at runtime.

```java
interface Notification {
    void send(String message);
}

class EmailNotification implements Notification {
    public void send(String message) { System.out.println("Email: " + message); }
}

class SMSNotification implements Notification {
    public void send(String message) { System.out.println("SMS: " + message); }
}

class NotificationFactory {
    public static Notification create(String type) {
        return switch (type) {
            case "EMAIL" -> new EmailNotification();
            case "SMS"   -> new SMSNotification();
            default      -> throw new IllegalArgumentException("Unknown type: " + type);
        };
    }
}
```

*Amazon interview use cases:* Notification system, payment processor factory, logger factory.

---

**Singleton (Thread-Safe)**

*When to use:* Exactly one instance required system-wide (config, logger, connection pool).

```java
public class ConfigManager {
    private static volatile ConfigManager instance; // volatile for visibility
    private final Map<String, String> configs;

    private ConfigManager() {
        configs = new HashMap<>();
        // load configs from file/env
    }

    public static ConfigManager getInstance() {
        if (instance == null) {                         // First check (no lock)
            synchronized (ConfigManager.class) {
                if (instance == null) {                 // Second check (with lock)
                    instance = new ConfigManager();
                }
            }
        }
        return instance;
    }

    public String get(String key) { return configs.get(key); }
}
```

*Interview trap:* Always use double-checked locking + `volatile`. Non-volatile singleton breaks in multi-core CPUs due to instruction reordering.

---

**Builder**

*When to use:* Object has many optional fields; prevents telescoping constructors.

```java
public class Order {
    private final String orderId;
    private final String customerId;
    private final List<OrderItem> items;
    private final String couponCode;  // optional
    private final Address deliveryAddress;

    private Order(Builder builder) {
        this.orderId = builder.orderId;
        this.customerId = builder.customerId;
        this.items = builder.items;
        this.couponCode = builder.couponCode;
        this.deliveryAddress = builder.deliveryAddress;
    }

    public static class Builder {
        private String orderId;
        private String customerId;
        private List<OrderItem> items = new ArrayList<>();
        private String couponCode;
        private Address deliveryAddress;

        public Builder orderId(String id) { this.orderId = id; return this; }
        public Builder customerId(String id) { this.customerId = id; return this; }
        public Builder addItem(OrderItem item) { this.items.add(item); return this; }
        public Builder couponCode(String code) { this.couponCode = code; return this; }
        public Builder deliveryAddress(Address addr) { this.deliveryAddress = addr; return this; }
        
        public Order build() {
            // validate required fields
            Objects.requireNonNull(orderId, "orderId required");
            Objects.requireNonNull(customerId, "customerId required");
            return new Order(this);
        }
    }
}

// Usage:
Order order = new Order.Builder()
    .orderId("ORD-001")
    .customerId("CUST-42")
    .addItem(item1)
    .couponCode("SAVE10")
    .build();
```

---

#### 2B: Structural Patterns

**Adapter**

*When to use:* Integrate a legacy/third-party interface into your system's expected interface.

```java
// Your system's interface
interface PaymentGateway {
    PaymentResult processPayment(double amount, String currency);
}

// Third-party Stripe SDK (you can't change it)
class StripeClient {
    public StripeResponse charge(StripeRequest req) { ... }
}

// Adapter bridges the gap
class StripePaymentAdapter implements PaymentGateway {
    private final StripeClient stripeClient;

    public StripePaymentAdapter(StripeClient client) {
        this.stripeClient = client;
    }

    @Override
    public PaymentResult processPayment(double amount, String currency) {
        StripeRequest req = new StripeRequest(amount * 100, currency); // cents
        StripeResponse resp = stripeClient.charge(req);
        return new PaymentResult(resp.getChargeId(), resp.isSuccessful());
    }
}
```

---

**Decorator**

*When to use:* Add behavior to objects at runtime without changing their class.

```java
interface Logger {
    void log(String message);
}

class ConsoleLogger implements Logger {
    public void log(String message) {
        System.out.println(message);
    }
}

// Base decorator
abstract class LoggerDecorator implements Logger {
    protected final Logger wrapped;
    LoggerDecorator(Logger logger) { this.wrapped = logger; }
}

class TimestampLogger extends LoggerDecorator {
    TimestampLogger(Logger logger) { super(logger); }
    
    public void log(String message) {
        wrapped.log("[" + Instant.now() + "] " + message);
    }
}

class MetricsLogger extends LoggerDecorator {
    MetricsLogger(Logger logger) { super(logger); }
    
    public void log(String message) {
        long start = System.nanoTime();
        wrapped.log(message);
        MetricsRegistry.record("log.latency", System.nanoTime() - start);
    }
}

// Usage — chain decorators
Logger logger = new MetricsLogger(new TimestampLogger(new ConsoleLogger()));
logger.log("Order placed");
```

---

#### 2C: Behavioral Patterns

**Strategy**

*When to use:* Multiple algorithms/behaviors that are interchangeable. Eliminates if-else chains.

```java
interface SortStrategy {
    void sort(int[] data);
}

class QuickSort implements SortStrategy {
    public void sort(int[] data) { /* quicksort impl */ }
}

class MergeSort implements SortStrategy {
    public void sort(int[] data) { /* mergesort impl */ }
}

class DataProcessor {
    private SortStrategy strategy;

    public void setStrategy(SortStrategy s) { this.strategy = s; }

    public void process(int[] data) {
        strategy.sort(data);
        // ... rest of processing
    }
}
```

*Amazon interview use cases:* Pricing strategy, discount calculation, route selection, retry policies.

---

**Observer**

*When to use:* One-to-many dependency — when one object changes state, all dependents are notified.

```java
interface OrderEventListener {
    void onOrderPlaced(Order order);
}

class OrderEventPublisher {
    private final List<OrderEventListener> listeners = new ArrayList<>();

    public void subscribe(OrderEventListener listener) { listeners.add(listener); }
    public void unsubscribe(OrderEventListener listener) { listeners.remove(listener); }

    public void notifyOrderPlaced(Order order) {
        listeners.forEach(l -> l.onOrderPlaced(order));
    }
}

class EmailNotificationListener implements OrderEventListener {
    public void onOrderPlaced(Order order) {
        // send confirmation email
    }
}

class InventoryUpdateListener implements OrderEventListener {
    public void onOrderPlaced(Order order) {
        // reduce stock
    }
}

class AnalyticsListener implements OrderEventListener {
    public void onOrderPlaced(Order order) {
        // record event in analytics
    }
}
```

---

**Command**

*When to use:* Encapsulate requests as objects — enables undo/redo, queuing, logging of operations.

```java
interface Command {
    void execute();
    void undo();
}

class PlaceOrderCommand implements Command {
    private final OrderService orderService;
    private final Order order;

    PlaceOrderCommand(OrderService svc, Order order) {
        this.orderService = svc;
        this.order = order;
    }

    public void execute() { orderService.place(order); }
    public void undo()    { orderService.cancel(order); }
}

class CommandHistory {
    private final Deque<Command> history = new ArrayDeque<>();

    public void execute(Command cmd) {
        cmd.execute();
        history.push(cmd);
    }

    public void undo() {
        if (!history.isEmpty()) history.pop().undo();
    }
}
```

---

### PHASE 3: LLD BUILDING BLOCKS (Week 4–5)

#### 3A: Class Design Principles
- Each class should map to a **real-world concept** in the domain
- Avoid anemic domain models (classes that are just bags of fields with no behavior)
- Name classes using **nouns**, methods using **verbs**
- Keep classes **small and focused**

#### 3B: Interfaces and Abstraction
- Define interfaces for **every dependency boundary**
- Name interfaces after behavior: `Sortable`, `Cacheable`, `Notifiable`
- Use abstract classes when you have **shared state** plus shared behavior
- Program to the interface, not the implementation

#### 3C: Dependency Injection
```java
// Instead of:
class OrderService {
    private EmailSender sender = new SMTPEmailSender(); // hard-coded
}

// Do:
class OrderService {
    private final NotificationSender sender;
    
    public OrderService(NotificationSender sender) { // injected
        this.sender = sender;
    }
}
```
Benefits: Testable, swappable, loosely coupled.

#### 3D: Error Handling
- Define a hierarchy of **domain exceptions**
- Never use exceptions for flow control
- Use **Result/Either types** in modern Java/Kotlin for expected failures

```java
// Domain exception hierarchy
class OrderException extends RuntimeException { ... }
class OrderNotFoundException extends OrderException { ... }
class InsufficientStockException extends OrderException { ... }
class PaymentFailedException extends OrderException { ... }
```

#### 3E: Enums for State and Type
```java
enum OrderStatus {
    PENDING, CONFIRMED, SHIPPED, DELIVERED, CANCELLED, REFUNDED;
    
    public boolean canTransitionTo(OrderStatus next) {
        return switch (this) {
            case PENDING -> next == CONFIRMED || next == CANCELLED;
            case CONFIRMED -> next == SHIPPED || next == CANCELLED;
            case SHIPPED -> next == DELIVERED;
            default -> false;
        };
    }
}
```

---

### PHASE 4: CONCURRENCY & MULTITHREADING (Week 5–6)

This is heavily tested for SDE-2 and CRITICAL for SDE-3 at Amazon.

---

#### 4A: Core Concepts

| Concept | Description |
|---------|------------|
| **Race Condition** | Two threads read-modify-write shared state non-atomically |
| **Deadlock** | Thread A holds Lock1, wants Lock2; Thread B holds Lock2, wants Lock1 |
| **Livelock** | Threads keep reacting to each other without making progress |
| **Starvation** | Low-priority thread never gets CPU time |
| **Visibility** | Changes in one thread may not be seen by another without `volatile`/`synchronized` |

---

#### 4B: Thread-Safe Cache (LRU) with ReadWriteLock

```java
public class ThreadSafeLRUCache<K, V> {
    private final int capacity;
    private final LinkedHashMap<K, V> cache;
    private final ReadWriteLock lock = new ReentrantReadWriteLock();

    public ThreadSafeLRUCache(int capacity) {
        this.capacity = capacity;
        this.cache = new LinkedHashMap<>(capacity, 0.75f, true) {
            protected boolean removeEldestEntry(Map.Entry<K, V> eldest) {
                return size() > capacity;
            }
        };
    }

    public V get(K key) {
        lock.readLock().lock();
        try {
            return cache.getOrDefault(key, null);
        } finally {
            lock.readLock().unlock();
        }
    }

    public void put(K key, V value) {
        lock.writeLock().lock();
        try {
            cache.put(key, value);
        } finally {
            lock.writeLock().unlock();
        }
    }
}
```

---

#### 4C: Thread-Safe Singleton Logger

```java
public class Logger {
    private static volatile Logger instance;
    private final BlockingQueue<String> logQueue = new LinkedBlockingQueue<>(10_000);
    private final ExecutorService writer = Executors.newSingleThreadExecutor();

    private Logger() {
        writer.submit(() -> {
            while (!Thread.currentThread().isInterrupted()) {
                try {
                    String entry = logQueue.take();  // blocks until available
                    System.out.println(entry);       // or write to file
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                }
            }
        });
    }

    public static Logger getInstance() {
        if (instance == null) {
            synchronized (Logger.class) {
                if (instance == null) instance = new Logger();
            }
        }
        return instance;
    }

    public void log(String message) {
        logQueue.offer("[" + Thread.currentThread().getName() + "] " + message);
    }
}
```

---

#### 4D: Producer-Consumer Problem

```java
public class OrderProcessor {
    private final BlockingQueue<Order> queue;
    private final int consumers;
    private final ExecutorService pool;

    public OrderProcessor(int capacity, int consumers) {
        this.queue = new ArrayBlockingQueue<>(capacity);
        this.consumers = consumers;
        this.pool = Executors.newFixedThreadPool(consumers);
    }

    public void start() {
        for (int i = 0; i < consumers; i++) {
            pool.submit(this::processOrders);
        }
    }

    public void submit(Order order) throws InterruptedException {
        queue.put(order); // blocks if queue is full (backpressure)
    }

    private void processOrders() {
        while (!Thread.currentThread().isInterrupted()) {
            try {
                Order order = queue.take(); // blocks if queue is empty
                // process order
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
            }
        }
    }

    public void shutdown() {
        pool.shutdown();
    }
}
```

---

#### 4E: Deadlock Prevention

```java
// Rule: Always acquire locks in a consistent order
// BAD: Can deadlock
void transfer(Account from, Account to, double amount) {
    synchronized (from) {            // Thread 1: locks A, wants B
        synchronized (to) {          // Thread 2: locks B, wants A → DEADLOCK
            from.debit(amount);
            to.credit(amount);
        }
    }
}

// GOOD: Impose a total order on lock acquisition
void transfer(Account from, Account to, double amount) {
    Account first  = from.getId() < to.getId() ? from : to;
    Account second = from.getId() < to.getId() ? to   : from;
    
    synchronized (first) {
        synchronized (second) {
            from.debit(amount);
            to.credit(amount);
        }
    }
}
```

---

### PHASE 5: PRACTICAL SYSTEM DESIGN — LLD PROBLEM SOLVING (Week 6–8)

#### Step-by-Step Approach to ANY LLD Question

```
STEP 1 ─ Requirement Clarification (3–4 min)
  • Ask: "What are the core features?"
  • Ask: "Are there any constraints? (e.g., concurrency, scale)"
  • Ask: "What should I NOT design?" (scope limiting)
  • Repeat back requirements to confirm understanding

STEP 2 ─ Identify Entities (2–3 min)
  • List the real-world objects/concepts in the domain
  • Every noun in the requirements is a potential class

STEP 3 ─ Define Classes & Interfaces (5 min)
  • Decide which are concrete classes, which are interfaces
  • Apply SOLID immediately

STEP 4 ─ Define Relationships (3 min)
  • Association, Composition, Aggregation, Inheritance
  • Draw a quick text class diagram

STEP 5 ─ Add Methods & Attributes (5 min)
  • Focus on behavior first, fields second
  • Define public API before internals

STEP 6 ─ Code the Core Flow (15–20 min)
  • Start with the most important use case
  • Write clean, compilable code (not pseudocode)
  • Narrate as you code

STEP 7 ─ Handle Edge Cases (3–5 min)
  • Null inputs, boundary values, invalid state transitions
  • Concurrency (mention explicitly for SDE-3)

STEP 8 ─ Discuss Tradeoffs (3–5 min)
  • "I chose X over Y because..."
  • "If requirements change to Z, I would..."
```

---

### PHASE 6: MOCK INTERVIEWS & OPTIMIZATION (Week 8–10)

#### How to Practice
1. **Solo** — Pick a problem, set a 45-min timer, whiteboard/code from scratch
2. **Pair** — Use Pramp, Interviewing.io, or find a practice partner
3. **Record yourself** — Narration is 30% of the score; watch it back
4. **Review real solutions** — GitHub: `tssovi/grokking-the-object-oriented-design-interview`

#### Common Mistakes in Amazon Interviews

| Mistake | Fix |
|---------|-----|
| Jumping to code immediately | Always spend 5+ min on requirements and design |
| God classes | Ask yourself: "Does this class have ONE responsibility?" |
| Forgetting to use interfaces | Every dependency should go through an abstraction |
| Ignoring concurrency | Proactively mention it even if not asked |
| Over-engineering | Design for stated requirements, mention extensions verbally |
| Silence | Never code silently; narrate your thinking constantly |
| Wrong patterns | Don't force patterns; explain why a pattern fits |
| Not asking for feedback | Ask "Does this meet what you had in mind?" |

#### Time Management in 45-Minute Interview

```
0–5  min : Clarify requirements, define scope
5–10 min : Identify entities, sketch class diagram
10–15 min: Define interfaces and core classes
15–35 min: Write code for the core use case
35–42 min: Add concurrency / edge cases / extensions
42–45 min: Discuss tradeoffs and summarize
```

#### Communication Strategy
- **Think aloud** — "I'm thinking about whether X should be a class or an enum..."
- **State decisions** — "I'll use Strategy pattern here because we may add more discount types later"
- **Acknowledge tradeoffs** — "This approach is simpler but doesn't scale to distributed; for now that seems fine"
- **Invite feedback** — "Does this direction make sense to you?"
- **Pivot gracefully** — "Good point, let me reconsider. I'll restructure this..."

---

## SECTION 3: AMAZON-SPECIFIC STRATEGY

### 3.1 How Amazon LLD Interviews Are Conducted (India & Malaysia)

**Typical Structure:**
- **Bangalore / Hyderabad / Chennai (India):** 4–6 interview rounds. 1–2 LLD rounds, 1–2 HLD, rest behavioral + coding.
- **Kuala Lumpur / Penang (Malaysia):** Similar structure; often virtual-first with shared code editor (CoderPad / Amazon Chime + code share).

**Common LLD interview flow:**
1. Interviewer gives a 2-line problem statement
2. You ask clarifying questions (they grade this)
3. You design verbally/on whiteboard
4. You write code (Java preferred, Python acceptable)
5. Interviewer extends the problem 1–2 times
6. Debrief: they ask "How would you handle X at scale?"

---

### 3.2 Leadership Principles in LLD Rounds

Amazon explicitly maps design decisions to Leadership Principles. Demonstrate them naturally:

| LP | How to Show in LLD |
|----|-------------------|
| **Customer Obsession** | "I want to make sure the API is intuitive for the caller" |
| **Ownership** | "I'd also handle the edge case of concurrent updates, even though it wasn't asked" |
| **Invent & Simplify** | "Instead of this complex approach, here's a simpler pattern that achieves the same result" |
| **Dive Deep** | "Let me think through the thread safety implications of this shared cache" |
| **Bias for Action** | "Let me start coding the core flow and we can refine as we go" |
| **Think Big** | "If this system needs to handle 10x load, this interface allows us to swap the impl" |

---

### 3.3 How to Explain Design While Coding

Narrate at THREE levels simultaneously:
1. **What** — "I'm creating an `OrderProcessor` class"
2. **Why** — "It's separate from `OrderService` so it follows SRP"
3. **How it fits** — "It implements the `Processor<Order>` interface so we can swap implementations"

**Sample narration:**
> "I'm defining the `Vehicle` interface here with `getType()` and `getSize()` methods. I'm using an interface rather than a base class because vehicles don't share any state — only behavior. Now I'll create `Car`, `Motorcycle`, and `Truck` implementing this. This makes it easy to add new vehicle types without changing the parking lot logic — that's the Open/Closed principle at work."

---

### 3.4 How Interviewers Extend Problems

Every LLD problem at Amazon will be extended. Be ready for:

| Original Problem | Common Extensions |
|-----------------|-------------------|
| Parking Lot | Add pricing model, add EV charging spots, handle reservations |
| LRU Cache | Make it distributed, add TTL expiry, add write-through |
| Rate Limiter | Switch from token bucket to sliding window, add per-user limits |
| Notification System | Add retry logic, add priority queues, add templating |
| Elevator | Add maintenance mode, add fire evacuation mode, optimize for energy |

**Strategy:** After your initial design, proactively say: *"I've designed this with extensibility in mind — for example, adding a new vehicle type or pricing strategy would only require a new class, no existing code changes."*

---

### 3.5 Common Traps Candidates Fall Into

1. **Designing only the happy path** — Always mention what happens when things fail
2. **Static methods everywhere** — Shows poor OOP understanding; use instances and inject dependencies
3. **Using `public` fields** — Always encapsulate; use getters/setters
4. **Not defining enums** — Status, types, categories should always be enums, never Strings
5. **Skipping interfaces** — "I'll just use concrete classes for now" is a red flag
6. **Premature optimization** — Don't add caching, indexing, sharding unless asked
7. **Ignoring `null` cases** — Show defensive programming
8. **Forgetting about the `equals`/`hashCode` contract** when objects go into Maps/Sets
9. **Using global state** — Avoid static mutable state; use DI
10. **Not handling the extension question** — This is where Bar Raiser separates strong from average

---

## SECTION 6: FINAL PREPARATION STRATEGY

### 30-Day Revision Plan

| Week | Focus |
|------|-------|
| **Week 1** | OOP + SOLID + UML review; code 2 examples per principle |
| **Week 2** | Design Patterns: 2 patterns/day with code + use cases |
| **Week 3** | Solve 10 LLD problems (Beginner + Intermediate) with full code |
| **Week 4** | Solve 10 more (Intermediate + Advanced); 3 mock interviews |

---

### Daily Practice Schedule (2 hours/day)

```
30 min — Review one concept/pattern (notes + code)
45 min — Solve one LLD problem from scratch (timer: 45 min)
30 min — Review a reference solution and compare
15 min — Note down gaps, update personal cheat sheet
```

---

### Must-Do vs Optional Topics

#### MUST DO (Zero compromise)
- [ ] SOLID principles with code examples
- [ ] Factory, Singleton (thread-safe), Builder, Strategy, Observer, Decorator, Command
- [ ] Parking Lot, LRU Cache, Rate Limiter, Elevator, Notification System
- [ ] Thread safety: synchronized, ReentrantLock, BlockingQueue, volatile
- [ ] State machine design with enums
- [ ] Dependency injection pattern

#### SHOULD DO (High value)
- [ ] Composite pattern (File System)
- [ ] Proxy pattern (Lazy loading, Access control)
- [ ] Iterator pattern
- [ ] Template Method pattern
- [ ] Event-driven architecture in LLD
- [ ] Order Management System, Splitwise, Library Management

#### OPTIONAL (Good to know)
- [ ] Flyweight, Memento, Chain of Responsibility
- [ ] Reactive/Async design patterns
- [ ] gRPC/REST as part of LLD interface

---

### Recommended Resources

#### Books
| Book | Why |
|------|-----|
| **Head First Design Patterns** | Best intro to patterns with visual examples |
| **Clean Code** (Robert Martin) | Code quality fundamentals |
| **Effective Java** (Joshua Bloch) | Java-specific best practices |
| **Designing Data-Intensive Applications** | Bridges LLD and HLD |

#### GitHub Repos
- `tssovi/grokking-the-object-oriented-design-interview` — Most referenced
- `ashishps1/awesome-low-level-design` — Clean code with full solutions
- `donnemartin/system-design-primer` — HLD but useful for context

#### Online Platforms
- **LeetCode** — Sections: OOP, Design; tagged "design"
- **Educative.io** — "Grokking the Low Level Design Interview"
- **YouTube** — Gaurav Sen, TechDummies, Concept&&Coding channels

#### Practice Interview Platforms
- **Pramp** — Free peer mock interviews
- **Interviewing.io** — Anonymous mock interviews with FAANG engineers
- **CodePair (HackerRank)** — Simulates Amazon's interview environment

---

### Personal Cheat Sheet Template

Create a one-page reference before interview day:

```
DESIGN PROCESS:
  Clarify → Entities → Classes → Relationships → Code → Edge Cases → Tradeoffs

SOLID TRIGGERS:
  Too many responsibilities → SRP
  Adding new types → OCP (Strategy/Factory)
  Inheritance issues → LSP or Composition
  Too many interface methods → ISP
  Hard dependencies → DIP (Constructor injection)

PATTERN TRIGGERS:
  Multiple algorithms → Strategy
  Object notification → Observer
  Complex creation → Builder/Factory
  Add behavior at runtime → Decorator
  Old interface → Adapter
  Single resource → Singleton
  Request as object → Command

CONCURRENCY CHECKLIST:
  Shared mutable state? → Need lock
  Mostly reads? → ReadWriteLock
  Single counter? → AtomicInteger
  Producer-consumer? → BlockingQueue
  One instance? → volatile + double-checked locking
```

---

*See companion files:*
- [Amazon-LLD-Top50-Questions.md](Amazon-LLD-Top50-Questions.md) — 50 categorized interview questions
- [Amazon-LLD-Detailed-Solutions.md](Amazon-LLD-Detailed-Solutions.md) — 5 fully worked solutions with code
