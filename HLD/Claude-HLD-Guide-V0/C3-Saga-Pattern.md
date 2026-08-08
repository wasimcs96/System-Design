# C3 — Saga Pattern

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** When you place an order online (charge card, reserve inventory, send email), each step is handled by a different system. What happens if step 3 fails? Saga manages these multi-step operations and handles rollbacks when something goes wrong.

**Technical:** The Saga pattern manages long-running transactions across multiple microservices, where each service has a local ACID transaction. A saga is a sequence of local transactions. If one fails, the saga executes compensating transactions to undo completed steps (eventual consistency via compensation, not rollback).

---

## 2. Real-World Analogy

**Travel booking:**
1. Book flight ✓
2. Book hotel ✓
3. Book car rental ✗ (all booked)

Without Saga: You're charged for flight and hotel, but no car. No automatic rollback.

With Saga: Car rental failure triggers compensating transactions:
- Cancel hotel booking
- Refund flight ticket
- Return user's money

The saga "unwinds" completed steps through explicit compensation.

---

## 3. Visual Diagram

```
CHOREOGRAPHY SAGA (event-driven):
OrderService ──→ [order.created event] ──→ PaymentService
                                               │
                                         payment.charged ──→ InventoryService
                                                                    │
                                               ┌─────── inventory.reserved
                                               ▼
                                         ShippingService ──→ saga complete ✓

COMPENSATION ON FAILURE:
InventoryService fails ──→ [inventory.failed event]
                                     │
                               PaymentService receives it
                               → refund payment
                               → [payment.refunded event]
                                         │
                                   OrderService receives it
                                   → cancel order ✓

ORCHESTRATION SAGA (centralized):
                   ┌──────────────────────────┐
                   │     Saga Orchestrator     │
                   │  (tracks saga state)     │
                   └──────────────────────────┘
                   │    │    │    │    │
                   ▼    ▼    ▼    ▼    ▼
               Payment  Inventory  Shipping  Notification
```

---

## 4. Deep Technical Explanation

### Choreography vs Orchestration

| Aspect | Choreography | Orchestration |
|--------|-------------|--------------|
| Coordination | Events (each service reacts) | Central orchestrator tells each service what to do |
| Coupling | Loose (services don't know each other) | Orchestrator knows all services |
| Visibility | Hard to track end-to-end | Easy (orchestrator tracks state) |
| Debugging | Difficult (distributed events) | Easier (central state machine) |
| Complexity | Simple for few steps; complex for many | Higher initial complexity |
| Failure handling | Each service must know compensations | Orchestrator handles |
| **Use when** | Simple sagas, loose coupling needed | Complex flows, clear visibility needed |

### Compensating Transactions
A compensating transaction is NOT a rollback (SQL rollback doesn't exist across services). It's a new forward-facing transaction that semantically undoes the effect:
- Payment charged → compensate with refund
- Inventory reserved → compensate with release
- Email sent → compensate with "sorry" email (can't unsend)
- Notification sent → **cannot always compensate** (some actions are irreversible)

This is why sagas achieve eventual consistency, not ACID consistency. Partially executed sagas are visible to users briefly.

### Saga States
Track each saga instance's state:
- STARTED
- PAYMENT_PENDING → PAYMENT_CHARGED → INVENTORY_PENDING → INVENTORY_RESERVED → SHIPPING_PENDING → COMPLETED
- On failure: COMPENSATING → PAYMENT_REFUNDING → PAYMENT_REFUNDED → FAILED

---

## 5. Code Example

```php
// Orchestration Saga — Central orchestrator
class OrderSaga {
    private SagaState $state;
    
    public function execute(array $orderData): void {
        $this->state = SagaState::create($orderData['order_id']);
        
        try {
            // Step 1: Charge payment
            $this->state->transition('PAYMENT_PENDING');
            $paymentRef = $this->paymentService->charge($orderData);
            $this->state->transition('PAYMENT_CHARGED', ['payment_ref' => $paymentRef]);
            
            // Step 2: Reserve inventory
            $this->state->transition('INVENTORY_PENDING');
            $this->inventoryService->reserve($orderData);
            $this->state->transition('INVENTORY_RESERVED');
            
            // Step 3: Create shipment
            $this->state->transition('SHIPPING_PENDING');
            $this->shippingService->createShipment($orderData);
            $this->state->transition('COMPLETED');
            
        } catch (PaymentFailedException $e) {
            // Payment failed — nothing to compensate (payment wasn't charged)
            $this->state->transition('FAILED', ['reason' => $e->getMessage()]);
            
        } catch (InventoryException $e) {
            // Inventory failed — compensate: refund payment
            $this->compensatePayment($orderData);
            $this->state->transition('FAILED');
            
        } catch (ShippingException $e) {
            // Shipping failed — compensate: release inventory + refund payment
            $this->compensateInventory($orderData);
            $this->compensatePayment($orderData);
            $this->state->transition('FAILED');
        }
    }
    
    private function compensatePayment(array $data): void {
        $this->state->transition('COMPENSATING_PAYMENT');
        $paymentRef = $this->state->getData('payment_ref');
        $this->paymentService->refund($paymentRef);
        $this->state->transition('PAYMENT_REFUNDED');
    }
    
    private function compensateInventory(array $data): void {
        $this->state->transition('COMPENSATING_INVENTORY');
        $this->inventoryService->release($data['order_id']);
        $this->state->transition('INVENTORY_RELEASED');
    }
}
```

---

## 6. Trade-offs

| Aspect | 2-Phase Commit (2PC) | Saga |
|--------|---------------------|------|
| Consistency | Strong (all-or-nothing) | Eventual (partial states visible) |
| Availability | Lower (locks held) | Higher (no locks) |
| Complexity | High (distributed locking) | High (compensation logic) |
| Performance | Lower (coordination overhead) | Higher (async) |
| **Use when** | Strong consistency required | Microservices, eventual OK |

---

## 7. Interview Q&A

**Q1: Why can't you use database transactions across microservices?**
> Each microservice has its own database. A SQL transaction requires a single database connection. Distributed transactions (2PC) hold locks across services — if ServiceA locks a record and waits for ServiceB, ServiceB's lock waits for ServiceC, and so on. This creates performance issues, coupling, and availability risk (if any participant fails, all are blocked). Sagas avoid distributed locks by using compensating transactions instead of rollback.

**Q2: How do you handle a compensation that fails?**
> Compensating transactions must be retryable (idempotent). If a refund fails, retry with exponential backoff. If it keeps failing after N retries, send to a dead-letter queue for manual intervention. Keep a saga state log — support teams can see exactly what succeeded and what compensation failed. For business-critical compensations (refunds), use a dedicated compensation retry service with alerting.

**Q3: What is the difference between Saga choreography and orchestration?**
> Choreography: each service listens for events and reacts. Service A publishes "payment.charged"; Service B listens and reserves inventory. Decoupled but hard to trace. Orchestration: a central coordinator (orchestrator) calls each service step by step and handles failures. Easier to reason about and debug. Prefer orchestration for complex sagas (5+ steps), use choreography for simple sagas where loose coupling is paramount.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Saga = sequence of local transactions + compensating transactions│
│ ✓ Choreography: event-driven; Orchestration: central coordinator  │
│ ✓ Compensation ≠ rollback — it's a new forward transaction        │
│ ✓ Sagas achieve eventual consistency, not ACID                    │
│ ✓ Make compensating transactions idempotent and retriable          │
│ ✓ Track saga state — essential for debugging and support          │
└────────────────────────────────────────────────────────────────────┘
```
