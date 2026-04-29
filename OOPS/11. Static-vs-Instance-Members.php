<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║        OOP CONCEPT #11 — STATIC vs INSTANCE MEMBERS             ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★☆  (static methods/LSB asked frequently)       ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * INSTANCE MEMBERS:
 *   - Belong to a specific OBJECT
 *   - Accessed via $this->property / $this->method()
 *   - Each object has its OWN copy of instance properties
 *   - Require object creation (new ClassName())
 *
 * STATIC MEMBERS:
 *   - Belong to the CLASS itself (not any individual object)
 *   - Accessed via ClassName::property / ClassName::method()
 *   - SHARED across all instances (one copy for entire class)
 *   - Accessible without creating an object
 *
 * ANALOGY:
 *   Instance: Each employee has their own $name (per object)
 *   Static:   The company headcount $totalEmployees (shared, class-level)
 */

// ═══════════════════════════════════════════════════════════════
// 1. INSTANCE vs STATIC PROPERTIES
// ═══════════════════════════════════════════════════════════════

class Counter
{
    private static int $totalCreated = 0;  // STATIC — shared by all instances
    private int        $count = 0;          // INSTANCE — each object has its own

    public function __construct(private string $name)
    {
        self::$totalCreated++;
        echo "  Counter '{$name}' created (total: " . self::$totalCreated . ")\n";
    }

    public function increment(int $by = 1): void { $this->count += $by; }
    public function decrement(): void             { $this->count = max(0, $this->count - 1); }
    public function reset(): void                 { $this->count = 0; }

    public function getCount(): int          { return $this->count; }
    public function getName(): string        { return $this->name; }
    public static function getTotalCreated(): int { return self::$totalCreated; }
}

echo "=== STATIC vs INSTANCE MEMBERS DEMO ===\n\n";

echo "--- 1. Instance vs Static Properties ---\n";
$c1 = new Counter('pageViews');
$c2 = new Counter('clicks');
$c3 = new Counter('errors');

$c1->increment(50);
$c1->increment(10);
$c2->increment(7);

echo "  {$c1->getName()}: " . $c1->getCount() . "\n";  // 60 (own count)
echo "  {$c2->getName()}: " . $c2->getCount() . "\n";  // 7  (own count)
echo "  {$c3->getName()}: " . $c3->getCount() . "\n";  // 0  (own count)
echo "  Total counters created: " . Counter::getTotalCreated() . "\n"; // 3 (SHARED)

// ═══════════════════════════════════════════════════════════════
// 2. STATIC METHODS — utility functions, no instance needed
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Static Methods ---\n";

class StringHelper
{
    // Pure utility — no state needed, therefore static
    public static function slug(string $title): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title)));
    }

    public static function truncate(string $text, int $max = 100): string
    {
        return strlen($text) <= $max ? $text : substr($text, 0, $max - 3) . '...';
    }

    public static function camelToSnake(string $camel): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($camel)));
    }

    public static function initials(string $name): string
    {
        return implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $name)));
    }
}

// No object needed — called directly on class
echo "  slug:    " . StringHelper::slug("Hello World! PHP 8 is Great") . "\n";
echo "  truncate: " . StringHelper::truncate("This is a long text that will get cut", 25) . "\n";
echo "  camel:   " . StringHelper::camelToSnake('getUserByEmail') . "\n";
echo "  initials: " . StringHelper::initials('Alan Mathison Turing') . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. self:: vs static:: — Late Static Binding (LSB)
// ═══════════════════════════════════════════════════════════════
/**
 *  self::  — refers to the class where the method is DEFINED (compile-time)
 *  static:: — refers to the class that was CALLED (runtime) = Late Static Binding
 *
 *  This matters when a static method is inherited and overridden.
 */

echo "\n--- 3. self:: vs static:: (Late Static Binding) ---\n";

class ParentClass
{
    protected static string $type = 'Parent';

    public static function createSelf(): static
    {
        // self:: → always creates ParentClass, even when called on ChildClass
        return new self();
    }

    public static function createStatic(): static
    {
        // static:: → creates whatever class this was called on (LSB)
        return new static();
    }

    public static function getType(): string
    {
        return static::$type;  // LSB — reads child's $type if overridden
    }

    public function getClass(): string { return static::class; }
}

class ChildClass extends ParentClass
{
    protected static string $type = 'Child';
}

$fromSelf   = ChildClass::createSelf();    // self::  → creates ParentClass!
$fromStatic = ChildClass::createStatic();  // static:: → creates ChildClass

echo "  createSelf()   → " . $fromSelf->getClass() . "\n";    // ParentClass
echo "  createStatic() → " . $fromStatic->getClass() . "\n";  // ChildClass
echo "  ParentClass::getType() = " . ParentClass::getType() . "\n"; // Parent
echo "  ChildClass::getType()  = " . ChildClass::getType() . "\n";  // Child (LSB)

// ═══════════════════════════════════════════════════════════════
// 4. SINGLETON PATTERN — Practical use of static
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. Singleton — Practical Static Usage ---\n";

class AppContainer
{
    private static ?AppContainer $instance = null;
    private array $bindings = [];

    // Private constructor — cannot instantiate directly
    private function __construct()
    {
        echo "  [AppContainer] Initialized\n";
    }

    // Static factory — returns the single shared instance
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function bind(string $key, callable $factory): void
    {
        $this->bindings[$key] = $factory;
    }

    public function make(string $key): mixed
    {
        if (!isset($this->bindings[$key])) {
            throw new \RuntimeException("No binding for: {$key}");
        }
        return ($this->bindings[$key])();
    }
}

$c1 = AppContainer::getInstance();
$c2 = AppContainer::getInstance();

echo "  Same instance? " . ($c1 === $c2 ? 'YES' : 'NO') . "\n";  // YES

$c1->bind('greeter', fn() => new class { public function hello(): string { return "Hello!"; } });
echo "  " . $c2->make('greeter')->hello() . "\n";  // c2 sees c1's binding (same instance)

// ═══════════════════════════════════════════════════════════════
// 5. WHEN TO USE STATIC vs INSTANCE — Decision guide
// ═══════════════════════════════════════════════════════════════

/**
 * USE STATIC WHEN:
 *   ✓ Pure utility / helper functions (no object state)
 *   ✓ Factory methods (named constructors)
 *   ✓ Singleton pattern (controlled shared state)
 *   ✓ Constants or configuration shared across all objects
 *   ✓ Registry/lookup that should be global
 *
 * USE INSTANCE WHEN:
 *   ✓ Behavior depends on the object's own data ($this->state)
 *   ✓ Multiple independent objects with different states
 *   ✓ You need to inject dependencies (DI)
 *   ✓ The class will be tested with mocking/substitution
 *
 * ❌ AVOID:
 *   Static state for things that should vary per request/user
 *   Static everywhere "because it's simpler" — kills testability
 */

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the difference between static and instance methods? │
 * │ A: Instance methods operate on an object's data using $this.   │
 * │    Static methods belong to the class (no $this), accessed via  │
 * │    ClassName::method(). Static is for utility, factory, or     │
 * │    shared state; instance is for per-object behavior.           │
 * │                                                                  │
 * │ Q2: What is Late Static Binding? Difference from self::?        │
 * │ A: `self::` resolves to the class where the method is WRITTEN.  │
 * │    `static::` resolves to the class that was CALLED at runtime  │
 * │    (Late Static Binding). This matters for factory methods and  │
 * │    inheritance: static:: creates the correct child class.       │
 * │                                                                  │
 * │ Q3: Can you use $this inside a static method?                   │
 * │ A: No. Static methods have no object context, so $this is       │
 * │    unavailable. Attempting to use $this in a static method      │
 * │    causes a fatal error.                                        │
 * │                                                                  │
 * │ Q4: Why is overusing static methods bad?                        │
 * │ A: Static methods are hard to mock in tests (can't inject),    │
 * │    create hidden global state, tightly couple callers to a     │
 * │    specific class, and make code harder to extend via DI.       │
 * │    Prefer instance methods with interface type hints.           │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Static properties hold state across requests in long-running │
 * │   PHP (Swoole/RoadRunner) — always reset between requests.     │
 * │ ✗ Calling self:: in base class factory — creates base class    │
 * │   objects even when child calls it. Use static:: for LSB.      │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Static = class-level, shared, no $this.                      │
 * │ ✓ Instance = object-level, own state, uses $this.              │
 * │ ✓ static:: for LSB in factory methods and polymorphic static.  │
 * │ ✓ Prefer instance with DI for testable, flexible design.       │
 * └─────────────────────────────────────────────────────────────────┘
 */
