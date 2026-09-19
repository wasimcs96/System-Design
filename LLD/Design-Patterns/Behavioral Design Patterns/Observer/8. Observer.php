<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #8 — OBSERVER                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★★ (Very commonly asked)                       ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: Object A changes state → Objects B, C, D need to know. │
 * │                                                                  │
 * │ Bad approach: A directly calls B.update(), C.update(), D.update()│
 * │   → A is tightly coupled to B, C, D                             │
 * │   → Adding a new observer requires modifying A                  │
 * │                                                                  │
 * │ Observer Pattern: A (Subject) maintains a list of Observers.    │
 * │ When A changes, it calls notify() → all registered Observers    │
 * │ update themselves. A doesn't know WHO the observers are.        │
 * │                                                                  │
 * │ Example:                                                         │
 * │  - Order placed → notify: Email service, Warehouse, Analytics   │
 * │  - Stock price changes → notify: UI display, Alert service      │
 * │  - User logged in → notify: Audit log, Session manager          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Subject (interface)           Observer (interface)             │
 * │  ├─ attach(Observer)           └─ update(event, data)           │
 * │  ├─ detach(Observer)                                             │
 * │  └─ notify()                                                     │
 * │        │                                                          │
 * │        ▼                                                          │
 * │  OrderSubject ──notify()──► EmailObserver.update()              │
 * │                         ──► WarehouseObserver.update()          │
 * │                         ──► AnalyticsObserver.update()          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE OBSERVER                             │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Observer interface with update() method          │
 * │ STEP 2: Define Subject interface with attach/detach/notify      │
 * │ STEP 3: Create ConcreteSubject (holds state + observer list)    │
 * │ STEP 4: Create ConcreteObservers that implement Observer        │
 * │ STEP 5: Register observers, trigger state changes, they auto-   │
 * │         update                                                   │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// REAL-WORLD EXAMPLE: E-commerce Order Status System
// When an order changes status, multiple systems react
// ═══════════════════════════════════════════════════════════════

// STEP 1: Observer interface
interface OrderObserver
{
    /**
     * Called when the subject (order) changes state.
     *
     * @param string $event  e.g., 'order.placed', 'order.shipped'
     * @param array  $data   event payload (orderId, total, etc.)
     */
    public function update(string $event, array $data): void;
}

// STEP 2: Subject interface
interface OrderSubjectInterface
{
    public function attach(string $event, OrderObserver $observer): void;
    public function detach(string $event, OrderObserver $observer): void;
    public function notify(string $event, array $data): void;
}

// STEP 3: ConcreteSubject — holds order data + observer list
class Order implements OrderSubjectInterface
{
    private array $observers = []; // ['event' => [Observer, Observer, ...]]
    private string $status   = 'pending';

    public function __construct(
        private readonly string $id,
        private readonly string $customerId,
        private float           $total
    ) {}

    // Register an observer for a specific event
    public function attach(string $event, OrderObserver $observer): void
    {
        $this->observers[$event][] = $observer;
    }

    // Remove an observer
    public function detach(string $event, OrderObserver $observer): void
    {
        if (!isset($this->observers[$event])) return;
        $this->observers[$event] = array_filter(
            $this->observers[$event],
            fn($o) => $o !== $observer
        );
    }

    // Notify all observers listening for this event
    public function notify(string $event, array $data): void
    {
        foreach ($this->observers[$event] ?? [] as $observer) {
            $observer->update($event, $data);
        }
    }

    // Business method — changing status triggers notifications
    public function place(): void
    {
        $this->status = 'placed';
        $this->notify('order.placed', [
            'orderId'    => $this->id,
            'customerId' => $this->customerId,
            'total'      => $this->total,
        ]);
    }

    public function pay(string $transactionId): void
    {
        $this->status = 'paid';
        $this->notify('order.paid', [
            'orderId'       => $this->id,
            'customerId'    => $this->customerId,
            'total'         => $this->total,
            'transactionId' => $transactionId,
        ]);
    }

    public function ship(string $trackingNo, string $carrier): void
    {
        $this->status = 'shipped';
        $this->notify('order.shipped', [
            'orderId'    => $this->id,
            'customerId' => $this->customerId,
            'trackingNo' => $trackingNo,
            'carrier'    => $carrier,
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->status = 'cancelled';
        $this->notify('order.cancelled', [
            'orderId'    => $this->id,
            'customerId' => $this->customerId,
            'reason'     => $reason,
        ]);
    }

    public function getStatus(): string { return $this->status; }
    public function getId(): string     { return $this->id; }
}

// STEP 4: Concrete Observers

class EmailNotificationObserver implements OrderObserver
{
    public function __construct(private string $customerEmail) {}

    public function update(string $event, array $data): void
    {
        $subject = match ($event) {
            'order.placed'    => "Your order #{$data['orderId']} has been placed!",
            'order.paid'      => "Payment confirmed for #{$data['orderId']}",
            'order.shipped'   => "Your order #{$data['orderId']} is on its way! Tracking: {$data['trackingNo']}",
            'order.cancelled' => "Order #{$data['orderId']} cancelled: {$data['reason']}",
            default           => "Order update: $event",
        };
        echo "  [Email → {$this->customerEmail}]: $subject\n";
    }
}

class WarehouseObserver implements OrderObserver
{
    public function update(string $event, array $data): void
    {
        if ($event === 'order.placed') {
            echo "  [Warehouse]: NEW ORDER #{$data['orderId']} — prepare for packing\n";
        } elseif ($event === 'order.cancelled') {
            echo "  [Warehouse]: CANCEL ORDER #{$data['orderId']} — restock items\n";
        }
    }
}

class AnalyticsObserver implements OrderObserver
{
    private static array $metrics = [];

    public function update(string $event, array $data): void
    {
        self::$metrics[$event]          = (self::$metrics[$event] ?? 0) + 1;
        self::$metrics['total_revenue'] = (self::$metrics['total_revenue'] ?? 0.0)
            + ($data['total'] ?? 0);
        echo "  [Analytics]: Tracked '$event' | Revenue: ₹{$data['total']}\n";
    }

    public static function getMetrics(): array { return self::$metrics; }
}

class FraudDetectionObserver implements OrderObserver
{
    public function update(string $event, array $data): void
    {
        if ($event !== 'order.placed') return;

        if ($data['total'] > 50000) {
            echo "  [Fraud]: HIGH VALUE order #{$data['orderId']} (₹{$data['total']}) — flagged for review\n";
        } else {
            echo "  [Fraud]: Order #{$data['orderId']} passed risk check ✓\n";
        }
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== OBSERVER PATTERN DEMO ===\n\n";

// Create an order (Subject)
$order = new Order('ORD-2024-001', 'CUST-001', 1299.99);

// Register observers for different events
$email      = new EmailNotificationObserver('alice@example.com');
$warehouse  = new WarehouseObserver();
$analytics  = new AnalyticsObserver();
$fraud      = new FraudDetectionObserver();

// Attach observers (event → observer mapping)
$order->attach('order.placed',    $email);
$order->attach('order.placed',    $warehouse);
$order->attach('order.placed',    $analytics);
$order->attach('order.placed',    $fraud);
$order->attach('order.paid',      $email);
$order->attach('order.paid',      $analytics);
$order->attach('order.shipped',   $email);
$order->attach('order.cancelled', $email);
$order->attach('order.cancelled', $warehouse);

echo "--- Order Placed ---\n";
$order->place();

echo "\n--- Order Paid ---\n";
$order->pay('TXN_ABC123');

echo "\n--- Order Shipped ---\n";
$order->ship('TRACK-XYZ789', 'FedEx');

echo "\n--- High-value order (fraud check) ---\n";
$bigOrder = new Order('ORD-2024-002', 'CUST-002', 75000.00);
$bigOrder->attach('order.placed', $fraud);
$bigOrder->attach('order.placed', $analytics);
$bigOrder->place();

echo "\n--- Detach email observer, then cancel ---\n";
$order->detach('order.cancelled', $email);
$order->cancel('Customer request');
echo "  (Email NOT sent after detach)\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Observer pattern?                                │
 * │ A: A behavioral pattern where a Subject maintains a list of     │
 * │    Observers. When the Subject changes state, it notifies all   │
 * │    Observers automatically. Subject doesn't know the concrete   │
 * │    type of its observers — only the Observer interface.          │
 * │                                                                  │
 * │ Q2: What is the difference between Push vs Pull model?           │
 * │ A: Push: Subject sends all relevant data in notify() call.      │
 * │    Observer receives data, doesn't need to query Subject.        │
 * │    Pull: Subject sends minimal signal, Observer calls back to   │
 * │    fetch what it needs (Subject provides getters).               │
 * │    Push is simpler; Pull is more flexible when observers need   │
 * │    different data.                                               │
 * │                                                                  │
 * │ Q3: Observer vs Event Bus / Pub-Sub?                             │
 * │ A: Observer: Subject holds direct references to observers.       │
 * │    Tight coupling between Subject and Observer (not anonymous). │
 * │    Event Bus: Observers subscribe by topic name (string).        │
 * │    Subject and observer don't know each other at all. Looser.   │
 * │                                                                  │
 * │ Q4: What is the memory leak risk in Observer?                    │
 * │ A: If observers hold references to subjects and vice versa,     │
 * │    and you forget to detach(), PHP's garbage collector may not  │
 * │    free them (circular reference). Always detach when done.     │
 * │    Use WeakReference for long-lived subjects.                   │
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: Laravel Events: Event::dispatch() → listeners notified.      │
 * │    WordPress hooks: add_action(), do_action().                   │
 * │    PHP SPL: SplSubject, SplObserver interfaces.                  │
 * │    Doctrine: EntityManager lifecycle events (prePersist, etc.)  │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Detach before an observer goes out of scope to prevent leaks  │
 * │ ✓ Exception in one observer should not break others — wrap in   │
 * │   try/catch in notify() loop                                     │
 * │ ✓ Recursive notifications (observer triggers subject) → guard   │
 * │   with a "notifying" boolean flag                                │
 * └─────────────────────────────────────────────────────────────────┘
 */
