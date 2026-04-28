<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          SOLID PRINCIPLE #2 — OPEN/CLOSED PRINCIPLE (OCP)        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  ACRONYM    : O in SOLID                                         ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★★ (Asked with Strategy, Decorator, etc.)      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DEFINITION                                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ "Software entities (classes, modules, functions) should be       │
 * │  OPEN for extension but CLOSED for modification."               │
 * │                                                                  │
 * │  OPEN for extension   → you CAN add new behavior                │
 * │  CLOSED for modification → you CANNOT change existing code      │
 * │                                                                  │
 * │ HOW TO ACHIEVE:                                                  │
 * │  Use abstractions (interfaces/abstract classes). New behavior    │
 * │  is added by creating NEW classes that implement the interface   │
 * │  — not by modifying the class that uses it.                      │
 * │                                                                  │
 * │ KEY INSIGHT: Every time you add an if/else or switch for a NEW   │
 * │ type/case in an existing class, you are MODIFYING that class.   │
 * │ OCP says: add a new class instead.                               │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ❌ BEFORE: Modification needed for each new type                │
 * │  ┌──────────────────────────────────────────────┐               │
 * │  │  ReportGenerator                             │               │
 * │  │  + generate(type: string)                    │               │
 * │  │    if type == 'csv'  { ... }  ← modify here  │               │
 * │  │    if type == 'pdf'  { ... }  ← modify here  │               │
 * │  │    if type == 'excel'{ ... }  ← ADD NEW if   │               │
 * │  └──────────────────────────────────────────────┘               │
 * │                                                                  │
 * │  ✅ AFTER: Extend by adding new class, zero modification         │
 * │  ReportGenerator (closed)                                        │
 * │       uses ▼ ReportFormatter (interface)                         │
 * │            ├── CsvFormatter   (new type = new class)            │
 * │            ├── PdfFormatter   (existing, untouched)              │
 * │            └── ExcelFormatter (new type = new class, no edits)  │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO APPLY OCP                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Find the part that changes (the "varying" behavior)      │
 * │ STEP 2: Extract that behavior into an interface/abstract class   │
 * │ STEP 3: Make the high-level class depend on the abstraction      │
 * │ STEP 4: Implement new behaviors as new classes (no modification) │
 * │ STEP 5: Wire up via constructor injection (or factory/DI)        │
 * └─────────────────────────────────────────────────────────────────┘
 */

echo "=== OPEN/CLOSED PRINCIPLE ===\n\n";

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION: if/else chains must be modified for every new type
// ═══════════════════════════════════════════════════════════════
class BadShippingCalculator
{
    // To add a new courier, you MUST modify this method → violates OCP
    public function calculate(string $courier, float $weightKg): float
    {
        if ($courier === 'fedex') {
            return 10.0 + ($weightKg * 2.5);
        } elseif ($courier === 'dhl') {
            return 8.0 + ($weightKg * 3.0);
        } elseif ($courier === 'ups') {
            return 12.0 + ($weightKg * 2.0);
        }
        // Adding BlueDart? MODIFY this class → risk breaking FedEx logic
        throw new \InvalidArgumentException("Unknown courier: $courier");
    }
}

echo "--- ❌ OCP Violation (if-else chain) ---\n";
$bad = new BadShippingCalculator();
echo "  FedEx 2kg: ₹" . $bad->calculate('fedex', 2.0) . "\n";
echo "  DHL   2kg: ₹" . $bad->calculate('dhl',   2.0) . "\n";

// ═══════════════════════════════════════════════════════════════
// ✅ CORRECT: Open for extension via interface, closed for modification
// ═══════════════════════════════════════════════════════════════

// STEP 2: Abstract the varying behavior
interface ShippingCalculator
{
    public function calculate(float $weightKg): float;
    public function getCourierName(): string;
}

// STEP 4: Each courier = a new class; existing classes untouched
class FedExShipping implements ShippingCalculator
{
    public function calculate(float $weightKg): float
    {
        return 10.0 + ($weightKg * 2.5); // FedEx pricing formula
    }
    public function getCourierName(): string { return 'FedEx'; }
}

class DhlShipping implements ShippingCalculator
{
    public function calculate(float $weightKg): float
    {
        return 8.0 + ($weightKg * 3.0); // DHL pricing formula
    }
    public function getCourierName(): string { return 'DHL'; }
}

class UpsShipping implements ShippingCalculator
{
    public function calculate(float $weightKg): float
    {
        return 12.0 + ($weightKg * 2.0); // UPS pricing formula
    }
    public function getCourierName(): string { return 'UPS'; }
}

// ✅ NEW COURIER: Just add this class. Zero modification to existing code.
class BlueDartShipping implements ShippingCalculator
{
    public function calculate(float $weightKg): float
    {
        return 6.0 + ($weightKg * 1.8); // BlueDart flat + per-kg
    }
    public function getCourierName(): string { return 'BlueDart'; }
}

// STEP 3: High-level class depends on abstraction, not concrete types
class ShippingService
{
    // Closed for modification — adding new couriers doesn't touch this
    public function getCheapestCourier(float $weightKg, array $couriers): ?ShippingCalculator
    {
        $cheapest = null;
        $lowest   = PHP_FLOAT_MAX;

        foreach ($couriers as $courier) {
            $cost = $courier->calculate($weightKg);
            if ($cost < $lowest) {
                $lowest   = $cost;
                $cheapest = $courier;
            }
        }
        return $cheapest;
    }

    public function printRates(float $weightKg, array $couriers): void
    {
        echo "  Shipping rates for {$weightKg}kg:\n";
        foreach ($couriers as $courier) {
            $cost = $courier->calculate($weightKg);
            echo "    {$courier->getCourierName()}: ₹" . number_format($cost, 2) . "\n";
        }
    }
}

echo "\n--- ✅ OCP Compliant: Shipping Calculator ---\n";

$couriers = [
    new FedExShipping(),
    new DhlShipping(),
    new UpsShipping(),
    new BlueDartShipping(), // Added with ZERO modification to existing classes
];

$service = new ShippingService();
$service->printRates(3.5, $couriers);

$cheapest = $service->getCheapestCourier(3.5, $couriers);
echo "  Cheapest: {$cheapest->getCourierName()} @ ₹" . number_format($cheapest->calculate(3.5), 2) . "\n";

// ─── REAL-WORLD EXAMPLE: Payment Processing ──────────────────────────────────

echo "\n--- Real-World: Payment Gateway (OCP) ---\n";

interface PaymentGateway
{
    public function charge(float $amount, string $currency): string;
    public function refund(string $transactionId, float $amount): bool;
}

class StripeGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): string
    {
        echo "  [Stripe] Charging {$currency}" . number_format($amount, 2) . " via Stripe API\n";
        return 'stripe_txn_' . uniqid();
    }
    public function refund(string $txnId, float $amount): bool
    {
        echo "  [Stripe] Refunding ₹" . number_format($amount, 2) . " for $txnId\n";
        return true;
    }
}

class RazorpayGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): string
    {
        echo "  [Razorpay] Charging {$currency}" . number_format($amount, 2) . " via Razorpay API\n";
        return 'rpay_' . uniqid();
    }
    public function refund(string $txnId, float $amount): bool
    {
        echo "  [Razorpay] Refunding ₹" . number_format($amount, 2) . " for $txnId\n";
        return true;
    }
}

// ✅ New gateway — NO changes to PaymentProcessor
class PayPalGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): string
    {
        echo "  [PayPal] Charging {$currency}" . number_format($amount, 2) . " via PayPal SDK\n";
        return 'paypal_' . uniqid();
    }
    public function refund(string $txnId, float $amount): bool
    {
        echo "  [PayPal] Refunding ₹" . number_format($amount, 2) . " for $txnId\n";
        return true;
    }
}

// High-level: CLOSED for modification, works with any PaymentGateway
class PaymentProcessor
{
    public function __construct(private PaymentGateway $gateway) {}

    public function processPayment(float $amount, string $currency = 'INR'): string
    {
        echo "  Processing payment...\n";
        $txnId = $this->gateway->charge($amount, $currency);
        echo "  Transaction ID: $txnId\n";
        return $txnId;
    }
}

$stripe    = new PaymentProcessor(new StripeGateway());
$razorpay  = new PaymentProcessor(new RazorpayGateway());
$paypal    = new PaymentProcessor(new PayPalGateway()); // No existing code modified

$txn1 = $stripe->processPayment(1500.00);
$txn2 = $razorpay->processPayment(2000.00);
$txn3 = $paypal->processPayment(999.00);

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Define Open/Closed Principle.                                │
 * │ A: A class should be open for extension (you can add behavior)  │
 * │    but closed for modification (you don't edit existing code).  │
 * │    Achieved via interfaces, abstract classes, composition.       │
 * │                                                                  │
 * │ Q2: How does OCP relate to the Strategy pattern?                │
 * │ A: OCP is the principle; Strategy is one pattern that implements │
 * │    it. Strategy puts variable behavior behind an interface,      │
 * │    letting you add new strategies without modifying the context. │
 * │    Decorator pattern also embodies OCP — add behavior by wrapping│
 * │    instead of modifying the original class.                      │
 * │                                                                  │
 * │ Q3: Does OCP mean you NEVER modify a class?                     │
 * │ A: No — OCP means once a class is tested and deployed, you      │
 * │    shouldn't need to modify it to support new variations.        │
 * │    Bug fixes or genuine design mistakes are acceptable changes.  │
 * │                                                                  │
 * │ Q4: How do if/else chains violate OCP?                          │
 * │ A: Each new type/case requires opening and editing the class.   │
 * │    This risks introducing bugs in existing (already-tested)      │
 * │    branches. Replace with polymorphism: each case becomes a      │
 * │    new class implementing the same interface.                    │
 * │                                                                  │
 * │ Q5: What's the cost of over-applying OCP?                       │
 * │ A: You can create needless abstractions too early. YAGNI         │
 * │    (You Ain't Gonna Need It) — apply OCP when you know there    │
 * │    will be multiple variations, not prematurely.                 │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ First iteration may be simpler (if/else fine); refactor to   │
 * │   OCP on the second variation (Rule of Three).                  │
 * │ ✓ Don't treat config changes (e.g., tax rate changes) as OCP   │
 * │   violations — use configuration/constants, not new classes.    │
 * └─────────────────────────────────────────────────────────────────┘
 */
