# C5 — Event Sourcing

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Instead of storing the current state of an object (e.g., "Account balance: $500"), Event Sourcing stores every event that led to that state (e.g., "Deposited $1000, Withdrew $300, Withdrew $200"). The current state is computed by replaying events.

**Technical:** Event Sourcing is a persistence pattern where application state is stored as an immutable, append-only sequence of domain events. Current state is derived by replaying events. Events are the source of truth; the current state view is a projection/derivation.

---

## 2. Real-World Analogy

**Bank ledger:**
Traditional DB: "Your balance is $500." (only current state)
Event Sourcing: "You deposited $1000 on Jan 1. Withdrew $300 on Jan 2. Withdrew $200 on Jan 3. Balance = $500."

The bank can answer any historical question: "What was my balance on Jan 2?" (replay up to Jan 2).
If there's a bug in withdrawal logic, replay events with the bug fixed to get the corrected current balance.

---

## 3. Visual Diagram

```
TRADITIONAL: Current State Only
┌─────────────────────────┐
│ orders table            │
│ id=1 status=shipped     │ ← Can't tell what happened
│ id=2 status=cancelled   │   or why
└─────────────────────────┘

EVENT SOURCING: Append-Only Event Store
┌──────────────────────────────────────────────────────────────┐
│  event_store                                                 │
│  seq │ aggregate_id │ event_type       │ payload             │
│  1   │ order-1      │ OrderCreated     │ {items:[...]}       │
│  2   │ order-1      │ PaymentCharged   │ {amount:500}        │
│  3   │ order-2      │ OrderCreated     │ {items:[...]}       │
│  4   │ order-1      │ OrderShipped     │ {tracking:ABC123}   │
│  5   │ order-2      │ OrderCancelled   │ {reason:user_req}   │
└──────────────────────────────────────────────────────────────┘

Current state of order-1 = replay events 1,2,4 → OrderCreated+PaymentCharged+OrderShipped
Any historical state = replay up to any event sequence number

SNAPSHOTS (performance optimization):
After N events, take a snapshot of current state
On next read: load snapshot + replay only events after snapshot
```

---

## 4. Deep Technical Explanation

### Benefits
1. **Complete audit log:** Every change is recorded — who did what, when, why
2. **Time travel:** Query state at any point in history (debug, compliance)
3. **Event replay:** Fix bugs by replaying events with corrected logic
4. **Multiple projections:** Same events → different read models (CQRS)
5. **Decoupled:** New services can replay old events to build their own views

### Challenges
1. **Query complexity:** Can't query "all orders with status=shipped" directly — need projection
2. **Snapshots:** Without snapshots, replaying 10,000 events per request is slow
3. **Schema evolution:** Old events must still be parseable after schema changes (versioning)
4. **Eventual consistency:** Projections are always slightly behind (async update)

### Snapshots
For aggregates with many events:
1. Every N events (e.g., every 100), store a snapshot of current state
2. Load latest snapshot + replay only events after snapshot
3. Reduces replay from O(all events) to O(snapshot + recent events)

### Event Schema Versioning
Events are immutable — can't change old events. But event schemas evolve.
Solutions:
1. **Upcasting:** When loading old events, transform them to new schema before applying
2. **Weak schema (JSON):** Add new fields as optional; old code ignores unknown fields
3. **Event versioning:** `OrderCreated_v1`, `OrderCreated_v2` — handle each version in aggregate

---

## 5. Code Example

```php
// Domain Event
class OrderCreated {
    public function __construct(
        public readonly string    $orderId,
        public readonly string    $userId,
        public readonly array     $items,
        public readonly float     $total,
        public readonly \DateTime $occurredAt,
    ) {}
}

// Aggregate with event sourcing
class Order {
    private string $id;
    private string $status;
    private float  $total;
    private array  $pendingEvents = [];
    
    // Reconstitute from events
    public static function reconstituteFrom(array $events): self {
        $order = new self();
        foreach ($events as $event) {
            $order->apply($event);
        }
        return $order;
    }
    
    // Command — creates event, does NOT directly mutate state
    public function create(string $id, string $userId, array $items, float $total): void {
        $event = new OrderCreated($id, $userId, $items, $total, new \DateTime());
        $this->apply($event);
        $this->pendingEvents[] = $event;
    }
    
    // Apply event — mutates state (pure, no side effects)
    private function apply(object $event): void {
        match(get_class($event)) {
            OrderCreated::class  => $this->applyOrderCreated($event),
            OrderShipped::class  => $this->applyOrderShipped($event),
            OrderCancelled::class => $this->applyOrderCancelled($event),
        };
    }
    
    private function applyOrderCreated(OrderCreated $event): void {
        $this->id     = $event->orderId;
        $this->status = 'pending';
        $this->total  = $event->total;
    }
    
    public function getPendingEvents(): array { return $this->pendingEvents; }
}
```

```php
// Event Store — append-only persistence
class EventStore {
    public function append(string $aggregateId, array $events, int $expectedVersion): void {
        DB::transaction(function() use ($aggregateId, $events, $expectedVersion) {
            // Optimistic concurrency: check current version
            $currentVersion = DB::table('event_store')
                ->where('aggregate_id', $aggregateId)
                ->max('version') ?? 0;
            
            if ($currentVersion !== $expectedVersion) {
                throw new ConcurrencyException("Expected version {$expectedVersion}, got {$currentVersion}");
            }
            
            foreach ($events as $i => $event) {
                DB::table('event_store')->insert([
                    'aggregate_id' => $aggregateId,
                    'event_type'   => get_class($event),
                    'payload'      => json_encode($event),
                    'version'      => $expectedVersion + $i + 1,
                    'occurred_at'  => $event->occurredAt,
                ]);
            }
        });
    }
    
    public function load(string $aggregateId, int $afterVersion = 0): array {
        return DB::table('event_store')
            ->where('aggregate_id', $aggregateId)
            ->where('version', '>', $afterVersion)
            ->orderBy('version')
            ->get()
            ->map(fn($row) => $this->deserialize($row))
            ->toArray();
    }
}
```

---

## 6. Trade-offs

| Aspect | Traditional | Event Sourcing |
|--------|-------------|---------------|
| Audit log | Manual, incomplete | Complete, built-in |
| Historical queries | Impossible | Trivial |
| Current state | O(1) read | O(events) replay (use snapshots) |
| Schema changes | Easy | Complex (versioning) |
| Query patterns | Flexible | Projection-dependent |
| **Complexity** | Low | High |

---

## 7. Interview Q&A

**Q1: What is the difference between Event Sourcing and traditional state storage?**
> Traditional storage: save the current state (UPDATE users SET balance = 500). Event Sourcing: save every event that led to the state (INSERT events: Deposit $1000, Withdraw $500). Current state is computed by replaying events. Benefits: complete audit trail, time travel (query historical state), rebuildable projections. Cost: higher complexity, query patterns require projections.

**Q2: How do you handle performance with thousands of events per aggregate?**
> Use snapshots. Every N events, persist the current computed state as a snapshot. When loading an aggregate, load the latest snapshot and replay only events that occurred after the snapshot. This reduces replay from O(all events) to O(events since last snapshot). For high-volume aggregates, snapshot every 50-100 events. For low-volume, snapshot less frequently.

**Q3: How do you change an event schema after it's been stored?**
> Events are immutable — you can't change them. Solutions: (1) Upcasting — when loading old events, transform them to the new schema in memory before applying to the aggregate; (2) Weak schema with optional fields — use JSON and add new fields; old code handles missing fields gracefully; (3) Event versioning — create OrderCreated_v2 for new events; handle both v1 and v2 in the aggregate. Never modify stored events.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Events are immutable source of truth; state is derived          │
│ ✓ Time travel: replay up to any point to get historical state     │
│ ✓ Snapshots = performance optimization for event-heavy aggregates │
│ ✓ Events drive CQRS read model projections                        │
│ ✓ Event schema versioning is challenging — plan ahead             │
│ ✓ Use for: financial systems, audit trails, complex domain models │
└────────────────────────────────────────────────────────────────────┘
```
