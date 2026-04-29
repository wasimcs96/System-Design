# D3 — Event-Driven Architecture

> **Section:** Architecture Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** In an event-driven architecture, services communicate by publishing and subscribing to events. When something happens (order placed), a service publishes an event. Other services that care subscribe and react — without any direct coupling between them.

**Technical:** Event-Driven Architecture (EDA) uses asynchronous event publishing and consumption for service decoupling. Services publish domain events to an event broker (Kafka, SNS); other services subscribe to relevant events and process them independently. Reduces temporal coupling (services don't need to be available simultaneously).

---

## 2. Real-World Analogy

**Radio broadcast:**
- Radio station (publisher) broadcasts on a frequency — doesn't know who's listening
- Listeners (subscribers) tune in and react — independently
- Adding new listeners doesn't affect the station
- Station doesn't need to know about listeners

vs. **Phone call (request-response):**
- Caller knows receiver's number (tight coupling)
- Receiver must be available when called
- Caller waits on hold (synchronous)

---

## 3. Visual Diagram

```
REQUEST-RESPONSE (synchronous — tight coupling):
OrderService ──call──→ InventoryService (must be up, response waited)
             ──call──→ PaymentService (must be up)
             ──call──→ EmailService (must be up)
If any service is slow/down → OrderService blocks/fails

EVENT-DRIVEN (asynchronous — loose coupling):
OrderService ──publishes──→ [order.created] ──→ InventoryService (async)
                                            ──→ PaymentService (async)
                                            ──→ EmailService (async)
                                            ──→ AnalyticsService (async)
OrderService returns immediately — no waiting
Any service can be down temporarily — events queue up

CHOREOGRAPHY (event-driven):
                  ┌──────────────────────────────────────────┐
order.created ──→ │ InventoryService  (reserves stock)       │
                  │     publishes inventory.reserved         │
                  └──────────────────────────────────────────┘
inventory.reserved ──→ PaymentService  (charges card)
                            publishes payment.charged
payment.charged ──→ EmailService (sends confirmation)
```

---

## 4. Deep Technical Explanation

### Event Types
1. **Domain events:** "OrderCreated", "UserRegistered", "PaymentFailed" — meaningful business facts
2. **Integration events:** Cross-service event contracts — must be stable, versioned
3. **Commands disguised as events:** Anti-pattern — "ProcessOrder" event means "please do this" — not truly event-driven

### Temporal Decoupling
Services don't need to be available at the same time:
- OrderService publishes event → goes to Kafka
- EmailService is down for maintenance
- EmailService comes back up → reads from Kafka (event persisted) → sends email
- No event lost, no failure in OrderService

### Fan-out Pattern
One event → multiple consumers:
- `order.created` → InventoryService + PaymentService + EmailService + AnalyticsService
- All process independently, at their own pace
- Consumer groups in Kafka: each gets its own copy of events

### Event Ordering and Idempotency
- Kafka guarantees order within a partition
- If event A must be processed before event B: use same Kafka partition key
- Consumers must handle duplicate events (at-least-once delivery): make handlers idempotent

### Choreography vs Orchestration (also covered in C3 Saga)
- **Choreography:** Services react to events independently — no central coordinator
- **Orchestration:** A saga orchestrator explicitly commands each service step

---

## 5. Code Example

```php
// Event publisher — OrderService
class OrderService {
    public function createOrder(array $data): array {
        $order = DB::transaction(function() use ($data) {
            $order = Order::create($data);
            // Outbox pattern — atomic with order creation
            OutboxEvent::create([
                'topic'   => 'order.created',
                'payload' => json_encode([
                    'order_id'  => $order->id,
                    'user_id'   => $order->user_id,
                    'items'     => $order->items->toArray(),
                    'total'     => $order->total,
                    'timestamp' => now()->toIso8601String(),
                ]),
            ]);
            return $order;
        });
        
        // Background job publishes outbox events to Kafka
        return ['order_id' => $order->id];
    }
}

// Event subscriber — InventoryService
class OrderCreatedHandler {
    public function handle(array $event): void {
        $orderId = $event['order_id'];
        
        // Idempotency check
        if (ProcessedEvent::where('event_id', $orderId)->exists()) return;
        
        DB::transaction(function() use ($event, $orderId) {
            foreach ($event['items'] as $item) {
                $inventory = Inventory::lockForUpdate()->find($item['product_id']);
                if ($inventory->quantity < $item['quantity']) {
                    // Publish compensation event
                    $this->eventBus->publish('inventory.reservation_failed', [
                        'order_id' => $orderId,
                        'reason'   => 'Insufficient stock',
                    ]);
                    return;
                }
                $inventory->decrement('quantity', $item['quantity']);
            }
            
            $this->eventBus->publish('inventory.reserved', ['order_id' => $orderId]);
            ProcessedEvent::create(['event_id' => $orderId]);
        });
    }
}
```

---

## 6. Trade-offs

| Aspect | Sync (Request-Response) | Async (Event-Driven) |
|--------|------------------------|---------------------|
| Coupling | Tight (knows endpoint) | Loose (knows event type) |
| Availability | Both must be up | Publisher doesn't care about consumer |
| Consistency | Immediate | Eventual |
| Complexity | Low | Medium-High |
| Debugging | Easy (linear trace) | Hard (distributed events) |
| **Use for** | Simple CRUD, reads | Complex workflows, fan-out |

---

## 7. Interview Q&A

**Q1: How do you handle event ordering in event-driven systems?**
> Use Kafka partition keys: all events for the same order should use `order_id` as the partition key. Kafka guarantees ordering within a partition. If strict global ordering is needed across orders: use a single partition (limits throughput). For most use cases, per-entity ordering (all events for order X are ordered) is sufficient. If consumers process events out of order despite partitioning: implement event sequencing with version numbers and queue events that arrive out of order.

**Q2: What is the difference between commands and events?**
> Commands are imperative — "ProcessPayment", "ReserveInventory" — telling a service to do something. Events are facts — "PaymentProcessed", "InventoryReserved" — describing something that happened. Commands are addressed to a specific service (tight coupling). Events are published to a topic (loose coupling); any service can react. Naming matters: past tense for events, imperative for commands.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Events = loose coupling, temporal decoupling                    │
│ ✓ Publisher doesn't know/care who consumes events                 │
│ ✓ Events are facts (past tense); commands are directives          │
│ ✓ Kafka partition key = guaranteed ordering per entity            │
│ ✓ Outbox Pattern = atomic event publish with DB transaction        │
│ ✓ Consumers must be idempotent (at-least-once delivery)           │
└────────────────────────────────────────────────────────────────────┘
```
