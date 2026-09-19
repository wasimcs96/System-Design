<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #13 — STATE                          ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆ (Order management, ATM, vending machine)   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: An object's behavior DEPENDS on its STATE, and it must │
 * │ change behavior at runtime when its state changes.               │
 * │                                                                  │
 * │ Without State pattern:                                           │
 * │  class Order {                                                   │
 * │    function pay() {                                              │
 * │      if ($this->status === 'pending')   { ... }                 │
 * │      elseif ($this->status === 'paid')  { throw ... }           │
 * │      elseif ($this->status === 'shipped') { throw ... }         │
 * │      // Every method has a big if/switch → maintenance nightmare │
 * │    }                                                             │
 * │  }                                                               │
 * │                                                                  │
 * │ With State pattern: Each state is its own class.                 │
 * │  PendingState.pay()   → transitions to PaidState                │
 * │  PaidState.pay()      → throws "already paid"                   │
 * │  ShippedState.pay()   → throws "too late to pay"                │
 * │ → No if/switch! Adding new state = add new class only.          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Context (Order)                                                 │
 * │  ├─ state: OrderState      ← current state (changes at runtime)  │
 * │  ├─ setState(OrderState)   ← called BY states to transition      │
 * │  ├─ pay()    → delegates → state.pay(this)                      │
 * │  └─ ship()   → delegates → state.ship(this)                     │
 * │                                                                   │
 * │  State transitions:                                              │
 * │  [Pending] ──pay()──► [Paid] ──ship()──► [Shipped]             │
 * │                                    └──deliver()──► [Delivered]  │
 * │  [Any except Delivered] ──cancel()──► [Cancelled]               │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE STATE                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define State interface with all possible actions         │
 * │ STEP 2: Create ConcreteState classes — each implements ALL       │
 * │         actions (valid: change state; invalid: throw exception)  │
 * │ STEP 3: Create Context class that holds current state and        │
 * │         delegates actions to it. Expose setState() for states   │
 * │         to trigger transitions.                                  │
 * │ STEP 4: States call context.setState(new NextState()) to         │
 * │         trigger transitions internally                           │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ─── STEP 1: State Interface ──────────────────────────────────────────────────

interface OrderState
{
    public function pay(Order $order): void;
    public function ship(Order $order): void;
    public function deliver(Order $order): void;
    public function cancel(Order $order): void;
    public function refund(Order $order): void;
    public function getName(): string;
}

// ─── HELPER: InvalidTransitionException ──────────────────────────────────────

class InvalidTransitionException extends \LogicException {}

// ─── STEP 2: Concrete States ──────────────────────────────────────────────────

/**
 * PendingState: Order placed but NOT paid yet.
 * Allowed: pay, cancel
 * Invalid:  ship, deliver, refund
 */
class PendingState implements OrderState
{
    public function pay(Order $order): void
    {
        echo "  [{$order->getId()}] Payment received ✓\n";
        $order->setState(new PaidState()); // Transition to PaidState
    }

    public function ship(Order $order): void
    {
        throw new InvalidTransitionException("Cannot ship: order not paid yet.");
    }

    public function deliver(Order $order): void
    {
        throw new InvalidTransitionException("Cannot deliver: order not paid yet.");
    }

    public function cancel(Order $order): void
    {
        echo "  [{$order->getId()}] Order cancelled (was pending)\n";
        $order->setState(new CancelledState());
    }

    public function refund(Order $order): void
    {
        throw new InvalidTransitionException("Cannot refund: payment was never made.");
    }

    public function getName(): string { return 'Pending'; }
}

/**
 * PaidState: Payment confirmed, awaiting dispatch.
 * Allowed: ship, cancel, refund
 * Invalid:  pay (already paid), deliver (not shipped yet)
 */
class PaidState implements OrderState
{
    public function pay(Order $order): void
    {
        throw new InvalidTransitionException("Order is already paid.");
    }

    public function ship(Order $order): void
    {
        echo "  [{$order->getId()}] Order shipped 🚚\n";
        $order->setState(new ShippedState());
    }

    public function deliver(Order $order): void
    {
        throw new InvalidTransitionException("Cannot deliver: not shipped yet.");
    }

    public function cancel(Order $order): void
    {
        echo "  [{$order->getId()}] Order cancelled (was paid) — initiating refund\n";
        $order->setState(new CancelledState());
    }

    public function refund(Order $order): void
    {
        echo "  [{$order->getId()}] Refund initiated\n";
        $order->setState(new CancelledState());
    }

    public function getName(): string { return 'Paid'; }
}

/**
 * ShippedState: In transit.
 * Allowed: deliver
 * Invalid:  pay, ship, cancel, refund (once shipped, hard to cancel)
 */
class ShippedState implements OrderState
{
    public function pay(Order $order): void
    {
        throw new InvalidTransitionException("Order already paid and shipped.");
    }

    public function ship(Order $order): void
    {
        throw new InvalidTransitionException("Order already shipped.");
    }

    public function deliver(Order $order): void
    {
        echo "  [{$order->getId()}] Order delivered ✅\n";
        $order->setState(new DeliveredState());
    }

    public function cancel(Order $order): void
    {
        // In real life: return request is raised, not full cancel
        throw new InvalidTransitionException("Cannot cancel: already shipped. Raise a return.");
    }

    public function refund(Order $order): void
    {
        throw new InvalidTransitionException("Cannot refund: item in transit.");
    }

    public function getName(): string { return 'Shipped'; }
}

/**
 * DeliveredState: Final state (successfully completed).
 * Allowed: refund (within return window)
 * Invalid:  everything else
 */
class DeliveredState implements OrderState
{
    public function pay(Order $order): void
    {
        throw new InvalidTransitionException("Order complete.");
    }

    public function ship(Order $order): void
    {
        throw new InvalidTransitionException("Order already delivered.");
    }

    public function deliver(Order $order): void
    {
        throw new InvalidTransitionException("Already delivered.");
    }

    public function cancel(Order $order): void
    {
        throw new InvalidTransitionException("Cannot cancel: already delivered. Raise a return.");
    }

    public function refund(Order $order): void
    {
        // E.g., within 7-day return window
        echo "  [{$order->getId()}] Return/refund initiated (delivered item)\n";
        $order->setState(new CancelledState()); // In production: RefundedState
    }

    public function getName(): string { return 'Delivered'; }
}

/**
 * CancelledState: Terminal state — no transitions out.
 */
class CancelledState implements OrderState
{
    public function pay(Order $order): void
    {
        throw new InvalidTransitionException("Order cancelled, cannot pay.");
    }
    public function ship(Order $order): void
    {
        throw new InvalidTransitionException("Order cancelled.");
    }
    public function deliver(Order $order): void
    {
        throw new InvalidTransitionException("Order cancelled.");
    }
    public function cancel(Order $order): void
    {
        throw new InvalidTransitionException("Already cancelled.");
    }
    public function refund(Order $order): void
    {
        echo "  [{$order->getId()}] Refund already processed at cancellation\n";
    }

    public function getName(): string { return 'Cancelled'; }
}

// ─── STEP 3: Context ─────────────────────────────────────────────────────────

class Order
{
    private OrderState $state;
    private array      $history = [];

    public function __construct(private readonly string $id)
    {
        $this->state = new PendingState(); // Initial state
        $this->history[] = 'Pending';
        echo "  [{$this->id}] Order created — state: Pending\n";
    }

    // Called BY state objects to perform transitions
    public function setState(OrderState $state): void
    {
        $this->state     = $state;
        $this->history[] = $state->getName();
        echo "  [{$this->id}] State changed → {$state->getName()}\n";
    }

    // All business methods delegate to current state
    public function pay(): void      { $this->state->pay($this); }
    public function ship(): void     { $this->state->ship($this); }
    public function deliver(): void  { $this->state->deliver($this); }
    public function cancel(): void   { $this->state->cancel($this); }
    public function refund(): void   { $this->state->refund($this); }

    public function getId(): string       { return $this->id; }
    public function getStatus(): string   { return $this->state->getName(); }
    public function getHistory(): array   { return $this->history; }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== STATE PATTERN DEMO ===\n\n";

echo "--- Happy Path: Pending → Paid → Shipped → Delivered ---\n";
$order1 = new Order('ORD-001');
$order1->pay();
$order1->ship();
$order1->deliver();
echo "  History: " . implode(' → ', $order1->getHistory()) . "\n";

echo "\n--- Cancel flow: Pending → Cancelled ---\n";
$order2 = new Order('ORD-002');
$order2->cancel();
echo "  History: " . implode(' → ', $order2->getHistory()) . "\n";

echo "\n--- Refund after delivery ---\n";
$order3 = new Order('ORD-003');
$order3->pay();
$order3->ship();
$order3->deliver();
$order3->refund();
echo "  History: " . implode(' → ', $order3->getHistory()) . "\n";

echo "\n--- Invalid transition: try to ship before paying ---\n";
$order4 = new Order('ORD-004');
try {
    $order4->ship(); // Still pending — should throw
} catch (InvalidTransitionException $e) {
    echo "  Error caught: {$e->getMessage()} ✓\n";
}

echo "\n--- Invalid: pay twice ---\n";
$order5 = new Order('ORD-005');
$order5->pay();
try {
    $order5->pay(); // Already paid
} catch (InvalidTransitionException $e) {
    echo "  Error caught: {$e->getMessage()} ✓\n";
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the State pattern?                                   │
 * │ A: Allows an object to change its behavior when its internal     │
 * │    state changes — it appears to change its class. State logic  │
 * │    is moved from the Context into State classes.                 │
 * │                                                                  │
 * │ Q2: Where do state transitions happen — in State or Context?     │
 * │ A: In State objects (preferred). PendingState.pay() calls       │
 * │    context.setState(new PaidState()). This keeps Context clean  │
 * │    and puts each transition rule with the state that owns it.   │
 * │    Alternatively: Context contains a transition table           │
 * │    (centralizes all transitions — easier to audit).              │
 * │                                                                  │
 * │ Q3: State vs Strategy — what's the difference?                   │
 * │ A: Strategy: algorithms are independent, client chooses which   │
 * │    to use. Context doesn't drive switching.                      │
 * │    State: states are AWARE of each other and trigger transitions │
 * │    automatically based on internal conditions. The context       │
 * │    doesn't decide which state comes next — the state does.       │
 * │                                                                  │
 * │ Q4: How do you prevent invalid state transitions?                │
 * │ A: Each state implements ALL actions. Actions that are invalid   │
 * │    in that state throw InvalidTransitionException. No need for  │
 * │    if/switch in the Context — the state object enforces rules.  │
 * │                                                                  │
 * │ Q5: Real-world examples?                                         │
 * │ A: E-commerce order lifecycle (Pending→Paid→Shipped→Delivered)  │
 * │    ATM (idle→card_inserted→pin_entered→dispensing)               │
 * │    Vending machine (idle→coin_inserted→item_selected→dispensing) │
 * │    TCP connection (closed→listen→established→fin_wait)           │
 * │    Document workflow (draft→review→approved→published)           │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Terminal states (Delivered, Cancelled) throw on all actions   │
 * │ ✓ History tracking: log each transition for audit trail          │
 * │ ✓ Thread safety: setState() should be atomic in concurrent apps  │
 * └─────────────────────────────────────────────────────────────────┘
 */
