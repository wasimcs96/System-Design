<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #9 — STRATEGY                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★★ (Very commonly asked)                       ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You have multiple algorithms for the same task.         │
 * │ Using `if/switch` to select an algorithm makes code hard to     │
 * │ maintain and extend:                                             │
 * │                                                                  │
 * │  function calculateDiscount(Cart $cart, string $type): float {  │
 * │    if ($type === 'percent') { return $cart->total * 0.1; }      │
 * │    if ($type === 'flat')    { return 100; }                      │
 * │    if ($type === 'loyalty') { return complex_calc($cart); }     │
 * │    // Adding new type = edit THIS function (violates OCP!)       │
 * │  }                                                               │
 * │                                                                  │
 * │ Strategy Pattern: Extract each algorithm into its own class.    │
 * │ The Context class receives a Strategy and delegates to it.       │
 * │ Adding a new algorithm = add new class, zero existing changes.  │
 * │                                                                  │
 * │ KEY RULE: Strategies are INTERCHANGEABLE — same interface,      │
 * │ different implementations. Can be swapped at runtime.           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Context (Cart)                                                  │
 * │  ├─ strategy: DiscountStrategy                                   │
 * │  ├─ setStrategy(DiscountStrategy)    ← swap at runtime           │
 * │  └─ applyDiscount()                  ← delegates to strategy     │
 * │         │                                                         │
 * │         ▼                                                         │
 * │  DiscountStrategy (interface)                                    │
 * │  ├── PercentageDiscount.calculate()                              │
 * │  ├── FlatDiscount.calculate()                                    │
 * │  ├── BuyOneGetOneFree.calculate()                                │
 * │  └── NoDiscount.calculate()                                      │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE STRATEGY                             │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Strategy interface with an execute() method      │
 * │ STEP 2: Create ConcreteStrategy classes for each algorithm      │
 * │ STEP 3: Context class accepts Strategy (constructor or setter)  │
 * │ STEP 4: Context delegates algorithm execution to Strategy        │
 * │ STEP 5: Client sets/changes strategy at runtime                  │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Shopping Cart Discount Strategies
// ═══════════════════════════════════════════════════════════════

// STEP 1: Strategy interface
interface DiscountStrategy
{
    /**
     * Calculate the discount amount (not final price — just the discount).
     *
     * @param float $subtotal  Cart subtotal before discount
     * @param array $items     Line items ['name', 'price', 'qty']
     */
    public function calculate(float $subtotal, array $items): float;
    public function getName(): string;
}

// STEP 2: Concrete Strategies

class NoDiscountStrategy implements DiscountStrategy
{
    public function calculate(float $subtotal, array $items): float
    {
        return 0.0; // No discount at all
    }
    public function getName(): string { return 'No Discount'; }
}

class PercentageDiscountStrategy implements DiscountStrategy
{
    public function __construct(private float $percentage) {}

    public function calculate(float $subtotal, array $items): float
    {
        return round($subtotal * ($this->percentage / 100), 2);
    }
    public function getName(): string { return "{$this->percentage}% Discount"; }
}

class FlatDiscountStrategy implements DiscountStrategy
{
    public function __construct(private float $discountAmount) {}

    public function calculate(float $subtotal, array $items): float
    {
        // Can't discount more than the subtotal
        return min($this->discountAmount, $subtotal);
    }
    public function getName(): string { return "₹{$this->discountAmount} Flat Off"; }
}

class MinimumOrderDiscountStrategy implements DiscountStrategy
{
    public function __construct(
        private float $minimumOrderValue,
        private float $discountAmount
    ) {}

    public function calculate(float $subtotal, array $items): float
    {
        if ($subtotal >= $this->minimumOrderValue) {
            return $this->discountAmount;
        }
        return 0.0; // No discount if minimum not met
    }
    public function getName(): string
    {
        return "₹{$this->discountAmount} off on orders over ₹{$this->minimumOrderValue}";
    }
}

class BuyTwoGetOneFreeStrategy implements DiscountStrategy
{
    public function calculate(float $subtotal, array $items): float
    {
        // Find cheapest item in every group of 3, make it free
        $prices = [];
        foreach ($items as $item) {
            for ($i = 0; $i < $item['qty']; $i++) {
                $prices[] = $item['price'];
            }
        }
        sort($prices); // Sort ascending so we compare cheapest

        $discount  = 0.0;
        $numGroups = intdiv(count($prices), 3);
        for ($i = 0; $i < $numGroups; $i++) {
            $discount += $prices[$i]; // Cheapest (first after sort) is free
        }
        return round($discount, 2);
    }
    public function getName(): string { return 'Buy 2 Get 1 Free'; }
}

// STEP 3 & 4: Context — Cart uses a DiscountStrategy
class ShoppingCart
{
    private array             $items    = [];
    private DiscountStrategy  $strategy;

    public function __construct(?DiscountStrategy $strategy = null)
    {
        // Default: no discount
        $this->strategy = $strategy ?? new NoDiscountStrategy();
    }

    // STEP 4: Allow runtime strategy swap
    public function setDiscountStrategy(DiscountStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function addItem(string $name, float $price, int $qty = 1): void
    {
        $this->items[] = ['name' => $name, 'price' => $price, 'qty' => $qty];
    }

    public function getSubtotal(): float
    {
        return array_sum(array_map(
            fn($item) => $item['price'] * $item['qty'],
            $this->items
        ));
    }

    public function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        $discount = $this->strategy->calculate($subtotal, $this->items);
        return max(0.0, $subtotal - $discount);
    }

    public function printSummary(): void
    {
        $subtotal = $this->getSubtotal();
        $discount = $this->strategy->calculate($subtotal, $this->items);
        echo "  Strategy: {$this->strategy->getName()}\n";
        foreach ($this->items as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            echo "    {$item['name']} x{$item['qty']} → ₹$lineTotal\n";
        }
        echo "  Subtotal: ₹$subtotal\n";
        echo "  Discount: -₹$discount\n";
        echo "  TOTAL:    ₹{$this->getTotal()}\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Sort Strategy (demonstrate runtime swapping)
// ═══════════════════════════════════════════════════════════════

interface SortStrategy
{
    public function sort(array &$data): void;
    public function getName(): string;
}

class BubbleSortStrategy implements SortStrategy
{
    public function sort(array &$data): void
    {
        $n = count($data);
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = 0; $j < $n - $i - 1; $j++) {
                if ($data[$j] > $data[$j + 1]) {
                    [$data[$j], $data[$j + 1]] = [$data[$j + 1], $data[$j]];
                }
            }
        }
    }
    public function getName(): string { return 'Bubble Sort O(n²)'; }
}

class QuickSortStrategy implements SortStrategy
{
    public function sort(array &$data): void
    {
        if (count($data) <= 1) return;
        sort($data); // PHP's built-in quicksort
    }
    public function getName(): string { return 'Quick Sort O(n log n)'; }
}

class Sorter
{
    public function __construct(private SortStrategy $strategy) {}

    public function setStrategy(SortStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function sort(array $data): array
    {
        $this->strategy->sort($data);
        return $data;
    }

    public function getStrategyName(): string { return $this->strategy->getName(); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== STRATEGY PATTERN DEMO ===\n\n";

echo "--- Example 1: Shopping Cart Discounts ---\n\n";

$cart = new ShoppingCart();
$cart->addItem('T-Shirt',  499.0, 2);
$cart->addItem('Jeans',   1999.0, 1);
$cart->addItem('Sneakers',2999.0, 1);

// No discount
$cart->setDiscountStrategy(new NoDiscountStrategy());
$cart->printSummary();

echo "\n";

// 10% off
$cart->setDiscountStrategy(new PercentageDiscountStrategy(10));
$cart->printSummary();

echo "\n";

// ₹500 flat
$cart->setDiscountStrategy(new FlatDiscountStrategy(500));
$cart->printSummary();

echo "\n";

// Buy 2 Get 1 Free
$cart->setDiscountStrategy(new BuyTwoGetOneFreeStrategy());
$cart->printSummary();

echo "\n";

// Minimum order discount
$cart->setDiscountStrategy(new MinimumOrderDiscountStrategy(5000, 300));
$cart->printSummary();

echo "\n--- Example 2: Sort Strategy Swapping ---\n";

$sorter = new Sorter(new BubbleSortStrategy());
$data   = [64, 34, 25, 12, 22, 11, 90];
$sorted = $sorter->sort($data);
echo "  {$sorter->getStrategyName()}: " . implode(', ', $sorted) . "\n";

// Runtime swap to faster algorithm for large datasets
$sorter->setStrategy(new QuickSortStrategy());
$sorted = $sorter->sort($data);
echo "  {$sorter->getStrategyName()}: " . implode(', ', $sorted) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Strategy pattern?                                │
 * │ A: Defines a family of algorithms, encapsulates each one, and  │
 * │    makes them interchangeable. The Context delegates the         │
 * │    algorithm to a Strategy object instead of implementing it     │
 * │    directly. Strategy can be swapped at runtime.                │
 * │                                                                  │
 * │ Q2: How is Strategy different from Template Method?             │
 * │ A: Strategy: Algorithm swapped via composition (inject         │
 * │    different object). No inheritance needed. Changes at runtime. │
 * │    Template Method: Algorithm structure defined in parent class, │
 * │    specific steps overridden in subclasses. Inheritance-based.  │
 * │    Strategy is more flexible; Template Method is simpler.        │
 * │                                                                  │
 * │ Q3: Strategy vs State — what's the difference?                   │
 * │ A: Strategy: Algorithms are independent, client manually         │
 * │    chooses which to use. Context doesn't know/care when to       │
 * │    switch.                                                        │
 * │    State: Object's behavior changes based on INTERNAL state.    │
 * │    Transitions happen automatically (state changes itself).     │
 * │                                                                  │
 * │ Q4: What SOLID principles does Strategy implement?               │
 * │ A: Open/Closed: Add new algorithm = new class, no existing      │
 * │    changes.                                                       │
 * │    Single Responsibility: Each strategy has ONE job.            │
 * │    Dependency Inversion: Context depends on Strategy interface, │
 * │    not concrete implementations.                                 │
 * │                                                                  │
 * │ Q5: Real-world PHP/Laravel examples?                             │
 * │ A: - Laravel's Auth guard: session vs token (swappable)         │
 * │    - Laravel's Cache store: redis vs file vs database            │
 * │    - Payment processors: Stripe vs PayPal vs Razorpay           │
 * │    - Sorting in data tables: sort by date vs price vs name      │
 * │    - Password hashing: bcrypt vs argon2 strategies              │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Default strategy in constructor prevents null pointer bugs    │
 * │ ✓ Strategies can be stateless (safe to share between contexts)  │
 * │ ✓ Context can provide data to strategy via method args or       │
 * │   constructor injection                                           │
 * │ ✓ Strategy factory: choose strategy based on user type/config   │
 * └─────────────────────────────────────────────────────────────────┘
 */
