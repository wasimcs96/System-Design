<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║           OOP CONCEPT #12 — FINAL & CONST                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate                                        ║
 * ║  FREQUENCY : ★★★☆☆  (asked in mid-senior interviews)            ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * FINAL KEYWORD:
 *   final class   — cannot be extended (no subclasses)
 *   final method  — cannot be overridden in subclasses
 *
 * CONST KEYWORD:
 *   const NAME    — class constant; shared, immutable, compile-time value
 *   Accessed via: ClassName::CONST_NAME or self::CONST_NAME
 *
 * COMPARISON: const vs static vs readonly:
 * ┌───────────────┬──────────────┬──────────────┬───────────────────┐
 * │               │ const        │ static prop  │ readonly prop     │
 * ├───────────────┼──────────────┼──────────────┼───────────────────┤
 * │ PHP version   │ All          │ All          │ 8.1+              │
 * │ Value set     │ Compile-time │ Runtime      │ Once (ctor)       │
 * │ Type          │ Scalar/array │ Any          │ Any               │
 * │ Can change?   │ No           │ Yes (static) │ No (after init)   │
 * │ Per-object?   │ No (shared)  │ No (shared)  │ Yes (per object)  │
 * │ Access        │ ClassName::  │ ClassName::  │ $this->           │
 * └───────────────┴──────────────┴──────────────┴───────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// 1. FINAL CLASS — Cannot be extended
// ═══════════════════════════════════════════════════════════════

final class Uuid
{
    private function __construct(private string $value) {}

    public static function generate(): self
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public static function fromString(string $uuid): self
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            throw new \InvalidArgumentException("Invalid UUID: {$uuid}");
        }
        return new self($uuid);
    }

    public function toString(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}

// final class Uuid → CANNOT do: class MyUuid extends Uuid {}

echo "=== FINAL & CONST DEMO ===\n\n";

echo "--- 1. final class ---\n";
$id1 = Uuid::generate();
$id2 = Uuid::generate();
$id3 = Uuid::fromString($id1->toString());

echo "  UUID 1: {$id1}\n";
echo "  UUID 2: {$id2}\n";
echo "  id1 == id3? " . ($id1->equals($id3) ? 'YES' : 'NO') . "\n";
echo "  id1 == id2? " . ($id1->equals($id2) ? 'YES' : 'NO') . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. FINAL METHOD — Cannot be overridden
// ═══════════════════════════════════════════════════════════════

echo "\n--- 2. final method ---\n";

abstract class BaseController
{
    // Template method — final so subclasses can't bypass auth
    final public function handleRequest(array $request): array
    {
        $this->authenticate($request);
        $this->validate($request);
        $result = $this->process($request);
        return $this->formatResponse($result);
    }

    // These CAN be overridden (non-final)
    protected function authenticate(array $req): void
    {
        echo "  [Auth] Checking token...\n";
    }

    protected function validate(array $req): void
    {
        echo "  [Validate] Checking inputs...\n";
    }

    abstract protected function process(array $request): array;

    protected function formatResponse(array $data): array
    {
        return ['success' => true, 'data' => $data, 'timestamp' => time()];
    }
}

class CreateUserController extends BaseController
{
    protected function process(array $request): array
    {
        echo "  [Process] Creating user: " . ($request['name'] ?? 'Unknown') . "\n";
        return ['id' => rand(100, 999), 'name' => $request['name'] ?? ''];
    }

    // ✓ Can override validate()
    protected function validate(array $req): void
    {
        parent::validate($req);
        if (empty($req['name'])) throw new \InvalidArgumentException("Name required");
        echo "  [Validate] Name present: OK\n";
    }

    // ✗ CANNOT override final handleRequest()
}

$ctrl = new CreateUserController();
$result = $ctrl->handleRequest(['name' => 'Alice', 'email' => 'alice@example.com']);
echo "  Response: success=" . ($result['success'] ? 'true' : 'false') . " | id=" . ($result['data']['id'] ?? '') . "\n";

// ═══════════════════════════════════════════════════════════════
// 3. CLASS CONSTANTS — const
// ═══════════════════════════════════════════════════════════════

echo "\n--- 3. class constants (const) ---\n";

class HttpStatus
{
    // Constants — immutable, shared, no $ prefix
    const OK                  = 200;
    const CREATED             = 201;
    const NO_CONTENT          = 204;
    const BAD_REQUEST         = 400;
    const UNAUTHORIZED        = 401;
    const FORBIDDEN           = 403;
    const NOT_FOUND           = 404;
    const UNPROCESSABLE       = 422;
    const INTERNAL_ERROR      = 500;
    const SERVICE_UNAVAILABLE = 503;

    // Method that uses constants
    public static function getMessage(int $code): string
    {
        return match($code) {
            self::OK                  => 'OK',
            self::CREATED             => 'Created',
            self::NO_CONTENT          => 'No Content',
            self::BAD_REQUEST         => 'Bad Request',
            self::UNAUTHORIZED        => 'Unauthorized',
            self::FORBIDDEN           => 'Forbidden',
            self::NOT_FOUND           => 'Not Found',
            self::UNPROCESSABLE       => 'Unprocessable Entity',
            self::INTERNAL_ERROR      => 'Internal Server Error',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            default                   => 'Unknown',
        };
    }

    public static function isSuccess(int $code): bool { return $code >= 200 && $code < 300; }
    public static function isError(int $code): bool   { return $code >= 400; }
}

echo "  200: " . HttpStatus::getMessage(HttpStatus::OK) . "\n";
echo "  404: " . HttpStatus::getMessage(HttpStatus::NOT_FOUND) . "\n";
echo "  201 success? " . (HttpStatus::isSuccess(HttpStatus::CREATED) ? 'YES' : 'NO') . "\n";
echo "  500 error?   " . (HttpStatus::isError(HttpStatus::INTERNAL_ERROR) ? 'YES' : 'NO') . "\n";

// Constants in inheritance — child can override parent's const
class ExtendedStatus extends HttpStatus
{
    const TOO_MANY_REQUESTS = 429;
    const OK = 200; // Can re-declare — not truly "overridden" but shadowed
}

echo "  429: " . ExtendedStatus::getMessage(ExtendedStatus::TOO_MANY_REQUESTS) . "\n";

// ═══════════════════════════════════════════════════════════════
// 4. INTERFACE CONSTANTS
// ═══════════════════════════════════════════════════════════════

echo "\n--- 4. Interface constants ---\n";

interface Directions
{
    const NORTH = 'N';
    const SOUTH = 'S';
    const EAST  = 'E';
    const WEST  = 'W';
}

class Compass implements Directions
{
    public function navigate(string $direction): void
    {
        $names = [self::NORTH => 'North', self::SOUTH => 'South',
                  self::EAST  => 'East',  self::WEST  => 'West'];
        echo "  Heading " . ($names[$direction] ?? 'Unknown') . "\n";
    }
}

$compass = new Compass();
$compass->navigate(Directions::NORTH);
$compass->navigate(Compass::EAST); // Also accessible via class

// ═══════════════════════════════════════════════════════════════
// 5. TYPED CONSTANTS (PHP 8.3+)
//    Before 8.3: constants had no declared type
// ═══════════════════════════════════════════════════════════════

echo "\n--- 5. Enum as typed constants (PHP 8.1+) ---\n";

enum Color: string
{
    case Red   = '#FF0000';
    case Green = '#00FF00';
    case Blue  = '#0000FF';
    case Black = '#000000';
    case White = '#FFFFFF';

    public function label(): string
    {
        return match($this) {
            Color::Red   => 'Red',
            Color::Green => 'Green',
            Color::Blue  => 'Blue',
            Color::Black => 'Black',
            Color::White => 'White',
        };
    }

    public function isDark(): bool
    {
        return in_array($this, [self::Black, self::Blue, self::Red]);
    }
}

foreach (Color::cases() as $color) {
    $dark = $color->isDark() ? ' (dark)' : '';
    echo "  {$color->label()}: {$color->value}{$dark}\n";
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What does the `final` keyword do in PHP?                     │
 * │ A: On a class: prevents the class from being extended.         │
 * │    On a method: prevents the method from being overridden in    │
 * │    any subclass. Used to lock down critical behavior (e.g.,     │
 * │    template methods, security-sensitive operations).            │
 * │                                                                  │
 * │ Q2: What is the difference between const and define()?          │
 * │ A: Class `const` is scoped to the class (ClassName::CONST),    │
 * │    evaluated at compile-time, can only hold scalars/arrays.     │
 * │    `define()` creates a global constant, evaluated at runtime,  │
 * │    can be conditional. For class constants, always use const.   │
 * │                                                                  │
 * │ Q3: Can a child class override a parent class constant?         │
 * │ A: Yes — a child class can re-declare the same constant with a  │
 * │    new value. It shadows (not overrides) the parent's constant. │
 * │    Access via parent::CONST or ChildClass::CONST respectively.  │
 * │                                                                  │
 * │ Q4: Why use final classes?                                       │
 * │ A: Prevents unintended subclassing that could break behavior   │
 * │    (e.g., value objects like Uuid, Money). Enables certain      │
 * │    optimizations. Also communicates design intent: "this class  │
 * │    is not designed to be extended."                             │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Making everything final — blocks legitimate extension.        │
 * │ ✗ Using const for values that may change per environment (use  │
 * │   .env / config instead).                                       │
 * │ ✗ Confusing PHP's `const` (class scope) with JS `const`.       │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ final class = cannot extend; final method = cannot override.  │
 * │ ✓ const = immutable class-level named value (no $).            │
 * │ ✓ Enums (PHP 8.1) = typed, safe alternative to const groups.   │
 * │ ✓ Use final for value objects, security, and template methods.  │
 * └─────────────────────────────────────────────────────────────────┘
 */
