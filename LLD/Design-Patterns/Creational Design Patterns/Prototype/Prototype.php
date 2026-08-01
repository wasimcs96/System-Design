<?php

declare(strict_types=1);

/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN — PROTOTYPE                          ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Creational Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : Low (not a headline round in any researched market ║
 * ║               — see Part 2 of the companion guide). Know it well ║
 * ║               as a follow-up, don't over-invest prep time here.  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: Object construction is expensive (I/O, parsing, crypto) │
 * │ and you need MANY structurally-similar instances built from the  │
 * │ same baseline configuration.                                     │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ THE #1 RULE OF THIS ENTIRE FILE                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ PHP's `clone` is SHALLOW by default:                             │
 * │   - scalar properties → copied by VALUE automatically. Safe.     │
 * │   - object properties (and arrays containing objects) → copied   │
 * │     by REFERENCE (shared handle). You must override __clone()    │
 * │     and manually re-clone them, or two "independent" objects     │
 * │     will silently share mutable state.                           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Full theory, market data, interview bank, and the Pattern Recognition
 * Drill live in the companion file: Prototype-Design-Pattern-Guide.md
 * (same folder). This file is the lab — every example referenced there
 * is implemented and runnable here.
 *
 * Run it with:  php Prototype.php
 */

// ═════════════════════════════════════════════════════════════════════════
// TIER 1 — BASIC CLONE MECHANICS  (companion guide: Part 9, Internal Working)
// ═════════════════════════════════════════════════════════════════════════
// Isolates the LANGUAGE MECHANIC from the design pattern. Only scalar
// properties here, so the default shallow clone is perfectly safe —
// the only thing being proven: clone does NOT call __construct().

class SimpleCounter
{
    public int $value = 0;
    public string $label;

    public function __construct(string $label)
    {
        echo "  [SimpleCounter] Constructor ran for '{$label}' (expensive setup simulated)\n";
        $this->label = $label;
    }
}

// ═════════════════════════════════════════════════════════════════════════
// TIER 2 — THE SHALLOW-COPY BUG, THEN THE FIX  (guide: Part 9 + Part 18)
// ═════════════════════════════════════════════════════════════════════════

class Address
{
    public function __construct(public string $city)
    {
    }
}

/** BUGGY — no __clone() override. Kept deliberately so driver code can
 *  PROVE the bug before showing the fix. Never ship this shape. */
class CustomerBuggy
{
    public Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }
}

/** FIXED — __clone() explicitly re-clones the nested Address. */
class CustomerFixed
{
    public Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    public function __clone(): void
    {
        // Runs AFTER PHP's shallow copy already exists. Replace the
        // shared reference with an independent clone.
        $this->address = clone $this->address;
    }
}

// ═════════════════════════════════════════════════════════════════════════
// TIER 3 — PRODUCTION-SHAPED: INVOICE PROTOTYPE REGISTRY  (guide: Part 12)
// ═════════════════════════════════════════════════════════════════════════
// The example to reproduce from memory in the Timed Mock Drill (guide Part 5).

class TaxProfile
{
    public function __construct(
        public string $country,
        public float $rate,
    ) {
    }
}

class InvoicePrototype
{
    public function __construct(
        // Expensive, shared per jurisdiction:
        public string $companyName,
        public TaxProfile $taxProfile,
        public string $currency,
        public string $pdfTemplate,
        // Cheap, order-specific — always overwritten after cloning:
        public ?int $orderId = null,
        public ?string $customerName = null,
        public ?float $amount = null,
    ) {
    }

    /**
     * Deep-copies every MUTABLE object-typed property. companyName,
     * currency, pdfTemplate are scalars -> safe by default. taxProfile
     * is an object -> MUST be re-cloned, or every invoice for this
     * jurisdiction shares ONE TaxProfile instance, and a one-off rate
     * override on one order would silently corrupt every other pending
     * invoice for that region.
     */
    public function __clone(): void
    {
        $this->taxProfile = clone $this->taxProfile;
    }

    public function withOrder(int $orderId, string $customerName, float $amount): static
    {
        $clone = clone $this;
        $clone->orderId = $orderId;
        $clone->customerName = $customerName;
        $clone->amount = $amount;
        return $clone;
    }

    public function describe(): string
    {
        return sprintf(
            "Invoice #%s | %s | %s%.2f %s tax=%.1f%% | template=%s | customer=%s",
            $this->orderId ?? '???',
            $this->companyName,
            $this->currency,
            $this->amount ?? 0.0,
            $this->taxProfile->country,
            $this->taxProfile->rate * 100,
            $this->pdfTemplate,
            $this->customerName ?? '???',
        );
    }
}

/**
 * Keyed registry (guide Part 10/12). Clients never touch the master
 * directly — get()/forOrder() always hand back a clone via withOrder(),
 * never the stored instance.
 */
class InvoicePrototypeRegistry
{
    /** @var array<string, InvoicePrototype> */
    private array $prototypes = [];

    public function register(string $key, InvoicePrototype $prototype): void
    {
        $this->prototypes[$key] = $prototype;
    }

    /** Simulates the registry rebuild that fires on a TaxRulesUpdated
     *  domain event (guide Part 12's ADR) instead of a blind TTL. */
    public function rebuild(string $key, InvoicePrototype $freshPrototype): void
    {
        echo "  [Registry] Rebuilding prototype for key '{$key}' (e.g. tax rules changed)\n";
        $this->prototypes[$key] = $freshPrototype;
    }

    public function forOrder(string $key, int $orderId, string $customerName, float $amount): InvoicePrototype
    {
        if (!isset($this->prototypes[$key])) {
            throw new \InvalidArgumentException("No prototype registered for key '{$key}'");
        }

        return $this->prototypes[$key]->withOrder($orderId, $customerName, $amount);
    }
}

// ═════════════════════════════════════════════════════════════════════════
// REFACTORING JOURNEY — STAGES 1-3  (guide: Part 19)
// ═════════════════════════════════════════════════════════════════════════
// Stage 4 and 5 are the InvoicePrototype + InvoicePrototypeRegistry above.

/** STAGE 1 — TERRIBLE. Every field rebuilt from scratch every call. */
final class Stage1_NaiveInvoice
{
    public function __construct(
        public string $companyName,
        public float $taxRate,
        public string $pdfTemplate,
        public int $orderId,
        public float $amount,
    ) {
        // Imagine: fetchCompanyBranding(), fetchTaxRate(), loadPdfTemplate()
        // all happen HERE, inline, on every single construction.
    }
}

/** STAGE 2 — BAD (a realistic first instinct, not a strawman). Hand-rolled
 *  static caching of individual fields. Solves the symptom, not the cause. */
final class Stage2_StaticCachedInvoice
{
    private static ?string $cachedCompanyName = null;
    private static ?float $cachedTaxRate = null;

    public int $orderId;
    public float $amount;
    public string $companyName;
    public float $taxRate;

    public function __construct(int $orderId, float $amount)
    {
        self::$cachedCompanyName ??= 'ABC Pvt Ltd';
        self::$cachedTaxRate ??= 0.05;

        $this->companyName = self::$cachedCompanyName;
        $this->taxRate = self::$cachedTaxRate;
        $this->orderId = $orderId;
        $this->amount = $amount;
    }
}

/** STAGE 3 — AVERAGE, and the most dangerous stage (guide Part 19).
 *  Correct split, but NO __clone() override — looks finished, isn't. */
final class Stage3_IncompletePrototype
{
    public function __construct(
        public string $companyName,
        public TaxProfile $taxProfile, // <-- object property, no __clone() below!
        public int $orderId = 0,
        public float $amount = 0.0,
    ) {
    }

    // Deliberately no __clone() here — this IS the bug.
}

// ═════════════════════════════════════════════════════════════════════════
// CODING PROBLEMS  (guide: Part 21) — solutions
// ═════════════════════════════════════════════════════════════════════════

/** PROBLEM 1 — "Fix the Leak" — solution = CustomerFixed above. */
/** PROBLEM 2 — "Build a Registry" — solution = InvoicePrototypeRegistry above. */

/** PROBLEM 3 — "Deep Clone a Three-Level Graph": Order -> CustomerFixed ->
 *  Address. Each level is individually responsible for cloning its OWN
 *  immediate object properties — the cascade only works if every level
 *  does its part. This is the detail most candidates get wrong. */
class Order
{
    public function __construct(
        public int $orderId,
        public CustomerFixed $customer,
    ) {
    }

    public function __clone(): void
    {
        // Order clones ITS immediate object property (customer).
        // CustomerFixed separately clones ITS immediate object property
        // (address). Neither level can skip its own responsibility.
        $this->customer = clone $this->customer;
    }
}

// ═════════════════════════════════════════════════════════════════════════
// SELF-ASSESSMENT EXERCISE — REFERENCE SOLUTION  (guide: Part 22)
// ═════════════════════════════════════════════════════════════════════════
// "Refactor ReportGenerator into a correctly-implemented Prototype."
// Attempt it yourself in the guide BEFORE reading this.

class ChartStyle
{
    public function __construct(public string $colorScheme, public string $fontFamily)
    {
    }
}

class ReportPrototype
{
    public function __construct(
        public ChartStyle $chartStyle,   // expensive/shared, object -> needs cloning
        public string $companyLogoUrl,   // expensive/shared, scalar -> safe by default
        public ?string $dateRange = null,
        public ?array $metrics = null,
    ) {
    }

    public function __clone(): void
    {
        $this->chartStyle = clone $this->chartStyle;
        // metrics is an array of scalars in this exercise -> safe by default.
        // If metrics ever held objects, THAT would need explicit cloning too.
    }

    public function forReport(string $dateRange, array $metrics): static
    {
        $clone = clone $this;
        $clone->dateRange = $dateRange;
        $clone->metrics = $metrics;
        return $clone;
    }
}

// ═════════════════════════════════════════════════════════════════════════
// DRIVER CODE — RUNS EVERY TIER AS A DEMO WITH PRINTED PROOF
// ═════════════════════════════════════════════════════════════════════════

echo "=== PROTOTYPE PATTERN — FULL DEMO ===\n\n";

echo "--- TIER 1: clone does NOT call __construct() ---\n";
$counterPrototype = new SimpleCounter('master'); // constructor runs ONCE, here
$counterA = clone $counterPrototype;              // no constructor output below
$counterB = clone $counterPrototype;              // no constructor output below
$counterA->value = 5;
$counterB->value = 99;
echo "  counterA->value = {$counterA->value}, counterB->value = {$counterB->value}\n";
echo "  (constructor ran exactly ONCE above, for both clones combined)\n\n";

echo "--- TIER 2a: THE BUG (shallow copy, no __clone()) ---\n";
$buggyOriginal = new CustomerBuggy(new Address('Dubai'));
$buggyClone = clone $buggyOriginal;
$buggyClone->address->city = 'Riyadh';
echo "  buggyClone->address->city    = {$buggyClone->address->city}\n";
echo "  buggyOriginal->address->city = {$buggyOriginal->address->city}"
    . " <-- BUG! changed too, they share ONE Address object\n";
echo "  Same object? " . ($buggyClone->address === $buggyOriginal->address ? "YES (bug confirmed)" : "no") . "\n\n";

echo "--- TIER 2b: THE FIX (__clone() re-clones the nested object) ---\n";
$fixedOriginal = new CustomerFixed(new Address('Dubai'));
$fixedClone = clone $fixedOriginal;
$fixedClone->address->city = 'Riyadh';
echo "  fixedClone->address->city    = {$fixedClone->address->city}\n";
echo "  fixedOriginal->address->city = {$fixedOriginal->address->city} <-- correct, unaffected\n";
echo "  Same object? " . ($fixedClone->address === $fixedOriginal->address ? "YES (still buggy)" : "NO (fixed, independent)") . "\n\n";

echo "--- TIER 3: PRODUCTION-SHAPED — INVOICE PROTOTYPE REGISTRY ---\n";
$registry = new InvoicePrototypeRegistry();
$registry->register('invoice.uae', new InvoicePrototype(
    companyName: 'Gulf Commerce LLC',
    taxProfile: new TaxProfile('UAE', 0.05),
    currency: 'AED',
    pdfTemplate: 'invoice-template-ar-en-v3',
));
$registry->register('invoice.ksa', new InvoicePrototype(
    companyName: 'Gulf Commerce LLC',
    taxProfile: new TaxProfile('KSA', 0.15),
    currency: 'SAR',
    pdfTemplate: 'invoice-template-ar-v2',
));

$invoice1 = $registry->forOrder('invoice.uae', 1001, 'Wasim', 2500.00);
$invoice2 = $registry->forOrder('invoice.uae', 1002, 'Ali', 4300.50);
$invoice3 = $registry->forOrder('invoice.ksa', 2001, 'Fatima', 1800.00);

echo "  " . $invoice1->describe() . "\n";
echo "  " . $invoice2->describe() . "\n";
echo "  " . $invoice3->describe() . "\n";

$invoice1->taxProfile->rate = 0.00; // simulate a one-off tax exemption
echo "\n  After giving invoice #1001 a manual 0% tax exemption:\n";
echo "  " . $invoice1->describe() . "\n";
echo "  " . $invoice2->describe() . " <-- unaffected, correct\n\n";

echo "--- TIER 3b: registry rebuild after a simulated TaxRulesUpdated event ---\n";
$registry->rebuild('invoice.uae', new InvoicePrototype(
    companyName: 'Gulf Commerce LLC',
    taxProfile: new TaxProfile('UAE', 0.06), // rate changed 5% -> 6%
    currency: 'AED',
    pdfTemplate: 'invoice-template-ar-en-v3',
));
$invoice4 = $registry->forOrder('invoice.uae', 1003, 'Noor', 1000.00);
echo "  " . $invoice4->describe() . " <-- picks up the NEW 6% rate\n\n";

echo "--- STAGE 3 DEMO: 'looks finished but isn't' at production scale ---\n";
$stage3Original = new Stage3_IncompletePrototype('ABC Pvt Ltd', new TaxProfile('IN', 0.18), 5001, 999.00);
$stage3Clone = clone $stage3Original; // shallow clone — no __clone() defined
$stage3Clone->taxProfile->rate = 0.00;
echo "  stage3Clone tax rate    = {$stage3Clone->taxProfile->rate}\n";
echo "  stage3Original tax rate = {$stage3Original->taxProfile->rate}"
    . " <-- BUG! Stage 3 has no __clone(), same failure as CustomerBuggy\n\n";

echo "--- PROBLEM 3: three-level deep clone (Order -> CustomerFixed -> Address) ---\n";
$originalOrder = new Order(9001, new CustomerFixed(new Address('Kuala Lumpur')));
$clonedOrder = clone $originalOrder;
$clonedOrder->customer->address->city = 'Penang';
echo "  clonedOrder customer address   = {$clonedOrder->customer->address->city}\n";
echo "  originalOrder customer address = {$originalOrder->customer->address->city}"
    . " <-- correct, unaffected at BOTH levels\n";
echo "  Level 1 independent (Customer)? "
    . ($clonedOrder->customer !== $originalOrder->customer ? "YES" : "NO") . "\n";
echo "  Level 2 independent (Address)?  "
    . ($clonedOrder->customer->address !== $originalOrder->customer->address ? "YES" : "NO") . "\n\n";

echo "--- SELF-ASSESSMENT EXERCISE: ReportPrototype ---\n";
$reportMaster = new ReportPrototype(
    chartStyle: new ChartStyle('blue-gradient', 'Inter'),
    companyLogoUrl: 'https://cdn.example.com/logo.png',
);
$q1Report = $reportMaster->forReport('2026-Q1', ['revenue', 'churn']);
$q2Report = $reportMaster->forReport('2026-Q2', ['revenue', 'nps']);
$q1Report->chartStyle->colorScheme = 'red-gradient';
echo "  Q1 report colorScheme = {$q1Report->chartStyle->colorScheme}\n";
echo "  Q2 report colorScheme = {$q2Report->chartStyle->colorScheme} <-- unaffected, correct\n\n";

echo "--- CLONE-INDEPENDENCE ASSERTIONS (guide Part 18 style) ---\n";
$assertions = [
    'Tier2 fix: address independent'                       => $fixedClone->address !== $fixedOriginal->address,
    'Tier3: invoice1 taxProfile independent from invoice2'  => $invoice1->taxProfile !== $invoice2->taxProfile,
    'Order: customer independent'                           => $clonedOrder->customer !== $originalOrder->customer,
    'Order: address independent (deep, level 2)'            => $clonedOrder->customer->address !== $originalOrder->customer->address,
    'ReportPrototype: chartStyle independent'                => $q1Report->chartStyle !== $q2Report->chartStyle,
];
foreach ($assertions as $name => $passed) {
    echo "  [" . ($passed ? 'PASS' : 'FAIL') . "] {$name}\n";
}

echo "\n=== DEMO COMPLETE ===\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ CONDENSED Q&A (full bank with wrong/good/excellent/follow-up      │
 * │ structure is in the .md guide, Part 21)                           │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Does clone call __construct()?                               │
 * │ A: No. Shallow property copy + __clone() if defined.             │
 * │                                                                  │
 * │ Q2: Is PHP's clone deep or shallow by default?                   │
 * │ A: Shallow. Object properties copy as SHARED REFERENCES unless   │
 * │    __clone() explicitly re-clones them.                          │
 * │                                                                  │
 * │ Q3: Does Laravel's replicate() use clone/__clone()?              │
 * │ A: No — verified against source (guide Part 11). It builds via   │
 * │    new static() and manually copies filtered attributes. It IS   │
 * │    Prototype in intent, not in mechanism.                        │
 * │                                                                  │
 * │ Q4: Why does forOrder() call withOrder() instead of returning    │
 * │     the stored prototype directly?                               │
 * │ A: Returning the master directly would let any caller mutate the │
 * │    ONE shared prototype for that key, corrupting every future    │
 * │    order. withOrder() clones internally so callers always get an │
 * │    independent instance.                                         │
 * │                                                                  │
 * │ Q5: In Order/CustomerFixed/Address, why does each level need its │
 * │     OWN __clone() override?                                      │
 * │ A: __clone() only fixes up properties on the object it's defined │
 * │    on. Skip a level and that level's object graph stays shallow. │
 * │                                                                  │
 * │ Q6: When should you NOT reach for this pattern?                  │
 * │ A: Cheap-to-construct objects, or session/auth/payment-           │
 * │    transaction state — cloning that risks leaking shared mutable │
 * │    state across logically independent operations.                │
 * └─────────────────────────────────────────────────────────────────┘
 */
