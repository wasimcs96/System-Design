<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #15 — MAGIC METHODS                     ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (PHP-specific — asked in every PHP interview)║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * Magic methods are special PHP methods starting with __ (double underscore).
 * PHP calls them AUTOMATICALLY in specific situations.
 *
 * COMPLETE LIST:
 *  Lifecycle:      __construct, __destruct
 *  String:         __toString
 *  Cloning:        __clone
 *  Invocation:     __invoke
 *  Property access: __get, __set, __isset, __unset
 *  Method access:  __call, __callStatic
 *  Serialization:  __sleep, __wakeup, __serialize, __unserialize
 *  Debugging:      __debugInfo
 */

// ═══════════════════════════════════════════════════════════════
// 1. __toString — Object to string conversion
// ═══════════════════════════════════════════════════════════════

class Money
{
    public function __construct(
        private int    $amount,  // in paise (smallest unit)
        private string $currency = 'INR'
    ) {}

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Currency mismatch");
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    // Called automatically: echo $money; or (string) $money; or "$money"
    public function __toString(): string
    {
        return $this->currency . ' ' . number_format($this->amount / 100, 2);
    }

    public function getAmount(): int      { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
}

echo "=== MAGIC METHODS DEMO ===\n\n";

echo "--- 1. __toString ---\n";
$price    = new Money(85000 * 100);  // ₹85,000
$discount = new Money(8500 * 100);
$final    = $price->add($price->multiply(-0.10)); // 10% off

echo "  Original:  {$price}\n";     // __toString auto-called
echo "  Discount:  {$discount}\n";
echo "  Final:     {$final}\n";
echo "  In string: 'Price is {$price}'\n";  // works in interpolation too

// ═══════════════════════════════════════════════════════════════
// 2. __invoke — Call object as a function
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. __invoke ---\n";

class TaxCalculator
{
    public function __construct(private float $rate) {}

    // Called when object is used as a function: $calc(1000)
    public function __invoke(float $amount): float
    {
        return $amount * (1 + $this->rate / 100);
    }
}

class Pipeline
{
    private array $stages = [];

    public function pipe(callable $stage): static
    {
        $stages = $this->stages;
        $stages[] = $stage;
        $clone = clone $this;
        $clone->stages = $stages;
        return $clone;
    }

    public function process(mixed $payload): mixed
    {
        return array_reduce($this->stages, fn($carry, $fn) => $fn($carry), $payload);
    }
}

$gst   = new TaxCalculator(18.0);  // object is callable
$tcs   = new TaxCalculator(1.0);

echo "  GST on ₹1000: ₹" . $gst(1000) . "\n";  // __invoke
echo "  is_callable: " . (is_callable($gst) ? 'YES' : 'NO') . "\n";

$pipeline = (new Pipeline())
    ->pipe(fn($v) => $v * 1.18)   // +18% GST
    ->pipe(fn($v) => $v * 1.01)   // +1% TCS
    ->pipe(fn($v) => round($v, 2));

echo "  Pipeline ₹1000 → ₹" . $pipeline->process(1000) . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. __get, __set, __isset, __unset — Dynamic property access
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. __get, __set, __isset, __unset ---\n";

class FluentConfig
{
    private array $data = [];
    private array $readonly = [];

    public function lock(string ...$keys): void
    {
        $this->readonly = $keys;
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        if (in_array($key, $this->readonly)) {
            throw new \RuntimeException("Config key '{$key}' is read-only.");
        }
        $this->data[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function __unset(string $key): void
    {
        if (in_array($key, $this->readonly)) {
            throw new \RuntimeException("Cannot unset read-only key '{$key}'.");
        }
        unset($this->data[$key]);
    }

    public function all(): array { return $this->data; }
}

$cfg = new FluentConfig();
$cfg->appName   = 'MyApp';
$cfg->debug     = false;
$cfg->dbHost    = 'localhost';
$cfg->lock('appName');

echo "  appName: {$cfg->appName}\n";
echo "  debug:   " . ($cfg->debug ? 'true' : 'false') . "\n";
echo "  isset(dbHost): " . (isset($cfg->dbHost) ? 'YES' : 'NO') . "\n";

unset($cfg->debug);
echo "  After unset debug: " . (isset($cfg->debug) ? 'set' : 'unset') . "\n";

try {
    $cfg->appName = 'NewApp';  // Read-only!
} catch (\RuntimeException $e) {
    echo "  ✗ {$e->getMessage()}\n";
}

// ═══════════════════════════════════════════════════════════════
// 4. __call, __callStatic — Dynamic method dispatch
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. __call and __callStatic ---\n";

class QueryBuilder
{
    private array  $conditions = [];
    private ?int   $limitVal   = null;
    private string $table      = '';

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    // Intercepts: $qb->whereEmail('alice@test.com')
    //              $qb->whereStatus('active')
    //              $qb->whereAgeGreaterThan(18)
    public function __call(string $name, array $args): static
    {
        if (str_starts_with($name, 'where')) {
            $column = lcfirst(substr($name, 5));           // 'whereEmail' → 'email'
            $column = strtolower(preg_replace('/([A-Z])/', '_$1', $column)); // camelCase → snake_case
            $this->conditions[] = "{$column} = '{$args[0]}'";
            return $this;
        }
        throw new \BadMethodCallException("Unknown method: {$name}");
    }

    public static function __callStatic(string $name, array $args): static
    {
        $instance = new static();
        if ($name === 'table') {
            return $instance->from($args[0]);
        }
        throw new \BadMethodCallException("Unknown static method: {$name}");
    }

    public function limit(int $n): static { $this->limitVal = $n; return $this; }

    public function toSql(): string
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }
        if ($this->limitVal !== null) {
            $sql .= " LIMIT {$this->limitVal}";
        }
        return $sql;
    }
}

// Using __callStatic
$query = QueryBuilder::table('users')
    ->whereEmail('alice@test.com')
    ->whereStatus('active')
    ->limit(10);

echo "  SQL: " . $query->toSql() . "\n";

// ═══════════════════════════════════════════════════════════════
// 5. __clone — Customize object cloning
// ═══════════════════════════════════════════════════════════════

echo "\n--- 5. __clone (shallow vs deep clone) ---\n";

class Address
{
    public function __construct(
        public string $city,
        public string $country
    ) {}
}

class Customer
{
    public Address $address;
    public array   $orders = [];

    public function __construct(public string $name, Address $address)
    {
        $this->address = $address;
    }

    // WITHOUT __clone: cloning Customer gives SHALLOW copy
    // Both original and clone share the SAME Address object
    public function __clone()
    {
        // Deep clone: create a new Address object for the clone
        $this->address = clone $this->address;
        $this->orders  = [];  // Reset orders for the cloned customer
    }
}

$original = new Customer('Alice', new Address('Mumbai', 'India'));
$original->orders = [101, 102, 103];

$clone = clone $original;             // __clone() is called here
$clone->name            = 'Bob';
$clone->address->city   = 'Delhi';    // Changes CLONE's address only (deep cloned)

echo "  Original: {$original->name} @ {$original->address->city} | Orders: " . count($original->orders) . "\n";
echo "  Clone:    {$clone->name} @ {$clone->address->city} | Orders: " . count($clone->orders) . "\n";
// Without __clone, both would show Delhi for address city

// ═══════════════════════════════════════════════════════════════
// 6. __serialize / __unserialize (PHP 8+) — Custom serialization
// ═══════════════════════════════════════════════════════════════

echo "\n--- 6. __serialize / __unserialize ---\n";

class SecureSession
{
    private string $secret;
    private array  $data = [];

    public function __construct(private string $userId, string $secret)
    {
        $this->secret = $secret;
    }

    public function set(string $key, mixed $val): void { $this->data[$key] = $val; }
    public function get(string $key): mixed             { return $this->data[$key] ?? null; }

    // Called by serialize() — control WHAT gets serialized
    public function __serialize(): array
    {
        return [
            'userId' => $this->userId,
            'data'   => $this->data,
            // $secret is intentionally EXCLUDED — never serialize secrets!
        ];
    }

    // Called by unserialize() — restore from serialized data
    public function __unserialize(array $data): void
    {
        $this->userId = $data['userId'];
        $this->data   = $data['data'];
        $this->secret = '';  // Re-inject via proper channel after unserialize
    }
}

$session = new SecureSession('user_42', 'top_secret_key');
$session->set('cart', ['item1', 'item2']);
$session->set('role', 'admin');

$serialized = serialize($session);
echo "  Serialized (secret excluded): "
   . (str_contains($serialized, 'top_secret') ? 'LEAKED!' : 'SAFE ✓') . "\n";

$restored = unserialize($serialized);
echo "  Restored role: " . $restored->get('role') . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ MAGIC METHOD QUICK REFERENCE                                     │
 * ├─────────────────────────┬───────────────────────────────────────┤
 * │ Method                  │ Triggered when                        │
 * ├─────────────────────────┼───────────────────────────────────────┤
 * │ __construct()           │ new ClassName()                        │
 * │ __destruct()            │ Object goes out of scope / unset()    │
 * │ __toString()            │ echo $obj; (string)$obj; "$obj"       │
 * │ __invoke()              │ $obj() — calling object as function   │
 * │ __get($name)            │ $obj->undeclaredProp                  │
 * │ __set($name, $value)    │ $obj->undeclaredProp = $val           │
 * │ __isset($name)          │ isset($obj->undeclaredProp)           │
 * │ __unset($name)          │ unset($obj->undeclaredProp)           │
 * │ __call($name, $args)    │ $obj->undeclaredMethod()              │
 * │ __callStatic($n, $args) │ ClassName::undeclaredStatic()         │
 * │ __clone()               │ clone $obj                            │
 * │ __serialize()           │ serialize($obj)                       │
 * │ __unserialize($data)    │ unserialize($str)                     │
 * │ __debugInfo()           │ var_dump($obj)                        │
 * └─────────────────────────┴───────────────────────────────────────┘
 *
 * INTERVIEW Q&A:
 *
 * Q1: What is __invoke and when is it useful?
 * A: __invoke is called when an object is used as a function ($obj()).
 *    Useful for: pipelines, middleware, strategy objects as callables,
 *    single-action controllers, and decorating functions.
 *
 * Q2: What's the difference between __sleep/__wakeup and __serialize/__unserialize?
 * A: __sleep/__wakeup are older (PHP 4+) — __sleep returns array of property NAMES
 *    to include. __serialize/__unserialize (PHP 7.4+) are more flexible — return
 *    arbitrary arrays including computed/transformed data. Use __serialize for
 *    new code.
 *
 * Q3: What's the difference between shallow clone and deep clone?
 * A: Shallow clone (default `clone`): copies scalar properties by value but
 *    object properties by REFERENCE — both original and clone share nested objects.
 *    Deep clone: implement __clone() and manually clone each nested object.
 *
 * PITFALLS:
 * ✗ __toString cannot throw exceptions (causes fatal error in PHP < 8.0).
 * ✗ Serializing objects with __sleep that return non-existent properties.
 * ✗ Never serialize sensitive data — use __serialize to explicitly exclude.
 * ✗ __get/__set only trigger for inaccessible properties, not all properties.
 */
