<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #1 — CLASS & OBJECT                     ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (First question in every OOP interview)     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 1: CONCEPT DEFINITION
// ═══════════════════════════════════════════════════════════════
/**
 * BEGINNER EXPLANATION:
 *   A CLASS is a blueprint / template — like an architectural plan for a house.
 *   An OBJECT is an actual house built from that plan.
 *   You can build many houses (objects) from the same plan (class).
 *
 * TECHNICAL DEFINITION:
 *   CLASS  : A user-defined data type that encapsulates data (properties)
 *            and behavior (methods) into a single unit.
 *   OBJECT : A runtime instance of a class. Each object has its own copy
 *            of instance properties but shares the class's method definitions.
 *
 * REAL-WORLD ANALOGY:
 *   Class  → Cookie cutter (mold / blueprint)
 *   Object → Individual cookies pressed from the cutter
 *   Each cookie (object) can have different frosting (property values)
 *   but all are shaped the same way (same structure from the class).
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 2: VISUAL DIAGRAM
// ═══════════════════════════════════════════════════════════════
/**
 *  ┌──────────────────────────────────────────────┐
 *  │               CLASS: Car                      │
 *  ├──────────────────────────────────────────────┤
 *  │  Properties (State / Data):                   │
 *  │    - make   : string                          │
 *  │    - model  : string                          │
 *  │    - speed  : int                             │
 *  ├──────────────────────────────────────────────┤
 *  │  Methods (Behavior):                          │
 *  │    + accelerate(amount: int): void            │
 *  │    + brake(amount: int): void                 │
 *  │    + getInfo(): string                        │
 *  └──────────────────────────────────────────────┘
 *           │                     │
 *     new Car(...)           new Car(...)
 *           │                     │
 *  ┌────────────────┐   ┌────────────────┐
 *  │ OBJECT: $car1  │   │ OBJECT: $car2  │
 *  │ make  = Toyota │   │ make  = BMW    │
 *  │ model = Camry  │   │ model = X5     │
 *  │ speed = 0      │   │ speed = 0      │
 *  └────────────────┘   └────────────────┘
 *  Each object is independent — changing $car1 does NOT affect $car2.
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 3: CODE EXAMPLE
// ═══════════════════════════════════════════════════════════════

// Define the CLASS — the blueprint
class Car
{
    // Properties: the DATA an object holds
    private string $make;
    private string $model;
    private int    $speed = 0;       // Default: every new car starts at 0 speed
    private int    $maxSpeed;

    // Constructor: called automatically when `new Car(...)` is used
    public function __construct(string $make, string $model, int $maxSpeed = 200)
    {
        $this->make     = $make;
        $this->model    = $model;
        $this->maxSpeed = $maxSpeed;
    }

    // Method: accelerate — changes the object's STATE (speed)
    public function accelerate(int $amount): void
    {
        $this->speed = min($this->speed + $amount, $this->maxSpeed);
        echo "  {$this->make} {$this->model} accelerates to {$this->speed} km/h\n";
    }

    // Method: brake — reduces speed (never below 0)
    public function brake(int $amount): void
    {
        $this->speed = max($this->speed - $amount, 0);
        echo "  {$this->make} {$this->model} brakes to {$this->speed} km/h\n";
    }

    // Getter — read-only access to private data
    public function getSpeed(): int  { return $this->speed; }
    public function getMake(): string { return $this->make; }

    // Method returning formatted state
    public function getInfo(): string
    {
        return "{$this->make} {$this->model} | Speed: {$this->speed}/{$this->maxSpeed} km/h";
    }
}

// ─── DRIVER CODE ─────────────────────────────────────────────

echo "=== CLASS & OBJECT DEMO ===\n\n";

// Instantiate OBJECTS from the CLASS
$car1 = new Car('Toyota', 'Camry');        // Object 1
$car2 = new Car('BMW', 'X5', 250);        // Object 2 — different max speed
$car3 = new Car('Tesla', 'Model 3', 300); // Object 3

echo "--- Object 1 ($car1->getMake() Camry) ---\n";
// wait, can't call method inside string directly for complex — use separate echo
echo "--- Object 1 ---\n";
$car1->accelerate(80);
$car1->accelerate(60);
$car1->brake(30);
echo "  Info: " . $car1->getInfo() . "\n";

echo "\n--- Object 2 ---\n";
$car2->accelerate(150);
echo "  Info: " . $car2->getInfo() . "\n";

// KEY POINT: objects are INDEPENDENT
echo "\n--- Independence check ---\n";
echo "  car1 speed: " . $car1->getSpeed() . " km/h\n";
echo "  car2 speed: " . $car2->getSpeed() . " km/h\n";
echo "  car3 speed: " . $car3->getSpeed() . " km/h (never touched)\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 4: CLASS WITH STATIC (shared) vs INSTANCE (per-object) MEMBERS
// ═══════════════════════════════════════════════════════════════

class Counter
{
    private static int $totalCreated = 0;  // Shared across ALL objects
    private int        $id;                // Unique per object

    public function __construct()
    {
        self::$totalCreated++;
        $this->id = self::$totalCreated; // Each object gets next sequential ID
    }

    public function getId(): int            { return $this->id; }
    public static function getTotal(): int  { return self::$totalCreated; }
}

echo "\n--- Static vs Instance ---\n";
$c1 = new Counter();
$c2 = new Counter();
$c3 = new Counter();

echo "  c1 ID: " . $c1->getId() . "\n";  // 1
echo "  c2 ID: " . $c2->getId() . "\n";  // 2
echo "  c3 ID: " . $c3->getId() . "\n";  // 3
echo "  Total counters ever created: " . Counter::getTotal() . "\n"; // 3 (shared)

/**
 * ═══════════════════════════════════════════════════════════════
 * SECTION 5: STEP-BY-STEP EXPLANATION
 * ═══════════════════════════════════════════════════════════════
 *
 * Line: class Car { ... }
 *   → Defines a new TYPE named Car. No memory allocated yet.
 *
 * Line: $car1 = new Car('Toyota', 'Camry');
 *   → `new` allocates memory on the heap for a Car object.
 *   → Calls __construct('Toyota', 'Camry') — initializes properties.
 *   → $car1 holds a REFERENCE (pointer) to that object in memory.
 *
 * Line: $car1->accelerate(80);
 *   → The `->` operator accesses the method on THIS specific object.
 *   → $this inside the method refers to $car1's data.
 *
 * Line: $car2 = new Car('BMW', 'X5', 250);
 *   → Creates a SEPARATE object in memory.
 *   → $car2->speed is independent from $car1->speed.
 *
 * Object assignment:
 *   $carCopy = $car1;          ← Both refer to SAME object (reference copy)
 *   $carClone = clone $car1;   ← Creates a SEPARATE copy (different memory)
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 6: OBJECT REFERENCE vs CLONE
// ═══════════════════════════════════════════════════════════════

echo "\n--- Reference vs Clone ---\n";

$original = new Car('Honda', 'Civic');
$original->accelerate(50);

$reference = $original;         // Same object — reference copy
$clone     = clone $original;   // New object — independent copy

$reference->accelerate(30);     // Affects $original too!
echo "  original speed : " . $original->getSpeed() . " (changed via \$reference)\n";

$clone->accelerate(100);        // Does NOT affect $original
echo "  original speed : " . $original->getSpeed() . " (clone is independent)\n";
echo "  clone speed    : " . $clone->getSpeed() . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the difference between a class and an object?        │
 * │ A: Class is a blueprint/template (no memory allocated).          │
 * │    Object is an instance of a class (memory allocated at runtime)│
 * │    You can create multiple objects from one class, each with its │
 * │    own property values.                                          │
 * │                                                                  │
 * │ Q2: How many objects can be created from one class?              │
 * │ A: Unlimited — bounded only by available memory.                │
 * │                                                                  │
 * │ Q3: What is $this in PHP?                                        │
 * │ A: $this is a reference to the CURRENT object — the specific    │
 * │    instance on which the method was called. It lets methods      │
 * │    access and modify that object's own properties.               │
 * │                                                                  │
 * │ Q4: What is the difference between = and clone for objects?      │
 * │ A: $b = $a copies the REFERENCE — both $a and $b point to the  │
 * │    same object in memory. Changing one changes the other.        │
 * │    $b = clone $a creates a NEW independent copy of the object.  │
 * │                                                                  │
 * │ Q5: When is __destruct() called?                                 │
 * │ A: When the object goes out of scope or is explicitly unset().  │
 * │    Used for cleanup: closing DB connections, releasing resources.│
 * │                                                                  │
 * │ COMMON PITFALLS:                                                 │
 * │ ✗ Forgetting that object assignment is by reference — mutations  │
 * │   affect all variables pointing to the same object.             │
 * │ ✗ Assuming class properties have default values when they don't. │
 * │ ✗ Calling a method on null: $obj = null; $obj->method() → Error. │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Class = blueprint. Object = instance. new = instantiation.    │
 * │ ✓ Each object is independent in memory.                         │
 * │ ✓ $this refers to the current object inside methods.            │
 * │ ✓ Use clone for a true independent copy; = copies the reference. │
 * └─────────────────────────────────────────────────────────────────┘
 */
