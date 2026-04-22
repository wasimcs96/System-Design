# LLD Design Patterns — Master Reference
> Full Java code + When to use + Amazon interview triggers for all 15 must-know patterns.

---

## How to Use This File

1. **Read the trigger table** at the top — memorise pattern names by symptom
2. **Study each pattern**: definition → code → interview use case
3. **Practice:** Given a problem, identify which pattern applies before looking at the solution

---

## Quick Trigger Table

| When you see... | Use this pattern |
|----------------|-----------------|
| Multiple interchangeable algorithms | Strategy |
| One object notifying many without tight coupling | Observer |
| Object creation is complex / conditonal | Factory Method |
| Object has many optional fields | Builder |
| Only ONE instance should exist system-wide | Singleton |
| Need to wrap old interface in new one | Adapter |
| Add behaviour at runtime without subclassing | Decorator |
| Client executes the same action on trees and leaves | Composite |
| Control access to an object (lazy load / security) | Proxy |
| Capture + restore object state (undo) | Memento |
| Encapsulate request as object (queue / undo) | Command |
| Chain of handlers, first one that can handle wins | Chain of Responsibility |
| Object's behaviour changes based on internal state | State |
| One-to-many creation of related families | Abstract Factory |
| Define skeleton algorithm, subclass fills in steps | Template Method |

---

## PART 1 — CREATIONAL PATTERNS

---

### Pattern 1: Factory Method

**Intent:** Define an interface for creating objects, but let subclasses decide which class to instantiate.

**Amazon Interview Triggers:**
- "Add a new payment method"
- "Support multiple notification channels"
- "Create loggers for different environments"

**Code:**
```java
// ── Product Interface ────────────────────────────────────────────────────────
interface NotificationSender {
    void send(String recipient, String message);
}

// ── Concrete Products ────────────────────────────────────────────────────────
class EmailSender implements NotificationSender {
    public void send(String recipient, String message) {
        System.out.println("Email → " + recipient + ": " + message);
    }
}

class SmsSender implements NotificationSender {
    public void send(String recipient, String message) {
        System.out.println("SMS → " + recipient + ": " + message);
    }
}

class PushSender implements NotificationSender {
    public void send(String recipient, String message) {
        System.out.println("PUSH → " + recipient + ": " + message);
    }
}

// ── Factory ───────────────────────────────────────────────────────────────────
class NotificationFactory {
    public static NotificationSender create(NotificationType type) {
        return switch (type) {
            case EMAIL -> new EmailSender();
            case SMS   -> new SmsSender();
            case PUSH  -> new PushSender();
        };
    }
}

enum NotificationType { EMAIL, SMS, PUSH }

// ── Usage ─────────────────────────────────────────────────────────────────────
// NotificationSender sender = NotificationFactory.create(NotificationType.EMAIL);
// sender.send("user@example.com", "Your order has been placed!");
```

**Extension question:** "How would you add WhatsApp notifications?"
> Add `WhatsAppSender` class + `WHATSAPP` to enum + one line in the factory switch. Zero changes to existing code. ✓ OCP.

---

### Pattern 2: Abstract Factory

**Intent:** Create families of related objects without specifying their concrete classes.

**Amazon Interview Triggers:**
- "Support both light and dark theme UI components"
- "Different cloud providers (AWS vs Azure vs GCP)"
- "Different database backends per environment"

**Code:**
```java
// ── Abstract Products ─────────────────────────────────────────────────────────
interface StorageService {
    void upload(String key, byte[] data);
    byte[] download(String key);
}

interface QueueService {
    void publish(String topic, String message);
    String consume(String topic);
}

// ── Concrete Products — AWS ───────────────────────────────────────────────────
class S3StorageService implements StorageService {
    public void upload(String key, byte[] data) { /* AWS S3 upload */ }
    public byte[] download(String key)          { return new byte[0]; /* S3 get */ }
}

class SqsQueueService implements QueueService {
    public void publish(String topic, String message) { /* SQS publish */ }
    public String consume(String topic)               { return ""; /* SQS receive */ }
}

// ── Concrete Products — GCP ───────────────────────────────────────────────────
class GcsStorageService implements StorageService {
    public void upload(String key, byte[] data) { /* GCS upload */ }
    public byte[] download(String key)          { return new byte[0]; }
}

class PubSubQueueService implements QueueService {
    public void publish(String topic, String message) { /* GCP Pub/Sub publish */ }
    public String consume(String topic)               { return ""; }
}

// ── Abstract Factory ─────────────────────────────────────────────────────────
interface CloudServiceFactory {
    StorageService createStorage();
    QueueService   createQueue();
}

class AwsServiceFactory implements CloudServiceFactory {
    public StorageService createStorage() { return new S3StorageService(); }
    public QueueService   createQueue()   { return new SqsQueueService();  }
}

class GcpServiceFactory implements CloudServiceFactory {
    public StorageService createStorage() { return new GcsStorageService();  }
    public QueueService   createQueue()   { return new PubSubQueueService(); }
}

// ── Usage ─────────────────────────────────────────────────────────────────────
// CloudServiceFactory factory = new AwsServiceFactory();
// StorageService storage = factory.createStorage();
// storage.upload("file.txt", data);
```

---

### Pattern 3: Singleton (Thread-Safe — Double-Checked Locking)

**Intent:** Ensure a class has only one instance and provide a global access point.

**Amazon Interview Triggers:**
- "Design a thread-safe Logger"
- "Design a ConfigurationManager"
- "Design a DatabaseConnectionPool"

**Code:**
```java
public class ApplicationConfig {
    // volatile prevents CPU instruction reordering across threads
    private static volatile ApplicationConfig instance;

    private final Map<String, String> properties;

    private ApplicationConfig() {
        // Load from env / config file
        properties = new HashMap<>();
        properties.put("db.host", "localhost");
        properties.put("db.port", "5432");
        properties.put("cache.ttl", "3600");
    }

    public static ApplicationConfig getInstance() {
        if (instance == null) {                        // First check — no locking (fast path)
            synchronized (ApplicationConfig.class) {
                if (instance == null) {                // Second check — with lock (safe path)
                    instance = new ApplicationConfig();
                }
            }
        }
        return instance;
    }

    public String get(String key) {
        return properties.getOrDefault(key, "");
    }

    public String get(String key, String defaultValue) {
        return properties.getOrDefault(key, defaultValue);
    }

    // Prevent cloning from breaking singleton
    @Override
    protected Object clone() throws CloneNotSupportedException {
        throw new CloneNotSupportedException("Singleton cannot be cloned");
    }
}
```

**Interview gotcha:** "Why `volatile`?"
> Without `volatile`, a partially constructed object may be visible to another thread due to CPU instruction reordering. `volatile` guarantees the happens-before relationship.

**Enum Singleton (foolproof alternative):**
```java
public enum DatabasePool {
    INSTANCE;

    private final Connection connection;

    DatabasePool() {
        // initialize connection
        this.connection = createConnection();
    }

    private Connection createConnection() { return null; /* init DB connection */ }
    public Connection getConnection() { return connection; }
}
// Usage: DatabasePool.INSTANCE.getConnection()
// Thread-safe by JVM guarantee, immune to reflection attacks and serialization issues
```

---

### Pattern 4: Builder

**Intent:** Construct complex objects step-by-step, separating construction from representation.

**Amazon Interview Triggers:**
- "Order has many optional fields (coupon, gift wrapping, address)"
- "Build a complex HTTP request"
- "Create an Order/Notification with optional fields"

**Code:**
```java
public final class Notification {
    // Required
    private final String recipientId;
    private final NotificationType type;
    private final String title;

    // Optional
    private final String body;
    private final String deepLinkUrl;
    private final int priority;          // 1–5
    private final Instant scheduledAt;   // null = send immediately
    private final Map<String, String> metadata;

    private Notification(Builder b) {
        this.recipientId  = b.recipientId;
        this.type         = b.type;
        this.title        = b.title;
        this.body         = b.body;
        this.deepLinkUrl  = b.deepLinkUrl;
        this.priority     = b.priority;
        this.scheduledAt  = b.scheduledAt;
        this.metadata     = Collections.unmodifiableMap(b.metadata);
    }

    // Getters (no setters — immutable)
    public String getRecipientId()   { return recipientId; }
    public NotificationType getType(){ return type; }
    public String getTitle()         { return title; }
    public String getBody()          { return body; }
    public int getPriority()         { return priority; }
    public Instant getScheduledAt()  { return scheduledAt; }

    public static class Builder {
        // Required
        private final String recipientId;
        private final NotificationType type;
        private final String title;

        // Optional — defaults set here
        private String body        = "";
        private String deepLinkUrl = null;
        private int priority       = 3;
        private Instant scheduledAt = null;
        private Map<String, String> metadata = new HashMap<>();

        public Builder(String recipientId, NotificationType type, String title) {
            Objects.requireNonNull(recipientId, "recipientId is required");
            Objects.requireNonNull(type, "type is required");
            Objects.requireNonNull(title, "title is required");
            this.recipientId = recipientId;
            this.type = type;
            this.title = title;
        }

        public Builder body(String body)               { this.body = body; return this; }
        public Builder deepLink(String url)            { this.deepLinkUrl = url; return this; }
        public Builder priority(int p)                 { this.priority = p; return this; }
        public Builder scheduledAt(Instant time)       { this.scheduledAt = time; return this; }
        public Builder metadata(String k, String v)    { this.metadata.put(k, v); return this; }

        public Notification build() {
            if (priority < 1 || priority > 5)
                throw new IllegalArgumentException("Priority must be 1–5");
            return new Notification(this);
        }
    }
}

// Usage:
// Notification n = new Notification.Builder("user_123", NotificationType.PUSH, "Order Shipped!")
//     .body("Your order ORD-456 has been shipped and will arrive tomorrow.")
//     .deepLink("app://orders/ORD-456")
//     .priority(4)
//     .metadata("order_id", "ORD-456")
//     .build();
```

---

## PART 2 — STRUCTURAL PATTERNS

---

### Pattern 5: Adapter

**Intent:** Convert the interface of a class into another interface clients expect. Lets incompatible classes work together.

**Amazon Interview Triggers:**
- "Integrate a legacy payment system"
- "Wrap a third-party SMS API"
- "Migrate from old interface to new without breaking callers"

**Code:**
```java
// ── Your System's Expected Interface ─────────────────────────────────────────
interface PaymentProcessor {
    PaymentResult charge(String customerId, double amount, String currency);
    boolean refund(String transactionId, double amount);
}

// ── Third-Party Stripe SDK (you cannot modify this) ──────────────────────────
class StripeSDK {
    public StripeChargeResponse createCharge(StripeChargeRequest req) {
        // Stripe API call
        return new StripeChargeResponse("ch_123", true, "usd");
    }

    public boolean issueRefund(String chargeId, long amountInCents) {
        // Stripe refund API
        return true;
    }
}

// ── Adapter ───────────────────────────────────────────────────────────────────
class StripePaymentAdapter implements PaymentProcessor {
    private final StripeSDK stripe;

    StripePaymentAdapter(StripeSDK stripe) {
        this.stripe = stripe;
    }

    @Override
    public PaymentResult charge(String customerId, double amount, String currency) {
        // Stripe needs amount in cents and expects its own request object
        StripeChargeRequest req = new StripeChargeRequest(
            customerId,
            (long)(amount * 100),  // dollars → cents
            currency.toLowerCase()
        );
        StripeChargeResponse resp = stripe.createCharge(req);
        return new PaymentResult(resp.getChargeId(), resp.isSuccess());
    }

    @Override
    public boolean refund(String transactionId, double amount) {
        return stripe.issueRefund(transactionId, (long)(amount * 100));
    }
}

// Helper classes (simplified)
record StripeChargeRequest(String customerId, long amountCents, String currency) {}
record StripeChargeResponse(String chargeId, boolean success, String currency) {
    public String getChargeId() { return chargeId; }
    public boolean isSuccess()  { return success; }
}
record PaymentResult(String transactionId, boolean success) {}
```

---

### Pattern 6: Decorator

**Intent:** Attach additional responsibilities to an object dynamically. Decorators provide a flexible alternative to subclassing.

**Amazon Interview Triggers:**
- "Add retry logic to any service call"
- "Add logging/metrics to any repository"
- "Add caching transparently to any data source"

**Code — Layered Repository with Caching + Logging:**
```java
// ── Core Interface ────────────────────────────────────────────────────────────
interface ProductRepository {
    Product findById(String id);
    void save(Product product);
}

// ── Base Implementation ───────────────────────────────────────────────────────
class DatabaseProductRepository implements ProductRepository {
    public Product findById(String id) {
        // DB query
        return new Product(id, "Sample Product", 29.99);
    }
    public void save(Product product) { /* DB insert/update */ }
}

// ── Base Decorator ────────────────────────────────────────────────────────────
abstract class ProductRepositoryDecorator implements ProductRepository {
    protected final ProductRepository delegate;
    ProductRepositoryDecorator(ProductRepository delegate) {
        this.delegate = delegate;
    }
}

// ── Caching Decorator ─────────────────────────────────────────────────────────
class CachingProductRepository extends ProductRepositoryDecorator {
    private final Map<String, Product> cache = new ConcurrentHashMap<>();
    private final Duration ttl;

    CachingProductRepository(ProductRepository delegate, Duration ttl) {
        super(delegate);
        this.ttl = ttl;
    }

    @Override
    public Product findById(String id) {
        return cache.computeIfAbsent(id, k -> delegate.findById(k));
        // Real implementation would also track expiry using expiry map + TTL
    }

    @Override
    public void save(Product product) {
        delegate.save(product);
        cache.put(product.getId(), product); // write-through
    }
}

// ── Logging Decorator ─────────────────────────────────────────────────────────
class LoggingProductRepository extends ProductRepositoryDecorator {
    LoggingProductRepository(ProductRepository delegate) { super(delegate); }

    @Override
    public Product findById(String id) {
        long start = System.currentTimeMillis();
        try {
            Product p = delegate.findById(id);
            System.out.printf("[REPO] findById(%s) → %s in %dms%n",
                id, p != null ? "found" : "null", System.currentTimeMillis() - start);
            return p;
        } catch (Exception e) {
            System.err.printf("[REPO] findById(%s) FAILED: %s%n", id, e.getMessage());
            throw e;
        }
    }

    @Override
    public void save(Product product) {
        System.out.printf("[REPO] save(%s)%n", product.getId());
        delegate.save(product);
    }
}

// ── Usage — Chain decorators ─────────────────────────────────────────────────
// ProductRepository repo = new LoggingProductRepository(
//     new CachingProductRepository(
//         new DatabaseProductRepository(),
//         Duration.ofMinutes(10)
//     )
// );
// Every read goes: LoggingDecorator → CachingDecorator → DB (only on miss)

record Product(String id, String name, double price) {
    public String getId() { return id; }
}
```

---

### Pattern 7: Composite

**Intent:** Compose objects into tree structures. Let clients treat individual objects and compositions uniformly.

**Amazon Interview Triggers:**
- "Design a file system (files and folders)"
- "Design an org chart (employees and managers)"
- "Design a menu system with sub-menus"

**Code — File System:**
```java
// ── Component Interface ───────────────────────────────────────────────────────
interface FileSystemNode {
    String getName();
    long getSize();          // bytes
    void print(String indent);
}

// ── Leaf: File ────────────────────────────────────────────────────────────────
class File implements FileSystemNode {
    private final String name;
    private final long size;

    File(String name, long size) { this.name = name; this.size = size; }

    public String getName()        { return name; }
    public long getSize()          { return size; }
    public void print(String indent) {
        System.out.printf("%s📄 %s (%d bytes)%n", indent, name, size);
    }
}

// ── Composite: Directory ──────────────────────────────────────────────────────
class Directory implements FileSystemNode {
    private final String name;
    private final List<FileSystemNode> children = new ArrayList<>();

    Directory(String name) { this.name = name; }

    public void add(FileSystemNode node)    { children.add(node); }
    public void remove(FileSystemNode node) { children.remove(node); }

    public String getName() { return name; }

    public long getSize() {
        return children.stream().mapToLong(FileSystemNode::getSize).sum();
    }

    public void print(String indent) {
        System.out.printf("%s📁 %s/ (%d bytes total)%n", indent, name, getSize());
        children.forEach(child -> child.print(indent + "    "));
    }
}

// ── Usage ─────────────────────────────────────────────────────────────────────
// Directory root = new Directory("root");
// Directory src = new Directory("src");
// src.add(new File("Main.java", 2048));
// src.add(new File("OrderService.java", 4096));
// root.add(src);
// root.add(new File("README.md", 512));
// root.print("");  // Recursively prints entire tree
```

---

### Pattern 8: Proxy

**Intent:** Provide a surrogate for another object to control access to it.

**Types:**
- **Virtual Proxy** — Lazy initialization (expensive object created only when needed)
- **Protection Proxy** — Access control
- **Remote Proxy** — Local representative for remote object
- **Caching Proxy** — Cache results

**Amazon Interview Triggers:**
- "Add access control to a sensitive service"
- "Lazy-load heavy images/data"
- "Add rate limiting to an API client"

**Code — Protection Proxy with Rate Limiting:**
```java
interface OrderService {
    Order getOrder(String orderId);
    Order placeOrder(OrderRequest request);
}

class OrderServiceImpl implements OrderService {
    public Order getOrder(String orderId) {
        // Fetch from DB
        return new Order(orderId, "DELIVERED");
    }
    public Order placeOrder(OrderRequest request) {
        return new Order(UUID.randomUUID().toString(), "PENDING");
    }
}

class RateLimitingOrderServiceProxy implements OrderService {
    private final OrderService delegate;
    private final int maxCallsPerMinute;
    private final Map<String, Integer> callCounts = new ConcurrentHashMap<>();
    private final Map<String, Long> windowStart  = new ConcurrentHashMap<>();

    RateLimitingOrderServiceProxy(OrderService delegate, int maxCallsPerMinute) {
        this.delegate = delegate;
        this.maxCallsPerMinute = maxCallsPerMinute;
    }

    private void checkRateLimit(String clientId) {
        long now = System.currentTimeMillis();
        windowStart.putIfAbsent(clientId, now);

        if (now - windowStart.get(clientId) > 60_000) {
            // Window expired — reset
            windowStart.put(clientId, now);
            callCounts.put(clientId, 0);
        }

        int count = callCounts.merge(clientId, 1, Integer::sum);
        if (count > maxCallsPerMinute) {
            throw new RateLimitExceededException("Rate limit exceeded for client: " + clientId);
        }
    }

    public Order getOrder(String orderId) {
        // Extract clientId from context (simplified)
        checkRateLimit("default");
        return delegate.getOrder(orderId);
    }

    public Order placeOrder(OrderRequest request) {
        checkRateLimit(request.getClientId());
        return delegate.placeOrder(request);
    }
}

class RateLimitExceededException extends RuntimeException {
    RateLimitExceededException(String msg) { super(msg); }
}

record Order(String id, String status) {}
record OrderRequest(String clientId, String productId, int quantity) {
    public String getClientId() { return clientId; }
}
```

---

## PART 3 — BEHAVIORAL PATTERNS

---

### Pattern 9: Strategy

**Intent:** Define a family of algorithms, encapsulate each one, and make them interchangeable.

**Amazon Interview Triggers:**
- "Support multiple discount types"
- "Support multiple sort orders"
- "Support multiple retry policies"

**Code — Retry Strategy:**
```java
interface RetryStrategy {
    long getDelayMs(int attemptNumber);
    int getMaxAttempts();
}

class ImmediateRetry implements RetryStrategy {
    private final int maxAttempts;
    ImmediateRetry(int maxAttempts) { this.maxAttempts = maxAttempts; }
    public long getDelayMs(int attempt) { return 0; }
    public int getMaxAttempts()         { return maxAttempts; }
}

class FixedDelayRetry implements RetryStrategy {
    private final long delayMs;
    private final int maxAttempts;
    FixedDelayRetry(long delayMs, int maxAttempts) {
        this.delayMs = delayMs;
        this.maxAttempts = maxAttempts;
    }
    public long getDelayMs(int attempt) { return delayMs; }
    public int getMaxAttempts()         { return maxAttempts; }
}

class ExponentialBackoffRetry implements RetryStrategy {
    private final long baseDelayMs;
    private final int maxAttempts;
    ExponentialBackoffRetry(long baseDelayMs, int maxAttempts) {
        this.baseDelayMs = baseDelayMs;
        this.maxAttempts = maxAttempts;
    }
    public long getDelayMs(int attempt) {
        // 2^attempt * baseDelay + random jitter
        long delay = (long) Math.pow(2, attempt) * baseDelayMs;
        long jitter = (long)(Math.random() * baseDelayMs);
        return delay + jitter;
    }
    public int getMaxAttempts() { return maxAttempts; }
}

// Context class that uses the strategy
class ServiceClient {
    private final RetryStrategy retryStrategy;

    ServiceClient(RetryStrategy strategy) { this.retryStrategy = strategy; }

    public <T> T callWithRetry(Supplier<T> operation) throws Exception {
        int attempts = 0;
        while (true) {
            try {
                return operation.get();
            } catch (Exception e) {
                attempts++;
                if (attempts >= retryStrategy.getMaxAttempts()) throw e;
                long delay = retryStrategy.getDelayMs(attempts);
                if (delay > 0) Thread.sleep(delay);
            }
        }
    }
}

// Usage:
// ServiceClient client = new ServiceClient(new ExponentialBackoffRetry(100, 5));
// Order order = client.callWithRetry(() -> orderApi.getOrder("ORD-123"));
```

---

### Pattern 10: Observer

**Intent:** Define a one-to-many dependency. When one object changes state, all dependents are notified automatically.

**Amazon Interview Triggers:**
- "When an order is placed, notify inventory, email service, analytics"
- "Event-driven notifications"
- "Publish-subscribe within a single service"

**Code — Type-Safe Event Bus:**
```java
// ── Events ────────────────────────────────────────────────────────────────────
interface DomainEvent {}

record OrderPlacedEvent(String orderId, String customerId, double total) implements DomainEvent {}
record OrderShippedEvent(String orderId, String trackingId) implements DomainEvent {}
record PaymentFailedEvent(String orderId, String reason) implements DomainEvent {}

// ── Listener Interface ────────────────────────────────────────────────────────
interface EventListener<T extends DomainEvent> {
    void onEvent(T event);
    Class<T> getEventType();
}

// ── Event Bus ─────────────────────────────────────────────────────────────────
class EventBus {
    private final Map<Class<?>, List<EventListener<?>>> listeners = new HashMap<>();

    public <T extends DomainEvent> void subscribe(EventListener<T> listener) {
        listeners.computeIfAbsent(listener.getEventType(), k -> new ArrayList<>())
                 .add(listener);
    }

    @SuppressWarnings("unchecked")
    public <T extends DomainEvent> void publish(T event) {
        List<EventListener<?>> handlers = listeners.getOrDefault(
            event.getClass(), Collections.emptyList()
        );
        handlers.forEach(h -> ((EventListener<T>) h).onEvent(event));
    }
}

// ── Concrete Listeners ────────────────────────────────────────────────────────
class InventoryUpdateListener implements EventListener<OrderPlacedEvent> {
    public void onEvent(OrderPlacedEvent e) {
        System.out.println("Inventory: Reserving stock for order " + e.orderId());
    }
    public Class<OrderPlacedEvent> getEventType() { return OrderPlacedEvent.class; }
}

class EmailConfirmationListener implements EventListener<OrderPlacedEvent> {
    public void onEvent(OrderPlacedEvent e) {
        System.out.println("Email: Sending confirmation to customer " + e.customerId());
    }
    public Class<OrderPlacedEvent> getEventType() { return OrderPlacedEvent.class; }
}

class FraudDetectionListener implements EventListener<PaymentFailedEvent> {
    public void onEvent(PaymentFailedEvent e) {
        System.out.println("Fraud: Flagging order " + e.orderId() + " reason: " + e.reason());
    }
    public Class<PaymentFailedEvent> getEventType() { return PaymentFailedEvent.class; }
}

// Usage:
// EventBus bus = new EventBus();
// bus.subscribe(new InventoryUpdateListener());
// bus.subscribe(new EmailConfirmationListener());
// bus.subscribe(new FraudDetectionListener());
// bus.publish(new OrderPlacedEvent("ORD-001", "CUST-42", 149.99));
// → Both inventory + email listeners fire, fraud listener is silent
```

---

### Pattern 11: Command

**Intent:** Encapsulate a request as an object, allowing parameterization, queuing, logging, and undo operations.

**Amazon Interview Triggers:**
- "Add undo/redo to a text editor"
- "Queue operations for async execution"
- "Audit log of all actions"
- "Transactional batch of operations"

**Code — Order Operation Commands with Undo:**
```java
interface Command {
    void execute();
    void undo();
    String getDescription();
}

class PlaceOrderCommand implements Command {
    private final OrderRepository repo;
    private final Order order;

    PlaceOrderCommand(OrderRepository repo, Order order) {
        this.repo = repo;
        this.order = order;
    }

    public void execute() {
        repo.save(order);
        order.setStatus(OrderStatus.CONFIRMED);
        System.out.println("Order placed: " + order.getId());
    }

    public void undo() {
        order.setStatus(OrderStatus.CANCELLED);
        repo.save(order);
        System.out.println("Order cancelled: " + order.getId());
    }

    public String getDescription() { return "PlaceOrder[" + order.getId() + "]"; }
}

class ApplyDiscountCommand implements Command {
    private final Order order;
    private final double discountAmount;
    private double originalTotal;

    ApplyDiscountCommand(Order order, double discountAmount) {
        this.order = order;
        this.discountAmount = discountAmount;
    }

    public void execute() {
        originalTotal = order.getTotal();
        order.setTotal(originalTotal - discountAmount);
        System.out.println("Discount applied: -" + discountAmount);
    }

    public void undo() {
        order.setTotal(originalTotal);
        System.out.println("Discount reverted");
    }

    public String getDescription() {
        return "ApplyDiscount[" + discountAmount + "]";
    }
}

// ── Command History (Undo Stack) ─────────────────────────────────────────────
class CommandHistory {
    private final Deque<Command> history  = new ArrayDeque<>();
    private final List<String>   auditLog = new ArrayList<>();

    public void execute(Command cmd) {
        cmd.execute();
        history.push(cmd);
        auditLog.add(Instant.now() + " EXECUTE: " + cmd.getDescription());
    }

    public void undo() {
        if (history.isEmpty()) {
            System.out.println("Nothing to undo");
            return;
        }
        Command cmd = history.pop();
        cmd.undo();
        auditLog.add(Instant.now() + " UNDO: " + cmd.getDescription());
    }

    public List<String> getAuditLog() { return Collections.unmodifiableList(auditLog); }
}
```

---

### Pattern 12: State

**Intent:** Allow an object to alter its behaviour when its internal state changes. The object will appear to change its class.

**Amazon Interview Triggers:**
- "Design a vending machine"
- "Design an ATM"
- "Design an order lifecycle (PENDING → SHIPPED → DELIVERED)"
- "Design a traffic light"

**Code — Order State Machine:**
```java
// ── State Interface ───────────────────────────────────────────────────────────
interface OrderState {
    void confirm(OrderContext ctx);
    void ship(OrderContext ctx);
    void deliver(OrderContext ctx);
    void cancel(OrderContext ctx);
    String getStateName();
}

// ── Context ───────────────────────────────────────────────────────────────────
class OrderContext {
    private OrderState currentState;
    private final String orderId;

    OrderContext(String orderId) {
        this.orderId = orderId;
        this.currentState = new PendingState();
    }

    public void setState(OrderState state) { this.currentState = state; }

    public void confirm()  { currentState.confirm(this); }
    public void ship()     { currentState.ship(this); }
    public void deliver()  { currentState.deliver(this); }
    public void cancel()   { currentState.cancel(this); }

    public String getStatus() { return currentState.getStateName(); }
    public String getOrderId() { return orderId; }
}

// ── Concrete States ───────────────────────────────────────────────────────────
class PendingState implements OrderState {
    public void confirm(OrderContext ctx) {
        System.out.println("Order confirmed");
        ctx.setState(new ConfirmedState());
    }
    public void ship(OrderContext ctx)    { throw new IllegalStateException("Cannot ship pending order"); }
    public void deliver(OrderContext ctx) { throw new IllegalStateException("Cannot deliver pending order"); }
    public void cancel(OrderContext ctx)  { System.out.println("Order cancelled"); ctx.setState(new CancelledState()); }
    public String getStateName()          { return "PENDING"; }
}

class ConfirmedState implements OrderState {
    public void confirm(OrderContext ctx) { System.out.println("Already confirmed"); }
    public void ship(OrderContext ctx)    { System.out.println("Order shipped"); ctx.setState(new ShippedState()); }
    public void deliver(OrderContext ctx) { throw new IllegalStateException("Must ship before deliver"); }
    public void cancel(OrderContext ctx)  { System.out.println("Order cancelled"); ctx.setState(new CancelledState()); }
    public String getStateName()          { return "CONFIRMED"; }
}

class ShippedState implements OrderState {
    public void confirm(OrderContext ctx) { throw new IllegalStateException("Already past confirm"); }
    public void ship(OrderContext ctx)    { System.out.println("Already shipped"); }
    public void deliver(OrderContext ctx) { System.out.println("Order delivered"); ctx.setState(new DeliveredState()); }
    public void cancel(OrderContext ctx)  { throw new IllegalStateException("Cannot cancel shipped order"); }
    public String getStateName()          { return "SHIPPED"; }
}

class DeliveredState implements OrderState {
    public void confirm(OrderContext ctx) { throw new IllegalStateException("Order complete"); }
    public void ship(OrderContext ctx)    { throw new IllegalStateException("Order complete"); }
    public void deliver(OrderContext ctx) { System.out.println("Already delivered"); }
    public void cancel(OrderContext ctx)  { throw new IllegalStateException("Cannot cancel delivered order"); }
    public String getStateName()          { return "DELIVERED"; }
}

class CancelledState implements OrderState {
    public void confirm(OrderContext ctx) { throw new IllegalStateException("Order cancelled"); }
    public void ship(OrderContext ctx)    { throw new IllegalStateException("Order cancelled"); }
    public void deliver(OrderContext ctx) { throw new IllegalStateException("Order cancelled"); }
    public void cancel(OrderContext ctx)  { System.out.println("Already cancelled"); }
    public String getStateName()          { return "CANCELLED"; }
}
```

---

### Pattern 13: Chain of Responsibility

**Intent:** Pass a request along a chain of handlers. Each handler decides to process or pass to the next.

**Amazon Interview Triggers:**
- "Design an order validation pipeline"
- "Design a middleware pipeline (auth → rate limit → validate → process)"
- "Design a support ticket escalation system"

**Code — Order Validation Pipeline:**
```java
abstract class OrderValidator {
    private OrderValidator next;

    public OrderValidator setNext(OrderValidator next) {
        this.next = next;
        return next; // allows chaining: v1.setNext(v2).setNext(v3)
    }

    protected final void passToNext(Order order) {
        if (next != null) next.validate(order);
    }

    public abstract void validate(Order order);
}

class StockValidator extends OrderValidator {
    private final InventoryService inventory;

    StockValidator(InventoryService inventory) { this.inventory = inventory; }

    public void validate(Order order) {
        order.getItems().forEach(item -> {
            if (!inventory.hasStock(item.getSkuId(), item.getQuantity()))
                throw new InsufficientStockException("Out of stock: " + item.getSkuId());
        });
        System.out.println("Stock check passed");
        passToNext(order);
    }
}

class PriceValidator extends OrderValidator {
    private final PricingService pricing;

    PriceValidator(PricingService pricing) { this.pricing = pricing; }

    public void validate(Order order) {
        order.getItems().forEach(item -> {
            double currentPrice = pricing.getPrice(item.getSkuId());
            if (Math.abs(item.getUnitPrice() - currentPrice) > 0.01)
                throw new PriceChangedException("Price changed for: " + item.getSkuId());
        });
        System.out.println("Price check passed");
        passToNext(order);
    }
}

class FraudValidator extends OrderValidator {
    private final FraudDetectionService fraud;

    FraudValidator(FraudDetectionService fraud) { this.fraud = fraud; }

    public void validate(Order order) {
        if (fraud.isSuspicious(order))
            throw new FraudSuspectedException("Order flagged for fraud review");
        System.out.println("Fraud check passed");
        passToNext(order);
    }
}

// ── Building the chain ────────────────────────────────────────────────────────
// OrderValidator pipeline = new StockValidator(inventory);
// pipeline.setNext(new PriceValidator(pricing))
//         .setNext(new FraudValidator(fraud));
// pipeline.validate(order);
// → All three validators run in sequence; any failure throws exception
```

---

### Pattern 14: Template Method

**Intent:** Define the skeleton of an algorithm in a base class, deferring some steps to subclasses.

**Amazon Interview Triggers:**
- "Different report types share the same generation steps"
- "Different order processors share the same workflow"
- "Different exporters (CSV, PDF, Excel) with same structure"

**Code — Report Generator:**
```java
abstract class ReportGenerator {
    // Template method — defines the algorithm skeleton
    public final Report generate(ReportRequest request) {
        validateRequest(request);           // common step
        List<Object> rawData = fetchData(request);  // abstract → subclass
        List<Object> processed = processData(rawData); // hook — optional override
        String formatted = formatReport(processed); // abstract → subclass
        auditLog(request, formatted);       // common step
        return new Report(request.getType(), formatted);
    }

    // Hook — optional override with sensible default
    protected List<Object> processData(List<Object> data) {
        return data; // default: no transformation
    }

    // Common steps
    private void validateRequest(ReportRequest req) {
        Objects.requireNonNull(req, "Request cannot be null");
    }

    private void auditLog(ReportRequest req, String result) {
        System.out.printf("[AUDIT] Report %s generated at %s%n", req.getType(), Instant.now());
    }

    // Abstract steps — each subclass MUST implement
    protected abstract List<Object> fetchData(ReportRequest request);
    protected abstract String formatReport(List<Object> data);
}

class SalesReportGenerator extends ReportGenerator {
    private final SalesRepository salesRepo;

    SalesReportGenerator(SalesRepository repo) { this.salesRepo = repo; }

    protected List<Object> fetchData(ReportRequest req) {
        return new ArrayList<>(salesRepo.findByDateRange(req.getStartDate(), req.getEndDate()));
    }

    @Override
    protected List<Object> processData(List<Object> data) {
        // Sort by revenue descending
        data.sort(Comparator.comparingDouble(o -> -((SaleRecord)o).getRevenue()));
        return data;
    }

    protected String formatReport(List<Object> data) {
        StringBuilder sb = new StringBuilder("=== Sales Report ===\n");
        data.forEach(row -> sb.append(row.toString()).append("\n"));
        return sb.toString();
    }
}

class InventoryReportGenerator extends ReportGenerator {
    protected List<Object> fetchData(ReportRequest req) {
        return List.of("SKU-001: 50 units", "SKU-002: 0 units (OUT OF STOCK)");
    }
    protected String formatReport(List<Object> data) {
        return "=== Inventory Report ===\n" + String.join("\n", data.stream()
            .map(Object::toString).toList());
    }
}

record Report(String type, String content) {}
record ReportRequest(String type, Object startDate, Object endDate) {
    public String getType() { return type; }
    public Object getStartDate() { return startDate; }
    public Object getEndDate() { return endDate; }
}
```

---

### Pattern 15: Memento

**Intent:** Capture and restore an object's internal state without violating encapsulation.

**Amazon Interview Triggers:**
- "Add undo to a cart"
- "Support rollback for a configuration change"
- "Checkpoint/restore for a long-running process"

**Code — Shopping Cart with Undo:**
```java
// ── Memento ───────────────────────────────────────────────────────────────────
class CartMemento {
    private final List<CartItem> snapshot;
    private final double totalSnapshot;
    private final Instant savedAt;

    CartMemento(List<CartItem> items, double total) {
        this.snapshot = new ArrayList<>(items); // deep copy
        this.totalSnapshot = total;
        this.savedAt = Instant.now();
    }

    List<CartItem> getSnapshot() { return Collections.unmodifiableList(snapshot); }
    double getTotalSnapshot()    { return totalSnapshot; }
    Instant getSavedAt()         { return savedAt; }
}

// ── Originator ────────────────────────────────────────────────────────────────
class ShoppingCart {
    private final List<CartItem> items = new ArrayList<>();
    private double total = 0;

    public void addItem(CartItem item) {
        items.add(item);
        total += item.getPrice() * item.getQuantity();
    }

    public boolean removeItem(String skuId) {
        CartItem item = items.stream()
            .filter(i -> i.getSkuId().equals(skuId))
            .findFirst().orElse(null);
        if (item == null) return false;
        items.remove(item);
        total -= item.getPrice() * item.getQuantity();
        return true;
    }

    public CartMemento save() {
        return new CartMemento(items, total);
    }

    public void restore(CartMemento memento) {
        items.clear();
        items.addAll(new ArrayList<>(memento.getSnapshot()));
        total = memento.getTotalSnapshot();
    }

    public double getTotal() { return total; }
    public List<CartItem> getItems() { return Collections.unmodifiableList(items); }
}

// ── Caretaker ─────────────────────────────────────────────────────────────────
class CartCaretaker {
    private final Deque<CartMemento> history = new ArrayDeque<>();
    private final ShoppingCart cart;

    CartCaretaker(ShoppingCart cart) { this.cart = cart; }

    public void saveState()  { history.push(cart.save()); }

    public void undo() {
        if (history.isEmpty()) { System.out.println("Nothing to undo"); return; }
        cart.restore(history.pop());
        System.out.println("Cart restored to previous state");
    }
}

record CartItem(String skuId, String name, double price, int quantity) {
    public String getSkuId()  { return skuId; }
    public double getPrice()  { return price; }
    public int getQuantity()  { return quantity; }
}
```

---

## PATTERN SELECTION GUIDE — Interview Cheat Sheet

```
PROBLEM: Need to switch between multiple algorithms at runtime?
→ STRATEGY

PROBLEM: One object changes, many others need to react?
→ OBSERVER (or EventBus variant)

PROBLEM: Need undo / operation queue / audit log?
→ COMMAND

PROBLEM: Object creation is complex or conditional?
→ FACTORY METHOD (one type) or ABSTRACT FACTORY (families)

PROBLEM: Object has optional fields, telescoping constructors?
→ BUILDER

PROBLEM: Need exactly one instance?
→ SINGLETON (with volatile + DCL)

PROBLEM: Incompatible interface from third-party?
→ ADAPTER

PROBLEM: Need to stack behaviors dynamically?
→ DECORATOR

PROBLEM: Tree structure of similar objects?
→ COMPOSITE

PROBLEM: Control access (lazy load, auth, rate limit)?
→ PROXY

PROBLEM: Object behaviour changes based on its own state?
→ STATE

PROBLEM: Sequential handlers, each may process or pass?
→ CHAIN OF RESPONSIBILITY

PROBLEM: Algorithm with fixed skeleton, variable steps?
→ TEMPLATE METHOD

PROBLEM: Need to save/restore object state?
→ MEMENTO

PROBLEM: Family of related objects to create together?
→ ABSTRACT FACTORY
```

---

*Companion files: [Amazon-LLD-Complete-Guide.md](Amazon-LLD-Complete-Guide.md) | [LLD-Advanced-Solutions.md](LLD-Advanced-Solutions.md) | [LLD-Concurrency-InterviewScenarios.md](LLD-Concurrency-InterviewScenarios.md)*
