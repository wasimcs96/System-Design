<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #2 — FACTORY METHOD                  ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Creational Pattern                                 ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★★                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: Your code needs to create objects, but you don't know   │
 * │ WHICH concrete class to instantiate until runtime.               │
 * │                                                                  │
 * │ Bad approach: Scatter `new ConcreteClass()` everywhere.          │
 * │   → Adding a new type means changing code in 20 places (OCP ✗)  │
 * │                                                                  │
 * │ Factory Method: Delegate object creation to a method/class.      │
 * │   → Adding a new type = add one new class (OCP ✓)               │
 * │                                                                  │
 * │ Two flavors:                                                      │
 * │  1. SIMPLE FACTORY  — static method that switches on type string │
 * │  2. FACTORY METHOD  — abstract creator, each subclass decides    │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM — FACTORY METHOD                                   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Creator (abstract)                                              │
 * │  ├─ + createProduct(): Product  ← factory method (abstract)     │
 * │  └─ + someOperation()           ← uses the product              │
 * │        │                                                          │
 * │        ├── ConcreteCreatorA ──► createProduct() → ProductA      │
 * │        └── ConcreteCreatorB ──► createProduct() → ProductB      │
 * │                                                                   │
 * │  Product (interface)                                             │
 * │  ├── ProductA                                                    │
 * │  └── ProductB                                                    │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE FACTORY METHOD                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define a Product interface (what all products do)        │
 * │ STEP 2: Create ConcreteProduct classes implementing the interface│
 * │ STEP 3: Define abstract Creator with factory method             │
 * │ STEP 4: Concrete Creators override factory method to return      │
 * │         their specific product                                   │
 * │ STEP 5: Client works with Creator interface — never knows        │
 * │         which ConcreteProduct it's using                         │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// PART 1: SIMPLE FACTORY (static factory method)
// Use when: few types, unlikely to change
// ═══════════════════════════════════════════════════════════════

// STEP 1: Product interface
interface Notification
{
    public function send(string $to, string $message): void;
    public function getType(): string;
}

// STEP 2: Concrete Products
class EmailNotification implements Notification
{
    public function send(string $to, string $message): void
    {
        echo "  [EMAIL → $to]: $message\n";
    }
    public function getType(): string { return 'email'; }
}

class SMSNotification implements Notification
{
    public function send(string $to, string $message): void
    {
        echo "  [SMS → $to]: $message\n";
    }
    public function getType(): string { return 'sms'; }
}

class PushNotification implements Notification
{
    public function send(string $to, string $message): void
    {
        echo "  [PUSH → $to]: $message\n";
    }
    public function getType(): string { return 'push'; }
}

// STEP 3: Simple Factory — one static method, switches on type
class NotificationFactory
{
    /**
     * createNotification() hides which concrete class gets instantiated.
     * Client just asks for 'email' — doesn't know EmailNotification exists.
     *
     * Limitation: Adding a new type requires modifying THIS switch.
     * That's why the next pattern (Factory Method) improves on this.
     */
    public static function create(string $type): Notification
    {
        return match (strtolower($type)) {
            'email' => new EmailNotification(),
            'sms'   => new SMSNotification(),
            'push'  => new PushNotification(),
            default => throw new \InvalidArgumentException("Unknown notification type: '$type'"),
        };
    }
}

// ═══════════════════════════════════════════════════════════════
// PART 2: FACTORY METHOD (polymorphic factory)
// Use when: many types, open for extension (OCP)
// ═══════════════════════════════════════════════════════════════

// STEP 1: Product interface
interface PaymentGateway
{
    public function charge(float $amount, string $currency): bool;
    public function refund(string $transactionId): bool;
    public function getName(): string;
}

// STEP 2: Concrete Products
class StripeGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): bool
    {
        echo "  [Stripe] Charging $amount $currency\n";
        return true; // Simulate success
    }
    public function refund(string $txId): bool
    {
        echo "  [Stripe] Refunding txn: $txId\n";
        return true;
    }
    public function getName(): string { return 'Stripe'; }
}

class RazorpayGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): bool
    {
        echo "  [Razorpay] Charging $amount $currency\n";
        return true;
    }
    public function refund(string $txId): bool
    {
        echo "  [Razorpay] Refunding txn: $txId\n";
        return true;
    }
    public function getName(): string { return 'Razorpay'; }
}

class PayPalGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): bool
    {
        echo "  [PayPal] Charging $amount $currency\n";
        return true;
    }
    public function refund(string $txId): bool
    {
        echo "  [PayPal] Refunding txn: $txId\n";
        return true;
    }
    public function getName(): string { return 'PayPal'; }
}

// STEP 3: Abstract Creator — declares the factory method
abstract class PaymentProcessor
{
    /**
     * This is the FACTORY METHOD.
     * Subclasses override this to return their specific gateway.
     * The creator never knows the concrete type — only the interface.
     */
    abstract protected function createGateway(): PaymentGateway;

    /**
     * Business logic lives here — uses the factory method.
     * This code never changes even when we add new payment types.
     */
    public function processPayment(float $amount, string $currency): void
    {
        $gateway = $this->createGateway(); // Polymorphic creation
        echo "  Processing via {$gateway->getName()}:\n";
        $result = $gateway->charge($amount, $currency);
        echo "  Result: " . ($result ? "SUCCESS ✓" : "FAILED ✗") . "\n";
    }

    public function processRefund(string $txId): void
    {
        $gateway = $this->createGateway();
        $gateway->refund($txId);
    }
}

// STEP 4: Concrete Creators — each overrides the factory method
class StripeProcessor extends PaymentProcessor
{
    protected function createGateway(): PaymentGateway
    {
        return new StripeGateway();
    }
}

class RazorpayProcessor extends PaymentProcessor
{
    protected function createGateway(): PaymentGateway
    {
        return new RazorpayGateway();
    }
}

class PayPalProcessor extends PaymentProcessor
{
    protected function createGateway(): PaymentGateway
    {
        return new PayPalGateway();
    }
}

// STEP 5: Client code — works with abstract PaymentProcessor
// To add new gateway: add new class, ZERO changes to existing code
function checkout(PaymentProcessor $processor, float $amount): void
{
    $processor->processPayment($amount, 'INR');
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== FACTORY PATTERN DEMO ===\n\n";

echo "--- Part 1: Simple Factory ---\n";
$email = NotificationFactory::create('email');
$sms   = NotificationFactory::create('sms');
$push  = NotificationFactory::create('push');
$email->send('alice@example.com', 'Your order is shipped!');
$sms->send('+919999999999',        'OTP: 123456');
$push->send('device_token_xyz',    'Flash sale starts now!');

try {
    NotificationFactory::create('fax'); // Unknown type
} catch (\InvalidArgumentException $e) {
    echo "  Error caught: " . $e->getMessage() . " ✓\n";
}

echo "\n--- Part 2: Factory Method ---\n";
$processors = [
    new StripeProcessor(),
    new RazorpayProcessor(),
    new PayPalProcessor(),
];
foreach ($processors as $processor) {
    checkout($processor, 999.00);
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Factory Method pattern?                         │
 * │ A: A creational pattern where a base class has an abstract      │
 * │    "factory method" for creating objects, and each subclass     │
 * │    overrides it to decide which concrete class to instantiate.   │
 * │                                                                  │
 * │ Q2: Difference between Simple Factory and Factory Method?        │
 * │ A: Simple Factory = one class with a static switch statement.   │
 * │    It's NOT a GoF pattern — adding new types requires editing   │
 * │    that switch (violates OCP).                                   │
 * │    Factory Method = each creator subclass returns its own type. │
 * │    Adding new type = add new subclass, zero existing changes.    │
 * │                                                                  │
 * │ Q3: What design principle does Factory Method promote?           │
 * │ A: Open/Closed Principle (OCP) — open for extension (add new   │
 * │    subclass), closed for modification (don't touch existing).   │
 * │    Also: Dependency Inversion (depend on abstractions).         │
 * │                                                                  │
 * │ Q4: When would you use Factory vs Abstract Factory?              │
 * │ A: Factory Method: one product type, multiple variants.         │
 * │    Abstract Factory: MULTIPLE product types that must be used   │
 * │    together (a "family" of related objects).                     │
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: Laravel's `DB::connection('mysql')` — SimpleFactory.         │
 * │    PHPUnit's TestCase — factory method `createStub()`.           │
 * │    PSR-3 LoggerFactory, PDO driver creation.                    │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Unknown type string → throw InvalidArgumentException          │
 * │ ✓ Gateway constructor fails → exception propagates naturally     │
 * │ ✓ Can cache created gateways inside creator if expensive         │
 * └─────────────────────────────────────────────────────────────────┘
 */
