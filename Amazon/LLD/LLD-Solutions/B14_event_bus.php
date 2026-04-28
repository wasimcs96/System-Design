<?php
/**
 * B14. EVENT BUS (Publish-Subscribe)
 * ============================================================
 * PROBLEM: Decouple components using an event bus where publishers
 * emit events and multiple subscribers handle them independently.
 *
 * PATTERNS:
 *  - Observer / Pub-Sub : EventBus dispatches to all subscribers
 * ============================================================
 */

// ─── Event ────────────────────────────────────────────────────
abstract class Event {
    public readonly string    $eventId;
    public readonly \DateTime $occurredAt;

    public function __construct() {
        $this->eventId    = uniqid('EVT-');
        $this->occurredAt = new \DateTime();
    }

    abstract public function getName(): string;
}

class UserRegisteredEvent extends Event {
    public function __construct(public readonly string $userId, public readonly string $email) {
        parent::__construct();
    }
    public function getName(): string { return 'user.registered'; }
}

class OrderPlacedEvent extends Event {
    public function __construct(public readonly string $orderId, public readonly float $amount) {
        parent::__construct();
    }
    public function getName(): string { return 'order.placed'; }
}

class PaymentFailedEvent extends Event {
    public function __construct(public readonly string $orderId, public readonly string $reason) {
        parent::__construct();
    }
    public function getName(): string { return 'payment.failed'; }
}

// ─── Event Handler Interface ──────────────────────────────────
interface EventHandler {
    public function handle(Event $event): void;
    public function getSubscribedEvent(): string;
}

// ─── Concrete Handlers ────────────────────────────────────────
class WelcomeEmailHandler implements EventHandler {
    public function handle(Event $event): void {
        /** @var UserRegisteredEvent $event */
        echo "  📧 Welcome email sent to {$event->email}\n";
    }
    public function getSubscribedEvent(): string { return 'user.registered'; }
}

class UserAnalyticsHandler implements EventHandler {
    public function handle(Event $event): void {
        /** @var UserRegisteredEvent $event */
        echo "  📊 Analytics: new user {$event->userId} registered\n";
    }
    public function getSubscribedEvent(): string { return 'user.registered'; }
}

class InventoryReservationHandler implements EventHandler {
    public function handle(Event $event): void {
        /** @var OrderPlacedEvent $event */
        echo "  📦 Inventory reserved for order {$event->orderId}\n";
    }
    public function getSubscribedEvent(): string { return 'order.placed'; }
}

class OrderNotificationHandler implements EventHandler {
    public function handle(Event $event): void {
        /** @var OrderPlacedEvent $event */
        echo "  🔔 Order #{$event->orderId} confirmation sent (₹{$event->amount})\n";
    }
    public function getSubscribedEvent(): string { return 'order.placed'; }
}

class PaymentFailureAlertHandler implements EventHandler {
    public function handle(Event $event): void {
        /** @var PaymentFailedEvent $event */
        echo "  🚨 Alert: Payment failed for order {$event->orderId}: {$event->reason}\n";
    }
    public function getSubscribedEvent(): string { return 'payment.failed'; }
}

// ─── Event Bus ────────────────────────────────────────────────
class EventBus {
    /** @var array<string,EventHandler[]> eventName → handlers */
    private array $handlers    = [];
    /** @var Event[] */
    private array $eventLog    = [];
    private bool  $asyncMode   = false; // Could queue events for async processing

    private static ?EventBus $instance = null;

    public static function getInstance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {}

    public function subscribe(EventHandler $handler): void {
        $event = $handler->getSubscribedEvent();
        $this->handlers[$event][] = $handler;
    }

    public function unsubscribe(EventHandler $handler): void {
        $event = $handler->getSubscribedEvent();
        $this->handlers[$event] = array_values(array_filter(
            $this->handlers[$event] ?? [],
            fn($h) => $h !== $handler
        ));
    }

    public function publish(Event $event): void {
        $this->eventLog[] = $event;
        $name = $event->getName();
        echo "  [BUS] Event: {$name} ({$event->eventId})\n";
        foreach ($this->handlers[$name] ?? [] as $handler) {
            $handler->handle($event);
        }
    }

    public function getEventLog(): array { return $this->eventLog; }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B14. Event Bus (Pub/Sub) ===\n\n";

$bus = EventBus::getInstance();
$bus->subscribe(new WelcomeEmailHandler());
$bus->subscribe(new UserAnalyticsHandler());
$bus->subscribe(new InventoryReservationHandler());
$bus->subscribe(new OrderNotificationHandler());
$bus->subscribe(new PaymentFailureAlertHandler());

echo "--- User registers ---\n";
$bus->publish(new UserRegisteredEvent('U001', 'alice@example.com'));

echo "\n--- Order placed ---\n";
$bus->publish(new OrderPlacedEvent('ORD-001', 1299.99));

echo "\n--- Payment fails ---\n";
$bus->publish(new PaymentFailedEvent('ORD-002', 'Card declined'));

echo "\nTotal events published: " . count($bus->getEventLog()) . "\n";
