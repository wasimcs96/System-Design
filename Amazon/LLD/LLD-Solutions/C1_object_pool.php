<?php
/**
 * C1. OBJECT POOL
 * ============================================================
 * PROBLEM: Reuse expensive-to-create objects (DB connections,
 * thread handles) instead of creating/destroying each time.
 *
 * PATTERNS:
 *  - Object Pool : Pool manages lifecycle of reusable objects
 * ============================================================
 */

// ─── Poolable Resource ────────────────────────────────────────
interface Poolable {
    public function reset(): void;  // Clean up before returning to pool
    public function getId(): string;
}

class DatabaseConnection implements Poolable {
    private static int $counter = 0;
    private string     $id;
    private bool       $inUse   = false;

    public function __construct(private string $dsn) {
        $this->id = 'CONN-' . (++self::$counter);
        echo "  [Pool] Created connection {$this->id}\n";
    }

    public function query(string $sql): string {
        return "Result of '{$sql}' via {$this->id}";
    }

    public function reset(): void {
        $this->inUse = false;
        // rollback any pending transactions etc.
    }

    public function getId(): string { return $this->id; }
    public function markInUse(): void { $this->inUse = true; }
}

// ─── Object Pool ──────────────────────────────────────────────
class ConnectionPool {
    /** @var DatabaseConnection[] Available connections */
    private array $available = [];
    /** @var DatabaseConnection[] Checked-out connections */
    private array $inUse     = [];

    public function __construct(
        private string $dsn,
        private int    $minSize = 2,
        private int    $maxSize = 5
    ) {
        // Pre-warm pool with minimum connections
        for ($i = 0; $i < $this->minSize; $i++) {
            $this->available[] = new DatabaseConnection($this->dsn);
        }
    }

    /**
     * Acquire a connection from the pool.
     * If pool empty and under max, create new connection.
     * If at max, throw exception (or block in real implementation).
     */
    public function acquire(): DatabaseConnection {
        if (!empty($this->available)) {
            $conn = array_pop($this->available);
            $conn->markInUse();
            $this->inUse[$conn->getId()] = $conn;
            echo "  [Pool] Acquired {$conn->getId()} (available=" . count($this->available) . ")\n";
            return $conn;
        }

        if (count($this->inUse) < $this->maxSize) {
            $conn = new DatabaseConnection($this->dsn);
            $conn->markInUse();
            $this->inUse[$conn->getId()] = $conn;
            echo "  [Pool] Created new {$conn->getId()} on demand\n";
            return $conn;
        }

        throw new \RuntimeException("Connection pool exhausted (max={$this->maxSize})");
    }

    /**
     * Return connection back to pool for reuse.
     */
    public function release(DatabaseConnection $conn): void {
        $conn->reset();
        unset($this->inUse[$conn->getId()]);
        $this->available[] = $conn;
        echo "  [Pool] Released {$conn->getId()} (available=" . count($this->available) . ")\n";
    }

    public function stats(): void {
        echo "  Pool stats: available=" . count($this->available) . " in-use=" . count($this->inUse) . "\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C1. Object Pool (DB Connection Pool) ===\n\n";

$pool = new ConnectionPool('mysql://localhost/mydb', 2, 4);
$pool->stats();

echo "\n--- Acquire connections ---\n";
$c1 = $pool->acquire();
$c2 = $pool->acquire();
$c3 = $pool->acquire(); // Creates new since both pre-warmed are checked out
$pool->stats();

echo "\n--- Execute queries ---\n";
echo $c1->query("SELECT * FROM users") . "\n";
echo $c2->query("SELECT * FROM orders") . "\n";

echo "\n--- Release ---\n";
$pool->release($c1);
$pool->release($c2);
$pool->stats();

echo "\n--- Acquire after release (should reuse) ---\n";
$c4 = $pool->acquire();
$pool->stats();
$pool->release($c3);
$pool->release($c4);
