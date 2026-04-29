<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #4 — INHERITANCE                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (All 5 types asked in senior interviews)    ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * BEGINNER EXPLANATION:
 *   Inheritance allows a class (child) to reuse properties and methods
 *   from another class (parent). Like a child inheriting traits from parents.
 *
 * TECHNICAL DEFINITION:
 *   A mechanism where a derived class (subclass) acquires properties
 *   and behaviors from a base class (superclass) using `extends`.
 *   Promotes code reuse, and establishes IS-A relationships.
 *
 * TYPES OF INHERITANCE:
 *   1. Single        : One child extends one parent
 *   2. Multilevel    : A → B → C (chain)
 *   3. Hierarchical  : One parent → multiple children
 *   4. Multiple      : PHP doesn't allow (use interfaces / traits)
 *   5. Hybrid        : Combination of above types
 *
 * IS-A RELATIONSHIP:
 *   Dog IS-A Animal ✓   (Dog extends Animal)
 *   Dog HAS-A Tail  ✓   (composition — not inheritance)
 *   Square IS-A Rectangle ✗ (LSP violation — see SOLID files)
 */

// ═══════════════════════════════════════════════════════════════
// TYPE 1: SINGLE INHERITANCE — One child, one parent
// ═══════════════════════════════════════════════════════════════
/**
 *  Animal
 *    └── Dog
 */

class Animal
{
    protected string $name;
    protected int    $age;

    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age  = $age;
    }

    public function breathe(): void  { echo "  {$this->name} is breathing.\n"; }
    public function eat(): void      { echo "  {$this->name} is eating.\n"; }
    public function getInfo(): string { return "{$this->name} (age {$this->age})"; }
}

// Dog IS-A Animal — inherits breathe(), eat(), getInfo()
class Dog extends Animal
{
    private string $breed;

    public function __construct(string $name, int $age, string $breed)
    {
        parent::__construct($name, $age); // Call parent constructor FIRST
        $this->breed = $breed;
    }

    // Dog-specific behavior (NEW method)
    public function bark(): void { echo "  {$this->name} barks: Woof! Woof!\n"; }
    public function fetch(): void { echo "  {$this->name} fetches the ball!\n"; }

    // Override parent method — ADD to it
    public function getInfo(): string
    {
        return parent::getInfo() . " | Breed: {$this->breed}";
    }
}

echo "=== INHERITANCE DEMO ===\n\n";

echo "--- Type 1: Single Inheritance ---\n";
$dog = new Dog('Buddy', 3, 'Golden Retriever');
$dog->breathe();   // Inherited from Animal
$dog->eat();       // Inherited from Animal
$dog->bark();      // Dog-specific
echo "  " . $dog->getInfo() . "\n"; // Overridden

// ═══════════════════════════════════════════════════════════════
// TYPE 2: MULTILEVEL INHERITANCE — Chain: A → B → C
// ═══════════════════════════════════════════════════════════════
/**
 *  Vehicle
 *    └── Car
 *          └── ElectricCar
 */

class Vehicle
{
    public function __construct(protected string $brand, protected int $year) {}

    public function start(): void { echo "  {$this->brand} engine started.\n"; }
    public function stop(): void  { echo "  {$this->brand} engine stopped.\n"; }
}

class Car extends Vehicle
{
    public function __construct(string $brand, int $year, protected int $doors)
    {
        parent::__construct($brand, $year);
    }

    public function drive(): void { echo "  {$this->brand} ({$this->doors}-door) is driving.\n"; }
}

// ElectricCar inherits from Car, which inherits from Vehicle
class ElectricCar extends Car
{
    private int $batteryPercent = 100;

    public function __construct(string $brand, int $year, int $doors, private int $rangKm)
    {
        parent::__construct($brand, $year, $doors);
    }

    // Override start() — electric cars don't have combustion engines
    public function start(): void
    {
        echo "  {$this->brand} silently powers on (electric).\n";
    }

    public function charge(): void { echo "  {$this->brand} charging... battery at {$this->batteryPercent}%\n"; }
    public function getRange(): string { return "{$this->rangKm} km range"; }
}

echo "\n--- Type 2: Multilevel Inheritance ---\n";
$tesla = new ElectricCar('Tesla', 2024, 4, 580);
$tesla->start();   // Overridden in ElectricCar
$tesla->drive();   // Inherited from Car
$tesla->stop();    // Inherited from Vehicle
$tesla->charge();  // ElectricCar specific
echo "  Range: " . $tesla->getRange() . "\n";

// ═══════════════════════════════════════════════════════════════
// TYPE 3: HIERARCHICAL INHERITANCE — One parent, multiple children
// ═══════════════════════════════════════════════════════════════
/**
 *  Employee
 *    ├── Manager
 *    ├── Developer
 *    └── Designer
 */

class Employee
{
    public function __construct(
        protected string $name,
        protected float  $baseSalary
    ) {}

    public function work(): void   { echo "  {$this->name} is working.\n"; }
    public function getPayslip(): string
    {
        return "  {$this->name}: Base=₹" . number_format($this->baseSalary);
    }
}

class Manager extends Employee
{
    public function __construct(string $name, float $base, private int $teamSize)
    {
        parent::__construct($name, $base);
    }

    public function conductMeeting(): void { echo "  {$this->name} is running a meeting.\n"; }

    public function getPayslip(): string
    {
        $bonus = $this->baseSalary * 0.30;
        return parent::getPayslip() . " + Bonus=₹" . number_format($bonus)
               . " | Team size: {$this->teamSize}";
    }
}

class Developer extends Employee
{
    public function __construct(string $name, float $base, private string $stack)
    {
        parent::__construct($name, $base);
    }

    public function writeCode(): void { echo "  {$this->name} ({$this->stack}) is coding.\n"; }

    public function getPayslip(): string
    {
        $bonus = $this->baseSalary * 0.20;
        return parent::getPayslip() . " + Bonus=₹" . number_format($bonus)
               . " | Stack: {$this->stack}";
    }
}

class Designer extends Employee
{
    public function __construct(string $name, float $base, private string $tool)
    {
        parent::__construct($name, $base);
    }

    public function design(): void { echo "  {$this->name} designing with {$this->tool}.\n"; }
}

echo "\n--- Type 3: Hierarchical Inheritance ---\n";
$employees = [
    new Manager('Ravi', 120000, 8),
    new Developer('Alice', 90000, 'PHP/Laravel'),
    new Designer('Bob', 70000, 'Figma'),
];

foreach ($employees as $emp) {
    echo $emp->getPayslip() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// TYPE 4: MULTIPLE INHERITANCE — PHP uses Traits (not extends)
// ═══════════════════════════════════════════════════════════════
/**
 *  PHP does NOT allow: class C extends A, B
 *  Solution: Use TRAITS — reusable method bundles
 *
 *  Flyable ──┐
 *  Swimmable ─┴─► Duck (uses both traits)
 */

trait Flyable
{
    public function fly(): void { echo "  " . static::class . " is flying!\n"; }
    public function land(): void { echo "  " . static::class . " lands.\n"; }
}

trait Swimmable
{
    public function swim(): void { echo "  " . static::class . " is swimming!\n"; }
    public function dive(): void { echo "  " . static::class . " dives.\n"; }
}

class Duck extends Animal
{
    use Flyable, Swimmable; // Multiple trait usage = "multiple inheritance" in PHP

    public function __construct(string $name)
    {
        parent::__construct($name, 2);
    }

    public function quack(): void { echo "  {$this->name}: Quack quack!\n"; }
}

class Fish extends Animal
{
    use Swimmable; // Only uses Swimmable

    public function __construct(string $name)
    {
        parent::__construct($name, 1);
    }
}

echo "\n--- Type 4: Multiple Inheritance via Traits ---\n";
$duck = new Duck('Donald');
$duck->eat();    // From Animal
$duck->fly();    // From Flyable trait
$duck->swim();   // From Swimmable trait
$duck->quack();  // Duck-specific

$fish = new Fish('Nemo');
$fish->swim();

// ═══════════════════════════════════════════════════════════════
// TYPE 5: HYBRID INHERITANCE — Combination of types
// ═══════════════════════════════════════════════════════════════
/**
 *  LivingBeing                (base)
 *      └── Animal             (single)
 *            ├── Pet          (hierarchical)
 *            └── WildAnimal   (hierarchical)
 *                  └── Lion   (multilevel from Animal)
 */

class LivingBeing
{
    public function grow(): void { echo "  Living being grows.\n"; }
}

class AnimalV2 extends LivingBeing
{
    public function breathe(): void { echo "  Animal breathes.\n"; }
}

class Pet extends AnimalV2
{
    public function beAdopted(): void { echo "  Pet is adopted by a family.\n"; }
}

class WildAnimal extends AnimalV2
{
    public function huntPrey(): void { echo "  Wild animal hunts prey.\n"; }
}

class Lion extends WildAnimal
{
    public function roar(): void { echo "  Lion ROARS!\n"; }
}

echo "\n--- Type 5: Hybrid Inheritance ---\n";
$lion = new Lion();
$lion->grow();       // From LivingBeing
$lion->breathe();    // From AnimalV2
$lion->huntPrey();   // From WildAnimal
$lion->roar();       // From Lion

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is inheritance? Name its types.                         │
 * │ A: Mechanism to reuse parent class code in child class.         │
 * │    Types: Single, Multilevel, Hierarchical, Multiple (via traits│
 * │    in PHP), Hybrid (combination of above).                      │
 * │                                                                  │
 * │ Q2: Does PHP support multiple inheritance?                       │
 * │ A: No, PHP doesn't allow a class to extend two classes. But you │
 * │    can simulate it with Traits (use TraitA, TraitB).            │
 * │                                                                  │
 * │ Q3: What is the diamond problem in multiple inheritance?         │
 * │ A: If B and C both extend A, and D extends both B and C,        │
 * │    calling D->method() is ambiguous if B and C both override it.│
 * │    PHP avoids this by not allowing multi-class inheritance.      │
 * │    Traits have a conflict resolution mechanism (insteadof).     │
 * │                                                                  │
 * │ Q4: What is parent::__construct() and when is it needed?        │
 * │ A: Explicitly calls the parent class constructor from a child.  │
 * │    Needed when the parent has initialization logic the child     │
 * │    must also run. Forgetting it means parent properties won't   │
 * │    be set.                                                       │
 * │                                                                  │
 * │ Q5: When should you NOT use inheritance?                        │
 * │ A: When the relationship is HAS-A (use composition instead).   │
 * │    When overusing just for code reuse — prefer composition over │
 * │    inheritance ("favor composition over inheritance").           │
 * │    When subclass must disable/override many parent methods      │
 * │    (violation of LSP — wrong hierarchy design).                 │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Deep inheritance chains (>3 levels) become hard to trace.    │
 * │ ✗ Inheriting for code reuse when relationship isn't IS-A.       │
 * │ ✗ Forgetting parent::__construct() — uninitialised parent data. │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Child class inherits all public/protected members of parent.  │
 * │ ✓ private members are NOT inherited (not visible to child).     │
 * │ ✓ Use parent:: to access overridden parent methods.             │
 * │ ✓ PHP uses Traits for multiple-inheritance-like behavior.       │
 * └─────────────────────────────────────────────────────────────────┘
 */
