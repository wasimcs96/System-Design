<?php
/**
 * B19. KEY-VALUE STORE (like Redis simplified)
 * ============================================================
 * PROBLEM: In-memory KV store with TTL, transactions, and
 * basic data types (string, list, hash).
 *
 * PATTERNS:
 *  - Command : Transaction commands (BEGIN/COMMIT/ROLLBACK)
 * ============================================================
 */

// ─── Key-Value Store ──────────────────────────────────────────
class KeyValueStore {
    /** @var array<string,array{value:mixed,expiresAt:float}> */
    private array $store = [];
    /** @var array<string,array{value:mixed,expiresAt:float}>|null snapshot for transaction */
    private ?array $txSnapshot = null;
    private bool   $inTx       = false;

    // ─── String operations ────────────────────────────────────
    public function set(string $key, mixed $value, float $ttl = 0): void {
        $data = $this->store;
        $data[$key] = [
            'value'     => $value,
            'expiresAt' => $ttl > 0 ? microtime(true) + $ttl : PHP_FLOAT_MAX,
        ];
        $this->store = $data;
    }

    public function get(string $key): mixed {
        if (!isset($this->store[$key])) return null;
        if (microtime(true) > $this->store[$key]['expiresAt']) {
            unset($this->store[$key]); return null;
        }
        return $this->store[$key]['value'];
    }

    public function delete(string $key): bool {
        if (!isset($this->store[$key])) return false;
        unset($this->store[$key]); return true;
    }

    public function exists(string $key): bool { return $this->get($key) !== null; }

    public function expire(string $key, float $ttl): bool {
        if (!$this->exists($key)) return false;
        $this->store[$key]['expiresAt'] = microtime(true) + $ttl;
        return true;
    }

    // ─── Increment (for counters) ─────────────────────────────
    public function incr(string $key, int $by = 1): int {
        $val = (int)($this->get($key) ?? 0) + $by;
        $this->set($key, $val);
        return $val;
    }

    // ─── List operations ──────────────────────────────────────
    public function lpush(string $key, mixed ...$values): int {
        $list = $this->get($key) ?? [];
        foreach (array_reverse($values) as $v) array_unshift($list, $v);
        $this->set($key, $list);
        return count($list);
    }

    public function lrange(string $key, int $start, int $end): array {
        $list = $this->get($key) ?? [];
        return array_slice($list, $start, $end === -1 ? null : $end - $start + 1);
    }

    // ─── Hash operations ──────────────────────────────────────
    public function hset(string $key, string $field, mixed $value): void {
        $hash = $this->get($key) ?? [];
        $hash[$field] = $value;
        $this->set($key, $hash);
    }

    public function hget(string $key, string $field): mixed {
        return ($this->get($key) ?? [])[$field] ?? null;
    }

    public function hgetall(string $key): array { return $this->get($key) ?? []; }

    // ─── Transactions ─────────────────────────────────────────
    public function begin(): void {
        $this->txSnapshot = $this->store;
        $this->inTx       = true;
        echo "  [TX] Transaction started\n";
    }

    public function commit(): void {
        $this->txSnapshot = null;
        $this->inTx       = false;
        echo "  [TX] Committed\n";
    }

    public function rollback(): void {
        if ($this->txSnapshot !== null) $this->store = $this->txSnapshot;
        $this->txSnapshot = null;
        $this->inTx       = false;
        echo "  [TX] Rolled back\n";
    }

    public function keys(string $pattern = '*'): array {
        $keys = array_keys(array_filter($this->store, fn($v) => microtime(true) <= $v['expiresAt']));
        if ($pattern === '*') return $keys;
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';
        return array_values(array_filter($keys, fn($k) => preg_match($regex, $k)));
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B19. Key-Value Store ===\n\n";

$kv = new KeyValueStore();

echo "--- String operations ---\n";
$kv->set('name', 'Alice');
$kv->set('counter', 0);
echo "name = " . $kv->get('name') . "\n";
echo "incr = " . $kv->incr('counter', 5) . "\n";
echo "incr = " . $kv->incr('counter') . "\n";

echo "\n--- List operations ---\n";
$kv->lpush('queue', 'task1', 'task2', 'task3');
$tasks = $kv->lrange('queue', 0, -1);
echo "queue = " . implode(', ', $tasks) . "\n";

echo "\n--- Hash operations ---\n";
$kv->hset('user:1', 'name', 'Bob');
$kv->hset('user:1', 'age', 30);
print_r($kv->hgetall('user:1'));

echo "--- Transaction: commit ---\n";
$kv->begin();
$kv->set('tx_key', 'value_inside_tx');
echo "  inside tx: " . $kv->get('tx_key') . "\n";
$kv->commit();
echo "  after commit: " . $kv->get('tx_key') . "\n";

echo "\n--- Transaction: rollback ---\n";
$kv->begin();
$kv->set('name', 'Temporary');
echo "  before rollback: " . $kv->get('name') . "\n";
$kv->rollback();
echo "  after rollback: " . $kv->get('name') . "\n";
