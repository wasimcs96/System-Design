<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║        OOP CONCEPT #13 — SOLID PRINCIPLES INTRODUCTION           ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (SOLID is the #1 senior interview topic)    ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * SOLID = 5 design principles for writing clean, maintainable OOP code.
 * Coined by Robert C. Martin ("Uncle Bob").
 *
 *  S — Single Responsibility Principle
 *  O — Open/Closed Principle
 *  L — Liskov Substitution Principle
 *  I — Interface Segregation Principle
 *  D — Dependency Inversion Principle
 *
 * WHY SOLID MATTERS:
 *   Without SOLID → code becomes a "Big Ball of Mud":
 *   hard to change, test, extend, or understand.
 *   With SOLID → modular, testable, extensible, maintainable code.
 *
 * NOTE: This is a BRIEF INTRODUCTION with simple examples.
 *       For full deep-dives with interview Q&A, refactoring examples,
 *       patterns mapping, and cheatsheet, see:
 *       /System-Design/SOLID/
 */

// ═══════════════════════════════════════════════════════════════
// S — Single Responsibility Principle (SRP)
//     "A class should have ONE reason to change."
// ═══════════════════════════════════════════════════════════════
/**
 *  ❌ VIOLATION: One class doing too much
 */
class UserFat
{
    public function register(array $data): void     { /* validate + save + send email */ }
    public function sendWelcomeEmail(): void         { /* email logic inside user */ }
    public function generateReport(): string        { /* reporting logic inside user */ }
    public function exportToCsv(): string           { /* CSV logic inside user */ }
}
// → 4 reasons to change: validation rules, email format, report format, CSV format

/**
 *  ✅ CORRECT: Each class has ONE job
 */
class User
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $email
    ) {}
}

class UserRegistration
{
    public function register(array $data): User
    {
        // Only responsibility: create a valid User
        return new User(rand(1, 1000), $data['name'], $data['email']);
    }
}

class EmailService
{
    public function sendWelcome(User $user): void
    {
        echo "  [Email] Welcome email → {$user->email}\n";
    }
}

class UserReportGenerator
{
    public function generate(User $user): string
    {
        return "User Report: {$user->name} (ID:{$user->id})";
    }
}

echo "=== SOLID PRINCIPLES INTRODUCTION ===\n\n";

echo "--- S: Single Responsibility ---\n";
$reg    = new UserRegistration();
$mailer = new EmailService();
$report = new UserReportGenerator();

$user = $reg->register(['name' => 'Alice', 'email' => 'alice@example.com']);
$mailer->sendWelcome($user);
echo "  " . $report->generate($user) . "\n";

// ═══════════════════════════════════════════════════════════════
// O — Open/Closed Principle (OCP)
//     "Open for extension, CLOSED for modification."
// ═══════════════════════════════════════════════════════════════
/**
 *  ❌ VIOLATION: Adding a new discount type requires modifying existing code
 */
class BadDiscountCalculator
{
    public function calculate(string $type, float $price): float
    {
        if ($type === 'seasonal') return $price * 0.9;
        if ($type === 'loyalty')  return $price * 0.85;
        if ($type === 'employee') return $price * 0.7;
        // Adding 'student' discount → must MODIFY this class ❌
        return $price;
    }
}

/**
 *  ✅ CORRECT: New discount type = new class (no modification)
 */
interface DiscountStrategy
{
    public function apply(float $price): float;
    public function getLabel(): string;
}

class SeasonalDiscount implements DiscountStrategy
{
    public function apply(float $price): float  { return $price * 0.90; }
    public function getLabel(): string          { return "Seasonal (10% off)"; }
}

class LoyaltyDiscount implements DiscountStrategy
{
    public function apply(float $price): float  { return $price * 0.85; }
    public function getLabel(): string          { return "Loyalty (15% off)"; }
}

class StudentDiscount implements DiscountStrategy
{
    // NEW discount — added WITHOUT touching existing code ✓
    public function apply(float $price): float  { return $price * 0.75; }
    public function getLabel(): string          { return "Student (25% off)"; }
}

class DiscountCalculator
{
    public function calculate(DiscountStrategy $strategy, float $price): float
    {
        return $strategy->apply($price);
    }
}

echo "\n--- O: Open/Closed ---\n";
$calc  = new DiscountCalculator();
$price = 1000.00;
foreach ([new SeasonalDiscount(), new LoyaltyDiscount(), new StudentDiscount()] as $d) {
    $final = $calc->calculate($d, $price);
    echo "  {$d->getLabel()}: ₹{$price} → ₹{$final}\n";
}

// ═══════════════════════════════════════════════════════════════
// L — Liskov Substitution Principle (LSP)
//     "Child objects must be substitutable for parent objects."
// ═══════════════════════════════════════════════════════════════
/**
 *  ❌ VIOLATION: Square extends Rectangle but breaks the contract
 */
class BadRectangle
{
    protected float $w = 0, $h = 0;
    public function setWidth(float $w): void  { $this->w = $w; }
    public function setHeight(float $h): void { $this->h = $h; }
    public function area(): float             { return $this->w * $this->h; }
}
class BadSquare extends BadRectangle
{
    // Square overrides both setters to force w == h — BREAKS LSP
    public function setWidth(float $w): void  { $this->w = $this->h = $w; }
    public function setHeight(float $h): void { $this->w = $this->h = $h; }
}
// setWidth(5); setHeight(10) → expect area=50, but Square gives 100 ❌

/**
 *  ✅ CORRECT: Separate hierarchy; each class honours its contract
 */
interface Shape
{
    public function area(): float;
}

class Rectangle implements Shape
{
    public function __construct(private float $w, private float $h) {}
    public function area(): float { return $this->w * $this->h; }
}

class Square implements Shape
{
    public function __construct(private float $side) {}
    public function area(): float { return $this->side ** 2; }
}

function printArea(Shape $shape): void
{
    // Works correctly for Rectangle AND Square — LSP satisfied
    echo "  Area = " . $shape->area() . "\n";
}

echo "\n--- L: Liskov Substitution ---\n";
printArea(new Rectangle(5, 10));  // 50
printArea(new Square(5));         // 25

// ═══════════════════════════════════════════════════════════════
// I — Interface Segregation Principle (ISP)
//     "No client should be forced to depend on methods it doesn't use."
// ═══════════════════════════════════════════════════════════════
/**
 *  ❌ VIOLATION: Fat interface forces unneeded methods
 */
interface FatPrinter
{
    public function print(): void;
    public function scan(): void;
    public function fax(): void;
    public function staple(): void;
}
// BasicPrinter must implement fax() and staple() even though it has neither ❌

/**
 *  ✅ CORRECT: Small, focused interfaces
 */
interface Printable { public function print(): void; }
interface Scannable  { public function scan(): void; }
interface Faxable    { public function fax(): void; }

class BasicPrinter implements Printable
{
    public function print(): void { echo "  [BasicPrinter] Printing...\n"; }
    // No scan(), no fax() — not forced by ISP ✓
}

class AllInOnePrinter implements Printable, Scannable, Faxable
{
    public function print(): void { echo "  [AllInOne] Printing...\n"; }
    public function scan(): void  { echo "  [AllInOne] Scanning...\n"; }
    public function fax(): void   { echo "  [AllInOne] Faxing...\n"; }
}

echo "\n--- I: Interface Segregation ---\n";
$basic = new BasicPrinter();
$basic->print();

$premium = new AllInOnePrinter();
$premium->print();
$premium->scan();
$premium->fax();

// ═══════════════════════════════════════════════════════════════
// D — Dependency Inversion Principle (DIP)
//     "Depend on abstractions, not concretions."
// ═══════════════════════════════════════════════════════════════
/**
 *  ❌ VIOLATION: High-level class directly creates low-level dependency
 */
class BadOrderService
{
    private MySQLDatabase $db;  // Hardcoded concrete class

    public function __construct()
    {
        $this->db = new MySQLDatabase();  // Tight coupling ❌
    }
}
class MySQLDatabase { public function save(array $data): void {} }
// Changing to PostgreSQL requires modifying BadOrderService ❌

/**
 *  ✅ CORRECT: Depend on abstraction (interface), inject the concrete
 */
interface DatabaseInterface
{
    public function save(array $data): bool;
    public function find(int $id): ?array;
}

class MySQLDb implements DatabaseInterface
{
    public function save(array $data): bool  { echo "  [MySQL] Saved\n"; return true; }
    public function find(int $id): ?array    { return ['id' => $id, 'source' => 'MySQL']; }
}

class RedisDb implements DatabaseInterface
{
    public function save(array $data): bool  { echo "  [Redis] Cached\n"; return true; }
    public function find(int $id): ?array    { return ['id' => $id, 'source' => 'Redis']; }
}

class OrderService
{
    // Depends on ABSTRACTION (interface), not concrete class
    public function __construct(private DatabaseInterface $db) {}

    public function createOrder(array $data): bool
    {
        return $this->db->save($data);  // Works with ANY DatabaseInterface implementation
    }

    public function getOrder(int $id): ?array
    {
        return $this->db->find($id);
    }
}

echo "\n--- D: Dependency Inversion ---\n";
$mysqlService = new OrderService(new MySQLDb());
$mysqlService->createOrder(['product' => 'Laptop', 'qty' => 1]);

$redisService = new OrderService(new RedisDb()); // Swap implementation — no code change ✓
$redisService->createOrder(['product' => 'Mouse', 'qty' => 2]);

$order = $mysqlService->getOrder(42);
echo "  Found: " . ($order['source'] ?? 'none') . " | id=" . ($order['id'] ?? '') . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ SOLID CHEATSHEET                                                 │
 * ├─────┬──────────────────────────────┬────────────────────────────┤
 * │     │ One-Line Definition          │ Violation Sign             │
 * ├─────┼──────────────────────────────┼────────────────────────────┤
 * │ SRP │ One class, one reason to     │ "And" in class name/purpose│
 * │     │ change                       │ 1000-line classes          │
 * ├─────┼──────────────────────────────┼────────────────────────────┤
 * │ OCP │ Extend without modifying     │ if/switch on type in core  │
 * │     │ existing code                │ every new feature needs    │
 * │     │                              │ old file edits             │
 * ├─────┼──────────────────────────────┼────────────────────────────┤
 * │ LSP │ Subtypes substitutable for   │ Overrides that throw or    │
 * │     │ base types                   │ return unexpected values   │
 * ├─────┼──────────────────────────────┼────────────────────────────┤
 * │ ISP │ Small focused interfaces     │ Implementing interface      │
 * │     │                              │ methods with empty body    │
 * ├─────┼──────────────────────────────┼────────────────────────────┤
 * │ DIP │ Depend on abstractions       │ `new ConcreteClass()` deep  │
 * │     │                              │ inside high-level classes  │
 * └─────┴──────────────────────────────┴────────────────────────────┘
 *
 * INTERVIEW Q&A:
 *
 * Q: What is SOLID?
 * A: 5 OOP design principles (SRP, OCP, LSP, ISP, DIP) by Robert C. Martin.
 *    Goal: produce code that is easy to maintain, test, and extend.
 *
 * Q: Which SOLID principle is most violated in practice?
 * A: SRP — classes tend to grow and accumulate responsibilities over time.
 *    The second most common is DIP — tight coupling via `new ConcreteClass`.
 *
 * Q: Are SOLID principles rules or guidelines?
 * A: Guidelines — apply them with judgment. In tiny scripts they may be
 *    overkill. In long-lived systems they prevent "big ball of mud."
 *
 * FOR FULL DETAIL: See /System-Design/SOLID/ for comprehensive
 *   per-principle files + interview cheatsheet with 11 sections.
 */
