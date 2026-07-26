<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║      OOP CONCEPT #7 — INTERFACE vs ABSTRACT CLASS               ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (#1 most-asked OOP comparison question)     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * ONE-LINE DISTINCTION:
 *   Interface  = CONTRACT — defines WHAT a class must do (no implementation)
 *   Abstract   = PARTIAL BLUEPRINT — defines WHAT + some shared HOW
 *
 * ANALOGY:
 *   Interface  = job description ("must be able to drive, code, manage")
 *   Abstract   = employee handbook ("here's common HR policy + you must
 *                implement your own role tasks")
 */

// ╔═══════════════════════════════════════════════════════════════════╗
// ║ COMPARISON TABLE                                                  ║
// ╠═════════════════════════════════╦═══════════════╦════════════════╣
// ║ Feature                         ║ Interface     ║ Abstract Class ║
// ╠═════════════════════════════════╬═══════════════╬════════════════╣
// ║ Can have concrete methods?      ║ No (PHP 8-)   ║ YES            ║
// ║ Can have constructor?           ║ No            ║ YES            ║
// ║ Can have properties?            ║ No            ║ YES            ║
// ║ Multiple implementation?        ║ YES           ║ No (single)    ║
// ║ Access modifiers on methods     ║ Always public ║ Any visibility ║
// ║ Constants allowed?              ║ YES           ║ YES            ║
// ║ Instantiation?                  ║ No            ║ No             ║
// ╚═════════════════════════════════╩═══════════════╩════════════════╝

// ═══════════════════════════════════════════════════════════════
// INTERFACE EXAMPLE — Pure contract (WHAT, not HOW)
// ═══════════════════════════════════════════════════════════════

interface Serializable
{
    public function serialize(): string;
    public function unserialize(string $data): static;
}

interface Cacheable
{
    public function getCacheKey(): string;
    public function getTtl(): int;
}

interface Loggable
{
    public function getLogMessage(): string;
}

// A class can implement MULTIPLE interfaces (multiple "contracts")
class UserProfile implements Serializable, Cacheable, Loggable
{
    public function __construct(
        private int    $id,
        private string $name,
        private string $email
    ) {}

    // Serializable
    public function serialize(): string
    {
        return json_encode(['id' => $this->id, 'name' => $this->name, 'email' => $this->email]);
    }

    public function unserialize(string $data): static
    {
        $arr = json_decode($data, true);
        return new static($arr['id'], $arr['name'], $arr['email']);
    }

    // Cacheable
    public function getCacheKey(): string { return "user:{$this->id}"; }
    public function getTtl(): int         { return 3600; }

    // Loggable
    public function getLogMessage(): string { return "UserProfile[{$this->id}]: {$this->name} ({$this->email})"; }

    public function getName(): string { return $this->name; }
}

echo "=== INTERFACE vs ABSTRACT CLASS DEMO ===\n\n";

echo "--- Interface: Multiple contract implementation ---\n";
$user = new UserProfile(1, 'Alice', 'alice@example.com');

echo "  Serialized:    " . $user->serialize() . "\n";
echo "  Cache key:     " . $user->getCacheKey() . "\n";
echo "  TTL:           " . $user->getTtl() . " seconds\n";
echo "  Log message:   " . $user->getLogMessage() . "\n";

// Functions work with the INTERFACE TYPE — polymorphism
function storeInCache(Cacheable $item, string $value): void
{
    echo "  Storing in cache[" . $item->getCacheKey() . "] for " . $item->getTtl() . "s\n";
}

storeInCache($user, $user->serialize());

// ═══════════════════════════════════════════════════════════════
// ABSTRACT CLASS EXAMPLE — Partial blueprint (WHAT + some HOW)
// ═══════════════════════════════════════════════════════════════
/**
 * Use abstract class when:
 *   - You have SHARED implementation code multiple children need
 *   - You want to provide a template method (algorithm skeleton)
 *   - Children share STATE (properties)
 */

abstract class BaseRepository
{
    // Shared state (properties — only abstract classes can have these)
    protected array $items = [];
    private int     $queryCount = 0;

    // Constructor — only abstract classes can have this
    public function __construct(protected string $tableName) {}

    // ── Abstract methods: children MUST implement ────────────────
    abstract protected function validate(array $data): bool;
    abstract protected function toEntity(array $row): object;
    abstract public    function getEntityName(): string;

    // ── Concrete shared methods — reusable logic ─────────────────
    public function findAll(): array
    {
        $this->queryCount++;
        echo "  SELECT * FROM {$this->tableName}\n";
        return array_map([$this, 'toEntity'], $this->items);
    }

    public function findById(int $id): ?object
    {
        $this->queryCount++;
        $row = array_values(array_filter($this->items, fn($r) => $r['id'] === $id));
        return empty($row) ? null : $this->toEntity($row[0]);
    }

    public function save(array $data): bool
    {
        if (!$this->validate($data)) {
            echo "  Validation failed for {$this->getEntityName()}\n";
            return false;
        }
        $data['id'] = count($this->items) + 1;
        $this->items[] = $data;
        echo "  Saved {$this->getEntityName()} #{$data['id']} to {$this->tableName}\n";
        return true;
    }

    public function count(): int    { return count($this->items); }
    public function getQueryCount(): int { return $this->queryCount; }
}

// Concrete repository — only implements what's abstract
class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('users');  // Call parent constructor
    }

    protected function validate(array $data): bool
    {
        return !empty($data['name']) && filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    }

    protected function toEntity(array $row): object
    {
        return (object)['id' => $row['id'], 'name' => $row['name'], 'email' => $row['email']];
    }

    public function getEntityName(): string { return 'User'; }

    // Extra method specific to UserRepository
    public function findByEmail(string $email): ?object
    {
        $row = array_values(array_filter($this->items, fn($r) => $r['email'] === $email));
        return empty($row) ? null : $this->toEntity($row[0]);
    }
}

class ProductRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('products');
    }

    protected function validate(array $data): bool
    {
        return !empty($data['name']) && isset($data['price']) && $data['price'] > 0;
    }

    protected function toEntity(array $row): object
    {
        return (object)['id' => $row['id'], 'name' => $row['name'], 'price' => $row['price']];
    }

    public function getEntityName(): string { return 'Product'; }
}

echo "\n--- Abstract Class: BaseRepository pattern ---\n";

$userRepo = new UserRepository();
$userRepo->save(['name' => 'Alice', 'email' => 'alice@example.com']);
$userRepo->save(['name' => 'Bob',   'email' => 'bob@example.com']);
$userRepo->save(['name' => '',      'email' => 'invalid']);  // Fails validation

$found = $userRepo->findById(1);
echo "  Found by ID: " . ($found ? $found->name : 'null') . "\n";

$byEmail = $userRepo->findByEmail('bob@example.com');
echo "  Found by email: " . ($byEmail ? $byEmail->name : 'null') . "\n";

echo "  Users: {$userRepo->count()}, Queries: {$userRepo->getQueryCount()}\n";

$prodRepo = new ProductRepository();
$prodRepo->save(['name' => 'Laptop', 'price' => 85000]);
$prodRepo->save(['name' => 'Mouse',  'price' => 0]);     // Fails validation
echo "  Products: {$prodRepo->count()}\n";

// ═══════════════════════════════════════════════════════════════
// WHEN TO USE WHICH — Practical decision guide
// ═══════════════════════════════════════════════════════════════

echo "\n--- Interface extending Interface ---\n";
/**
 * Interfaces can extend other interfaces (multiple allowed)
 */
interface Readable
{
    public function read(): string;
}

interface Writable
{
    public function write(string $content): void;
}

interface ReadWrite extends Readable, Writable
{
    public function flush(): void;
}

class FileStream implements ReadWrite
{
    private string $buffer = '';
    private string $content = '';

    public function read(): string    { return $this->content; }
    public function write(string $content): void { $this->buffer .= $content; }
    public function flush(): void     { $this->content = $this->buffer; $this->buffer = ''; }
}

$stream = new FileStream();
$stream->write("Hello, ");
$stream->write("World!");
$stream->flush();
echo "  Stream content: '" . $stream->read() . "'\n";

// Abstract class extending abstract class
abstract class BaseNotification
{
    abstract public function getMessage(): string;
    abstract public function getRecipient(): string;

    public function send(): void
    {
        echo "  Sending to {$this->getRecipient()}: {$this->getMessage()}\n";
    }
}

abstract class EmailNotification extends BaseNotification
{
    abstract public function getSubject(): string;  // adds another abstract

    public function send(): void
    {
        echo "  [EMAIL] To: {$this->getRecipient()} | Subject: {$this->getSubject()}\n";
        echo "         Body: {$this->getMessage()}\n";
    }
}

class WelcomeEmail extends EmailNotification
{
    public function __construct(private string $userName, private string $email) {}
    public function getMessage(): string   { return "Welcome to our platform, {$this->userName}!"; }
    public function getRecipient(): string { return $this->email; }
    public function getSubject(): string   { return "Welcome!"; }
}

echo "\n--- Abstract extends Abstract ---\n";
$email = new WelcomeEmail('Alice', 'alice@example.com');
$email->send();

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DECISION GUIDE                                                   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Use INTERFACE when:                                              │
 * │   ✓ You want to define a pure contract (WHAT, not HOW)          │
 * │   ✓ Multiple unrelated classes need to share a capability       │
 * │   ✓ A class needs to fulfil multiple contracts                  │
 * │   ✓ You expect the contract to be stable and widely implemented │
 * │   Example: Serializable, Countable, Iterator                   │
 * │                                                                  │
 * │ Use ABSTRACT CLASS when:                                         │
 * │   ✓ Classes share common implementation code                    │
 * │   ✓ You need shared state (properties)                          │
 * │   ✓ Template Method pattern (algorithm skeleton)                │
 * │   ✓ Base class needs a constructor                              │
 * │   Example: BaseRepository, BaseController, AbstractValidator   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Main difference between interface and abstract class?        │
 * │ A: Interface = pure contract, all methods abstract, no state,   │
 * │    multiple implementation allowed. Abstract class = partial    │
 * │    blueprint, can have concrete methods and properties, only    │
 * │    single inheritance. Use interface for capability contracts,  │
 * │    abstract class for shared implementation logic.              │
 * │                                                                  │
 * │ Q2: Can an abstract class implement an interface?                │
 * │ A: Yes. An abstract class can implement an interface and leave  │
 * │    the interface methods abstract for concrete subclasses to    │
 * │    implement. This is a common base-class pattern.             │
 * │                                                                  │
 * │ Q3: Can a class extend abstract + implement interface?           │
 * │ A: Yes. class Foo extends AbstractBase implements IFace1, IFace2│
 * │    This is the most flexible design in PHP.                     │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Adding implementation to an interface — not allowed in PHP.  │
 * │ ✗ Making too many abstract classes when interfaces would do.    │
 * │ ✗ Abstract class with NO abstract methods (use regular class).  │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Interface = WHAT (capability contract).                       │
 * │ ✓ Abstract = WHAT + partial HOW (shared implementation).        │
 * │ ✓ Interface = multiple; Abstract = single inheritance.          │
 * │ ✓ "Program to an interface, not an implementation."            │
 * └─────────────────────────────────────────────────────────────────┘
 */
