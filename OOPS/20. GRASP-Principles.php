<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          OOP CONCEPT #20 — GRASP PRINCIPLES                      ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Advanced                                            ║
 * ║  FREQUENCY : ★★★★☆  (Asked in L5+ / Staff engineer interviews)  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * GRASP = General Responsibility Assignment Software Patterns
 * By Craig Larman — 9 principles for assigning responsibilities to classes.
 *
 * WHERE SOLID tells you WHAT good design looks like,
 * GRASP tells you HOW to assign responsibilities when designing a system.
 *
 * 9 GRASP PRINCIPLES:
 *   1. Information Expert     — Give responsibility to who has the data
 *   2. Creator                — Who creates B? Whoever uses/owns B
 *   3. Controller             — Handle system events via a controller object
 *   4. Low Coupling           — Minimize dependencies between classes
 *   5. High Cohesion          — Keep related responsibilities together
 *   6. Polymorphism           — Use type-based variation, not if/switch
 *   7. Pure Fabrication       — Create a class that doesn't exist in domain
 *   8. Indirection            — Introduce mediator to decouple two classes
 *   9. Protected Variations   — Wrap unstable things with stable interfaces
 *
 * GRASP vs SOLID:
 *   SOLID = design principles (rules for individual classes/relationships)
 *   GRASP = responsibility assignment patterns (who should own what logic?)
 */

echo "=== GRASP PRINCIPLES DEMO ===\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. INFORMATION EXPERT
//    "Assign responsibility to the class that has the data to fulfill it."
// ═══════════════════════════════════════════════════════════════

echo "--- 1. Information Expert ---\n";

class OrderLine
{
    public function __construct(
        private string $product,
        private int    $quantity,
        private float  $unitPrice
    ) {}

    // OrderLine KNOWS its own data → it calculates its OWN subtotal (Information Expert)
    public function subtotal(): float { return $this->quantity * $this->unitPrice; }
    public function getProduct(): string { return $this->product; }
}

class ShoppingCart
{
    private array $lines = [];

    public function addItem(string $product, int $qty, float $price): void
    {
        $this->lines[] = new OrderLine($product, $qty, $price);
    }

    // Cart KNOWS its lines → it calculates TOTAL (Information Expert at cart level)
    public function total(): float
    {
        return array_sum(array_map(fn(OrderLine $l) => $l->subtotal(), $this->lines));
    }

    public function itemCount(): int { return count($this->lines); }
    public function getLines(): array { return $this->lines; }
}

$cart = new ShoppingCart();
$cart->addItem('Laptop',  1, 85000);
$cart->addItem('Mouse',   2,   500);
$cart->addItem('Bag',     1,  1500);

foreach ($cart->getLines() as $line) {
    echo "  {$line->getProduct()}: ₹" . number_format($line->subtotal()) . "\n";
}
echo "  TOTAL: ₹" . number_format($cart->total()) . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. CREATOR
//    "Assign class B creation to class A if A contains, aggregates, or uses B."
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Creator ---\n";

class Address
{
    public function __construct(
        public readonly string $city,
        public readonly string $pincode
    ) {}
}

class OrderCreator
{
    private array $items = [];
    private ?Address $shippingAddress = null;

    public function addProduct(string $name, int $qty, float $price): void
    {
        $this->items[] = ['name' => $name, 'qty' => $qty, 'price' => $price];
    }

    public function setAddress(string $city, string $pin): void
    {
        // OrderCreator creates Address — Creator: OrderCreator USES Address
        $this->shippingAddress = new Address($city, $pin);
    }

    // OrderCreator creates Order — Creator: it contains all the data Order needs
    public function build(): FinalOrder
    {
        if (empty($this->items)) throw new \RuntimeException("No items");
        return new FinalOrder($this->items, $this->shippingAddress);
    }
}

class FinalOrder
{
    private string $id;

    public function __construct(
        private array    $items,
        private ?Address $address
    ) {
        $this->id = 'ORD-' . rand(1000, 9999);
    }

    public function summary(): string
    {
        $total = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $this->items));
        $city  = $this->address ? " → {$this->address->city}" : "";
        return "Order[{$this->id}] | " . count($this->items) . " items | ₹" . number_format($total) . $city;
    }
}

$builder = new OrderCreator();
$builder->addProduct('Phone', 1, 25000);
$builder->setAddress('Mumbai', '400001');
$order = $builder->build();
echo "  " . $order->summary() . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. CONTROLLER
//    "Assign system event handling to a non-UI class that represents
//     the overall system or a use-case scenario."
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. Controller ---\n";

// Controller handles USE CASE — not domain logic, not UI, just orchestration
class CheckoutController
{
    public function __construct(
        private CartService    $cartService,
        private PaymentService $paymentService,
        private OrderService2  $orderService
    ) {}

    // handleCheckout = system event handler (GRASP: Controller)
    public function handleCheckout(int $userId, string $paymentMethod): array
    {
        $cart = $this->cartService->getCart($userId);
        if ($cart->isEmpty()) {
            return ['success' => false, 'error' => 'Cart is empty'];
        }

        $charged = $this->paymentService->charge($cart->total(), $paymentMethod);
        if (!$charged) {
            return ['success' => false, 'error' => 'Payment failed'];
        }

        $orderId = $this->orderService->createFromCart($cart, $userId);
        $this->cartService->clear($userId);

        return ['success' => true, 'orderId' => $orderId];
    }
}

// Minimal service stubs for demo
class CartService2
{
    public function getCart(int $uid): object
    {
        return new class { public function isEmpty(): bool { return false; } public function total(): float { return 1500; } };
    }
    public function clear(int $uid): void {}
}

class CartService extends CartService2 {}

class PaymentService
{
    public function charge(float $amount, string $method): bool
    {
        echo "  [Payment] Charging ₹{$amount} via {$method}\n";
        return true;
    }
}

class OrderService2
{
    public function createFromCart(object $cart, int $userId): string
    {
        $id = 'ORD-' . rand(1000, 9999);
        echo "  [Order] Created {$id} for user #{$userId}\n";
        return $id;
    }
}

$ctrl   = new CheckoutController(new CartService(), new PaymentService(), new OrderService2());
$result = $ctrl->handleCheckout(1, 'UPI');
echo "  Result: " . ($result['success'] ? "Order {$result['orderId']}" : $result['error']) . "\n";

// ═══════════════════════════════════════════════════════════════
// 4. POLYMORPHISM  (GRASP)
//    "Use polymorphism instead of if/switch on type."
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. Polymorphism (GRASP) ---\n";

// ❌ Without polymorphism — must modify when new type added
function calcShippingBAD(string $type, float $weight): float
{
    if ($type === 'standard') return $weight * 50;
    if ($type === 'express')  return $weight * 120;
    if ($type === 'same_day') return $weight * 200;
    return 0;
}

// ✅ With polymorphism — Open/Closed + GRASP Polymorphism
interface ShippingRate
{
    public function calculate(float $weightKg): float;
    public function getLabel(): string;
}

class StandardShipping implements ShippingRate
{
    public function calculate(float $w): float { return $w * 50; }
    public function getLabel(): string         { return "Standard (3-5 days)"; }
}

class ExpressShipping implements ShippingRate
{
    public function calculate(float $w): float { return $w * 120; }
    public function getLabel(): string         { return "Express (1-2 days)"; }
}

class SameDayShipping implements ShippingRate
{
    public function calculate(float $w): float { return $w * 200; }
    public function getLabel(): string         { return "Same Day"; }
}

$options = [new StandardShipping(), new ExpressShipping(), new SameDayShipping()];
foreach ($options as $rate) {
    echo "  {$rate->getLabel()}: ₹" . $rate->calculate(2.5) . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 5. PURE FABRICATION
//    "Create a class that doesn't exist in domain model to achieve
//     low coupling/high cohesion — a 'service' class."
// ═══════════════════════════════════════════════════════════════

echo "\n--- 5. Pure Fabrication ---\n";

// "OrderPersistenceService" is NOT in the real-world domain
// but exists purely to keep DB logic out of Order entity (Pure Fabrication)
class OrderPersistenceService
{
    private array $store = [];

    public function persist(FinalOrder $order): string
    {
        $id = 'DB-' . rand(10000, 99999);
        $this->store[$id] = $order;
        echo "  [PersistenceService] Saved order as {$id}\n";
        return $id;
    }

    public function load(string $id): ?FinalOrder
    {
        return $this->store[$id] ?? null;
    }
}

$persist = new OrderPersistenceService();
$builder2 = new OrderCreator();
$builder2->addProduct('Tablet', 1, 35000);
$order2 = $builder2->build();
$dbId   = $persist->persist($order2);
echo "  Stored: {$dbId}\n";

// ═══════════════════════════════════════════════════════════════
// 6. PROTECTED VARIATIONS
//    "Identify likely change points, wrap them with stable interfaces."
// ═══════════════════════════════════════════════════════════════

echo "\n--- 6. Protected Variations ---\n";

// Likely variation: how tax is calculated (GST, VAT, sales tax)
interface TaxStrategy
{
    public function calculate(float $amount, string $category): float;
    public function getName(): string;
}

class GstStrategy implements TaxStrategy
{
    public function calculate(float $amount, string $cat): float
    {
        $rate = match($cat) { 'electronics' => 0.18, 'food' => 0.05, default => 0.12 };
        return $amount * $rate;
    }
    public function getName(): string { return "GST"; }
}

class VatStrategy implements TaxStrategy
{
    public function calculate(float $amount, string $cat): float { return $amount * 0.20; }
    public function getName(): string { return "VAT"; }
}

class TaxCalculatorService
{
    public function __construct(private TaxStrategy $strategy) {}

    public function compute(float $amount, string $category): array
    {
        $tax   = $this->strategy->calculate($amount, $category);
        $total = $amount + $tax;
        return ['base' => $amount, 'tax' => $tax, 'total' => $total, 'method' => $this->strategy->getName()];
    }
}

$gstCalc = new TaxCalculatorService(new GstStrategy());
$vatCalc = new TaxCalculatorService(new VatStrategy());

$gstResult = $gstCalc->compute(10000, 'electronics');
$vatResult = $vatCalc->compute(10000, 'electronics');
echo "  GST: base=₹{$gstResult['base']} + tax=₹{$gstResult['tax']} = ₹{$gstResult['total']}\n";
echo "  VAT: base=₹{$vatResult['base']} + tax=₹{$vatResult['tax']} = ₹{$vatResult['total']}\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ GRASP QUICK REFERENCE                                           │
 * ├─────────────────────────────┬───────────────────────────────────┤
 * │ Principle                   │ One-line rule                     │
 * ├─────────────────────────────┼───────────────────────────────────┤
 * │ Information Expert          │ Who has the data does the work   │
 * │ Creator                     │ Who uses/contains B creates B    │
 * │ Controller                  │ Delegate system events to a ctrl │
 * │ Low Coupling                │ Minimize class dependencies      │
 * │ High Cohesion               │ Keep related things together     │
 * │ Polymorphism                │ Use interfaces, not if/switch    │
 * │ Pure Fabrication            │ Invent a class for clean design  │
 * │ Indirection                 │ Add a mediator to decouple       │
 * │ Protected Variations        │ Wrap change points with interfaces│
 * └─────────────────────────────┴───────────────────────────────────┘
 *
 * INTERVIEW Q&A:
 *
 * Q: How is GRASP different from SOLID?
 * A: SOLID = 5 principles about code quality (SRP, OCP, LSP, ISP, DIP).
 *    GRASP = 9 patterns for deciding WHO (which class) should own which
 *    responsibility when designing from scratch. SOLID validates design
 *    quality; GRASP guides the ASSIGNMENT of responsibilities.
 *
 * Q: What is Information Expert in GRASP?
 * A: The class that has the data needed to fulfill a responsibility should
 *    own that responsibility. e.g., OrderLine knows its price and qty,
 *    so OrderLine calculates its own subtotal (not Cart, not Order).
 *
 * Q: What is Pure Fabrication?
 * A: When no domain class is a natural fit for a responsibility (e.g.,
 *    database saving), invent a service class (OrderRepository,
 *    UserPersistenceService) that doesn't exist in the domain but
 *    achieves low coupling and high cohesion.
 *
 * Q: Give an example of Protected Variations.
 * A: Payment method integration: the payment method (Stripe, Razorpay, UPI)
 *    is a "variation point". Wrap it with PaymentGatewayInterface so all
 *    business logic depends on the stable interface, not the volatile
 *    concrete implementation.
 */
