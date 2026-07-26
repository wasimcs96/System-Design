<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║        OOP CONCEPT #19 — DEPENDENCY INJECTION & IoC              ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Advanced                                            ║
 * ║  FREQUENCY : ★★★★★  (Core of Laravel — asked in EVERY interview) ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * DEPENDENCY INJECTION (DI):
 *   Instead of a class creating its own dependencies,
 *   they are "injected" from the outside.
 *
 *   "Don't call us, we'll call you." — Hollywood Principle
 *
 * INVERSION OF CONTROL (IoC):
 *   A broader principle — control of object creation is "inverted"
 *   from the class itself to an external container/framework.
 *   DI is the most common way to implement IoC.
 *
 * THREE TYPES OF DI:
 *   1. Constructor Injection  — passed via constructor (PREFERRED)
 *   2. Setter Injection       — passed via setter method (optional deps)
 *   3. Interface Injection    — class implements an "inject" interface
 *
 * WHY IT MATTERS:
 *   Testability  — inject mocks instead of real dependencies
 *   Flexibility  — swap implementations without changing business logic
 *   Loose coupling — class doesn't know HOW its dep is built
 *   Follows DIP  — depends on abstractions, not concretions
 */

// ═══════════════════════════════════════════════════════════════
// BEFORE DI — Hard dependencies (Anti-pattern)
// ═══════════════════════════════════════════════════════════════

echo "=== DEPENDENCY INJECTION & IoC DEMO ===\n\n";

echo "--- ❌ WITHOUT DI (tight coupling) ---\n";

class RealMysqlDb
{
    public function query(string $sql): array
    {
        echo "  [MySQL] Executing: {$sql}\n";
        return [['id' => 1, 'name' => 'Alice']];
    }
}

class RealSmtpMailer2
{
    public function send(string $to, string $subject): void
    {
        echo "  [SMTP] Sending '{$subject}' → {$to}\n";
    }
}

class TightUserService
{
    // Creates own deps — CANNOT be tested or swapped without editing code
    private RealMysqlDb $db;
    private RealSmtpMailer2 $mailer;

    public function __construct()
    {
        $this->db     = new RealMysqlDb();      // ❌ Hardcoded
        $this->mailer = new RealSmtpMailer2();  // ❌ Hardcoded
    }

    public function getUser(int $id): array
    {
        return $this->db->query("SELECT * FROM users WHERE id={$id}")[0] ?? [];
    }
}

$tight = new TightUserService();
$user  = $tight->getUser(1);
echo "  User: " . $user['name'] . "\n";

// ═══════════════════════════════════════════════════════════════
// TYPE 1: CONSTRUCTOR INJECTION (Preferred)
// ═══════════════════════════════════════════════════════════════

echo "\n--- ✅ TYPE 1: Constructor Injection ---\n";

interface Database
{
    public function query(string $sql): array;
    public function execute(string $sql, array $bindings = []): bool;
}

interface Mailer
{
    public function send(string $to, string $subject, string $body): void;
}

interface Cache
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): void;
    public function has(string $key): bool;
}

// Concrete implementations
class MysqlDatabase implements Database
{
    public function query(string $sql): array
    {
        echo "  [MySQL] {$sql}\n";
        return [['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com']];
    }

    public function execute(string $sql, array $b = []): bool
    {
        echo "  [MySQL] Execute: {$sql}\n";
        return true;
    }
}

class SmtpMailer implements Mailer
{
    public function send(string $to, string $subject, string $body): void
    {
        echo "  [SMTP] To:{$to} | Subject:{$subject}\n";
    }
}

class RedisCache implements Cache
{
    private array $store = [];

    public function get(string $key): mixed             { return $this->store[$key] ?? null; }
    public function set(string $key, mixed $v, int $ttl = 3600): void { $this->store[$key] = $v; }
    public function has(string $key): bool              { return isset($this->store[$key]); }
}

// Service receives ALL deps via constructor — no hardcoding
class UserService
{
    public function __construct(
        private Database $db,       // Interface — any DB implementation
        private Mailer   $mailer,   // Interface — any Mailer implementation
        private Cache    $cache     // Interface — any Cache implementation
    ) {}

    public function find(int $id): ?array
    {
        $cacheKey = "user:{$id}";

        if ($this->cache->has($cacheKey)) {
            echo "  [Cache HIT] {$cacheKey}\n";
            return $this->cache->get($cacheKey);
        }

        $rows = $this->db->query("SELECT * FROM users WHERE id = {$id}");
        $user = $rows[0] ?? null;

        if ($user) {
            $this->cache->set($cacheKey, $user);
        }

        return $user;
    }

    public function register(array $data): bool
    {
        $ok = $this->db->execute("INSERT INTO users ...", $data);
        if ($ok) {
            $this->mailer->send($data['email'], 'Welcome!', 'Thanks for joining.');
        }
        return $ok;
    }
}

$service = new UserService(new MysqlDatabase(), new SmtpMailer(), new RedisCache());

$user = $service->find(1);     // DB miss → fetches → caches
$user = $service->find(1);     // Cache hit
echo "  User: {$user['name']}\n";

$service->register(['email' => 'bob@test.com', 'name' => 'Bob']);

// ═══════════════════════════════════════════════════════════════
// TYPE 2: SETTER INJECTION — Optional dependencies
// ═══════════════════════════════════════════════════════════════

echo "\n--- TYPE 2: Setter Injection ---\n";

class NotificationService
{
    private ?Mailer $mailer  = null;
    private ?Cache  $cache   = null;

    public function __construct(private Database $db) {}  // Required dep via constructor

    // Optional deps via setters
    public function setMailer(Mailer $mailer): void { $this->mailer = $mailer; }
    public function setCache(Cache $cache): void    { $this->cache  = $cache; }

    public function notify(int $userId, string $message): void
    {
        $user = $this->db->query("SELECT * FROM users WHERE id={$userId}")[0] ?? null;
        if (!$user) return;

        if ($this->mailer !== null) {
            $this->mailer->send($user['email'], 'Notification', $message);
        } else {
            echo "  [Notify] {$message} → (no mailer set, skipping email)\n";
        }

        if ($this->cache !== null) {
            $this->cache->set("notif:{$userId}", $message);
        }
    }
}

$notif = new NotificationService(new MysqlDatabase());
$notif->notify(1, "Your order shipped!");    // No mailer — graceful skip

$notif->setMailer(new SmtpMailer());
$notif->notify(1, "Delivery tomorrow!");     // Now sends email

// ═══════════════════════════════════════════════════════════════
// SIMPLE IoC CONTAINER — How Laravel's container works
// ═══════════════════════════════════════════════════════════════

echo "\n--- IoC Container (simplified Laravel-style) ---\n";

class Container
{
    private array $bindings  = [];
    private array $singletons = [];
    private array $instances  = [];

    // Bind interface → factory function
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    // Singleton binding — resolve once, return same instance
    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    // Resolve: build the concrete implementation
    public function make(string $abstract): mixed
    {
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            echo "  [Container] Returning singleton: {$abstract}\n";
            return $this->instances[$abstract];
        }

        // Build singleton
        if (isset($this->singletons[$abstract])) {
            $instance = ($this->singletons[$abstract])($this);
            $this->instances[$abstract] = $instance;
            echo "  [Container] Created singleton: {$abstract}\n";
            return $instance;
        }

        // Build fresh instance
        if (isset($this->bindings[$abstract])) {
            echo "  [Container] Resolving: {$abstract}\n";
            return ($this->bindings[$abstract])($this);
        }

        throw new \RuntimeException("No binding registered for: {$abstract}");
    }
}

// Register bindings — separate from business logic
$container = new Container();

$container->singleton(Database::class, fn() => new MysqlDatabase());
$container->singleton(Cache::class,    fn() => new RedisCache());
$container->bind(Mailer::class,        fn() => new SmtpMailer());  // new instance each time

$container->bind(UserService::class, fn(Container $c) => new UserService(
    $c->make(Database::class),
    $c->make(Mailer::class),
    $c->make(Cache::class)
));

// Resolve the service — container wires all dependencies automatically
$userSvc1 = $container->make(UserService::class);
$userSvc2 = $container->make(UserService::class);

$found = $userSvc1->find(1);
echo "  Resolved user: " . ($found['name'] ?? 'null') . "\n";

// Database is singleton — same instance
$db1 = $container->make(Database::class);
$db2 = $container->make(Database::class);
echo "  DB is singleton: " . ($db1 === $db2 ? 'YES' : 'NO') . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is Dependency Injection?                                │
 * │ A: A technique where a class receives its dependencies from     │
 * │    outside rather than creating them internally. Achieved via   │
 * │    constructor, setter, or interface injection. Makes code      │
 * │    testable (inject mocks), flexible (swap implementations),   │
 * │    and loosely coupled.                                         │
 * │                                                                  │
 * │ Q2: What is the difference between DI and IoC?                  │
 * │ A: IoC (Inversion of Control) is the PRINCIPLE — control of    │
 * │    object creation is moved from the class to an external       │
 * │    orchestrator. DI is the most common TECHNIQUE to implement   │
 * │    IoC. Laravel's Service Container is an IoC container.        │
 * │                                                                  │
 * │ Q3: What's the difference between bind() and singleton()?       │
 * │    (Laravel-specific, very frequently asked)                    │
 * │ A: bind(): creates a NEW instance every time make() is called. │
 * │    singleton(): creates ONE instance and returns the SAME one   │
 * │    on every subsequent call. Use singleton for stateless        │
 * │    services (DB connection), bind for stateful (request-scoped).│
 * │                                                                  │
 * │ Q4: Why is constructor injection preferred over setter?         │
 * │ A: Constructor injection guarantees the dependency is available │
 * │    when the object is created — no null checks needed. Setter   │
 * │    injection leaves the object in a potentially invalid state   │
 * │    if the setter isn't called. Use setter only for truly        │
 * │    optional dependencies.                                        │
 * │                                                                  │
 * │ Q5: How does Laravel's Service Container work?                  │
 * │ A: When you type-hint a class/interface in a controller or      │
 * │    service constructor, Laravel's container automatically       │
 * │    resolves and injects it. Bindings (interface→concrete) are  │
 * │    registered in Service Providers (AppServiceProvider).        │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ DI = receive deps, don't create them.                       │
 * │ ✓ Depend on INTERFACES — swap implementations freely.          │
 * │ ✓ Constructor injection = required deps; setter = optional.    │
 * │ ✓ IoC Container automates DI wiring at scale.                 │
 * └─────────────────────────────────────────────────────────────────┘
 */
