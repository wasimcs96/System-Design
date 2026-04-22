# Amazon LLD — 5 Detailed Solutions
> Full class diagrams, code skeletons, explanations, tradeoffs, and extension questions for 5 critical problems.

---

## SOLUTION 1: PARKING LOT SYSTEM

### Class Diagram
```
+------------------+      +-------------------+
|   ParkingLot     |<>----|   ParkingFloor    |
+------------------+      +-------------------+
| - name: String   |      | - floorId: int    |
| - floors: List   |      | - spots: List     |
+------------------+      +-------------------+
| + park(vehicle)  |      | + getFreeSpot()   |
| + unpark(ticket) |      | + getSpotById()   |
+------------------+      +-------------------+
                                   |
                                   |<>
                          +-------------------+
                          |   ParkingSpot     |
                          +-------------------+
                          | - spotId: String  |
                          | - type: SpotType  |
                          | - status: Status  |
                          | - vehicle: Vehicle|
                          +-------------------+
                          | + assign(vehicle) |
                          | + vacate()        |
                          +-------------------+

+------------------+      +-------------------+
|    Vehicle       |      |     Ticket        |
+------------------+      +-------------------+
| # plate: String  |      | - ticketId: String|
| # type: VehicleT |      | - spot: ParkSpot  |
+------------------+      | - entryTime: Inst |
       /\                  +-------------------+
       |                   | + getVehicle()    |
  _____|______             +-------------------+
 |     |      |
Car  Bike  Truck

+------------------+
| PricingStrategy  |<<interface>>
+------------------+
| + calculate(Tick)|
+------------------+
       /\
  _____|______
 |            |
HourlyPricing WeekendPricing
```

### Full Code (Java)

```java
// ─── Enums ──────────────────────────────────────────────────────────────────

enum VehicleType { MOTORCYCLE, CAR, TRUCK }

enum SpotType { SMALL, MEDIUM, LARGE }

enum SpotStatus { FREE, OCCUPIED }

// ─── Vehicle Hierarchy ───────────────────────────────────────────────────────

abstract class Vehicle {
    protected final String licensePlate;
    protected final VehicleType type;

    Vehicle(String licensePlate, VehicleType type) {
        this.licensePlate = licensePlate;
        this.type = type;
    }

    public String getLicensePlate() { return licensePlate; }
    public VehicleType getType() { return type; }
    public abstract SpotType requiredSpotType();
}

class Motorcycle extends Vehicle {
    Motorcycle(String plate) { super(plate, VehicleType.MOTORCYCLE); }
    public SpotType requiredSpotType() { return SpotType.SMALL; }
}

class Car extends Vehicle {
    Car(String plate) { super(plate, VehicleType.CAR); }
    public SpotType requiredSpotType() { return SpotType.MEDIUM; }
}

class Truck extends Vehicle {
    Truck(String plate) { super(plate, VehicleType.TRUCK); }
    public SpotType requiredSpotType() { return SpotType.LARGE; }
}

// ─── Pricing Strategy ────────────────────────────────────────────────────────

interface PricingStrategy {
    double calculate(Ticket ticket);
}

class HourlyPricing implements PricingStrategy {
    private static final Map<VehicleType, Double> RATES = Map.of(
        VehicleType.MOTORCYCLE, 20.0,
        VehicleType.CAR, 40.0,
        VehicleType.TRUCK, 80.0
    );

    public double calculate(Ticket ticket) {
        long hours = ChronoUnit.HOURS.between(ticket.getEntryTime(), Instant.now()) + 1;
        double rate = RATES.getOrDefault(ticket.getVehicle().getType(), 40.0);
        return hours * rate;
    }
}

// ─── Parking Spot ────────────────────────────────────────────────────────────

class ParkingSpot {
    private final String spotId;
    private final SpotType type;
    private SpotStatus status;
    private Vehicle vehicle;

    ParkingSpot(String spotId, SpotType type) {
        this.spotId = spotId;
        this.type = type;
        this.status = SpotStatus.FREE;
    }

    public synchronized boolean assign(Vehicle v) {
        if (status != SpotStatus.FREE) return false;
        if (type != v.requiredSpotType()) return false;
        this.vehicle = v;
        this.status = SpotStatus.OCCUPIED;
        return true;
    }

    public synchronized void vacate() {
        this.vehicle = null;
        this.status = SpotStatus.FREE;
    }

    public boolean isFree() { return status == SpotStatus.FREE; }
    public SpotType getType() { return type; }
    public String getSpotId() { return spotId; }
    public Vehicle getVehicle() { return vehicle; }
}

// ─── Ticket ──────────────────────────────────────────────────────────────────

class Ticket {
    private final String ticketId;
    private final Vehicle vehicle;
    private final ParkingSpot spot;
    private final Instant entryTime;

    Ticket(Vehicle vehicle, ParkingSpot spot) {
        this.ticketId = UUID.randomUUID().toString();
        this.vehicle = vehicle;
        this.spot = spot;
        this.entryTime = Instant.now();
    }

    public String getTicketId() { return ticketId; }
    public Vehicle getVehicle() { return vehicle; }
    public ParkingSpot getSpot() { return spot; }
    public Instant getEntryTime() { return entryTime; }
}

// ─── Parking Floor ───────────────────────────────────────────────────────────

class ParkingFloor {
    private final int floorId;
    private final List<ParkingSpot> spots;

    ParkingFloor(int floorId, List<ParkingSpot> spots) {
        this.floorId = floorId;
        this.spots = spots;
    }

    public Optional<ParkingSpot> findFreeSpot(SpotType type) {
        return spots.stream()
            .filter(s -> s.getType() == type && s.isFree())
            .findFirst();
    }
}

// ─── Parking Lot ─────────────────────────────────────────────────────────────

public class ParkingLot {
    private final String name;
    private final List<ParkingFloor> floors;
    private final PricingStrategy pricing;
    private final Map<String, Ticket> activeTickets = new ConcurrentHashMap<>();

    public ParkingLot(String name, List<ParkingFloor> floors, PricingStrategy pricing) {
        this.name = name;
        this.floors = floors;
        this.pricing = pricing;
    }

    public Ticket park(Vehicle vehicle) {
        for (ParkingFloor floor : floors) {
            Optional<ParkingSpot> spot = floor.findFreeSpot(vehicle.requiredSpotType());
            if (spot.isPresent() && spot.get().assign(vehicle)) {
                Ticket ticket = new Ticket(vehicle, spot.get());
                activeTickets.put(ticket.getTicketId(), ticket);
                return ticket;
            }
        }
        throw new RuntimeException("No available spot for vehicle type: " + vehicle.getType());
    }

    public double unpark(String ticketId) {
        Ticket ticket = activeTickets.remove(ticketId);
        if (ticket == null) throw new IllegalArgumentException("Invalid ticket: " + ticketId);
        double fee = pricing.calculate(ticket);
        ticket.getSpot().vacate();
        return fee;
    }
}
```

### Tradeoffs

| Decision | Chosen Approach | Alternative | Why |
|----------|----------------|-------------|-----|
| Spot finding | Linear scan per floor | HashMap<SpotType, Queue<Spot>> | Scan is simpler; queue approach is O(1) but more complex |
| Thread safety | `synchronized` on spot | `ReentrantLock` | synchronized is sufficient here |
| Pricing | Strategy pattern | Hardcoded in Lot | Strategy allows runtime swap without code change |

### Extension Questions Amazon Asks
1. *"Add reserved spots for disabled customers"* → Add `HANDICAPPED` SpotType + priority assignment
2. *"Add monthly pass holders who don't pay per hour"* → Add `CustomerType` to Ticket; PricingStrategy checks it
3. *"How would you handle a full parking lot?"* → Return `Optional<Ticket>` or throw typed exception; add waitlist queue
4. *"Add electric vehicle charging spots"* → `EVSpot extends ParkingSpot` with `chargerAvailable` flag

---

## SOLUTION 2: LRU CACHE (Thread-Safe)

### Class Diagram
```
+---------------------------+
|    LRUCache<K, V>         |
+---------------------------+
| - capacity: int           |
| - map: HashMap<K,Node>    |
| - list: DoublyLinkedList  |
| - lock: ReadWriteLock     |
+---------------------------+
| + get(key): V             |
| + put(key, value): void   |
| - moveToFront(node): void |
| - evict(): void           |
+---------------------------+

+---------------------------+
|      Node<K, V>           |
+---------------------------+
| - key: K                  |
| - value: V                |
| - prev: Node              |
| - next: Node              |
+---------------------------+

+---------------------------+
|   DoublyLinkedList<K,V>   |
+---------------------------+
| - head: Node (dummy)      |
| - tail: Node (dummy)      |
| - size: int               |
+---------------------------+
| + addToFront(node)        |
| + remove(node)            |
| + removeLast(): Node      |
+---------------------------+
```

### Full Code (Java)

```java
public class LRUCache<K, V> {

    private final int capacity;
    private final Map<K, Node<K, V>> map;
    private final DoublyLinkedList<K, V> list;
    private final ReadWriteLock lock = new ReentrantReadWriteLock();

    public LRUCache(int capacity) {
        if (capacity <= 0) throw new IllegalArgumentException("Capacity must be positive");
        this.capacity = capacity;
        this.map = new HashMap<>(capacity);
        this.list = new DoublyLinkedList<>();
    }

    public V get(K key) {
        lock.readLock().lock();
        try {
            Node<K, V> node = map.get(key);
            if (node == null) return null;
            // Upgrade to write lock for LRU update
            lock.readLock().unlock();
            lock.writeLock().lock();
            try {
                // Re-check after acquiring write lock
                node = map.get(key);
                if (node == null) return null;
                list.moveToFront(node);
                return node.value;
            } finally {
                lock.readLock().lock(); // downgrade back
                lock.writeLock().unlock();
            }
        } finally {
            lock.readLock().unlock();
        }
    }

    public void put(K key, V value) {
        lock.writeLock().lock();
        try {
            if (map.containsKey(key)) {
                Node<K, V> node = map.get(key);
                node.value = value;
                list.moveToFront(node);
            } else {
                if (map.size() >= capacity) {
                    Node<K, V> evicted = list.removeLast();
                    map.remove(evicted.key);
                }
                Node<K, V> newNode = new Node<>(key, value);
                map.put(key, newNode);
                list.addToFront(newNode);
            }
        } finally {
            lock.writeLock().unlock();
        }
    }

    // ─── Inner classes ───────────────────────────────────────

    static class Node<K, V> {
        K key;
        V value;
        Node<K, V> prev, next;

        Node(K key, V value) {
            this.key = key;
            this.value = value;
        }
    }

    static class DoublyLinkedList<K, V> {
        private final Node<K, V> head = new Node<>(null, null); // dummy head
        private final Node<K, V> tail = new Node<>(null, null); // dummy tail

        DoublyLinkedList() {
            head.next = tail;
            tail.prev = head;
        }

        void addToFront(Node<K, V> node) {
            node.next = head.next;
            node.prev = head;
            head.next.prev = node;
            head.next = node;
        }

        void remove(Node<K, V> node) {
            node.prev.next = node.next;
            node.next.prev = node.prev;
        }

        void moveToFront(Node<K, V> node) {
            remove(node);
            addToFront(node);
        }

        Node<K, V> removeLast() {
            if (tail.prev == head) throw new IllegalStateException("Cache is empty");
            Node<K, V> last = tail.prev;
            remove(last);
            return last;
        }
    }
}
```

### Tradeoffs

| Decision | Chosen | Alternative | Why |
|----------|--------|-------------|-----|
| Data structure | HashMap + DLL | Java `LinkedHashMap` | Interview expects manual DLL to test DS knowledge |
| Locking | ReadWriteLock | `synchronized` | Concurrent reads are common; RWLock allows parallel reads |
| Get behavior | Move to front on read | Only on write | Amazon standard: access = recency update |

### Extension Questions Amazon Asks
1. *"Add TTL support"* → `Node` stores `expiryTime`; check on `get()`; background sweeper thread
2. *"Make it distributed"* → Consistent hashing ring; serialize nodes; use Redis as backend
3. *"Add stats: hit rate, miss rate"* → `AtomicLong hits, misses` counters; expose via `getStats()`
4. *"What if writes are far more frequent than reads?"* → Use `ConcurrentLinkedDeque` + `ConcurrentHashMap`; consider lock-free approaches

---

## SOLUTION 3: RATE LIMITER (Token Bucket)

### Class Diagram
```
+----------------------------------+
|   RateLimiter <<interface>>      |
+----------------------------------+
| + isAllowed(clientId): boolean   |
+----------------------------------+
         /\
    _____|_______
   |             |
TokenBucket   SlidingWindow
RateLimiter   RateLimiter

+----------------------------------+
|   TokenBucketRateLimiter         |
+----------------------------------+
| - maxTokens: int                 |
| - refillRate: double (tok/sec)   |
| - buckets: ConcurrentHashMap     |
+----------------------------------+
| + isAllowed(clientId): boolean   |
| - refill(bucket): void           |
+----------------------------------+

+----------------------------------+
|   TokenBucket                    |
+----------------------------------+
| - tokens: double                 |
| - lastRefillTime: long           |
+----------------------------------+
```

### Full Code (Java)

```java
public interface RateLimiter {
    boolean isAllowed(String clientId);
}

// ─── Token Bucket Implementation ─────────────────────────────────────────────

public class TokenBucketRateLimiter implements RateLimiter {
    private final int maxTokens;
    private final double refillRatePerSecond;
    private final ConcurrentHashMap<String, TokenBucket> buckets = new ConcurrentHashMap<>();

    public TokenBucketRateLimiter(int maxTokens, double refillRatePerSecond) {
        this.maxTokens = maxTokens;
        this.refillRatePerSecond = refillRatePerSecond;
    }

    @Override
    public boolean isAllowed(String clientId) {
        TokenBucket bucket = buckets.computeIfAbsent(clientId,
            id -> new TokenBucket(maxTokens, maxTokens));
        return bucket.tryConsume();
    }

    private class TokenBucket {
        private double tokens;
        private long lastRefillTimeNanos;
        private final int maxTokens;

        TokenBucket(int maxTokens, double initialTokens) {
            this.maxTokens = maxTokens;
            this.tokens = initialTokens;
            this.lastRefillTimeNanos = System.nanoTime();
        }

        synchronized boolean tryConsume() {
            refill();
            if (tokens >= 1) {
                tokens -= 1;
                return true;
            }
            return false;
        }

        private void refill() {
            long now = System.nanoTime();
            double elapsed = (now - lastRefillTimeNanos) / 1_000_000_000.0;
            double tokensToAdd = elapsed * refillRatePerSecond;
            tokens = Math.min(maxTokens, tokens + tokensToAdd);
            lastRefillTimeNanos = now;
        }
    }
}

// ─── Sliding Window Log Implementation ───────────────────────────────────────

public class SlidingWindowRateLimiter implements RateLimiter {
    private final int maxRequests;
    private final long windowMs;
    private final ConcurrentHashMap<String, Deque<Long>> requestLogs = new ConcurrentHashMap<>();

    public SlidingWindowRateLimiter(int maxRequests, long windowMs) {
        this.maxRequests = maxRequests;
        this.windowMs = windowMs;
    }

    @Override
    public synchronized boolean isAllowed(String clientId) {
        long now = System.currentTimeMillis();
        Deque<Long> log = requestLogs.computeIfAbsent(clientId, id -> new ArrayDeque<>());

        // Remove timestamps outside the window
        while (!log.isEmpty() && now - log.peekFirst() >= windowMs) {
            log.pollFirst();
        }

        if (log.size() < maxRequests) {
            log.addLast(now);
            return true;
        }
        return false;
    }
}

// ─── Usage ───────────────────────────────────────────────────────────────────

// Factory to select algorithm
class RateLimiterFactory {
    public static RateLimiter create(String algorithm, int limit, long windowMs) {
        return switch (algorithm) {
            case "TOKEN_BUCKET"    -> new TokenBucketRateLimiter(limit, limit / (windowMs / 1000.0));
            case "SLIDING_WINDOW"  -> new SlidingWindowRateLimiter(limit, windowMs);
            default -> throw new IllegalArgumentException("Unknown algorithm: " + algorithm);
        };
    }
}
```

### Tradeoffs

| Aspect | Token Bucket | Sliding Window |
|--------|-------------|----------------|
| Memory | O(1) per client | O(requests per window) per client |
| Burst handling | Allows burst up to maxTokens | Strictly limits per window |
| Accuracy | Approximate | Exact |
| Complexity | Medium | Low |

### Extension Questions Amazon Asks
1. *"Rate limit per API endpoint AND per user"* → Key becomes `clientId + ":" + endpoint`
2. *"What if you need distributed rate limiting?"* → Use Redis INCR + EXPIRE for atomic counter; Lua script for sliding window
3. *"How to handle clock skew?"* → Use monotonic clock (`System.nanoTime()`) for token bucket; NTP sync for distributed
4. *"Add different tiers: Free (100/min), Pro (1000/min)"* → `ClientTier` enum; `RateLimitRule` maps tier to limits

---

## SOLUTION 4: NOTIFICATION SYSTEM

### Class Diagram
```
+---------------------------+
| NotificationService       |
+---------------------------+
| - channels: List<Channel> |
| - userPrefs: PrefService  |
| - queue: BlockingQueue    |
+---------------------------+
| + send(notification)      |
| + sendAsync(notification) |
+---------------------------+

+---------------------------+
| NotificationChannel <<if>>|
+---------------------------+
| + send(notif): boolean    |
| + supports(type): boolean |
+---------------------------+
     /\
  ___|______
 |    |     |
Email SMS  Push

+---------------------------+
|     Notification          |
+---------------------------+
| - id: String              |
| - userId: String          |
| - type: NotifType         |
| - title: String           |
| - body: String            |
| - priority: Priority      |
+---------------------------+

+---------------------------+
| UserNotifPreferences      |
+---------------------------+
| - userId: String          |
| - enabledChannels: Set    |
| - quietHours: TimeRange   |
+---------------------------+
```

### Full Code (Java)

```java
// ─── Enums ───────────────────────────────────────────────────────────────────

enum NotificationType { ORDER_PLACED, ORDER_SHIPPED, PAYMENT_FAILED, PROMO }

enum NotificationPriority { LOW, MEDIUM, HIGH, CRITICAL }

// ─── Notification ────────────────────────────────────────────────────────────

public class Notification {
    private final String id;
    private final String userId;
    private final NotificationType type;
    private final String title;
    private final String body;
    private final NotificationPriority priority;

    public Notification(String userId, NotificationType type, String title,
                        String body, NotificationPriority priority) {
        this.id = UUID.randomUUID().toString();
        this.userId = userId;
        this.type = type;
        this.title = title;
        this.body = body;
        this.priority = priority;
    }

    // Getters omitted for brevity
    public String getUserId() { return userId; }
    public NotificationType getType() { return type; }
    public NotificationPriority getPriority() { return priority; }
}

// ─── Channel Interface ───────────────────────────────────────────────────────

public interface NotificationChannel {
    boolean send(Notification notification);
    boolean supports(NotificationType type);
    String channelName();
}

// ─── Channel Implementations ─────────────────────────────────────────────────

public class EmailChannel implements NotificationChannel {
    public boolean send(Notification n) {
        System.out.printf("EMAIL to user %s: [%s] %s%n",
            n.getUserId(), n.type, n.title);
        return true; // simulate success
    }
    public boolean supports(NotificationType type) { return true; }
    public String channelName() { return "EMAIL"; }
}

public class SMSChannel implements NotificationChannel {
    // Only for critical/high priority
    public boolean send(Notification n) {
        System.out.printf("SMS to user %s: %s%n", n.getUserId(), n.body);
        return true;
    }
    public boolean supports(NotificationType type) {
        return type != NotificationType.PROMO;
    }
    public String channelName() { return "SMS"; }
}

public class PushChannel implements NotificationChannel {
    public boolean send(Notification n) {
        System.out.printf("PUSH to user %s: %s%n", n.getUserId(), n.title);
        return true;
    }
    public boolean supports(NotificationType type) { return true; }
    public String channelName() { return "PUSH"; }
}

// ─── User Preferences ────────────────────────────────────────────────────────

public class UserNotificationPreferences {
    private final Map<String, Set<String>> userChannels = new ConcurrentHashMap<>();

    public void setPreferences(String userId, Set<String> channels) {
        userChannels.put(userId, channels);
    }

    public Set<String> getEnabledChannels(String userId) {
        return userChannels.getOrDefault(userId, Set.of("EMAIL")); // default: email only
    }
}

// ─── Retry Decorator ─────────────────────────────────────────────────────────

public class RetryNotificationChannel implements NotificationChannel {
    private final NotificationChannel wrapped;
    private final int maxRetries;

    public RetryNotificationChannel(NotificationChannel channel, int maxRetries) {
        this.wrapped = channel;
        this.maxRetries = maxRetries;
    }

    public boolean send(Notification notification) {
        for (int attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                if (wrapped.send(notification)) return true;
            } catch (Exception e) {
                if (attempt == maxRetries) return false;
                sleep(100L * attempt); // simple linear backoff
            }
        }
        return false;
    }

    private void sleep(long ms) {
        try { Thread.sleep(ms); } catch (InterruptedException e) { Thread.currentThread().interrupt(); }
    }

    public boolean supports(NotificationType type) { return wrapped.supports(type); }
    public String channelName() { return wrapped.channelName(); }
}

// ─── Notification Service ────────────────────────────────────────────────────

public class NotificationService {
    private final List<NotificationChannel> channels;
    private final UserNotificationPreferences preferences;
    private final BlockingQueue<Notification> queue;
    private final ExecutorService workers;

    public NotificationService(List<NotificationChannel> channels,
                                UserNotificationPreferences prefs,
                                int queueCapacity, int workerCount) {
        this.channels = channels;
        this.preferences = prefs;
        this.queue = new PriorityBlockingQueue<>(queueCapacity,
            Comparator.comparingInt(n -> -n.getPriority().ordinal())); // higher priority first
        this.workers = Executors.newFixedThreadPool(workerCount);
        startWorkers(workerCount);
    }

    public void send(Notification notification) {
        // Synchronous
        dispatch(notification);
    }

    public void sendAsync(Notification notification) {
        queue.offer(notification);
    }

    private void startWorkers(int count) {
        for (int i = 0; i < count; i++) {
            workers.submit(() -> {
                while (!Thread.currentThread().isInterrupted()) {
                    try {
                        Notification n = queue.take();
                        dispatch(n);
                    } catch (InterruptedException e) {
                        Thread.currentThread().interrupt();
                    }
                }
            });
        }
    }

    private void dispatch(Notification notification) {
        Set<String> enabledChannels = preferences.getEnabledChannels(notification.getUserId());

        channels.stream()
            .filter(c -> enabledChannels.contains(c.channelName()))
            .filter(c -> c.supports(notification.getType()))
            .forEach(c -> {
                try {
                    c.send(notification);
                } catch (Exception e) {
                    System.err.println("Failed to send via " + c.channelName() + ": " + e.getMessage());
                }
            });
    }

    public void shutdown() {
        workers.shutdown();
    }
}

// ─── Wiring ──────────────────────────────────────────────────────────────────

public class Main {
    public static void main(String[] args) {
        UserNotificationPreferences prefs = new UserNotificationPreferences();
        prefs.setPreferences("user-1", Set.of("EMAIL", "PUSH"));
        prefs.setPreferences("user-2", Set.of("EMAIL", "SMS"));

        List<NotificationChannel> channels = List.of(
            new RetryNotificationChannel(new EmailChannel(), 3),
            new RetryNotificationChannel(new SMSChannel(), 2),
            new PushChannel()
        );

        NotificationService service = new NotificationService(channels, prefs, 1000, 4);

        Notification n = new Notification("user-1", NotificationType.ORDER_PLACED,
            "Order Confirmed", "Your order #123 is confirmed!", NotificationPriority.HIGH);

        service.sendAsync(n);
    }
}
```

### Tradeoffs

| Decision | Chosen | Alternative | Reason |
|----------|--------|-------------|--------|
| Async dispatch | PriorityBlockingQueue | Kafka/SQS | In-memory is simpler; Kafka needed for durability at scale |
| Retry | Decorator pattern | Inside channel impl | Decorator allows reuse across all channel types |
| User prefs | In-memory map | DB lookup per send | In-memory for interview; mention caching with TTL for production |

### Extension Questions Amazon Asks
1. *"Add templating (dynamic content in notifications)"* → `TemplateEngine.render(template, data)` called before sending
2. *"Add quiet hours (no SMS at night)"* → Check `QuietHoursPolicy` in `dispatch()` before sending
3. *"Add delivery receipts and analytics"* → `send()` returns `DeliveryResult`; publish to analytics event stream
4. *"How would this scale to 1 million notifications/minute?"* → Replace `BlockingQueue` with Kafka; deploy notification workers as separate microservices

---

## SOLUTION 5: AMAZON LOCKER SYSTEM

### Class Diagram
```
+---------------------------+      +---------------------------+
|     LockerSystem          |<>----|      LockerStation        |
+---------------------------+      +---------------------------+
| - stations: List          |      | - stationId: String       |
| - deliveryMap: Map        |      | - location: Location      |
| - otpService: OTPService  |      | - lockers: List<Locker>   |
+---------------------------+      +---------------------------+
| + assignLocker(delivery)  |      | + findAvailableLocker()   |
| + pickupPackage(code, otp)|      | + getLockerById(id)       |
| + returnPackage(code)     |      +---------------------------+
+---------------------------+
                                   +---------------------------+
+---------------------------+      |        Locker             |
|       Delivery            |      +---------------------------+
+---------------------------+      | - lockerId: String        |
| - deliveryId: String      |      | - size: LockerSize        |
| - package: Package        |      | - status: LockerStatus    |
| - customerId: String      |      | - currentDelivery: Deliv  |
| - assignedLocker: Locker  |      | - expiryTime: Instant     |
| - assignmentCode: String  |      +---------------------------+
| - status: DeliveryStatus  |      | + lock(delivery)          |
+---------------------------+      | + unlock(): Delivery      |
                                   +---------------------------+

+---------------------------+
|      OTPService           |
+---------------------------+
| + generate(code): String  |
| + validate(code, otp): bool|
+---------------------------+
```

### Full Code (Java)

```java
// ─── Enums ───────────────────────────────────────────────────────────────────

enum LockerSize { SMALL, MEDIUM, LARGE, EXTRA_LARGE }

enum LockerStatus { AVAILABLE, OCCUPIED, MAINTENANCE }

enum DeliveryStatus { PENDING_ASSIGNMENT, ASSIGNED, PICKED_UP, RETURNED, EXPIRED }

// ─── Package ─────────────────────────────────────────────────────────────────

class Package {
    private final String packageId;
    private final LockerSize requiredSize;
    private final double weightKg;

    Package(String packageId, LockerSize requiredSize, double weightKg) {
        this.packageId = packageId;
        this.requiredSize = requiredSize;
        this.weightKg = weightKg;
    }

    public LockerSize getRequiredSize() { return requiredSize; }
    public String getPackageId() { return packageId; }
}

// ─── Locker ──────────────────────────────────────────────────────────────────

class Locker {
    private final String lockerId;
    private final LockerSize size;
    private volatile LockerStatus status;
    private Delivery currentDelivery;
    private Instant expiryTime;
    private final ReentrantLock lock = new ReentrantLock();

    Locker(String lockerId, LockerSize size) {
        this.lockerId = lockerId;
        this.size = size;
        this.status = LockerStatus.AVAILABLE;
    }

    public boolean assignDelivery(Delivery delivery, Duration holdDuration) {
        lock.lock();
        try {
            if (status != LockerStatus.AVAILABLE) return false;
            this.currentDelivery = delivery;
            this.status = LockerStatus.OCCUPIED;
            this.expiryTime = Instant.now().plus(holdDuration);
            return true;
        } finally {
            lock.unlock();
        }
    }

    public Delivery release() {
        lock.lock();
        try {
            if (status != LockerStatus.OCCUPIED) throw new IllegalStateException("Locker not occupied");
            Delivery d = this.currentDelivery;
            this.currentDelivery = null;
            this.expiryTime = null;
            this.status = LockerStatus.AVAILABLE;
            return d;
        } finally {
            lock.unlock();
        }
    }

    public boolean isExpired() {
        return expiryTime != null && Instant.now().isAfter(expiryTime);
    }

    public String getLockerId() { return lockerId; }
    public LockerSize getSize() { return size; }
    public LockerStatus getStatus() { return status; }
}

// ─── OTP Service ─────────────────────────────────────────────────────────────

class OTPService {
    private final ConcurrentHashMap<String, String> otpStore = new ConcurrentHashMap<>();
    private final SecureRandom random = new SecureRandom();

    public String generate(String deliveryCode) {
        String otp = String.format("%06d", random.nextInt(1_000_000));
        otpStore.put(deliveryCode, otp);
        return otp;
    }

    public boolean validate(String deliveryCode, String otp) {
        String stored = otpStore.remove(deliveryCode); // one-time use
        return stored != null && stored.equals(otp);
    }
}

// ─── Delivery ────────────────────────────────────────────────────────────────

class Delivery {
    private final String deliveryId;
    private final Package pkg;
    private final String customerId;
    private Locker assignedLocker;
    private String assignmentCode;
    private DeliveryStatus status;

    Delivery(String deliveryId, Package pkg, String customerId) {
        this.deliveryId = deliveryId;
        this.pkg = pkg;
        this.customerId = customerId;
        this.status = DeliveryStatus.PENDING_ASSIGNMENT;
    }

    public void assign(Locker locker, String code) {
        this.assignedLocker = locker;
        this.assignmentCode = code;
        this.status = DeliveryStatus.ASSIGNED;
    }

    public Package getPackage() { return pkg; }
    public String getDeliveryId() { return deliveryId; }
    public Locker getAssignedLocker() { return assignedLocker; }
    public String getAssignmentCode() { return assignmentCode; }
    public DeliveryStatus getStatus() { return status; }
    public void setStatus(DeliveryStatus s) { this.status = s; }
}

// ─── Locker Station ──────────────────────────────────────────────────────────

class LockerStation {
    private final String stationId;
    private final String location;
    private final List<Locker> lockers;

    LockerStation(String stationId, String location, List<Locker> lockers) {
        this.stationId = stationId;
        this.location = location;
        this.lockers = lockers;
    }

    public Optional<Locker> findAvailable(LockerSize requiredSize) {
        // Find smallest locker that fits (waste minimization)
        return lockers.stream()
            .filter(l -> l.getStatus() == LockerStatus.AVAILABLE)
            .filter(l -> l.getSize().ordinal() >= requiredSize.ordinal())
            .min(Comparator.comparingInt(l -> l.getSize().ordinal()));
    }

    public Optional<Locker> getById(String lockerId) {
        return lockers.stream().filter(l -> l.getLockerId().equals(lockerId)).findFirst();
    }

    public String getStationId() { return stationId; }
}

// ─── Locker System ───────────────────────────────────────────────────────────

public class LockerSystem {
    private final List<LockerStation> stations;
    private final OTPService otpService;
    private final Map<String, Delivery> deliveries = new ConcurrentHashMap<>();
    private static final Duration DEFAULT_HOLD = Duration.ofDays(3);

    public LockerSystem(List<LockerStation> stations, OTPService otpService) {
        this.stations = stations;
        this.otpService = otpService;
        startExpiryChecker();
    }

    public String assignLocker(Delivery delivery, String preferredStationId) {
        LockerStation station = findStation(preferredStationId);
        Optional<Locker> locker = station.findAvailable(delivery.getPackage().getRequiredSize());

        if (locker.isEmpty()) throw new RuntimeException("No available locker at station: " + preferredStationId);

        String assignmentCode = UUID.randomUUID().toString().substring(0, 8).toUpperCase();
        locker.get().assignDelivery(delivery, DEFAULT_HOLD);
        delivery.assign(locker.get(), assignmentCode);

        deliveries.put(assignmentCode, delivery);

        String otp = otpService.generate(assignmentCode);
        // In production: send OTP to customer via SMS/email
        System.out.println("OTP for customer " + delivery.getCustomerId() + ": " + otp);

        return assignmentCode;
    }

    public Delivery pickupPackage(String assignmentCode, String otp) {
        if (!otpService.validate(assignmentCode, otp)) {
            throw new SecurityException("Invalid OTP for code: " + assignmentCode);
        }

        Delivery delivery = deliveries.remove(assignmentCode);
        if (delivery == null) throw new IllegalArgumentException("Invalid assignment code");
        if (delivery.getAssignedLocker().isExpired()) {
            throw new IllegalStateException("Locker reservation has expired");
        }

        delivery.getAssignedLocker().release();
        delivery.setStatus(DeliveryStatus.PICKED_UP);
        return delivery;
    }

    private LockerStation findStation(String stationId) {
        return stations.stream()
            .filter(s -> s.getStationId().equals(stationId))
            .findFirst()
            .orElseThrow(() -> new IllegalArgumentException("Station not found: " + stationId));
    }

    private void startExpiryChecker() {
        ScheduledExecutorService scheduler = Executors.newSingleThreadScheduledExecutor();
        scheduler.scheduleAtFixedRate(() -> {
            deliveries.entrySet().removeIf(entry -> {
                Delivery d = entry.getValue();
                if (d.getAssignedLocker().isExpired()) {
                    d.getAssignedLocker().release();
                    d.setStatus(DeliveryStatus.EXPIRED);
                    System.out.println("Expired locker released for delivery: " + d.getDeliveryId());
                    return true;
                }
                return false;
            });
        }, 1, 1, TimeUnit.HOURS);
    }
}
```

### Tradeoffs

| Decision | Chosen | Alternative | Reason |
|----------|--------|-------------|--------|
| OTP storage | In-memory ConcurrentHashMap | Redis with TTL | Redis needed for multi-server; in-memory for single node |
| Locker selection | Best-fit (smallest that fits) | First-fit | Best-fit reduces wasted space |
| Expiry detection | Background scheduler | Lazy check on access | Background frees lockers proactively; lazy is simpler |
| Lock granularity | Per-locker ReentrantLock | Global synchronized | Fine-grained locking allows parallel locker operations |

### Extension Questions Amazon Asks
1. *"A customer lost their OTP — how do you handle it?"* → Add `resendOTP(assignmentCode, customerId)` with identity verification
2. *"Locker malfunctions — door won't open"* → `Locker.setStatus(MAINTENANCE)`; trigger reassignment; alert maintenance team
3. *"How do you find the nearest locker station?"* → Add `Location` with lat/lng; use geospatial index (PostGIS / geohash); return stations sorted by distance
4. *"Handle very high concurrency — 10,000 deliveries/second"* → Shard `deliveries` map by hash; use CAS operations; deploy station service per geographic region

---

## COMMON PATTERNS ACROSS ALL SOLUTIONS

| Problem | State Machine | Strategy | Observer | Concurrency Tool |
|---------|--------------|----------|----------|-----------------|
| Parking Lot | SpotStatus | PricingStrategy | — | synchronized on spot |
| LRU Cache | — | — | — | ReadWriteLock |
| Rate Limiter | — | Algorithm selection | — | synchronized on bucket |
| Notification | — | Channel selection | Pub-Sub | BlockingQueue + ExecutorService |
| Locker System | LockerStatus, DeliveryStatus | Locker selection | — | ReentrantLock per locker |

---

*See also:*
- [Amazon-LLD-Complete-Guide.md](Amazon-LLD-Complete-Guide.md) — Full preparation roadmap (Sections 1–3, 6)
- [Amazon-LLD-Top50-Questions.md](Amazon-LLD-Top50-Questions.md) — 50 categorized interview questions
