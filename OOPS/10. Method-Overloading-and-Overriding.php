<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║     OOP CONCEPT #10 — METHOD OVERLOADING & OVERRIDING            ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (directly asked in 80%+ PHP interviews)     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * DEFINITIONS:
 *
 *   OVERLOADING  (compile-time / static polymorphism):
 *     Same method name, different parameters — resolved at compile time.
 *     PHP does NOT support true overloading (no signature-based dispatch).
 *     PHP's "overloading" = magic methods (__call, __get, __set) for
 *     intercepting calls to inaccessible/non-existent members.
 *
 *   OVERRIDING (runtime / dynamic polymorphism):
 *     Subclass provides its OWN implementation of a parent method.
 *     Same name + same signature — resolved at RUNTIME.
 *     This IS supported in PHP (core polymorphism mechanism).
 *
 * ┌────────────────┬─────────────────────────────────────────────────┐
 * │ Feature        │ Overloading       │ Overriding                  │
 * ├────────────────┼───────────────────┼─────────────────────────────┤
 * │ Class          │ Same class        │ Parent + Child class        │
 * │ Params         │ Different         │ Same (or compatible)        │
 * │ Resolution     │ Compile-time      │ Runtime                     │
 * │ PHP support    │ Partial (__call)  │ YES (native)                │
 * │ Purpose        │ Multiple APIs     │ Customize parent behavior   │
 * └────────────────┴───────────────────┴─────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// 1. PHP "OVERLOADING" — Magic Methods
// ═══════════════════════════════════════════════════════════════
/**
 * PHP's "overloading" means DYNAMIC property/method access.
 * Not to be confused with Java/C++ overloading.
 *
 * Magic methods involved:
 *   __get($name)              — accessing undefined/inaccessible property
 *   __set($name, $value)      — setting undefined/inaccessible property
 *   __isset($name)            — isset() on undefined property
 *   __unset($name)            — unset() on undefined property
 *   __call($name, $args)      — calling undefined/inaccessible method
 *   __callStatic($name, $args)— calling undefined static method
 */

class DynamicEntity
{
    private array $data = [];

    // __get — intercepts: $obj->anyProp
    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        echo "  __get: Property '{$name}' not found, returning null\n";
        return null;
    }

    // __set — intercepts: $obj->anyProp = $value
    public function __set(string $name, mixed $value): void
    {
        echo "  __set: Setting '{$name}' = " . (is_array($value) ? '[array]' : $value) . "\n";
        $this->data[$name] = $value;
    }

    // __isset — intercepts: isset($obj->anyProp)
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    // __call — intercepts: $obj->anyMethod()
    public function __call(string $name, array $args): mixed
    {
        echo "  __call: Method '{$name}' called with args: " . implode(', ', $args) . "\n";

        // Simulate method overloading by method name prefix
        if (str_starts_with($name, 'find')) {
            $field = lcfirst(substr($name, 4));  // findByEmail → email
            return "Searching by {$field}: " . ($args[0] ?? '');
        }
        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    // __callStatic — intercepts: ClassName::anyMethod()
    public static function __callStatic(string $name, array $args): mixed
    {
        echo "  __callStatic: '{$name}' called\n";
        return null;
    }
}

echo "=== METHOD OVERLOADING & OVERRIDING DEMO ===\n\n";

echo "--- 1. PHP Magic Method Overloading ---\n";
$entity = new DynamicEntity();
$entity->name  = 'Alice';          // __set
$entity->email = 'alice@test.com'; // __set
echo "  Name:  " . $entity->name . "\n";   // __get
echo "  Email: " . $entity->email . "\n";  // __get
echo "  Phone: " . $entity->phone . "\n";  // __get (not set)
echo "  isset: " . (isset($entity->name) ? 'yes' : 'no') . "\n";  // __isset

echo "\n  Simulated 'overloading' via __call:\n";
echo "  " . $entity->findByEmail('alice@test.com') . "\n";
echo "  " . $entity->findByUsername('alice') . "\n";

// Simulate overloading with default params (most practical PHP approach)
class MathHelper
{
    // Simulate 'overloading' using default params
    public function multiply(float $a, float $b, float $c = 1.0): float
    {
        return $a * $b * $c;
    }

    // Simulate overloading with variadic args
    public function sum(float ...$numbers): float
    {
        return array_sum($numbers);
    }
}

echo "\n  Default params + variadic (practical overloading simulation):\n";
$m = new MathHelper();
echo "  multiply(3, 4)     = " . $m->multiply(3, 4) . "\n";     // 12
echo "  multiply(3, 4, 5)  = " . $m->multiply(3, 4, 5) . "\n";  // 60
echo "  sum(1,2,3,4,5)     = " . $m->sum(1, 2, 3, 4, 5) . "\n"; // 15

// ═══════════════════════════════════════════════════════════════
// 2. METHOD OVERRIDING — Subclass redefines parent method
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Method Overriding ---\n";

class Logger
{
    protected string $prefix = 'LOG';

    public function log(string $message): void
    {
        echo "  [{$this->prefix}] {$message}\n";
    }

    public function error(string $message): void
    {
        echo "  [{$this->prefix}] ERROR: {$message}\n";
    }

    public function info(): string
    {
        return "Logger: prefix={$this->prefix}";
    }
}

class FileLogger extends Logger
{
    private array $buffer = [];
    protected string $prefix = 'FILE'; // Override property

    // Override log() — different behavior
    public function log(string $message): void
    {
        $this->buffer[] = $message;
        parent::log($message); // Call parent behavior, then add own
        echo "    → Also buffered for disk ({$this->bufferSize()} buffered)\n";
    }

    public function flush(): void
    {
        echo "  [FILE] Flushing " . count($this->buffer) . " messages to disk\n";
        $this->buffer = [];
    }

    public function bufferSize(): int { return count($this->buffer); }

    // Override info()
    public function info(): string
    {
        return parent::info() . " | buffered=" . $this->bufferSize();
    }
}

class SilentLogger extends Logger
{
    protected string $prefix = 'SILENT';

    // Complete override — swallows all output (useful for testing)
    public function log(string $message): void
    {
        // Intentionally no output — completely overrides parent
    }

    public function error(string $message): void
    {
        // Still log errors even in silent mode
        parent::error($message);
    }
}

$plain  = new Logger();
$file   = new FileLogger();
$silent = new SilentLogger();

$plain->log("App started");
$plain->error("Something went wrong");

echo "\n";
$file->log("Processing order #123");
$file->log("Order complete");
$file->flush();
echo "  " . $file->info() . "\n";

echo "\n  SilentLogger (no output for log, only errors):\n";
$silent->log("This is suppressed");
$silent->error("This is NOT suppressed");

// ═══════════════════════════════════════════════════════════════
// 3. FINAL — Prevent overriding specific methods
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. final method — prevent overriding ---\n";

class BaseModel
{
    private int $id;
    private string $createdAt;

    // final: NO subclass can override this — core identity, must not change
    final public function getId(): int
    {
        return $this->id ?? 0;
    }

    final public function getCreatedAt(): string
    {
        return $this->createdAt ?? date('Y-m-d');
    }

    // Non-final: subclasses CAN override
    public function toArray(): array
    {
        return ['id' => $this->getId(), 'created_at' => $this->getCreatedAt()];
    }
}

class Post extends BaseModel
{
    public function __construct(private string $title, private string $body) {}

    // ✓ Can override non-final toArray()
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'title' => $this->title,
            'body'  => $this->body,
        ]);
    }

    // ✗ Cannot override final getId() — would cause Fatal Error:
    // public function getId(): int { ... }
}

$post = new Post('Hello OOP', 'This post covers overriding...');
print_r($post->toArray());

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the difference between overloading and overriding?  │
 * │ A: Overloading = same method name, different parameters,        │
 * │    same class (compile-time). PHP doesn't support it natively;  │
 * │    use __call() or default params.                               │
 * │    Overriding = subclass redefines parent method with same name │
 * │    and compatible signature (runtime polymorphism). PHP supports │
 * │    this natively.                                               │
 * │                                                                  │
 * │ Q2: Does PHP support method overloading?                         │
 * │ A: Not in the traditional sense (no signature-based dispatch).  │
 * │    PHP's "overloading" refers to magic methods (__call, __get,  │
 * │    __set) that intercept access to inaccessible members. For     │
 * │    function-like overloading, use default params or variadic.   │
 * │                                                                  │
 * │ Q3: Can you prevent a method from being overridden in PHP?       │
 * │ A: Yes — use the `final` keyword on the method or class. A      │
 * │    final method cannot be overridden in any subclass. A final   │
 * │    class cannot be extended at all.                             │
 * │                                                                  │
 * │ Q4: When overriding, can you change the return type?            │
 * │ A: Yes, but only to a covariant (narrower) type. If the parent  │
 * │    returns Animal, the override may return Dog (subtype). This  │
 * │    is valid and aligns with Liskov Substitution Principle.      │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Widening the overridden return type (parent: Animal, child:  │
 * │   object) — breaks LSP and PHP throws a fatal error.           │
 * │ ✗ Making overriding method less accessible (public → protected) │
 * │   — PHP does NOT allow narrowing visibility in overrides.       │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Overriding is the PRIMARY polymorphism mechanism in PHP.     │
 * │ ✓ Use parent:: to reuse parent logic in override.              │
 * │ ✓ final prevents overriding — use for security-critical methods.│
 * │ ✓ PHP "overloading" ≠ Java "overloading" — magic methods only. │
 * └─────────────────────────────────────────────────────────────────┘
 */
