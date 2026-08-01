<?php

/**
 * algomaster_singleton.php
 * ---------------------------------------------------------------------------
 * Standalone runnable file collecting every PHP code example from
 * Singleton-AlgoMaster-Bilingual-Study.md, in the same order they appear in
 * that document. All code is original (AlgoMaster's own code sits behind a
 * subscription — see the sourcing note at the top of that document) — this
 * file just makes the examples runnable independent of the markdown/PDF.
 *
 * Sections:
 *   4.1 Lazy Initialization (Not Thread-Safe)
 *   4.2 Thread-Safe (Locked) Singleton
 *   4.3 Double-Checked Locking
 *   4.4 Eager Initialization
 *   5.  Practical Example: In-Memory Cache Manager
 *
 * Note: sections 4.2 and 4.3 use \Swoole\Lock and will only actually RUN on
 * a PHP install with the ext-swoole extension loaded. They still parse and
 * type-check correctly without it; sections 4.1, 4.4, and 5 have no such
 * dependency and run anywhere.
 *
 * Run with: php algomaster_singleton.php
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

echo "=== Section 4.1: Lazy Initialization (Not Thread-Safe) ===\n\n";

class LazySingleton
{
    private static ?LazySingleton $instance = null;

    private function __construct()
    {
    }

    // NOT safe under a shared, concurrent process (e.g. Swoole) — two
    // callers can both pass this null-check before either finishes
    // assigning self::$instance.
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

$lazyA = LazySingleton::getInstance();
$lazyB = LazySingleton::getInstance();
echo ($lazyA === $lazyB ? "Same instance — as expected.\n" : "BUG: different instances.\n");
echo "\n";


echo "=== Section 4.2: Thread-Safe (Locked) Singleton ===\n";
echo "(Requires ext-swoole to actually execute the locking path.)\n\n";

final class LockedSingleton
{
    private static ?LockedSingleton $instance = null;
    private static ?\Swoole\Lock $lock = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        self::$lock ??= new \Swoole\Lock(SWOOLE_MUTEX);
        self::$lock->lock(); // every call pays this cost, even call #10,000
        try {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        } finally {
            self::$lock->unlock();
        }
    }
}

if (extension_loaded('swoole')) {
    $lockedA = LockedSingleton::getInstance();
    $lockedB = LockedSingleton::getInstance();
    echo ($lockedA === $lockedB ? "Same instance — as expected.\n" : "BUG: different instances.\n");
} else {
    echo "[skipped: ext-swoole not loaded in this environment]\n";
}
echo "\n";


echo "=== Section 4.3: Double-Checked Locking ===\n";
echo "(Requires ext-swoole to actually execute the locking path.)\n\n";

final class DoubleCheckedSingleton
{
    private static ?DoubleCheckedSingleton $instance = null;
    private static ?\Swoole\Lock $lock = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance !== null) {
            return self::$instance; // fast path — no locking after warm-up
        }

        self::$lock ??= new \Swoole\Lock(SWOOLE_MUTEX);
        self::$lock->lock();
        try {
            if (self::$instance === null) { // re-check: another caller may have won the race
                self::$instance = new self();
            }
        } finally {
            self::$lock->unlock();
        }
        return self::$instance;
    }
}

if (extension_loaded('swoole')) {
    $dclA = DoubleCheckedSingleton::getInstance();
    $dclB = DoubleCheckedSingleton::getInstance();
    echo ($dclA === $dclB ? "Same instance — as expected.\n" : "BUG: different instances.\n");
} else {
    echo "[skipped: ext-swoole not loaded in this environment]\n";
}
echo "\n";


echo "=== Section 4.4: Eager Initialization ===\n\n";

final class EagerSingleton
{
    // Created the moment this file/class is loaded — no lazy check needed,
    // and therefore no concurrency race is even possible.
    private static EagerSingleton $instance;

    private function __construct()
    {
    }

    public static function boot(): void
    {
        self::$instance = new self();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }
}

EagerSingleton::boot(); // called once, at application/worker startup
$eagerA = EagerSingleton::getInstance();
$eagerB = EagerSingleton::getInstance();
echo ($eagerA === $eagerB ? "Same instance — as expected.\n" : "BUG: different instances.\n");
echo "\n";


echo "=== Section 5: Practical Example — In-Memory Cache Manager ===\n\n";

final class CacheManager
{
    private static ?CacheManager $instance = null;

    /** @var array<string, array{value: mixed, expiresAt: int}> */
    private array $store = [];

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

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->store[$key] = [
            'value' => $value,
            'expiresAt' => time() + $ttlSeconds,
        ];
    }

    public function get(string $key): mixed
    {
        if (!isset($this->store[$key])) {
            return null;
        }

        // Lazy TTL cleanup: expired entries are only actually removed the
        // next time someone reads that specific key, rather than via a
        // background sweep — a common, low-overhead real-world choice.
        if ($this->store[$key]['expiresAt'] < time()) {
            unset($this->store[$key]);
            return null;
        }

        return $this->store[$key]['value'];
    }

    public function __clone()
    {
        throw new \LogicException('Cloning this Singleton is not allowed.');
    }
}

// Two unrelated "components" sharing the same cache:
function httpHandlerReadsProfile(string $userId): void
{
    $cache = CacheManager::getInstance();
    $profile = $cache->get("profile:{$userId}");
    if ($profile === null) {
        $profile = ['id' => $userId, 'name' => 'Fetched From DB'];
        $cache->put("profile:{$userId}", $profile, ttlSeconds: 60);
    }
    echo "HTTP handler sees: " . json_encode($profile) . "\n";
}

function backgroundJobUpdatesProfile(string $userId): void
{
    $cache = CacheManager::getInstance();
    $cache->put("profile:{$userId}", ['id' => $userId, 'name' => 'Updated By Background Job'], ttlSeconds: 60);
}

httpHandlerReadsProfile('u1');
backgroundJobUpdatesProfile('u1');
httpHandlerReadsProfile('u1'); // sees the background job's update immediately
