<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #1 — SINGLETON                      ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Creational Pattern                                 ║
 * ║  DIFFICULTY : Easy                                               ║
 * ║  FREQUENCY  : ★★★★★ (Asked in almost every PHP interview)       ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You need to ensure that a class has ONLY ONE instance   │
 * │ throughout the entire application lifecycle, and provide a       │
 * │ global access point to that instance.                            │
 * │                                                                  │
 * │ Example:                                                         │
 * │  - A Logger should write to ONE log file (not create 100 handles)│
 * │  - A DB Connection Pool should have ONE instance managing conns  │
 * │  - App Config should be loaded ONCE, shared everywhere          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ WHEN TO USE                                                      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ ✓ Shared resource (DB connection, config, logger, cache)        │
 * │ ✓ Exactly one object must coordinate actions across the system  │
 * │ ✗ Don't use if the object needs different states in different    │
 * │   parts of the app (use Dependency Injection instead)            │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client1 ──┐                                                     │
 * │             ├──► Singleton::getInstance() ──► [Single Instance] │
 * │  Client2 ──┘                                                     │
 * │                                                                  │
 * │  ┌────────────────────────────────┐                              │
 * │  │ Singleton                      │                              │
 * │  ├────────────────────────────────┤                              │
 * │  │ - instance: Singleton = null   │ ← static, private           │
 * │  ├────────────────────────────────┤                              │
 * │  │ - __construct()                │ ← private (block new)        │
 * │  │ - __clone()                    │ ← private (block clone)      │
 * │  │ - __wakeup()                   │ ← private (block unserialize)│
 * │  │ + getInstance(): Singleton     │ ← static, public            │
 * │  └────────────────────────────────┘                              │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE SINGLETON                            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Make constructor PRIVATE → prevent `new Singleton()`    │
 * │ STEP 2: Add static property `$instance = null`                  │
 * │ STEP 3: Add static `getInstance()` that lazy-creates instance   │
 * │ STEP 4: Block clone() and wakeup() to prevent bypass            │
 * │ STEP 5: (Optional) Support subclasses via `static::class`       │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DRY RUN                                                          │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  $a = Singleton::getInstance()   → creates instance, stores it  │
 * │  $b = Singleton::getInstance()   → returns SAME stored instance  │
 * │  $a === $b                       → true (same object)            │
 * │  $c = new Singleton()            → Fatal Error: private ctor     │
 * │  $d = clone $a                   → Fatal Error: private clone    │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ─── IMPLEMENTATION ───────────────────────────────────────────────────────────

class Singleton
{
    /**
     * STEP 2: Static map of subclass → instance
     * Using array allows subclasses (like Logger, DBConnection) to each
     * have their own singleton instance (not share the parent's slot).
     *
     * Why array? Because static::class gives us the calling class name,
     * allowing Logger::getInstance() and Config::getInstance() to return
     * different objects, both still singletons in their own class.
     */
    private static array $instances = [];

    /**
     * STEP 1: Private constructor — no one can call `new Singleton()`
     * The constructor can still do initialization work (e.g., open log file).
     */
    private function __construct() {}

    /**
     * STEP 4a: Block cloning — prevents: $b = clone $singletonObj;
     */
    private function __clone() {}

    /**
     * STEP 4b: Block unserialization — prevents bypassing via:
     *   $s = unserialize(serialize($singleton));
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a Singleton.");
    }

    /**
     * STEP 3: The one public entry point.
     * `static::class` = Late Static Binding → returns the actual subclass name
     * so that Logger::getInstance() creates a Logger, not a raw Singleton.
     */
    public static function getInstance(): static
    {
        $subclass = static::class; // e.g., "Logger" or "DBConnection"

        if (!isset(self::$instances[$subclass])) {
            // First call → create and cache
            self::$instances[$subclass] = new static();
        }

        return self::$instances[$subclass]; // Every subsequent call → same object
    }
}

// ─── REAL-WORLD EXAMPLE 1: Logger ─────────────────────────────────────────────

/**
 * Logger Singleton:
 * - Only ONE file handle opened throughout app lifecycle
 * - All classes call Logger::getInstance()->log("...")
 */
class Logger extends Singleton
{
    private string $logFile;
    private int    $logCount = 0;

    // Private constructor — initialization happens once
    private function __construct()
    {
        $this->logFile = '/var/log/app.log';
        // In production: $this->handle = fopen($this->logFile, 'a');
    }

    public function log(string $level, string $message): void
    {
        $this->logCount++;
        $timestamp = date('Y-m-d H:i:s');
        // In production: fwrite($this->handle, "[$timestamp][$level] $message\n");
        echo "[$timestamp][$level] $message\n";
    }

    public function getLogCount(): int
    {
        return $this->logCount;
    }
}

// ─── REAL-WORLD EXAMPLE 2: Database Connection ────────────────────────────────

/**
 * DBConnection Singleton:
 * - Expensive to create (TCP handshake, auth)
 * - Must be shared across all repositories
 */
class DBConnection extends Singleton
{
    private \PDO $pdo;

    private function __construct()
    {
        // In production: $this->pdo = new \PDO('mysql:host=...', $user, $pass);
        echo "  [DB] Connection established (expensive op)\n";
    }

    public function query(string $sql): string
    {
        // In production: return $this->pdo->query($sql);
        return "Result of: $sql";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== SINGLETON PATTERN DEMO ===\n\n";

echo "--- Test 1: Same instance returned ---\n";
$logger1 = Logger::getInstance();
$logger2 = Logger::getInstance();
echo "Same instance? " . ($logger1 === $logger2 ? "YES ✓" : "NO ✗") . "\n\n";

echo "--- Test 2: Logger works correctly ---\n";
Logger::getInstance()->log('INFO',  'Application started');
Logger::getInstance()->log('ERROR', 'Something went wrong');
Logger::getInstance()->log('DEBUG', 'User logged in');
echo "Total logs: " . Logger::getInstance()->getLogCount() . "\n\n";

echo "--- Test 3: Separate singleton per subclass ---\n";
$db1 = DBConnection::getInstance();
$db2 = DBConnection::getInstance(); // Should NOT create a new connection
echo "DB Same instance? " . ($db1 === $db2 ? "YES ✓" : "NO ✗") . "\n\n";

echo "--- Test 4: Logger and DBConnection are different singletons ---\n";
// They DON'T share the same instance — each class has its own
echo "Logger === DBConnection? " . ($logger1 === $db1 ? "YES" : "NO ✓ (correct)") . "\n\n";

echo "--- Test 5: Cannot clone ---\n";
try {
    // This will throw because __clone() is private
    $clone = clone Logger::getInstance();
} catch (\Error $e) {
    echo "Clone blocked: " . $e->getMessage() . " ✓\n";
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Singleton pattern?                               │
 * │ A: Ensures a class has only ONE instance and provides a global  │
 * │    access point. The constructor is private, and a static method │
 * │    returns the same instance every time.                         │
 * │                                                                  │
 * │ Q2: Why make __clone() private?                                  │
 * │ A: $copy = clone $singleton would bypass the single-instance    │
 * │    guarantee by creating a second object without calling         │
 * │    getInstance(). Making it private blocks this.                 │
 * │                                                                  │
 * │ Q3: Why handle __wakeup()?                                       │
 * │ A: PHP's unserialize() reconstructs objects by calling           │
 * │    __wakeup(). Without protection, someone could serialize the   │
 * │    singleton and unserialize it to get a fresh instance.         │
 * │                                                                  │
 * │ Q4: How do you make Singleton thread-safe?                       │
 * │ A: PHP is single-threaded per request (no shared memory between  │
 * │    requests), so standard Singleton is safe. In multi-threaded  │
 * │    PHP (via Swoole/ReactPHP), use a mutex/lock around creation.  │
 * │                                                                  │
 * │ Q5: What is Late Static Binding (static::class)?                 │
 * │ A: `self::class` always returns the class where the method is   │
 * │    defined (Singleton). `static::class` returns the ACTUAL      │
 * │    calling class (Logger, DBConnection). Needed for subclassing. │
 * │                                                                  │
 * │ Q6: Is Singleton an anti-pattern?                                │
 * │ A: It can be! Problems: (1) Hidden global state makes testing   │
 * │    hard, (2) violates Single Responsibility Principle, (3) tight │
 * │    coupling. Better alternative: Dependency Injection Container. │
 * │    Use Singleton sparingly — only for truly shared resources.    │
 * │                                                                  │
 * │ Q7: Difference between Singleton and static class?              │
 * │ A: Static class = just functions, no instance, no polymorphism. │
 * │    Singleton = real object, supports interfaces, inheritance,   │
 * │    lazy initialization, and can be passed as dependency.        │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Subclasses each get their own singleton (via static::class)   │
 * │ ✓ Serialization bypass blocked via __wakeup()                   │
 * │ ✓ Clone bypass blocked via private __clone()                    │
 * │ ✓ Reflection bypass: can still occur — document as "don't do"  │
 * └─────────────────────────────────────────────────────────────────┘
 */
