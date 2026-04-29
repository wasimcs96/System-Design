<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #8 — ACCESS MODIFIERS                   ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Intermediate                             ║
 * ║  FREQUENCY : ★★★★☆  (always tested in PHP interviews)           ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * ACCESS MODIFIERS control WHERE a class member can be accessed.
 *
 * ┌───────────────┬──────────────┬─────────────┬─────────────────┐
 * │ Modifier      │ Same Class   │ Child Class │ Outside Class   │
 * ├───────────────┼──────────────┼─────────────┼─────────────────┤
 * │ public        │     ✓        │      ✓      │       ✓         │
 * │ protected     │     ✓        │      ✓      │       ✗         │
 * │ private       │     ✓        │      ✗      │       ✗         │
 * │ readonly*     │  ✓ (write 1x)│   (inherit) │  ✗ (no write)  │
 * └───────────────┴──────────────┴─────────────┴─────────────────┘
 * * PHP 8.1+ readonly: can be written once (in constructor), then read-only
 *
 * ANALOGY:
 *   public    = front door (anyone can enter)
 *   protected = family entrance (family/children only)
 *   private   = owner's safe (only you)
 */

// ═══════════════════════════════════════════════════════════════
// 1. PUBLIC — accessible everywhere
// ═══════════════════════════════════════════════════════════════

class PublicExample
{
    public string $name = 'Alice';  // Directly accessible

    public function greet(): string
    {
        return "Hello, I'm {$this->name}";
    }
}

echo "=== ACCESS MODIFIERS DEMO ===\n\n";

echo "--- 1. public ---\n";
$obj = new PublicExample();
echo "  " . $obj->greet() . "\n";
$obj->name = 'Bob';              // ✓ Directly modifiable from outside
echo "  Name: " . $obj->name . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. PRIVATE — only accessible within the declaring class
// ═══════════════════════════════════════════════════════════════

class BankAccount
{
    private float  $balance = 0;
    private string $pin;
    private array  $transactions = [];

    public function __construct(float $initialBalance, string $pin)
    {
        $this->balance = $initialBalance;
        $this->pin     = password_hash($pin, PASSWORD_BCRYPT);
        $this->logTransaction('Account opened', $initialBalance);
    }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) {
            echo "  ✗ Deposit amount must be positive.\n";
            return;
        }
        $this->balance += $amount;               // ✓ private access inside class
        $this->logTransaction('Deposit', $amount); // ✓ calling private method
    }

    public function withdraw(float $amount, string $pin): void
    {
        if (!$this->verifyPin($pin)) {           // ✓ calling private method
            echo "  ✗ Wrong PIN.\n";
            return;
        }
        if ($amount > $this->balance) {
            echo "  ✗ Insufficient funds.\n";
            return;
        }
        $this->balance -= $amount;
        $this->logTransaction('Withdrawal', -$amount);
    }

    public function getBalance(): float     { return $this->balance; }  // public getter
    public function getStatement(): array   { return $this->transactions; }

    // private — internal helpers, not part of the public API
    private function verifyPin(string $pin): bool
    {
        return password_verify($pin, $this->pin);
    }

    private function logTransaction(string $type, float $amount): void
    {
        $this->transactions[] = [
            'type'   => $type,
            'amount' => $amount,
            'time'   => date('H:i:s'),
        ];
    }
}

echo "\n--- 2. private ---\n";
$acc = new BankAccount(5000.00, '1234');
$acc->deposit(2000);
$acc->withdraw(1000, '1234');
$acc->withdraw(1000, '9999');   // Wrong PIN
echo "  Balance: ₹" . $acc->getBalance() . "\n";
// $acc->balance       → Fatal Error: private access
// $acc->verifyPin()   → Fatal Error: private method

// ═══════════════════════════════════════════════════════════════
// 3. PROTECTED — accessible in class + subclasses
// ═══════════════════════════════════════════════════════════════

class Shape
{
    protected float $x = 0;
    protected float $y = 0;
    protected string $color;

    public function __construct(string $color = 'red')
    {
        $this->color = $color;
    }

    protected function describe(): string
    {
        return "Color={$this->color}, Position=({$this->x},{$this->y})";
    }

    public function moveTo(float $x, float $y): void
    {
        $this->x = $x;  // ✓ protected access in same class
        $this->y = $y;
    }
}

class Circle extends Shape
{
    public function __construct(private float $radius, string $color = 'blue')
    {
        parent::__construct($color);
    }

    public function area(): float
    {
        return M_PI * $this->radius ** 2;
    }

    public function getInfo(): string
    {
        // ✓ Accessing protected $color, $x, $y from parent, and calling protected describe()
        return "Circle(r={$this->radius}) | " . $this->describe()
            . " | Area=" . round($this->area(), 2);
    }

    // protected method can be overridden in subclass
    protected function describe(): string
    {
        return parent::describe() . " | Radius={$this->radius}";
    }
}

class Rectangle extends Shape
{
    public function __construct(
        private float $width,
        private float $height,
        string $color = 'green'
    ) {
        parent::__construct($color);
    }

    public function area(): float { return $this->width * $this->height; }

    public function getInfo(): string
    {
        // ✓ Accessing protected members inherited from Shape
        return "Rectangle({$this->width}x{$this->height}) | " . $this->describe()
            . " | Area=" . $this->area();
    }
}

echo "\n--- 3. protected ---\n";
$c = new Circle(5.0, 'red');
$c->moveTo(10, 20);
echo "  " . $c->getInfo() . "\n";

$r = new Rectangle(4, 6, 'green');
$r->moveTo(5, 5);
echo "  " . $r->getInfo() . "\n";

// $c->color    → Fatal: protected access from outside
// $c->describe() → Fatal: protected method from outside

// ═══════════════════════════════════════════════════════════════
// 4. READONLY (PHP 8.1+) — write once, read anywhere
// ═══════════════════════════════════════════════════════════════

class Product
{
    // readonly: set ONCE in constructor, then immutable
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly float  $price,
        public readonly string $sku
    ) {}

    public function withDiscount(float $pct): static
    {
        // Return NEW instance — readonly properties are immutable
        return new static(
            $this->id,
            $this->name,
            round($this->price * (1 - $pct / 100), 2),
            $this->sku
        );
    }
}

echo "\n--- 4. readonly (PHP 8.1+) ---\n";
$product = new Product(1, 'Laptop', 85000.00, 'LP-001');
echo "  Product: {$product->name} | ₹{$product->price} | SKU: {$product->sku}\n";

$discounted = $product->withDiscount(10);
echo "  Discounted: {$discounted->name} | ₹{$discounted->price}\n";
echo "  Original unchanged: ₹{$product->price}\n";

// This would throw: $product->price = 70000; → Cannot modify readonly

// ═══════════════════════════════════════════════════════════════
// 5. VISIBILITY IN STATIC CONTEXT
// ═══════════════════════════════════════════════════════════════

class Config
{
    private static array  $settings = [];
    protected static int  $loadCount = 0;
    public static string  $environment = 'production';

    public static function set(string $key, mixed $value): void
    {
        self::$settings[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::$loadCount++;
        return self::$settings[$key] ?? $default;
    }

    public static function getLoadCount(): int { return self::$loadCount; }
}

class AppConfig extends Config
{
    public static function initialize(): void
    {
        self::$loadCount++;  // ✓ Can access protected static from parent
        // self::$settings  → ✗ Cannot access private static from parent
    }
}

echo "\n--- 5. Static visibility ---\n";
Config::set('db_host', 'localhost');
Config::set('debug',   true);
echo "  db_host: " . Config::get('db_host') . "\n";
echo "  debug:   " . (Config::get('debug') ? 'true' : 'false') . "\n";
echo "  env:     " . Config::$environment . "\n";
echo "  loads:   " . Config::getLoadCount() . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What are the access modifiers in PHP?                        │
 * │ A: public (anywhere), protected (class + children), private     │
 * │    (class only). PHP 8.1 adds readonly (write once in ctor).   │
 * │                                                                  │
 * │ Q2: Can a child class access parent's private members?          │
 * │ A: No. private members are NOT inherited. The child class       │
 * │    cannot see or call them directly. protected is the way to    │
 * │    share internals with subclasses while hiding from outside.   │
 * │                                                                  │
 * │ Q3: What is readonly and when should you use it?                │
 * │ A: readonly (PHP 8.1) allows a property to be written exactly   │
 * │    once (during construction/initialization) and then read-only.│
 * │    Use for value objects, DTOs, and immutable data transfer.    │
 * │                                                                  │
 * │ Q4: Why are public properties usually a bad idea?               │
 * │ A: They break encapsulation — any code can modify them without  │
 * │    validation, making bugs hard to trace. Use private/protected │
 * │    with getter/setter methods that can enforce rules.           │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Making everything public for "convenience" — kills OOP.      │
 * │ ✗ Using protected when private is sufficient (over-exposure).   │
 * │ ✗ Forgetting that private is NOT inherited — child can't use it.│
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Default to private, promote to protected when child needs it.│
 * │ ✓ public properties only for read-only value objects.          │
 * │ ✓ readonly = immutable after construction (PHP 8.1).           │
 * │ ✓ Encapsulation is enforced through access modifiers.          │
 * └─────────────────────────────────────────────────────────────────┘
 */
