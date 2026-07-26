<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #5 — POLYMORPHISM                       ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (Core interview topic — both types asked)   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * BEGINNER EXPLANATION:
 *   "Poly" = many, "morph" = forms. Polymorphism means one interface,
 *   many behaviors. The same method call behaves differently depending
 *   on the actual object type.
 *
 * ANALOGY: A "+" operator: 5+3=8 (addition), "Hello"+"World"="HelloWorld" (concat).
 *   Same operator, different behavior depending on context.
 *
 * TECHNICAL DEFINITION:
 *   The ability of different objects to respond to the same message
 *   (method call) in different ways.
 *
 * TWO TYPES:
 *   1. COMPILE-TIME (Static) Polymorphism — resolved at compile time
 *      In PHP: Method Overloading via __call() / default params
 *      (PHP doesn't have true compile-time polymorphism like Java)
 *
 *   2. RUN-TIME (Dynamic) Polymorphism — resolved at runtime
 *      In PHP: Method Overriding — subclass provides its own implementation
 *      This is the PRIMARY polymorphism in PHP (and most OOP)
 */

// ═══════════════════════════════════════════════════════════════
// TYPE 1: RUNTIME POLYMORPHISM — Method Overriding
// ═══════════════════════════════════════════════════════════════
/**
 *  VISUAL:
 *
 *  Animal
 *  + makeSound(): void  ← same method name
 *       ▲
 *  ─────┬─────┬──────
 *  Dog  Cat  Cow
 *  "Woof" "Meow" "Moo"  ← different behavior at runtime
 *
 *  $animals = [new Dog(), new Cat(), new Cow()];
 *  foreach ($animals as $a) { $a->makeSound(); }
 *  → Each resolves to its OWN makeSound() at runtime
 */

abstract class Animal
{
    abstract public function makeSound(): string;
    abstract public function getType(): string;

    // Concrete shared method — uses abstract methods (template method pattern)
    public function introduce(): void
    {
        echo "  I am a {$this->getType()} and I say: {$this->makeSound()}\n";
    }
}

class Dog extends Animal
{
    public function makeSound(): string { return "Woof! Woof!"; }
    public function getType(): string   { return "Dog"; }
    public function fetch(): void       { echo "  Dog fetches the stick!\n"; }
}

class Cat extends Animal
{
    public function makeSound(): string { return "Meow~"; }
    public function getType(): string   { return "Cat"; }
    public function purr(): void        { echo "  Cat purrs...\n"; }
}

class Cow extends Animal
{
    public function makeSound(): string { return "Moooooo!"; }
    public function getType(): string   { return "Cow"; }
}

class Parrot extends Animal
{
    public function __construct(private string $phrase = "Polly wants a cracker") {}
    public function makeSound(): string { return $this->phrase; }
    public function getType(): string   { return "Parrot"; }
}

echo "=== POLYMORPHISM DEMO ===\n\n";

echo "--- Runtime Polymorphism: makeSound() ---\n";

// Array of different objects — ALL treated as Animal
$animals = [
    new Dog(),
    new Cat(),
    new Cow(),
    new Parrot("Hello, I can talk!"),
];

// SAME method call → DIFFERENT behavior per object — that's polymorphism!
foreach ($animals as $animal) {
    $animal->introduce();
}

// ─── Polymorphism in function parameters ──────────────────────

function makeAnimalSpeak(Animal $animal): void
{
    // This function works for ANY Animal subclass — doesn't know the specific type
    echo "  [{$animal->getType()}] says: {$animal->makeSound()}\n";
}

echo "\n--- Polymorphic function call ---\n";
makeAnimalSpeak(new Dog());
makeAnimalSpeak(new Cat());
makeAnimalSpeak(new Parrot("Squawk!"));

// ═══════════════════════════════════════════════════════════════
// RUNTIME POLYMORPHISM — Real-world: Payment Processing
// ═══════════════════════════════════════════════════════════════

echo "\n--- Real-World: Payment Gateway Polymorphism ---\n";

interface PaymentMethod
{
    public function processPayment(float $amount): string;
    public function getMethodName(): string;
}

class CreditCard implements PaymentMethod
{
    public function __construct(private string $last4) {}
    public function processPayment(float $amount): string
    {
        return "Charged ₹{$amount} to card ending {$this->last4}";
    }
    public function getMethodName(): string { return "Credit Card"; }
}

class UPI implements PaymentMethod
{
    public function __construct(private string $upiId) {}
    public function processPayment(float $amount): string
    {
        return "Transferred ₹{$amount} via UPI ({$this->upiId})";
    }
    public function getMethodName(): string { return "UPI"; }
}

class NetBanking implements PaymentMethod
{
    public function __construct(private string $bank) {}
    public function processPayment(float $amount): string
    {
        return "Debited ₹{$amount} from {$this->bank} bank account";
    }
    public function getMethodName(): string { return "Net Banking"; }
}

// Checkout — works with ANY PaymentMethod (polymorphism)
class Checkout
{
    public function pay(PaymentMethod $method, float $amount): void
    {
        echo "  Processing via " . $method->getMethodName() . "...\n";
        echo "  " . $method->processPayment($amount) . "\n";
        echo "  ✓ Payment of ₹{$amount} complete.\n";
    }
}

$checkout = new Checkout();
$checkout->pay(new CreditCard('4242'), 1500.00);
echo "\n";
$checkout->pay(new UPI('alice@paytm'), 800.00);
echo "\n";
$checkout->pay(new NetBanking('HDFC'), 5000.00);

// ═══════════════════════════════════════════════════════════════
// TYPE 2: COMPILE-TIME POLYMORPHISM in PHP
// ═══════════════════════════════════════════════════════════════
/**
 * PHP doesn't have true method overloading (same name, different params).
 * Alternatives:
 *   a) Default parameter values
 *   b) __call() magic method for dynamic dispatch
 *   c) Variadic arguments (...$args)
 */

echo "\n--- Compile-time (Static) Polymorphism — PHP Alternatives ---\n";

class Calculator
{
    // (a) Default params simulate overloading
    public function add(float $a, float $b, float $c = 0): float
    {
        return $a + $b + $c;
    }

    // (b) Variadic — accepts any number of args
    public function sum(float ...$numbers): float
    {
        return array_sum($numbers);
    }

    // (c) __call() — intercepts calls to non-existent methods
    public function __call(string $name, array $args): mixed
    {
        if ($name === 'multiply') {
            return array_product($args);
        }
        throw new \BadMethodCallException("Method $name not found.");
    }
}

$calc = new Calculator();
echo "  add(2, 3)      = " . $calc->add(2, 3) . "\n";       // 5
echo "  add(2, 3, 4)   = " . $calc->add(2, 3, 4) . "\n";    // 9
echo "  sum(1,2,3,4,5) = " . $calc->sum(1, 2, 3, 4, 5) . "\n"; // 15
echo "  multiply(3, 4) = " . $calc->multiply(3, 4) . "\n";   // __call dispatched

// ═══════════════════════════════════════════════════════════════
// INTERFACE POLYMORPHISM
// ═══════════════════════════════════════════════════════════════

echo "\n--- Interface Polymorphism ---\n";

interface Printable
{
    public function printDetails(): void;
}

class Invoice implements Printable
{
    public function __construct(private int $id, private float $amount) {}
    public function printDetails(): void
    {
        echo "  INVOICE #" . str_pad($this->id, 5, '0', STR_PAD_LEFT)
           . " | Amount: ₹" . number_format($this->amount, 2) . "\n";
    }
}

class Report implements Printable
{
    public function __construct(private string $title, private int $pages) {}
    public function printDetails(): void
    {
        echo "  REPORT: {$this->title} | Pages: {$this->pages}\n";
    }
}

class Receipt implements Printable
{
    public function __construct(private string $merchant, private float $total) {}
    public function printDetails(): void
    {
        echo "  RECEIPT from {$this->merchant} | Total: ₹" . number_format($this->total, 2) . "\n";
    }
}

// Printer doesn't know or care WHICH Printable — polymorphism
class Printer
{
    public function printAll(array $printables): void
    {
        foreach ($printables as $item) {
            $item->printDetails();  // Each calls its own implementation
        }
    }
}

$printer = new Printer();
$printer->printAll([
    new Invoice(1, 4500.00),
    new Report('Q1 Sales', 12),
    new Receipt('Zomato', 350.00),
    new Invoice(2, 12000.00),
]);

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is polymorphism? What are its types?                    │
 * │ A: One interface, many behaviors. Two types:                    │
 * │    Compile-time (static): resolved at compile time — method     │
 * │    overloading (not natively in PHP; use default params/__call) │
 * │    Runtime (dynamic): resolved at runtime — method overriding   │
 * │    via inheritance/interfaces. Most common in PHP.              │
 * │                                                                  │
 * │ Q2: What's the difference between method overloading and        │
 * │     method overriding?                                           │
 * │ A: Overloading: SAME class, same method name, different params  │
 * │    (compile-time). PHP achieves this via default params/variadics│
 * │    Overriding: CHILD class redefines parent's method with same  │
 * │    name and signature (runtime polymorphism).                    │
 * │                                                                  │
 * │ Q3: How does PHP resolve which method to call at runtime?       │
 * │ A: Through dynamic dispatch — PHP looks up the actual runtime   │
 * │    type of the object and calls its version of the method.      │
 * │    Even if the variable is typed as Animal, calling makeSound() │
 * │    on a Dog object will call Dog::makeSound().                  │
 * │                                                                  │
 * │ Q4: Can polymorphism work with interfaces?                       │
 * │ A: Yes — interface polymorphism is the most common and cleanest │
 * │    form. Any class implementing Printable can be used wherever   │
 * │    Printable is expected. This is how Dependency Inversion works.│
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Thinking PHP supports true method overloading — it doesn't.  │
 * │ ✗ Using instanceof for polymorphic dispatch — defeats the        │
 * │   purpose. Let the type system dispatch via override instead.   │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Runtime polymorphism via method overriding is the core type.  │
 * │ ✓ One interface (type hint) → many possible concrete behaviors. │
 * │ ✓ Eliminates if/elseif type-checking in client code.           │
 * │ ✓ Enables OCP (open/closed principle) naturally.               │
 * └─────────────────────────────────────────────────────────────────┘
 */
