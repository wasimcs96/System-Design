<?php
/**
 * C6. ORDER MANAGEMENT SYSTEM
 * ============================================================
 * PROBLEM: End-to-end order lifecycle: cart → order → payment →
 * fulfilment → return/refund.
 *
 * PATTERNS:
 *  - State    : Order lifecycle state machine
 *  - Observer : Notify warehouse, customer, finance on status changes
 *  - Strategy : PaymentGateway
 * ============================================================
 */

enum OrderState: string {
    case CART         = 'Cart';
    case PLACED       = 'Placed';
    case PAID         = 'Paid';
    case PROCESSING   = 'Processing';
    case SHIPPED      = 'Shipped';
    case DELIVERED    = 'Delivered';
    case RETURN_REQ   = 'ReturnRequested';
    case REFUNDED     = 'Refunded';
    case CANCELLED    = 'Cancelled';
}

// ─── Payment Strategy ─────────────────────────────────────────
interface PaymentGateway {
    public function charge(string $orderId, float $amount): bool;
    public function refund(string $orderId, float $amount): bool;
}

class MockPaymentGateway implements PaymentGateway {
    public function charge(string $orderId, float $amount): bool {
        echo "  💳 Payment gateway: charged ₹{$amount} for order {$orderId}\n";
        return true;
    }
    public function refund(string $orderId, float $amount): bool {
        echo "  💳 Payment gateway: refunded ₹{$amount} for order {$orderId}\n";
        return true;
    }
}

// ─── Observer ─────────────────────────────────────────────────
interface OrderObserver {
    public function onStateChange(Order $order, OrderState $old, OrderState $new): void;
}

class WarehouseNotifier implements OrderObserver {
    public function onStateChange(Order $order, OrderState $old, OrderState $new): void {
        if ($new === OrderState::PAID) echo "  🏭 Warehouse: begin fulfilment for {$order->orderId}\n";
        if ($new === OrderState::RETURN_REQ) echo "  🏭 Warehouse: prepare return label\n";
    }
}

class CustomerNotifier implements OrderObserver {
    public function onStateChange(Order $order, OrderState $old, OrderState $new): void {
        echo "  📱 Customer [{$order->customerId}]: Order {$order->orderId} → {$new->value}\n";
    }
}

// ─── Order Line Item ──────────────────────────────────────────
class OrderLineItem {
    public function __construct(
        public readonly string $productId,
        public readonly string $name,
        public readonly float  $price,
        public readonly int    $qty
    ) {}
    public function getTotal(): float { return $this->price * $this->qty; }
}

// ─── Order ────────────────────────────────────────────────────
class Order {
    public readonly string $orderId;
    private OrderState     $state = OrderState::CART;
    /** @var OrderLineItem[] */
    private array          $items     = [];
    /** @var OrderObserver[] */
    private array          $observers = [];
    private ?string        $trackingNo = null;

    public function __construct(
        public readonly string         $customerId,
        private readonly PaymentGateway $payment
    ) {
        $this->orderId = uniqid('ORD-');
    }

    public function addObserver(OrderObserver $obs): void { $this->observers[] = $obs; }
    public function addItem(OrderLineItem $item): void    { $this->items[] = $item; }

    public function getTotal(): float {
        return array_sum(array_map(fn($i) => $i->getTotal(), $this->items));
    }

    private function transition(OrderState $newState): void {
        $old = $this->state;
        $this->state = $newState;
        foreach ($this->observers as $obs) $obs->onStateChange($this, $old, $newState);
    }

    public function place(): bool {
        if ($this->state !== OrderState::CART || empty($this->items)) return false;
        $this->transition(OrderState::PLACED);
        return true;
    }

    public function pay(): bool {
        if ($this->state !== OrderState::PLACED) return false;
        if (!$this->payment->charge($this->orderId, $this->getTotal())) return false;
        $this->transition(OrderState::PAID);
        return true;
    }

    public function process(): void { $this->transition(OrderState::PROCESSING); }

    public function ship(string $trackingNo): void {
        $this->trackingNo = $trackingNo;
        $this->transition(OrderState::SHIPPED);
        echo "  🚚 Tracking: {$trackingNo}\n";
    }

    public function deliver(): void { $this->transition(OrderState::DELIVERED); }

    public function requestReturn(): bool {
        if ($this->state !== OrderState::DELIVERED) return false;
        $this->transition(OrderState::RETURN_REQ);
        return true;
    }

    public function refund(): bool {
        if ($this->state !== OrderState::RETURN_REQ) return false;
        $this->payment->refund($this->orderId, $this->getTotal());
        $this->transition(OrderState::REFUNDED);
        return true;
    }

    public function cancel(): bool {
        if (in_array($this->state, [OrderState::SHIPPED, OrderState::DELIVERED])) return false;
        $this->transition(OrderState::CANCELLED);
        return true;
    }

    public function getState(): OrderState { return $this->state; }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C6. Order Management System ===\n\n";

$gateway = new MockPaymentGateway();
$order   = new Order('CUST-001', $gateway);
$order->addObserver(new CustomerNotifier());
$order->addObserver(new WarehouseNotifier());

$order->addItem(new OrderLineItem('P1', 'Headphones', 2999.0, 1));
$order->addItem(new OrderLineItem('P2', 'USB Cable',    199.0, 2));

echo "Total: ₹{$order->getTotal()}\n\n";

echo "--- Happy path ---\n";
$order->place();
$order->pay();
$order->process();
$order->ship('TRACK-XYZ-789');
$order->deliver();

echo "\n--- Return & Refund ---\n";
$order->requestReturn();
$order->refund();
echo "Final state: " . $order->getState()->value . "\n";
