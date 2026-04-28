<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║       SOLID PRINCIPLE #3 — LISKOV SUBSTITUTION PRINCIPLE (LSP)   ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  ACRONYM    : L in SOLID                                         ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆ (Classic Square/Rectangle question)         ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DEFINITION                                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ "Objects of a subclass must be substitutable for objects of     │
 * │  the superclass WITHOUT altering the correctness of the program."│
 * │                                                                  │
 * │ In plain English: if you have code that works with a Bird,      │
 * │ it should work CORRECTLY when given any Bird subclass — even     │
 * │ Penguin. If substituting breaks behavior, LSP is violated.       │
 * │                                                                  │
 * │ LSP RULES (Barbara Liskov, 1987):                                │
 * │  1. PRECONDITIONS cannot be strengthened in a subtype            │
 * │     (subclass can't be MORE restrictive about inputs)            │
 * │  2. POSTCONDITIONS cannot be weakened in a subtype               │
 * │     (subclass must deliver at least what base class promised)    │
 * │  3. INVARIANTS of the supertype must be preserved                │
 * │  4. Subtype cannot throw NEW exceptions not declared in base     │
 * │                                                                  │
 * │ TELL-TALE SIGNS OF LSP VIOLATION:                                │
 * │  • Subclass throws UnsupportedException for a base method        │
 * │  • Client code does instanceof checks: if ($x instanceof Duck)  │
 * │  • Subclass overrides method with empty/no-op implementation     │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM — Classic Square/Rectangle Problem                 │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ❌ Mathematically Square IS-A Rectangle.                        │
 * │     But in OOP, Square breaks the Rectangle CONTRACT:            │
 * │                                                                  │
 * │  Rectangle  ──setWidth(w)──►  width = w                         │
 * │                ──setHeight(h)► height = h                        │
 * │                                                                  │
 * │  Square     ──setWidth(w)──►  width = w AND height = w  ← BREAKS│
 * │  (extends Rect) ──setHeight(h)► width = h AND height = h ← BREAKS│
 * │                                                                  │
 * │  Result: area calculation that relied on Rectangle invariant     │
 * │  (w and h are independent) silently produces wrong results.      │
 * │                                                                  │
 * │  ✅ Fix: Rectangle and Square both implement Shape.              │
 * │  No inheritance between them.                                    │
 * └─────────────────────────────────────────────────────────────────┘
 */

echo "=== LISKOV SUBSTITUTION PRINCIPLE ===\n\n";

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION 1: Classic Square extends Rectangle
// ═══════════════════════════════════════════════════════════════
class Rectangle
{
    protected float $width;
    protected float $height;

    public function setWidth(float $w): void  { $this->width  = $w; }
    public function setHeight(float $h): void { $this->height = $h; }
    public function getArea(): float          { return $this->width * $this->height; }
}

// A square is mathematically a rectangle, but this breaks LSP
class Square extends Rectangle
{
    // Square MUST keep both sides equal — this changes Rectangle's invariant
    public function setWidth(float $w): void
    {
        $this->width  = $w;
        $this->height = $w; // ← changes height too! breaks Rectangle contract
    }

    public function setHeight(float $h): void
    {
        $this->width  = $h; // ← changes width too! breaks Rectangle contract
        $this->height = $h;
    }
}

// Function that works correctly with Rectangle...
function assertRectangleArea(Rectangle $rect): void
{
    $rect->setWidth(5);
    $rect->setHeight(3);
    $expected = 15;
    $actual   = $rect->getArea();
    echo "  Expected area=15, Got=$actual → " . ($actual === 15.0 ? "✓" : "✗ LSP VIOLATED") . "\n";
}

echo "--- ❌ LSP Violation: Square extends Rectangle ---\n";
assertRectangleArea(new Rectangle()); // Works: 5×3=15 ✓
assertRectangleArea(new Square());    // FAILS: 3×3=9  ✗ — Square is NOT substitutable

// ═══════════════════════════════════════════════════════════════
// ✅ FIX 1: No inheritance between Square and Rectangle.
//           Both implement a Shape interface.
// ═══════════════════════════════════════════════════════════════
interface Shape
{
    public function getArea(): float;
    public function getPerimeter(): float;
    public function describe(): string;
}

class GoodRectangle implements Shape
{
    public function __construct(
        private float $width,
        private float $height
    ) {}

    public function getArea(): float      { return $this->width * $this->height; }
    public function getPerimeter(): float { return 2 * ($this->width + $this->height); }
    public function describe(): string
    {
        return "Rectangle({$this->width}×{$this->height})";
    }
}

class GoodSquare implements Shape
{
    public function __construct(private float $side) {}

    public function getArea(): float      { return $this->side ** 2; }
    public function getPerimeter(): float { return 4 * $this->side; }
    public function describe(): string    { return "Square({$this->side})"; }
}

class Circle implements Shape
{
    public function __construct(private float $radius) {}

    public function getArea(): float      { return M_PI * $this->radius ** 2; }
    public function getPerimeter(): float { return 2 * M_PI * $this->radius; }
    public function describe(): string    { return "Circle(r={$this->radius})"; }
}

// This function works CORRECTLY for ALL Shape implementations
function printShapeInfo(Shape $shape): void
{
    echo "  {$shape->describe()}: area=" . round($shape->getArea(), 2)
       . ", perimeter=" . round($shape->getPerimeter(), 2) . "\n";
}

echo "\n--- ✅ LSP Compliant: Shape Hierarchy ---\n";
$shapes = [
    new GoodRectangle(5, 3),
    new GoodSquare(4),
    new Circle(7),
];
foreach ($shapes as $shape) {
    printShapeInfo($shape); // Works correctly for ALL shapes ✓
}

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION 2: Temporary Employee can't get a bonus
// ═══════════════════════════════════════════════════════════════
echo "\n--- ❌ LSP Violation: Employee throws on calculateBonus ---\n";

abstract class BadEmployee
{
    public function __construct(
        protected string $name,
        protected float  $salary
    ) {}

    abstract public function calculateSalary(): float;
    abstract public function calculateBonus(): float; // Contract: ALL employees have bonuses
}

class BadPermanentEmployee extends BadEmployee
{
    public function calculateSalary(): float { return $this->salary; }
    public function calculateBonus(): float  { return $this->salary * 0.20; }
}

class BadContractEmployee extends BadEmployee
{
    public function calculateSalary(): float { return $this->salary; }

    // ❌ Violates LSP: caller expected a float, gets an exception
    public function calculateBonus(): float
    {
        throw new \LogicException("Contract employees don't receive bonuses.");
    }
}

function printEmployeeCompensation(BadEmployee $emp): void
{
    try {
        $bonus = $emp->calculateBonus();
        echo "  Bonus: ₹" . number_format($bonus) . "\n";
    } catch (\LogicException $e) {
        echo "  ✗ LSP BROKEN: {$e->getMessage()}\n";
    }
}

printEmployeeCompensation(new BadPermanentEmployee("Alice", 100000));
printEmployeeCompensation(new BadContractEmployee("Bob",   80000));

// ═══════════════════════════════════════════════════════════════
// ✅ FIX 2: Use interface segregation — not all employees get bonuses
// ═══════════════════════════════════════════════════════════════
echo "\n--- ✅ LSP Compliant: Segregated Employee Interfaces ---\n";

interface Employee
{
    public function getName(): string;
    public function calculateSalary(): float;
}

interface BonusEligible
{
    public function calculateBonus(): float;
}

// Permanent employees: salary + bonus
class PermanentEmployee implements Employee, BonusEligible
{
    public function __construct(
        private string $name,
        private float  $salary
    ) {}

    public function getName(): string       { return $this->name; }
    public function calculateSalary(): float { return $this->salary; }
    public function calculateBonus(): float  { return $this->salary * 0.20; }
}

// Contract employees: salary only — NOT forced to implement BonusEligible
class ContractEmployee implements Employee
{
    public function __construct(
        private string $name,
        private float  $hourlyRate,
        private int    $hoursWorked
    ) {}

    public function getName(): string       { return $this->name; }
    public function calculateSalary(): float { return $this->hourlyRate * $this->hoursWorked; }
    // No calculateBonus() — honest about capabilities
}

// Payroll processor: works with all Employees
function printSalarySlip(Employee $emp): void
{
    $salary = $emp->calculateSalary();
    echo "  {$emp->getName()}: Salary=₹" . number_format($salary);

    // Only call bonus IF the employee is eligible — checked via interface
    if ($emp instanceof BonusEligible) {
        $bonus = $emp->calculateBonus(); // Safe — no exception possible
        echo " + Bonus=₹" . number_format($bonus);
    }
    echo "\n";
}

$employees = [
    new PermanentEmployee("Alice", 100000),
    new ContractEmployee("Bob", 500, 160),
    new PermanentEmployee("Carol", 120000),
];

foreach ($employees as $emp) {
    printSalarySlip($emp); // Works correctly for ALL ✓
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Define LSP.                                                  │
 * │ A: Subtypes must be substitutable for their base types without  │
 * │    altering the correctness of the program. Any code that works  │
 * │    with a base class should work with all subclasses too.        │
 * │                                                                  │
 * │ Q2: Why does Square extending Rectangle violate LSP?             │
 * │ A: Rectangle has an invariant: width and height are independent. │
 * │    Setting width doesn't change height and vice versa. Square    │
 * │    breaks this — setWidth also sets height. Code that relied on  │
 * │    the invariant produces wrong area after substitution.         │
 * │                                                                  │
 * │ Q3: How do you detect LSP violations?                            │
 * │ A: 1. Subclass throws UnsupportedOperationException on a method. │
 * │    2. You write instanceof checks in client code to handle       │
 * │       subclasses differently.                                    │
 * │    3. Subclass overrides method with empty/no-op body.          │
 * │    4. Subclass weakens a postcondition (returns null when base   │
 * │       always returned a valid object).                           │
 * │                                                                  │
 * │ Q4: How do LSP and ISP relate?                                   │
 * │ A: A common LSP fix is to use ISP. Instead of forcing a subclass │
 * │    to implement a method it can't support (→ throws exception),  │
 * │    split the interface so the subclass only implements what it   │
 * │    can actually do.                                               │
 * │                                                                  │
 * │ Q5: Is-A relationship in OOP vs mathematics?                     │
 * │ A: Mathematically, every square IS a rectangle. In OOP, Is-A   │
 * │    is about BEHAVIORAL substitutability, not just classification.│
 * │    A Square is NOT a Rectangle in OOP because it can't satisfy  │
 * │    Rectangle's behavioral contract.                               │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Covariant return types: subclass can return a more specific   │
 * │   type (narrower) — this is allowed by LSP.                     │
 * │ ✓ Contravariant parameters: subclass should accept types as     │
 * │   broad or broader than parent — rarely needed in PHP.           │
 * └─────────────────────────────────────────────────────────────────┘
 */
