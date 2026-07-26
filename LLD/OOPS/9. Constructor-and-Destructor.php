<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          OOP CONCEPT #9 — CONSTRUCTOR & DESTRUCTOR               ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Intermediate                             ║
 * ║  FREQUENCY : ★★★★☆  (common in Laravel/framework interviews)    ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * CONSTRUCTOR (__construct):
 *   - Called automatically when an object is created (new ClassName())
 *   - Used for initialization: set properties, inject dependencies, open resources
 *   - PHP allows only ONE __construct() per class (no overloading)
 *   - Use static factory methods or default params to simulate overloading
 *
 * DESTRUCTOR (__destruct):
 *   - Called automatically when an object is destroyed (unset, out-of-scope)
 *   - Used for cleanup: close DB connections, flush buffers, log resource release
 *   - Called even on script termination for remaining objects
 *   - Order: reverse of construction (last created, first destroyed)
 */

// ═══════════════════════════════════════════════════════════════
// 1. BASIC CONSTRUCTOR — Initialization
// ═══════════════════════════════════════════════════════════════

class User
{
    public string $name;
    public string $email;
    public string $role;
    private string $createdAt;

    public function __construct(string $name, string $email, string $role = 'user')
    {
        $this->name      = $name;
        $this->email     = $email;
        $this->role      = $role;
        $this->createdAt = date('Y-m-d H:i:s');

        echo "  [Constructor] User '{$name}' created\n";
    }

    public function __destruct()
    {
        echo "  [Destructor] User '{$this->name}' object destroyed\n";
    }

    public function getInfo(): string
    {
        return "{$this->name} <{$this->email}> [{$this->role}]";
    }
}

echo "=== CONSTRUCTOR & DESTRUCTOR DEMO ===\n\n";

echo "--- 1. Basic Constructor/Destructor ---\n";
{
    $u1 = new User('Alice', 'alice@example.com', 'admin');
    $u2 = new User('Bob',   'bob@example.com');
    echo "  u1: " . $u1->getInfo() . "\n";
    echo "  u2: " . $u2->getInfo() . "\n";
    // Both destructors fire here as scope ends
}
echo "  (scope ended)\n";

// ═══════════════════════════════════════════════════════════════
// 2. PHP 8 CONSTRUCTOR PROPERTY PROMOTION
//    Shorthand: declare + assign properties directly in constructor params
// ═══════════════════════════════════════════════════════════════

class Address
{
    // PHP 8 promoted properties — equivalent to declaring $street, $city, etc.
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $pincode,
        public readonly string $country = 'India'
    ) {}

    public function format(): string
    {
        return "{$this->street}, {$this->city}, {$this->state} - {$this->pincode}, {$this->country}";
    }
}

// Equivalent to old-style (3x more lines):
// class Address {
//     public string $street; public string $city; ...
//     public function __construct(string $street, ...) {
//         $this->street = $street; ...
//     }
// }

echo "\n--- 2. Constructor Property Promotion (PHP 8) ---\n";
$addr = new Address('42 MG Road', 'Bangalore', 'Karnataka', '560001');
echo "  " . $addr->format() . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. STATIC FACTORY METHODS — Simulate constructor overloading
//    PHP allows only ONE __construct, use named static methods instead
// ═══════════════════════════════════════════════════════════════

class Color
{
    private function __construct(
        private int $r,
        private int $g,
        private int $b
    ) {}

    // Named constructors — simulate overloading
    public static function fromRGB(int $r, int $g, int $b): static
    {
        return new static($r, $g, $b);
    }

    public static function fromHex(string $hex): static
    {
        $hex = ltrim($hex, '#');
        return new static(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }

    public static function black(): static  { return new static(0, 0, 0); }
    public static function white(): static  { return new static(255, 255, 255); }
    public static function red(): static    { return new static(255, 0, 0); }

    public function toHex(): string   { return sprintf('#%02X%02X%02X', $this->r, $this->g, $this->b); }
    public function toRGB(): string   { return "rgb({$this->r}, {$this->g}, {$this->b})"; }
}

echo "\n--- 3. Static Factory Methods (simulated overloading) ---\n";
$c1 = Color::fromRGB(255, 128, 0);
$c2 = Color::fromHex('#1A2B3C');
$c3 = Color::red();

echo "  fromRGB:  " . $c1->toHex() . " → " . $c1->toRGB() . "\n";
echo "  fromHex:  " . $c2->toHex() . " → " . $c2->toRGB() . "\n";
echo "  red():    " . $c3->toHex() . " → " . $c3->toRGB() . "\n";

// ═══════════════════════════════════════════════════════════════
// 4. CONSTRUCTOR CHAINING — parent::__construct()
// ═══════════════════════════════════════════════════════════════

class Vehicle
{
    protected string $id;

    public function __construct(
        protected string $brand,
        protected string $model,
        protected int    $year
    ) {
        $this->id = strtoupper(substr($brand, 0, 3)) . $year;
        echo "  [Vehicle ctor] {$this->brand} {$this->model} ({$year})\n";
    }
}

class Car extends Vehicle
{
    public function __construct(
        string $brand,
        string $model,
        int    $year,
        protected int $doors
    ) {
        parent::__construct($brand, $model, $year);  // Must call first
        echo "  [Car ctor]     doors={$doors}\n";
    }
}

class ElectricCar extends Car
{
    public function __construct(
        string $brand,
        string $model,
        int    $year,
        int    $doors,
        private int $batteryKwh
    ) {
        parent::__construct($brand, $model, $year, $doors);  // Chains up
        echo "  [ElectricCar ctor] battery={$batteryKwh}kWh\n";
    }

    public function getInfo(): string
    {
        return "{$this->brand} {$this->model} ({$this->year}) | "
             . "{$this->doors}-door | {$this->batteryKwh}kWh | ID:{$this->id}";
    }
}

echo "\n--- 4. Constructor Chaining ---\n";
$car = new ElectricCar('Tesla', 'Model S', 2024, 4, 100);
echo "  " . $car->getInfo() . "\n";

// ═══════════════════════════════════════════════════════════════
// 5. DESTRUCTOR — Resource Cleanup
// ═══════════════════════════════════════════════════════════════

class DatabaseConnection
{
    private bool $connected = false;
    private array $queryLog = [];
    private static int $activeConnections = 0;

    public function __construct(private string $dsn)
    {
        self::$activeConnections++;
        $this->connected = true;
        echo "  [DB] Connected to {$dsn} (active: " . self::$activeConnections . ")\n";
    }

    public function query(string $sql): array
    {
        if (!$this->connected) throw new \RuntimeException("Not connected");
        $this->queryLog[] = $sql;
        echo "  [DB] Executed: {$sql}\n";
        return ['rows' => 0]; // simulated
    }

    public function getQueryCount(): int { return count($this->queryLog); }

    public function __destruct()
    {
        if ($this->connected) {
            $this->connected = false;
            self::$activeConnections--;
            echo "  [DB] Connection closed to {$this->dsn} ({$this->getQueryCount()} queries run)"
               . " (active: " . self::$activeConnections . ")\n";
        }
    }
}

class FileLogger
{
    private bool $open = false;
    private array $lines = [];

    public function __construct(private string $filename)
    {
        $this->open = true;
        echo "  [Logger] Log file '{$filename}' opened\n";
    }

    public function log(string $message): void
    {
        $this->lines[] = "[" . date('H:i:s') . "] " . $message;
        echo "  [Logger] " . end($this->lines) . "\n";
    }

    public function __destruct()
    {
        if ($this->open) {
            $count = count($this->lines);
            echo "  [Logger] Flushing {$count} log lines to '{$this->filename}' and closing.\n";
            $this->open = false;
        }
    }
}

echo "\n--- 5. Destructor: Resource Cleanup ---\n";

function runBatch(): void
{
    $db     = new DatabaseConnection('mysql://localhost/mydb');
    $logger = new FileLogger('app.log');

    $db->query('SELECT * FROM users');
    $db->query('UPDATE orders SET status="paid" WHERE id=1');
    $logger->log('Batch job started');
    $logger->log('2 queries executed');

    // Both $db and $logger go out of scope here — destructors fire automatically
}

runBatch();
echo "  (runBatch returned — resources cleaned up)\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the purpose of a constructor?                        │
 * │ A: Initialize an object's state when it is created. Set         │
 * │    properties, validate inputs, inject dependencies, open       │
 * │    resources. Always called on `new ClassName()`.               │
 * │                                                                  │
 * │ Q2: Does PHP support constructor overloading?                    │
 * │ A: No — PHP allows only one __construct per class. Simulate     │
 * │    multiple constructors using static factory methods (Color::  │
 * │    fromHex(), Color::fromRGB()) or default parameter values.    │
 * │                                                                  │
 * │ Q3: What is constructor property promotion (PHP 8)?              │
 * │ A: A shorthand that combines property declaration and           │
 * │    constructor assignment in one line. Writing `public string   │
 * │    $name` in the constructor params is equivalent to declaring  │
 * │    the property and assigning $this->name = $name.             │
 * │                                                                  │
 * │ Q4: When is __destruct() called?                                 │
 * │ A: When the object's reference count reaches zero (goes out of  │
 * │    scope, unset() is called, or script ends). Used for cleanup: │
 * │    closing DB connections, file handles, releasing resources.   │
 * │                                                                  │
 * │ Q5: If parent has a constructor, must child call it?            │
 * │ A: PHP will NOT auto-call parent::__construct(). You must call  │
 * │    it explicitly in the child's constructor if needed. Forgetting│
 * │    it means parent properties won't be initialized.             │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Not calling parent::__construct() — parent state missing.    │
 * │ ✗ Throwing exceptions in __destruct() — can cause fatal errors.│
 * │ ✗ Relying on destructor ORDER — not guaranteed across objects.  │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Constructor = setup; Destructor = teardown/cleanup.          │
 * │ ✓ PHP 8 constructor promotion eliminates boilerplate.          │
 * │ ✓ Use static factory methods for named/multiple constructors.  │
 * │ ✓ Always chain parent::__construct() when extending.           │
 * └─────────────────────────────────────────────────────────────────┘
 */
