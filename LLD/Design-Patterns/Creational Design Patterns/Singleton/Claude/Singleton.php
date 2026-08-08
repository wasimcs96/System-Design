<?php

/**
 * Singleton.php
 * ---------------------------------------------------------------------------
 * Companion runnable code file for Singleton-Design-Pattern-Guide.md.
 *
 * Progression:
 *   Tier 1 — Naive Singleton (correctness only, no concurrency story)
 *   Tier 2 — Guarded Singleton (blocks clone/wakeup/unserialize — Part 18/19's
 *            Stage 3 -> Stage 4 fix)
 *   Tier 3 — Swoole-aware ConfigManager (Part 9/15's concurrency discussion,
 *            eager boot-time init vs. lazy check-then-act)
 *   Tier 4 — Real-world DbConnectionPool (Part 12's production scenario)
 *   Tier 5 — Container-managed alternative (Part 11/17 — the modern,
 *            testable replacement most production PHP teams actually use)
 *
 * Run with: php Singleton.php
 * No framework dependency required — Tier 5's container is a tiny
 * hand-rolled stand-in for Laravel's real service container, included only
 * to make the contrast concrete and runnable without installing Laravel.
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

echo "=== Singleton Design Pattern — companion code ===\n\n";


/* =============================================================================
 * TIER 1 — Naive Singleton
 * =============================================================================
 * Correct for the single most basic requirement: "only one instance, ever,
 * within this process." No concurrency guard, no clone/wakeup guard yet.
 * This is Part 19's Stage 3 minus even the clone guard — intentionally
 * shown broken first so the fix in Tier 2 is concrete, not abstract.
 */
class NaiveSingleton
{
    private static ?NaiveSingleton $instance = null;
    private int $createdAtMicrotime;

    // Private constructor: nobody outside this class can call `new NaiveSingleton()`.
    private function __construct()
    {
        $this->createdAtMicrotime = (int) (microtime(true) * 1_000_000);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getCreatedAtMicrotime(): int
    {
        return $this->createdAtMicrotime;
    }
}


/* =============================================================================
 * TIER 2 — Guarded Singleton (the Part 18/19 fix)
 * =============================================================================
 * Adds the two guards AI-generated code and rushed human code most commonly
 * omit: __clone() and __wakeup()/__unserialize(). Without these, `clone` or
 * `unserialize()` can silently produce a second, independent instance —
 * defeating the entire point of the pattern.
 */
class GuardedSingleton
{
    private static ?GuardedSingleton $instance = null;
    private array $state = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->state[$key] ?? null;
    }

    // Block cloning: without this, `clone GuardedSingleton::getInstance()`
    // would produce a second, independent object with a copy of the state.
    public function __clone()
    {
        throw new \LogicException('Cloning a Singleton is not allowed.');
    }

    // Block waking up a serialized copy into a second live instance.
    public function __wakeup()
    {
        throw new \LogicException('Unserializing a Singleton is not allowed.');
    }

    // PHP 8.1+: __unserialize() takes precedence over __wakeup() if both
    // exist — guard both so this stays correct regardless of which magic
    // method a given serialization path triggers.
    public function __unserialize(array $data): void
    {
        throw new \LogicException('Unserializing a Singleton is not allowed.');
    }
}


/* =============================================================================
 * TIER 3 — Swoole-aware ConfigManager (Part 9 / Part 15's concurrency story)
 * =============================================================================
 * PHP-FPM: each request is an isolated process — the lazy check-then-act in
 * Tier 1/2 is already safe there, because no two requests ever execute PHP
 * concurrently inside the same process/memory space.
 *
 * Swoole / RoadRunner: a single worker process handles MANY requests/
 * coroutines over its lifetime, and coroutines can genuinely interleave.
 * The lazy "is it null? -> create it" check is now a real race: two
 * coroutines can both observe null before either finishes construction.
 *
 * PHP has no `volatile` keyword (unlike Java) and no built-in equivalent to
 * Go's `sync.Once`, so the two practical fixes are:
 *   (a) eager initialization at worker-boot time, before any
 *       request-handling coroutine starts (shown below, and the more common
 *       real-world choice), or
 *   (b) a coroutine-safe lock around the lazy check (sketched in a comment,
 *       since it requires the ext-swoole extension to actually run).
 */
final class SwooleAwareConfigManager
{
    private static ?SwooleAwareConfigManager $instance = null;
    private array $config;

    private function __construct(array $config)
    {
        // Simulate "expensive" config loading (file I/O + parsing).
        $this->config = $config;
    }

    // Call this exactly once, at worker-boot time, BEFORE the worker starts
    // accepting requests/coroutines — this sidesteps the lazy check-then-act
    // race entirely, because there is no concurrent access possible yet.
    public static function bootstrap(array $config): void
    {
        if (self::$instance !== null) {
            throw new \LogicException('ConfigManager already bootstrapped.');
        }
        self::$instance = new self($config);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            // In a real Swoole app, reaching here would mean bootstrap()
            // was never called — treat it as a startup-ordering bug, not
            // something to lazily paper over with a race-prone fallback.
            throw new \LogicException(
                'ConfigManager was not bootstrapped before first access. ' .
                'Call bootstrap() once at worker start.'
            );
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function __clone()
    {
        throw new \LogicException('Cloning a Singleton is not allowed.');
    }

    /*
     * Sketch of option (b) — a coroutine-safe LAZY guard, for cases where
     * eager bootstrap genuinely isn't possible (left as a comment because it
     * requires ext-swoole to run):
     *
     * private static ?\Swoole\Lock $lock = null;
     *
     * public static function getInstanceLazy(): self
     * {
     *     if (self::$instance !== null) {
     *         return self::$instance; // fast path, no locking once created
     *     }
     *     self::$lock ??= new \Swoole\Lock(SWOOLE_MUTEX);
     *     self::$lock->lock();
     *     try {
     *         if (self::$instance === null) { // re-check inside the lock
     *             self::$instance = new self(self::loadConfigFromDisk());
     *         }
     *     } finally {
     *         self::$lock->unlock();
     *     }
     *     return self::$instance;
     * }
     *
     * This is PHP's closest analogue to Java's double-checked locking — the
     * `Swoole\Lock` plays the role `synchronized` plays in Java, since PHP
     * has no `volatile` keyword or JMM-style visibility guarantee to lean on.
     */
}


/* =============================================================================
 * TIER 4 — Real-world example: DbConnectionPool (Part 12's production scenario)
 * =============================================================================
 * The pool OBJECT is the singleton (there should be exactly one coordinator);
 * the connections it hands out are pooled/reused, not cloned prototypes.
 */
final class FakeDbConnection
{
    public function __construct(public readonly int $connectionId)
    {
    }
}

final class DbConnectionPool
{
    private static ?DbConnectionPool $instance = null;

    /** @var FakeDbConnection[] */
    private array $available = [];

    /** @var FakeDbConnection[] */
    private array $inUse = [];

    private int $nextConnectionId = 1;
    private int $maxConnections;

    private function __construct(int $maxConnections)
    {
        $this->maxConnections = $maxConnections;
    }

    public static function getInstance(int $maxConnections = 5): self
    {
        if (self::$instance === null) {
            self::$instance = new self($maxConnections);
        }
        return self::$instance;
    }

    public function checkout(): FakeDbConnection
    {
        if ($connection = array_pop($this->available)) {
            $this->inUse[$connection->connectionId] = $connection;
            return $connection;
        }

        $totalConnections = count($this->inUse) + count($this->available);
        if ($totalConnections >= $this->maxConnections) {
            throw new \RuntimeException('Connection pool exhausted.');
        }

        $connection = new FakeDbConnection($this->nextConnectionId++);
        $this->inUse[$connection->connectionId] = $connection;
        return $connection;
    }

    public function release(FakeDbConnection $connection): void
    {
        unset($this->inUse[$connection->connectionId]);
        $this->available[] = $connection;
    }

    public function stats(): array
    {
        return [
            'in_use' => count($this->inUse),
            'available' => count($this->available),
            'max' => $this->maxConnections,
        ];
    }

    public function __clone()
    {
        throw new \LogicException('Cloning a Singleton is not allowed.');
    }
}


/* =============================================================================
 * TIER 5 — The modern alternative: a tiny hand-rolled DI container
 * =============================================================================
 * Mirrors Laravel's ->singleton() vs ->bind() distinction (Part 11), without
 * requiring Laravel to be installed. The registered class stays a PLAIN
 * class — no private constructor, no getInstance() call sites baked into
 * consumers — which is exactly why this form stays easily testable.
 */
final class TinyContainer
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $singletonInstances = [];

    /** @var array<string, bool> */
    private array $isSingleton = [];

    public function bind(string $key, callable $factory): void
    {
        $this->bindings[$key] = $factory;
        $this->isSingleton[$key] = false;
    }

    public function singleton(string $key, callable $factory): void
    {
        $this->bindings[$key] = $factory;
        $this->isSingleton[$key] = true;
    }

    public function make(string $key): object
    {
        if (!isset($this->bindings[$key])) {
            throw new \InvalidArgumentException("No binding registered for: {$key}");
        }

        if (($this->isSingleton[$key] ?? false) === true) {
            // First resolution builds and caches it; every later call
            // returns the same cached object — this is the behavior
            // Laravel's real singleton() binding provides.
            return $this->singletonInstances[$key] ??= ($this->bindings[$key])($this);
        }

        // bind(): a fresh instance every single resolution.
        return ($this->bindings[$key])($this);
    }
}

// A PLAIN class — no private constructor, no static accessor. It has no
// idea it's being treated as a singleton; the container decides that.
class PlainLogger
{
    private array $lines = [];

    public function log(string $message): void
    {
        $this->lines[] = $message;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }
}


/* =============================================================================
 * Driver code — demonstrates and asserts every tier above.
 * =============================================================================
 */

function assertTrue(bool $condition, string $message): void
{
    echo ($condition ? "[PASS] " : "[FAIL] ") . $message . "\n";
}

echo "--- Tier 1: Naive Singleton ---\n";
$n1 = NaiveSingleton::getInstance();
$n2 = NaiveSingleton::getInstance();
assertTrue($n1 === $n2, 'getInstance() returns the same object on repeated calls');
echo "\n";

echo "--- Tier 2: Guarded Singleton ---\n";
$g1 = GuardedSingleton::getInstance();
$g1->set('feature_flag.new_checkout', true);
$g2 = GuardedSingleton::getInstance();
assertTrue($g1 === $g2, 'getInstance() returns the same object');
assertTrue($g2->get('feature_flag.new_checkout') === true, 'state set via one reference is visible via the other');

try {
    $clone = clone $g1;
    assertTrue(false, 'clone should have thrown but did not');
} catch (\LogicException $e) {
    assertTrue(true, 'cloning throws as expected: ' . $e->getMessage());
}

try {
    $serialized = serialize($g1);
    unserialize($serialized);
    assertTrue(false, 'unserialize should have thrown but did not');
} catch (\LogicException $e) {
    assertTrue(true, 'unserializing throws as expected: ' . $e->getMessage());
}
echo "\n";

echo "--- Tier 3: Swoole-aware ConfigManager ---\n";
SwooleAwareConfigManager::bootstrap(['app_name' => 'PayFlow', 'max_retries' => 3]);
$cfg1 = SwooleAwareConfigManager::getInstance();
$cfg2 = SwooleAwareConfigManager::getInstance();
assertTrue($cfg1 === $cfg2, 'getInstance() returns the same bootstrapped object');
assertTrue($cfg1->get('app_name') === 'PayFlow', 'config value readable after eager bootstrap');

try {
    SwooleAwareConfigManager::bootstrap(['x' => 1]);
    assertTrue(false, 'double bootstrap should have thrown but did not');
} catch (\LogicException $e) {
    assertTrue(true, 'double bootstrap correctly rejected: ' . $e->getMessage());
}
echo "\n";

echo "--- Tier 4: DbConnectionPool ---\n";
$pool = DbConnectionPool::getInstance(maxConnections: 2);
$conn1 = $pool->checkout();
$conn2 = $pool->checkout();
assertTrue($pool->stats()['in_use'] === 2, 'both connections checked out are tracked as in-use');

try {
    $pool->checkout();
    assertTrue(false, 'exhausting the pool should have thrown but did not');
} catch (\RuntimeException $e) {
    assertTrue(true, 'pool exhaustion correctly rejected: ' . $e->getMessage());
}

$pool->release($conn1);
assertTrue($pool->stats()['available'] === 1, 'released connection returns to the available pool');
$conn3 = $pool->checkout();
assertTrue($conn3->connectionId === $conn1->connectionId, 'checkout reuses a released connection instead of minting a new one');
echo "\n";

echo "--- Tier 5: DI-container singleton binding (the modern alternative) ---\n";
$container = new TinyContainer();
$container->singleton(PlainLogger::class, fn () => new PlainLogger());
$container->bind('request_id', fn () => bin2hex(random_bytes(4)));

$loggerA = $container->make(PlainLogger::class);
$loggerB = $container->make(PlainLogger::class);
$loggerA->log('first line');
assertTrue($loggerA === $loggerB, 'singleton() binding returns the same PlainLogger instance');
assertTrue($loggerB->lineCount() === 1, 'state written via one resolved reference is visible via the other');

$id1 = $container->make('request_id');
$id2 = $container->make('request_id');
assertTrue($id1 !== $id2, 'bind() (non-singleton) produces a fresh value on every resolution');
echo "\n";

echo "=== All tiers demonstrated. ===\n";
