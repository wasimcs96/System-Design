<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #3 — ABSTRACTION                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (Always asked alongside Encapsulation)      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 1: CONCEPT DEFINITION
// ═══════════════════════════════════════════════════════════════
/**
 * BEGINNER EXPLANATION:
 *   Abstraction = showing only WHAT an object does, hiding HOW it does it.
 *   You drive a car — you know the steering wheel turns it, the pedal moves it.
 *   You don't need to know HOW the engine combustion works internally.
 *   The complexity is hidden; the interface is simple.
 *
 * TECHNICAL DEFINITION:
 *   The process of defining a simplified, high-level interface (contract)
 *   for complex systems, hiding implementation details from the user.
 *   Achieved via: Abstract Classes and Interfaces.
 *
 * TWO MECHANISMS IN PHP:
 *   1. Abstract Class : Can have both abstract methods (no body) and
 *                       concrete methods (with body). Cannot be instantiated.
 *   2. Interface      : Only method signatures, no implementations.
 *                       A class implements an interface as a contract.
 *
 * REAL-WORLD ANALOGY:
 *   A TV remote: you press "Volume Up" — you don't know if the TV uses
 *   infrared or Bluetooth internally. The remote is the abstraction.
 *   You interact with the abstraction, not the implementation.
 *
 * ABSTRACTION vs ENCAPSULATION (most common interview trap):
 *   Abstraction  → Design level: WHAT to expose (the contract/interface)
 *   Encapsulation → Implementation level: HOW to hide data (private fields)
 *   They complement each other. Abstraction defines the WHAT;
 *   Encapsulation protects the HOW.
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 2: VISUAL DIAGRAM
// ═══════════════════════════════════════════════════════════════
/**
 *  Abstract Class: Shape
 *  ┌────────────────────────────────────────┐
 *  │  <<abstract>> Shape                    │
 *  ├────────────────────────────────────────┤
 *  │  # color: string (concrete property)  │
 *  ├────────────────────────────────────────┤
 *  │  + getColor(): string (concrete)      │  ← Shared implementation
 *  │  + area(): float      (abstract) ◄───────  No body — subclass MUST implement
 *  │  + perimeter(): float (abstract) ◄───────  No body — subclass MUST implement
 *  │  + describe(): void   (concrete)      │  ← Uses abstract methods (template)
 *  └────────────────────────────────────────┘
 *            ▲               ▲               ▲
 *         Circle          Rectangle        Triangle
 *  implements area()   implements area()  implements area()
 *  own formula         own formula        own formula
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 3: ABSTRACT CLASS EXAMPLE
// ═══════════════════════════════════════════════════════════════

// Abstract class — defines the CONTRACT; cannot be instantiated directly
abstract class Shape
{
    // Concrete property — shared by all shapes
    protected string $color;

    public function __construct(string $color = 'white')
    {
        $this->color = $color;
    }

    // ABSTRACT methods — WHAT every shape must do (no implementation)
    abstract public function area(): float;
    abstract public function perimeter(): float;
    abstract public function getShapeName(): string;

    // CONCRETE method — HOW is defined here (shared behavior)
    public function getColor(): string { return $this->color; }

    // Template method — uses abstract methods internally (pattern!)
    // Subclasses don't need to override this; they just implement the abstract parts
    public function describe(): void
    {
        echo "  Shape   : " . $this->getShapeName() . "\n";
        echo "  Color   : {$this->color}\n";
        echo "  Area    : " . round($this->area(), 2) . " sq units\n";
        echo "  Perim.  : " . round($this->perimeter(), 2) . " units\n";
    }
}

// Concrete class — fills in all abstract methods with specific formulas
class Circle extends Shape
{
    public function __construct(private float $radius, string $color = 'red')
    {
        parent::__construct($color);
    }

    // Must implement all abstract methods
    public function area(): float      { return M_PI * $this->radius ** 2; }
    public function perimeter(): float { return 2 * M_PI * $this->radius; }
    public function getShapeName(): string { return 'Circle'; }
}

class Rectangle extends Shape
{
    public function __construct(
        private float $width,
        private float $height,
        string        $color = 'blue'
    ) {
        parent::__construct($color);
    }

    public function area(): float      { return $this->width * $this->height; }
    public function perimeter(): float { return 2 * ($this->width + $this->height); }
    public function getShapeName(): string { return 'Rectangle'; }
}

class Triangle extends Shape
{
    public function __construct(
        private float $a,
        private float $b,
        private float $c,
        string        $color = 'green'
    ) {
        parent::__construct($color);
    }

    public function area(): float
    {
        $s = ($this->a + $this->b + $this->c) / 2; // Heron's formula
        return sqrt($s * ($s - $this->a) * ($s - $this->b) * ($s - $this->c));
    }

    public function perimeter(): float { return $this->a + $this->b + $this->c; }
    public function getShapeName(): string { return 'Triangle'; }
}

// ─── DRIVER CODE ─────────────────────────────────────────────

echo "=== ABSTRACTION DEMO ===\n\n";

// ❌ Cannot instantiate abstract class
// $shape = new Shape(); // Fatal error: Cannot instantiate abstract class Shape

$shapes = [
    new Circle(7.0),
    new Rectangle(5.0, 3.0),
    new Triangle(3.0, 4.0, 5.0),
];

// Client code works with the ABSTRACTION (Shape) — doesn't care about the implementation
foreach ($shapes as $shape) {
    $shape->describe();
    echo "\n";
}

// Total area — works on any Shape without knowing type
$totalArea = array_sum(array_map(fn($s) => $s->area(), $shapes));
echo "  Total area of all shapes: " . round($totalArea, 2) . " sq units\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 4: REAL-WORLD EXAMPLE — Payment Gateway Abstraction
// ═══════════════════════════════════════════════════════════════

echo "\n--- Real-World: Payment Gateway ---\n";

// The abstraction — defines WHAT a payment gateway must do
abstract class PaymentGateway
{
    // Concrete: shared logic for all gateways
    public function processPayment(float $amount, string $currency): void
    {
        echo "  Initiating payment of {$currency} " . number_format($amount, 2) . "...\n";
        $this->validateAmount($amount);
        $txnId = $this->charge($amount, $currency); // Calls abstract method
        $this->logTransaction($txnId, $amount);
        echo "  ✓ Payment successful. TxnID: $txnId\n";
    }

    // Abstract — each gateway charges differently
    abstract protected function charge(float $amount, string $currency): string;

    // Concrete helper
    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) throw new \InvalidArgumentException("Amount must be positive.");
    }

    private function logTransaction(string $txnId, float $amount): void
    {
        echo "  [Log] Recorded TxnID=$txnId, Amount=$amount\n";
    }
}

class StripeGateway extends PaymentGateway
{
    // HOW Stripe charges — implementation detail hidden from client
    protected function charge(float $amount, string $currency): string
    {
        // Real: Stripe SDK call, convert to cents, etc.
        echo "  [Stripe] Sending charge to Stripe API...\n";
        return 'stripe_' . uniqid();
    }
}

class RazorpayGateway extends PaymentGateway
{
    protected function charge(float $amount, string $currency): string
    {
        echo "  [Razorpay] Sending charge to Razorpay API...\n";
        return 'rpay_' . uniqid();
    }
}

// Client only sees processPayment() — implementation is abstracted away
$stripe   = new StripeGateway();
$razorpay = new RazorpayGateway();

$stripe->processPayment(1500.00, 'INR');
echo "\n";
$razorpay->processPayment(2000.00, 'INR');

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is abstraction?                                         │
 * │ A: Showing only relevant features and hiding implementation      │
 * │    complexity. Achieved in PHP via abstract classes (partial     │
 * │    abstraction) and interfaces (full abstraction).               │
 * │                                                                  │
 * │ Q2: Abstract class vs Interface — when to use which?            │
 * │ A: Abstract class: when subclasses SHARE some behavior (code    │
 * │    reuse via concrete methods). Use when you have an IS-A        │
 * │    relationship AND common implementation.                       │
 * │    Interface: when you define a CONTRACT — multiple unrelated    │
 * │    classes must fulfill the same behavior. No shared code.       │
 * │    PHP supports only single inheritance but multiple interfaces. │
 * │                                                                  │
 * │ Q3: Can abstract class have a constructor?                       │
 * │ A: Yes — abstract classes can have constructors. They're called │
 * │    via parent::__construct() in the subclass.                    │
 * │                                                                  │
 * │ Q4: What happens if a subclass doesn't implement all abstract   │
 * │     methods?                                                     │
 * │ A: PHP throws a Fatal Error — the subclass must be declared      │
 * │    abstract itself, OR implement all inherited abstract methods. │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Confusing abstraction with encapsulation (see Q2 in           │
 * │   Encapsulation file — they're related but different).          │
 * │ ✗ Abstract class with NO abstract methods — valid PHP but       │
 * │   defeats the purpose; consider using a regular class instead.  │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Abstraction = hide HOW, expose WHAT.                         │
 * │ ✓ Abstract classes: partial abstraction + code sharing.        │
 * │ ✓ Interfaces: full abstraction + pure contract.                 │
 * │ ✓ Client programs to the abstraction — swappable implementations│
 * └─────────────────────────────────────────────────────────────────┘
 */
