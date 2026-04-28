<?php
/**
 * B9. FOOD DELIVERY SYSTEM (like Swiggy/Zomato)
 * ============================================================
 * PROBLEM: Restaurant listing, menu management, order placement,
 * delivery tracking with real-time status updates.
 *
 * PATTERNS:
 *  - Observer : OrderStatus updates notify customer + delivery agent
 *  - Strategy : DeliveryAssignment (nearest agent, round-robin)
 * ============================================================
 */

enum OrderStatus: string {
    case PLACED      = 'Placed';
    case CONFIRMED   = 'Confirmed';
    case PREPARING   = 'Preparing';
    case OUT_FOR_DEL = 'OutForDelivery';
    case DELIVERED   = 'Delivered';
    case CANCELLED   = 'Cancelled';
}

// ─── Menu Item ──────────────────────────────────────────────────
class MenuItem {
    public function __construct(
        public readonly string $itemId,
        public readonly string $name,
        public readonly float  $price,
        public bool            $available = true
    ) {}
}

// ─── Restaurant ─────────────────────────────────────────────────
class Restaurant {
    /** @var MenuItem[] itemId → item */
    private array $menu = [];

    public function __construct(
        public readonly string $restaurantId,
        public readonly string $name,
        public readonly string $address
    ) {}

    public function addItem(MenuItem $item): void { $this->menu[$item->itemId] = $item; }
    public function getItem(string $id): ?MenuItem { return $this->menu[$id] ?? null; }
    public function getMenu(): array { return $this->menu; }
}

// ─── Order Item ────────────────────────────────────────────────
class OrderItem {
    public function __construct(
        public readonly MenuItem $menuItem,
        public readonly int      $quantity
    ) {}
    public function getSubtotal(): float { return $this->menuItem->price * $this->quantity; }
}

// ─── Observer ─────────────────────────────────────────────────
interface OrderObserver {
    public function onStatusChange(Order $order, OrderStatus $newStatus): void;
}

class CustomerNotifier implements OrderObserver {
    public function onStatusChange(Order $order, OrderStatus $newStatus): void {
        echo "  📱 Customer[{$order->customerId}]: Order #{$order->orderId} → {$newStatus->value}\n";
    }
}

class DeliveryAgentNotifier implements OrderObserver {
    public function onStatusChange(Order $order, OrderStatus $newStatus): void {
        if ($newStatus === OrderStatus::OUT_FOR_DEL) {
            echo "  🛵 Agent: Pick up order #{$order->orderId} from {$order->restaurant->name}\n";
        }
    }
}

// ─── Order ──────────────────────────────────────────────────────
class Order {
    public readonly string $orderId;
    private OrderStatus    $status = OrderStatus::PLACED;
    /** @var OrderObserver[] */
    private array $observers = [];
    /** @var OrderItem[] */
    private array $items     = [];

    public function __construct(
        public readonly string     $customerId,
        public readonly Restaurant $restaurant
    ) {
        $this->orderId = uniqid('ORD-');
    }

    public function addItem(OrderItem $item): void { $this->items[] = $item; }

    public function getTotal(): float {
        return array_sum(array_map(fn($i) => $i->getSubtotal(), $this->items));
    }

    public function addObserver(OrderObserver $obs): void { $this->observers[] = $obs; }

    public function updateStatus(OrderStatus $newStatus): void {
        $this->status = $newStatus;
        foreach ($this->observers as $obs) $obs->onStatusChange($this, $newStatus);
    }

    public function getStatus(): OrderStatus { return $this->status; }
}

// ─── Delivery Service ──────────────────────────────────────────
class DeliveryService {
    public function assignAgent(Order $order): string {
        // Simplified: just return a mock agent ID
        return 'AGENT-' . rand(1, 10);
    }
}

// ─── Order Service (Facade) ───────────────────────────────────
class OrderService {
    public function __construct(private DeliveryService $delivery) {}

    public function placeOrder(
        string     $customerId,
        Restaurant $restaurant,
        array      $itemsWithQty // [[itemId, qty], ...]
    ): Order {
        $order = new Order($customerId, $restaurant);
        $order->addObserver(new CustomerNotifier());
        $order->addObserver(new DeliveryAgentNotifier());

        foreach ($itemsWithQty as [$itemId, $qty]) {
            $item = $restaurant->getItem($itemId);
            if (!$item || !$item->available) {
                echo "  ✗ Item $itemId unavailable\n"; continue;
            }
            $order->addItem(new OrderItem($item, $qty));
        }

        echo "  ✓ Order placed: #{$order->orderId} | Total: ₹{$order->getTotal()}\n";
        return $order;
    }

    public function processOrder(Order $order): void {
        $order->updateStatus(OrderStatus::CONFIRMED);
        $order->updateStatus(OrderStatus::PREPARING);
        $agent = $this->delivery->assignAgent($order);
        echo "  Assigned agent: $agent\n";
        $order->updateStatus(OrderStatus::OUT_FOR_DEL);
        $order->updateStatus(OrderStatus::DELIVERED);
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B9. Food Delivery System ===\n\n";

$rest = new Restaurant('R001', 'Masala Kitchen', '123 MG Road');
$rest->addItem(new MenuItem('M01', 'Biryani',  250.0));
$rest->addItem(new MenuItem('M02', 'Paneer',   180.0));
$rest->addItem(new MenuItem('M03', 'Lassi',     60.0));

$service = new OrderService(new DeliveryService());
$order   = $service->placeOrder('C001', $rest, [['M01', 2], ['M03', 1]]);
echo "\n";
$service->processOrder($order);
