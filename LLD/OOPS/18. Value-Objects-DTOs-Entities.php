<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║     OOP CONCEPT #18 — VALUE OBJECTS, DTOs & ENTITIES             ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Advanced (Domain-Driven Design)                     ║
 * ║  FREQUENCY : ★★★★★  (#1 LLD DOMAIN MODELING topic in interviews)║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * DOMAIN MODELING — 3 fundamental building blocks:
 *
 * 1. VALUE OBJECT (VO)
 *    - Defined by its VALUE, not identity
 *    - IMMUTABLE — never change after creation
 *    - Two VOs with same value ARE equal
 *    - No ID field
 *    - Examples: Money, Email, PhoneNumber, Address, Color, DateRange
 *
 * 2. ENTITY
 *    - Defined by its IDENTITY (ID), not value
 *    - MUTABLE — state can change over time
 *    - Two entities are equal if they have the SAME ID
 *    - Has an ID field
 *    - Examples: User, Order, Product, Invoice
 *
 * 3. DATA TRANSFER OBJECT (DTO)
 *    - Pure data carrier — no behavior
 *    - Used to transfer data between layers (Controller → Service → DB)
 *    - Validated at the boundary (API controller)
 *    - Examples: RegisterRequest, OrderResponse, UserDTO
 *
 * ┌────────────────┬────────────┬──────────────┬───────────────────┐
 * │                │ VO         │ Entity       │ DTO               │
 * ├────────────────┼────────────┼──────────────┼───────────────────┤
 * │ Identity       │ By value   │ By ID        │ None              │
 * │ Mutable?       │ No         │ Yes          │ Yes (or readonly) │
 * │ Equality       │ Value      │ ID           │ N/A               │
 * │ Has behavior?  │ Yes        │ Yes          │ No                │
 * │ Persistence    │ Embedded   │ Own table    │ Not stored        │
 * └────────────────┴────────────┴──────────────┴───────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// 1. VALUE OBJECTS — Immutable, equality by value
// ═══════════════════════════════════════════════════════════════

echo "=== VALUE OBJECTS / DTOs / ENTITIES DEMO ===\n\n";

// ── Email VO ──────────────────────────────────────────────────
final class Email
{
    private string $value;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: {$email}");
        }
        $this->value = strtolower(trim($email));
    }

    public function getValue(): string    { return $this->value; }
    public function getDomain(): string   { return explode('@', $this->value)[1]; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string  { return $this->value; }
}

// ── Money VO ──────────────────────────────────────────────────
final class Money
{
    public function __construct(
        private readonly int    $amount,    // stored in smallest unit (paise)
        private readonly string $currency
    ) {
        if ($amount < 0) throw new \InvalidArgumentException("Amount cannot be negative");
        if (!in_array($currency, ['INR', 'USD', 'EUR'])) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    public static function inINR(float $rupees): self
    {
        return new self((int) round($rupees * 100), 'INR');
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->amount - $other->amount;
        if ($result < 0) throw new \RuntimeException("Result would be negative");
        return new self($result, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \RuntimeException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }

    public function format(): string
    {
        return $this->currency . ' ' . number_format($this->amount / 100, 2);
    }

    public function __toString(): string { return $this->format(); }
    public function getAmount(): int     { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
}

// ── Address VO ────────────────────────────────────────────────
final class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $pincode,
        public readonly string $country = 'India'
    ) {
        if (!preg_match('/^\d{6}$/', $pincode)) {
            throw new \InvalidArgumentException("Invalid pincode: {$pincode}");
        }
    }

    public function withCity(string $city): self
    {
        // Immutable update — return new instance
        return new self($this->street, $city, $this->state, $this->pincode, $this->country);
    }

    public function equals(self $other): bool
    {
        return $this->street   === $other->street
            && $this->city     === $other->city
            && $this->pincode  === $other->pincode;
    }

    public function format(): string
    {
        return "{$this->street}, {$this->city}, {$this->state} {$this->pincode}";
    }
}

echo "--- 1. Value Objects ---\n";

$email1 = new Email('Alice@EXAMPLE.com');
$email2 = new Email('alice@example.com');  // normalized same value
$email3 = new Email('bob@example.com');

echo "  email1: {$email1}\n";
echo "  email1 == email2? " . ($email1->equals($email2) ? 'YES' : 'NO') . "\n";  // YES
echo "  email1 == email3? " . ($email1->equals($email3) ? 'YES' : 'NO') . "\n";  // NO
echo "  domain: " . $email1->getDomain() . "\n";

$price    = Money::inINR(1000);
$tax      = $price->multiply(0.18);
$total    = $price->add($tax);
$discount = Money::inINR(50);

echo "  Price:    {$price}\n";
echo "  Tax 18%:  {$tax}\n";
echo "  Total:    {$total}\n";
echo "  After discount: " . $total->subtract($discount) . "\n";
echo "  price == price? " . ($price->equals(Money::inINR(1000)) ? 'YES' : 'NO') . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. ENTITIES — Mutable, equality by ID
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Entities ---\n";

class OrderId
{
    private string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? ('ORD-' . strtoupper(bin2hex(random_bytes(4))));
    }

    public function getValue(): string     { return $this->value; }
    public function __toString(): string   { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
}

class OrderItem
{
    public function __construct(
        public readonly string $productName,
        public readonly int    $quantity,
        public readonly Money  $unitPrice
    ) {}

    public function total(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}

class Order  // Entity — identity matters
{
    private OrderId $id;
    private array   $items     = [];
    private string  $status    = 'pending';
    private string  $createdAt;
    private ?string $placedAt  = null;

    public function __construct(private Email $customerEmail)
    {
        $this->id        = new OrderId();
        $this->createdAt = date('Y-m-d H:i:s');
    }

    public function addItem(string $product, int $qty, Money $price): void
    {
        if ($this->status !== 'pending') {
            throw new \RuntimeException("Cannot modify {$this->status} order");
        }
        $this->items[] = new OrderItem($product, $qty, $price);
    }

    public function place(): void
    {
        if (empty($this->items)) throw new \RuntimeException("Cannot place empty order");
        $this->status   = 'placed';
        $this->placedAt = date('Y-m-d H:i:s');
    }

    public function cancel(): void
    {
        if ($this->status === 'shipped') throw new \RuntimeException("Cannot cancel shipped order");
        $this->status = 'cancelled';
    }

    public function total(): Money
    {
        $total = Money::inINR(0);
        foreach ($this->items as $item) {
            $total = $total->add($item->total());
        }
        return $total;
    }

    public function getId(): OrderId    { return $this->id; }
    public function getStatus(): string { return $this->status; }
    public function getItemCount(): int { return count($this->items); }

    // ENTITY equality = same ID (even if state differs)
    public function equals(self $other): bool { return $this->id->equals($other->id); }

    public function summary(): string
    {
        return "Order[{$this->id}] for {$this->customerEmail} | "
             . "{$this->status} | {$this->getItemCount()} items | Total: {$this->total()}";
    }
}

$order = new Order(new Email('alice@example.com'));
$order->addItem('Laptop',  1, Money::inINR(85000));
$order->addItem('Mouse',   2, Money::inINR(500));
$order->addItem('Keyboard',1, Money::inINR(2000));

echo "  Before: " . $order->summary() . "\n";
$order->place();
echo "  After:  " . $order->summary() . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. DATA TRANSFER OBJECTS — Pure data carriers
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. DTOs ---\n";

// REQUEST DTO — incoming API data (validated at controller boundary)
final class CreateOrderRequest
{
    public function __construct(
        public readonly string $customerEmail,
        public readonly array  $items,       // [{product, qty, price}]
        public readonly string $shippingCity
    ) {}

    public static function fromArray(array $data): self
    {
        // Validation at the boundary
        if (empty($data['customer_email'])) throw new \InvalidArgumentException("Email required");
        if (empty($data['items']))          throw new \InvalidArgumentException("Items required");

        return new self(
            $data['customer_email'],
            $data['items'],
            $data['shipping_city'] ?? 'Unknown'
        );
    }
}

// RESPONSE DTO — outgoing API data (serialization-friendly)
final class OrderResponse
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $status,
        public readonly string $total,
        public readonly int    $itemCount,
        public readonly string $customerEmail
    ) {}

    public static function fromOrder(Order $order): self
    {
        return new self(
            (string) $order->getId(),
            $order->getStatus(),
            (string) $order->total(),
            $order->getItemCount(),
            'customer@example.com'  // simplified
        );
    }

    public function toArray(): array
    {
        return [
            'order_id'   => $this->orderId,
            'status'     => $this->status,
            'total'      => $this->total,
            'item_count' => $this->itemCount,
        ];
    }
}

// Simulated controller using DTOs
$request = CreateOrderRequest::fromArray([
    'customer_email' => 'bob@example.com',
    'items'          => [['product' => 'Phone', 'qty' => 1, 'price' => 25000]],
    'shipping_city'  => 'Mumbai',
]);

// Service processes the DTO
$newOrder = new Order(new Email($request->customerEmail));
foreach ($request->items as $item) {
    $newOrder->addItem($item['product'], $item['qty'], Money::inINR($item['price']));
}
$newOrder->place();

// Return DTO
$response = OrderResponse::fromOrder($newOrder);
echo "  Response: " . json_encode($response->toArray()) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ LLD DESIGN TIP: When to use each pattern                        │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ VALUE OBJECT when:                                               │
 * │   ✓ Concept is defined by its VALUE (Money=₹100, not object ID) │
 * │   ✓ Data should be immutable and self-validating                │
 * │   ✓ You want to replace primitive obsession (string email,     │
 * │     int amount, etc.) with expressive domain types              │
 * │                                                                  │
 * │ ENTITY when:                                                     │
 * │   ✓ Object has a lifecycle (order goes from pending → placed)  │
 * │   ✓ Identity matters over time (same user, different data)     │
 * │   ✓ Object is stored with a unique ID in a database            │
 * │                                                                  │
 * │ DTO when:                                                        │
 * │   ✓ Transferring data across layer boundaries                  │
 * │   ✓ API request/response shapes                                 │
 * │   ✓ Avoiding exposing domain objects directly to API clients   │
 * │                                                                  │
 * │ INTERVIEW Q&A:                                                   │
 * │ Q: What is "Primitive Obsession" and how do VOs solve it?       │
 * │ A: Using raw primitives (string, int) for domain concepts.     │
 * │    e.g., $email = 'not-valid' passes string validation but not │
 * │    email validation. Email VO encapsulates both the value AND   │
 * │    its validation rule — you can't have an invalid Email VO.   │
 * │                                                                  │
 * │ Q: What's the difference between Entity and VO?                 │
 * │ A: Entity = identity-based (same ID = same entity regardless   │
 * │    of data changes). VO = value-based (same value = same VO,   │
 * │    identity doesn't matter). Money(100, INR) == Money(100, INR)│
 * │    but User(id:1) != User(id:2) even if both have name='Alice'.│
 * └─────────────────────────────────────────────────────────────────┘
 */
