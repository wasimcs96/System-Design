# C4 — CQRS (Command Query Responsibility Segregation)

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** CQRS separates "writing data" (commands) from "reading data" (queries) into separate systems. The write side handles updates; the read side is optimized for fast lookups with denormalized, pre-computed data.

**Technical:** CQRS is an architectural pattern that uses different data models (and often different databases) for reads and writes. Commands mutate state; Queries return data. The read model is a projection of the write model, updated asynchronously via events. This enables independent scaling, different consistency requirements, and highly optimized read models.

---

## 2. Real-World Analogy

**Accounting ledger:**
- **Write side (journal entries):** Accountant records every transaction as an entry: "Received $500 payment for Invoice #123." Each entry is immutable.
- **Read side (balance sheet):** The balance sheet is a computed summary — Total Assets, Total Liabilities, Net Income. It's regenerated from journal entries when needed.
- The journal (write model) is normalized and accurate. The balance sheet (read model) is denormalized and optimized for presentation.

---

## 3. Visual Diagram

```
TRADITIONAL CRUD (single model for reads and writes):
Client ──→ Service ──→ Database (normalized)
                   ←─ same table for reads and writes
Problem: Write-optimized (normalized) schema is slow for reads
         Read-optimized (denormalized) schema is slow for writes

CQRS PATTERN:
                   ┌─────────────────────────────────────────┐
                   │             WRITE SIDE                  │
Client ──→ Command Handler ──→ Write DB (normalized, ACID)   │
                   │              │                          │
                   │              │ publish events           │
                   └──────────────┼──────────────────────────┘
                                  │ async
                   ┌──────────────▼──────────────────────────┐
                   │             READ SIDE                   │
                   │ Event Handler ──→ Read DB (denormalized) │
Client ←── Query Handler ←──────────────────────────────────  │
                   └─────────────────────────────────────────┘

Example: E-commerce product page
Write DB: products, inventory, prices (normalized, 5 tables)
Read DB:  product_view (denormalized JSON): all data for product page in 1 record
```

---

## 4. Deep Technical Explanation

### When to Use CQRS
- Read/write ratio is highly asymmetric (99% reads, 1% writes)
- Read and write operations have very different performance/scaling requirements
- Complex domain model on write side, simple view on read side
- Multiple different "views" of the same data (mobile app, web, reporting)
- Event Sourcing (CQRS and Event Sourcing are often combined)

### When NOT to Use CQRS
- Simple CRUD operations with low complexity
- Same read and write volume
- Strong consistency required between read and write (financial dashboards)
- Small team — CQRS adds significant complexity

### Read Model Projections
The read model is built by processing events from the write side:
- One event stream → multiple projections (different read models)
- Projections can be rebuilt from scratch by replaying events (self-healing)
- Each projection optimized for one specific query pattern

### Eventual Consistency
- Write succeeds → event published → read model updated (milliseconds later)
- During this window, reads may return stale data
- This is usually acceptable for non-financial queries
- For financial or critical reads: query the write side directly (bypass read model)

---

## 5. Code Example

```php
// COMMAND SIDE — write model
class CreateOrderCommand {
    public function __construct(
        public readonly string $userId,
        public readonly array  $items,
        public readonly float  $total,
    ) {}
}

class OrderCommandHandler {
    public function handle(CreateOrderCommand $cmd): string {
        // Write to normalized database + publish event
        $orderId = DB::transaction(function() use ($cmd) {
            $order = Order::create([
                'user_id'    => $cmd->userId,
                'total'      => $cmd->total,
                'status'     => 'pending',
                'created_at' => now(),
            ]);
            
            foreach ($cmd->items as $item) {
                OrderItem::create(['order_id' => $order->id, ...$item]);
            }
            
            // Publish event (within same transaction via Outbox pattern)
            OutboxEvent::create([
                'aggregate_id' => $order->id,
                'event_type'   => 'OrderCreated',
                'payload'      => json_encode($cmd),
            ]);
            
            return $order->id;
        });
        
        return $orderId;
    }
}
```

```php
// QUERY SIDE — read model (denormalized)
class OrderReadModel {
    // Stored in Redis or a separate denormalized DB table
    // Built by projecting events
}

class OrderProjection {
    public function onOrderCreated(OrderCreated $event): void {
        // Update read model — denormalized for O(1) lookup
        $user = User::find($event->userId);
        
        Redis::setex(
            "order:view:{$event->orderId}",
            86400,
            json_encode([
                'id'         => $event->orderId,
                'user_name'  => $user->name,
                'user_email' => $user->email,
                'items'      => $event->items,  // already denormalized
                'total'      => $event->total,
                'status'     => 'pending',
                'created_at' => $event->createdAt,
            ])
        );
    }
}

// QUERY HANDLER — reads from optimized read model
class OrderQueryHandler {
    public function getOrder(string $orderId): array {
        $view = Redis::get("order:view:{$orderId}");
        if ($view) return json_decode($view, true);
        
        // Fallback to write DB if read model not yet populated
        return Order::with('items', 'user')->findOrFail($orderId)->toArray();
    }
}
```

---

## 6. Trade-offs

| Aspect | Traditional CRUD | CQRS |
|--------|-----------------|------|
| Complexity | Low | High |
| Read performance | Limited by write schema | Highly optimized |
| Scalability | Coupled | Independent |
| Consistency | Strong | Eventual (default) |
| Rebuild-ability | N/A | Read models rebuildable |
| **Use when** | Simple CRUD | High read volume, complex domain |

---

## 7. Interview Q&A

**Q1: How does CQRS help with read performance?**
> The write side uses a normalized schema optimized for ACID transactions. The read side uses a denormalized read model — a pre-computed projection with all data needed for a specific query, stored as a single document. Example: a product page that normally requires JOINing 5 tables can be served from a single Redis key containing all product data. Read latency drops from 100ms (5-table JOIN) to 1ms (Redis GET).

**Q2: How do you keep the read model consistent with the write model?**
> Via event-driven projection. When a command mutates the write model, it publishes a domain event (e.g., `OrderCreated`). Event handlers consume these events and update the read model accordingly. The read model is always slightly behind (eventual consistency, typically milliseconds). If the read model gets corrupted, it can be fully rebuilt by replaying all events from the beginning.

**Q3: Is CQRS always combined with Event Sourcing?**
> No, but they complement each other well. CQRS says: separate read and write concerns. Event Sourcing says: store state as a sequence of events (not current state). When combined: the event store IS the write model, and events drive all read model projections. You can use CQRS without Event Sourcing (separate read/write databases, updated synchronously or async). CQRS + ES is powerful but adds significant complexity.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Write side: normalized, ACID; Read side: denormalized, fast     │
│ ✓ Read models updated via events (eventual consistency)           │
│ ✓ Read models are projections — rebuildable from events           │
│ ✓ Use for: high read/write ratio, complex domain models           │
│ ✓ Avoid for: simple CRUD, where eventual consistency is a problem │
│ ✓ CQRS + Event Sourcing is powerful but complex — don't over-use  │
└────────────────────────────────────────────────────────────────────┘
```
