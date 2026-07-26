<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #14 — TRAITS                            ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (PHP-specific — asked in ALL PHP interviews) ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * WHAT IS A TRAIT?
 *   A reusable code bundle that can be "mixed into" any class.
 *   PHP doesn't allow multiple class inheritance, but you CAN use
 *   multiple traits in one class.
 *
 * ANALOGY:
 *   Class  = employee's job description (one role)
 *   Trait  = skill certification (can have many: PHP cert, AWS cert, etc.)
 *   `use`  = "this employee has these certifications"
 *
 * TRAIT vs ABSTRACT CLASS vs INTERFACE:
 * ┌───────────────────┬──────────┬────────────────┬───────────────┐
 * │ Feature           │ Trait    │ Abstract Class │ Interface     │
 * ├───────────────────┼──────────┼────────────────┼───────────────┤
 * │ Has concrete code │ YES      │ YES            │ NO            │
 * │ Has properties    │ YES      │ YES            │ NO            │
 * │ Has constructor   │ NO       │ YES            │ NO            │
 * │ Multiple use      │ YES      │ NO             │ YES           │
 * │ Instantiable      │ NO       │ NO             │ NO            │
 * │ IS-A relationship │ NO       │ YES            │ YES           │
 * └───────────────────┴──────────┴────────────────┴───────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// 1. BASIC TRAIT — Reusable method bundle
// ═══════════════════════════════════════════════════════════════

trait Timestampable
{
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function touch(): void
    {
        $now = date('Y-m-d H:i:s');
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function getDates(): string
    {
        return "created={$this->createdAt}, updated={$this->updatedAt}";
    }
}

trait SoftDeletable
{
    private ?string $deletedAt = null;

    public function softDelete(): void { $this->deletedAt = date('Y-m-d H:i:s'); }
    public function restore(): void    { $this->deletedAt = null; }
    public function isDeleted(): bool  { return $this->deletedAt !== null; }
    public function getDeletedAt(): ?string { return $this->deletedAt; }
}

trait Serializable
{
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

// Use MULTIPLE traits in one class
class Post
{
    use Timestampable, SoftDeletable, Serializable;

    public function __construct(
        private int    $id,
        private string $title,
        private string $body
    ) {
        $this->touch();
    }

    public function update(string $title, string $body): void
    {
        $this->title = $title;
        $this->body  = $body;
        $this->touch();  // From Timestampable trait
    }

    public function getId(): int    { return $this->id; }
    public function getTitle(): string { return $this->title; }
}

echo "=== TRAITS DEMO ===\n\n";

echo "--- 1. Basic Trait usage ---\n";
$post = new Post(1, 'Hello OOP', 'Traits are powerful.');
echo "  Title: {$post->getTitle()}\n";
echo "  Dates: " . $post->getDates() . "\n";

$post->update('Updated Title', 'New body content.');
echo "  After update: " . $post->getDates() . "\n";

$post->softDelete();
echo "  Deleted: " . ($post->isDeleted() ? 'YES' : 'NO') . "\n";
$post->restore();
echo "  Restored: " . ($post->isDeleted() ? 'YES' : 'NO') . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. TRAIT CONFLICT RESOLUTION — insteadof + as
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Conflict resolution: insteadof + as ---\n";

trait Logger
{
    public function log(string $msg): void { echo "  [Logger] {$msg}\n"; }
    public function format(string $msg): string { return "[LOG] {$msg}"; }
}

trait Auditor
{
    public function log(string $msg): void { echo "  [Auditor] AUDIT: {$msg}\n"; }
    public function format(string $msg): string { return "[AUDIT] {$msg}"; }
}

class OrderService
{
    use Logger, Auditor {
        Logger::log    insteadof Auditor;   // Use Logger's log(), not Auditor's
        Auditor::log   as auditLog;          // Alias Auditor's log() as auditLog()
        Logger::format insteadof Auditor;
        Auditor::format as auditFormat;      // Keep Auditor's format under new name
    }

    public function placeOrder(int $id): void
    {
        $this->log("Order #{$id} placed");       // Logger's log()
        $this->auditLog("Order #{$id} saved to audit trail");  // Auditor's log()
    }
}

$svc = new OrderService();
$svc->placeOrder(42);

// ═══════════════════════════════════════════════════════════════
// 3. ABSTRACT METHODS IN TRAITS
//    Trait can declare abstract method → class MUST implement it
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. Abstract methods in traits ---\n";

trait Validatable
{
    // Trait declares abstract — forces implementing class to provide validation rules
    abstract protected function validationRules(): array;

    public function validate(array $data): array
    {
        $errors = [];
        foreach ($this->validationRules() as $field => $rule) {
            if ($rule === 'required' && empty($data[$field])) {
                $errors[] = "{$field} is required";
            }
            if (str_starts_with($rule, 'min:')) {
                $min = (int) substr($rule, 4);
                if (strlen($data[$field] ?? '') < $min) {
                    $errors[] = "{$field} must be at least {$min} chars";
                }
            }
        }
        return $errors;
    }
}

class RegistrationForm
{
    use Validatable;

    protected function validationRules(): array
    {
        return [
            'name'     => 'required',
            'email'    => 'required',
            'password' => 'min:8',
        ];
    }
}

$form = new RegistrationForm();
$errors = $form->validate(['name' => 'Alice', 'email' => '', 'password' => 'abc']);
foreach ($errors as $e) { echo "  ✗ {$e}\n"; }

$errors = $form->validate(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'secret123']);
echo "  Valid: " . (empty($errors) ? 'YES' : 'NO') . "\n";

// ═══════════════════════════════════════════════════════════════
// 4. TRAIT WITH STATIC MEMBERS
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. Static trait members ---\n";

trait Singleton
{
    private static ?self $instance = null;

    // Private constructor enforced via trait
    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // Prevent cloning and unserialization
    public function __clone() { throw new \RuntimeException("Cannot clone singleton"); }
}

class DatabasePool
{
    use Singleton;

    private int $connectionCount = 0;

    public function getConnection(): string
    {
        $this->connectionCount++;
        return "conn_" . $this->connectionCount;
    }
}

$pool1 = DatabasePool::getInstance();
$pool2 = DatabasePool::getInstance();
echo "  Same instance? " . ($pool1 === $pool2 ? 'YES' : 'NO') . "\n";
echo "  Conn 1: " . $pool1->getConnection() . "\n";
echo "  Conn 2: " . $pool2->getConnection() . "\n";  // counter continues — same object

// ═══════════════════════════════════════════════════════════════
// 5. TRAIT VISIBILITY CHANGE via `as`
// ═══════════════════════════════════════════════════════════════

echo "\n--- 5. Visibility change with 'as' ---\n";

trait Greetable
{
    public function greet(): string { return "Hello, I am " . static::class; }
}

class StrictGreeter
{
    use Greetable {
        greet as protected;  // Make greet() protected in this class
    }

    public function introduce(): string
    {
        return $this->greet() . " (via introduce)";  // Can call internally
    }
}

$g = new StrictGreeter();
echo "  " . $g->introduce() . "\n";
// $g->greet()  → Fatal: protected method

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is a trait? How is it different from an interface?     │
 * │ A: A trait is a reusable code bundle with concrete methods and  │
 * │    properties, included via `use`. An interface is a pure       │
 * │    contract with no implementation. Traits provide code reuse   │
 * │    without IS-A inheritance; interfaces enforce capability.     │
 * │                                                                  │
 * │ Q2: Can a trait have a constructor?                              │
 * │ A: Technically yes, but it's a bad practice. If both the class  │
 * │    and the trait define __construct(), it causes conflicts.      │
 * │    Traits should not own initialization — leave that to classes. │
 * │                                                                  │
 * │ Q3: How do you resolve a method conflict between two traits?    │
 * │ A: Use `insteadof` to pick one implementation, and `as` to     │
 * │    create an alias for the discarded one so it's still usable.  │
 * │    Example: TraitA::method insteadof TraitB; TraitB::method as  │
 * │    methodB;                                                      │
 * │                                                                  │
 * │ Q4: What's the difference between traits and mixins?            │
 * │ A: PHP traits ARE PHP's implementation of the mixin concept.   │
 * │    Mixins provide reusable behavior without inheritance.        │
 * │    PHP's traits are "compiler-assisted copy-paste" — at        │
 * │    runtime, trait methods behave as if written in the class.    │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Traits with state (properties) shared between classes can    │
 * │   cause unexpected behavior — treat trait properties carefully. │
 * │ ✗ Too many traits = "trait soup" — same smell as God class.    │
 * │ ✗ Traits don't create IS-A relationship — instanceof won't     │
 * │   return true for a trait name.                                 │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Traits = horizontal code reuse without inheritance.          │
 * │ ✓ PHP's answer to multiple inheritance limitations.             │
 * │ ✓ Use for cross-cutting concerns: logging, timestamps, caching. │
 * │ ✓ insteadof + as = conflict resolution tools.                  │
 * └─────────────────────────────────────────────────────────────────┘
 */
