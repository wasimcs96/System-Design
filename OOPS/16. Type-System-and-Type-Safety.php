<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          OOP CONCEPT #16 — TYPE SYSTEM & TYPE SAFETY             ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (PHP 8 features asked heavily in 2024-25)   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * PHP'S TYPE SYSTEM EVOLUTION:
 *   PHP 5    → basic type hints (class/interface names, array, callable)
 *   PHP 7.0  → scalar types (int, float, string, bool), return types, strict_types
 *   PHP 7.1  → nullable (?Type), void return type
 *   PHP 7.4  → typed properties, covariant return types
 *   PHP 8.0  → union types (A|B), mixed, static return type
 *   PHP 8.1  → intersection types (A&B), never return type, readonly props
 *   PHP 8.2  → readonly classes, true/false/null standalone types
 *   PHP 8.3  → typed class constants
 *
 * WHY IT MATTERS FOR INTERVIEWS:
 *   Type-safe code is self-documenting, catches bugs at compile-time (IDEs),
 *   and is expected in senior/lead PHP positions. LLD code with proper
 *   type hints shows production-quality thinking.
 */

declare(strict_types=1); // All scalar args are validated against declared types

// ═══════════════════════════════════════════════════════════════
// 1. SCALAR + RETURN TYPES — PHP 7.0+
// ═══════════════════════════════════════════════════════════════

function divide(int $a, int $b): float
{
    if ($b === 0) throw new \DivisionByZeroError("Cannot divide by zero");
    return $a / $b;
}

function greet(string $name, int $times = 1): string
{
    return implode(' ', array_fill(0, $times, "Hello, {$name}!"));
}

function isAdult(int $age): bool { return $age >= 18; }

echo "=== TYPE SYSTEM & TYPE SAFETY DEMO ===\n\n";

echo "--- 1. Scalar & return types ---\n";
echo "  " . greet('Alice', 2) . "\n";
echo "  divide(10, 3) = " . divide(10, 3) . "\n";
echo "  isAdult(16) = " . (isAdult(16) ? 'true' : 'false') . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. NULLABLE TYPES — ?Type (PHP 7.1+)
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. Nullable types (?Type) ---\n";

class UserProfile
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly ?string $bio      = null,  // Optional bio
        public readonly ?string $avatarUrl = null  // Optional avatar
    ) {}

    public function hasBio(): bool     { return $this->bio !== null; }
    public function hasAvatar(): bool  { return $this->avatarUrl !== null; }
}

function findUserById(int $id): ?UserProfile  // Returns null if not found
{
    $users = [1 => new UserProfile(1, 'Alice', 'PHP developer'), 2 => new UserProfile(2, 'Bob')];
    return $users[$id] ?? null;
}

$user = findUserById(1);
echo "  User: " . ($user?->name ?? 'Not found') . "\n";  // Nullsafe operator ?.
echo "  Bio:  " . ($user?->bio ?? 'No bio') . "\n";

$ghost = findUserById(99);
echo "  Ghost: " . ($ghost?->name ?? 'Not found') . "\n";  // null safely

// ═══════════════════════════════════════════════════════════════
// 3. UNION TYPES — A|B (PHP 8.0+)
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. Union types (A|B) ---\n";

function formatId(int|string $id): string
{
    return is_int($id) ? str_pad((string)$id, 8, '0', STR_PAD_LEFT) : strtoupper($id);
}

class EventDispatcher
{
    private array $listeners = [];

    // Accept either a Closure or any callable (string function name, array method)
    public function on(string $event, Closure|array|string $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function emit(string $event, mixed ...$args): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$args);
        }
    }
}

echo "  int ID:    " . formatId(42) . "\n";
echo "  string ID: " . formatId('usr-abc') . "\n";

$dispatcher = new EventDispatcher();
$dispatcher->on('login', fn(string $user) => print("  [Event] {$user} logged in\n"));
$dispatcher->emit('login', 'Alice');

// ═══════════════════════════════════════════════════════════════
// 4. INTERSECTION TYPES — A&B (PHP 8.1+)
//    Value must implement ALL listed interfaces
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. Intersection types (A&B) ---\n";

interface Countable2 { public function count(): int; }
interface Iterable2  { public function toArray(): array; }
interface Filterable { public function filter(callable $fn): static; }

// Function requires object that IS BOTH Countable2 AND Iterable2
function processCollection(Countable2&Iterable2 $collection): void
{
    echo "  Collection has " . $collection->count() . " items: "
       . implode(', ', $collection->toArray()) . "\n";
}

class NumberList implements Countable2, Iterable2, Filterable
{
    private array $items;

    public function __construct(int ...$items) { $this->items = $items; }

    public function count(): int          { return count($this->items); }
    public function toArray(): array      { return $this->items; }
    public function filter(callable $fn): static
    {
        return new static(...array_values(array_filter($this->items, $fn)));
    }
}

$list = new NumberList(1, 2, 3, 4, 5, 6, 7, 8);
processCollection($list);

$evens = $list->filter(fn($n) => $n % 2 === 0);
processCollection($evens);

// ═══════════════════════════════════════════════════════════════
// 5. COVARIANCE & CONTRAVARIANCE (PHP 7.4+)
// ═══════════════════════════════════════════════════════════════
/**
 * COVARIANCE: Override can return a MORE SPECIFIC (narrower) type
 *   Parent returns Animal → Child can return Dog (subtype) ✓
 *
 * CONTRAVARIANCE: Override can accept a LESS SPECIFIC (wider) type
 *   Parent param: Dog → Child param: Animal ✓
 */

echo "\n--- 5. Covariance & Contravariance ---\n";

class AnimalBase
{
    public function __construct(public readonly string $name) {}
    public function __toString(): string { return static::class . "({$this->name})"; }
}

class DogBase extends AnimalBase {}
class CatBase extends AnimalBase {}

class AnimalFactory
{
    public function create(string $name): AnimalBase  // returns AnimalBase
    {
        return new AnimalBase($name);
    }
}

// COVARIANT return — DogFactory returns DogBase (more specific than AnimalBase) ✓
class DogFactory extends AnimalFactory
{
    public function create(string $name): DogBase  // DogBase IS-A AnimalBase → valid!
    {
        return new DogBase($name);
    }
}

$factory = new DogFactory();
$dog = $factory->create('Rex');
echo "  Covariant: " . $dog . " (is DogBase: " . ($dog instanceof DogBase ? 'YES' : 'NO') . ")\n";

// CONTRAVARIANT parameter — accept wider type
class AnimalShelter
{
    public function accept(DogBase $dog): void  // accepts only DogBase
    {
        echo "  Shelter accepts: {$dog}\n";
    }
}

class SuperShelter extends AnimalShelter
{
    public function accept(AnimalBase $animal): void  // accepts any AnimalBase → wider ✓
    {
        echo "  SuperShelter accepts: {$animal}\n";
    }
}

$shelter = new SuperShelter();
$shelter->accept(new DogBase('Buddy'));
$shelter->accept(new CatBase('Whiskers'));

// ═══════════════════════════════════════════════════════════════
// 6. `never` RETURN TYPE — function never returns normally
// ═══════════════════════════════════════════════════════════════

echo "\n--- 6. 'never' return type ---\n";

function throwNotFound(string $resource, int $id): never
{
    throw new \RuntimeException("{$resource} with ID {$id} not found.");
}

function redirectTo(string $url): never
{
    // In real code: header("Location: $url"); exit;
    throw new \RuntimeException("Redirect to: {$url}");  // simulated
}

try {
    throwNotFound('User', 99);
} catch (\RuntimeException $e) {
    echo "  Caught: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 7. mixed, void, static return types
// ═══════════════════════════════════════════════════════════════

echo "\n--- 7. mixed, void, static return types ---\n";

class FluentBuilder
{
    private array $attributes = [];

    public function set(string $key, mixed $value): static  // static = return same class or child
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function get(string $key): mixed  // mixed = any type
    {
        return $this->attributes[$key] ?? null;
    }

    public function build(): array       { return $this->attributes; }

    public function dump(): void         // void = no return value
    {
        echo "  Attributes: " . json_encode($this->attributes) . "\n";
    }
}

class ExtendedBuilder extends FluentBuilder
{
    public function withDefaults(): static  // static returns ExtendedBuilder ✓
    {
        return $this->set('created_at', date('Y-m-d'))
                    ->set('active', true);
    }
}

$result = (new ExtendedBuilder())
    ->withDefaults()   // Returns ExtendedBuilder (static)
    ->set('name', 'Alice')
    ->set('age', 25);

$result->dump();
echo "  Name: " . $result->get('name') . " | Type: " . gettype($result->get('age')) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is strict_types=1 and why use it?                       │
 * │ A: declare(strict_types=1) forces PHP to validate scalar type   │
 * │    hints strictly (no implicit coercion). Without it, PHP       │
 * │    silently converts '5' to 5 for int params. Use it always for │
 * │    production code — catches bugs at runtime before they become  │
 * │    data corruption issues.                                       │
 * │                                                                  │
 * │ Q2: What is the difference between union and intersection types? │
 * │ A: Union (A|B): value can be A OR B. Accept multiple types.     │
 * │    Intersection (A&B): value must implement BOTH A AND B.       │
 * │    Union = OR; Intersection = AND.                              │
 * │                                                                  │
 * │ Q3: What is covariance in PHP return types?                      │
 * │ A: When an overriding method returns a MORE SPECIFIC type than  │
 * │    the parent. If parent returns Animal, child can return Dog.  │
 * │    Supported since PHP 7.4. Makes LSP work naturally.          │
 * │                                                                  │
 * │ Q4: What does the `never` return type mean?                      │
 * │ A: Indicates the function NEVER returns normally — it always    │
 * │    throws an exception or terminates the script. Useful for     │
 * │    error helpers, redirect functions, and exit wrappers.        │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Always use declare(strict_types=1) in production PHP.        │
 * │ ✓ Prefer specific types over mixed/no type hints.              │
 * │ ✓ Nullsafe operator (?->) avoids null-check boilerplate.       │
 * │ ✓ Union types replace old docblock @param int|string patterns. │
 * └─────────────────────────────────────────────────────────────────┘
 */
