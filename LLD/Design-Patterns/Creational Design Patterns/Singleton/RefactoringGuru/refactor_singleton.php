<?php

/**
 * refactor_singleton.php
 * ---------------------------------------------------------------------------
 * Standalone runnable file collecting every PHP code example from
 * Singleton-RefactoringGuru-Bilingual-Study.md, in the same order they
 * appear in that document. All code is original (see the sourcing note at
 * the top of that document) — this file just makes the examples runnable
 * independent of the markdown/PDF.
 *
 * Sections:
 *   1. Conceptual Example — Basic Singleton (DatabaseConnection)
 *   2. Real-World-Shaped Example — Guarded, Concurrency-Aware Singleton (ConfigRegistry)
 *
 * Run with: php refactor_singleton.php
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

echo "=== Section 1: Conceptual Example — Basic Singleton ===\n\n";

class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private string $connectionLabel;

    // Private constructor: nothing outside this class can call `new DatabaseConnection()`.
    private function __construct()
    {
        // Simulate an expensive connection setup.
        $this->connectionLabel = "conn-" . bin2hex(random_bytes(3));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function runQuery(string $sql): string
    {
        return "[{$this->connectionLabel}] running: {$sql}";
    }

    public function __clone()
    {
        throw new \LogicException("Cloning this Singleton is not allowed.");
    }
}

// Client code
$a = DatabaseConnection::getInstance();
$b = DatabaseConnection::getInstance();

echo $a->runQuery("SELECT * FROM users") . "\n";
echo ($a === $b ? "Same instance — as expected.\n" : "Different instances — this is a bug!\n");
echo "\n";


echo "=== Section 2: Real-World-Shaped Example — Guarded, Concurrency-Aware Singleton ===\n\n";

final class ConfigRegistry
{
    private static ?ConfigRegistry $instance = null;
    private array $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    // Under classic PHP-FPM, each request is an isolated process, so this
    // lazy check is already safe — there's no concurrent access within one
    // request's execution. Under a long-running Swoole/RoadRunner worker,
    // this same check-then-act sequence becomes a genuine race between
    // coroutines; the safer choice there is to call a dedicated bootstrap
    // method once at worker-boot time, before any request-handling
    // coroutine starts.
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(['app_env' => 'production']);
        }
        return self::$instance;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function __clone()
    {
        throw new \LogicException("Cloning this Singleton is not allowed.");
    }

    public function __wakeup()
    {
        throw new \LogicException("Unserializing this Singleton is not allowed.");
    }
}

// Client code
$config = ConfigRegistry::getInstance();
echo "app_env = " . $config->get('app_env') . "\n";

try {
    $copy = clone $config;
} catch (\LogicException $e) {
    echo "Blocked as expected: " . $e->getMessage() . "\n";
}
