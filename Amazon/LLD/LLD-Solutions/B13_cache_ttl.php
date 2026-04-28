<?php
/**
 * B13. CACHE WITH TTL (Time-To-Live)
 * ============================================================
 * PROBLEM: Key-value cache with expiry, namespace isolation,
 * and eviction policies.
 *
 * PATTERNS:
 *  - Strategy : EvictionPolicy (LRU, FIFO, TTL-only)
 *  - Decorator: NamespacedCache wraps Cache to prefix keys
 * ============================================================
 */

// ─── Cache Entry ──────────────────────────────────────────────
class CacheEntry {
    public readonly float $createdAt;
    public readonly float $expiresAt;

    public function __construct(
        public mixed $value,
        public float $ttl     // seconds; 0 = no expiry
    ) {
        $this->createdAt = microtime(true);
        $this->expiresAt = $ttl > 0 ? $this->createdAt + $ttl : PHP_FLOAT_MAX;
    }

    public function isExpired(): bool { return microtime(true) > $this->expiresAt; }
}

// ─── Cache Interface ──────────────────────────────────────────
interface CacheInterface {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, float $ttl = 0): void;
    public function delete(string $key): void;
    public function has(string $key): bool;
    public function clear(): void;
}

// ─── In-Memory Cache ──────────────────────────────────────────
class InMemoryCache implements CacheInterface {
    /** @var array<string,CacheEntry> */
    private array $store  = [];
    private int   $hits   = 0;
    private int   $misses = 0;

    public function __construct(private int $maxSize = 100) {}

    public function get(string $key): mixed {
        if (!isset($this->store[$key])) { $this->misses++; return null; }
        $entry = $this->store[$key];
        if ($entry->isExpired()) {
            unset($this->store[$key]);
            $this->misses++;
            return null;
        }
        $this->hits++;
        return $entry->value;
    }

    public function set(string $key, mixed $value, float $ttl = 0): void {
        if (count($this->store) >= $this->maxSize && !isset($this->store[$key])) {
            // Simple FIFO eviction when full
            reset($this->store);
            $firstKey = key($this->store);
            unset($this->store[$firstKey]);
        }
        $this->store[$key] = new CacheEntry($value, $ttl);
    }

    public function delete(string $key): void { unset($this->store[$key]); }

    public function has(string $key): bool {
        if (!isset($this->store[$key])) return false;
        if ($this->store[$key]->isExpired()) { unset($this->store[$key]); return false; }
        return true;
    }

    public function clear(): void { $this->store = []; }

    // Evict all expired entries (passive cleanup)
    public function evictExpired(): int {
        $before = count($this->store);
        $this->store = array_filter($this->store, fn($e) => !$e->isExpired());
        return $before - count($this->store);
    }

    public function stats(): void {
        $total = $this->hits + $this->misses;
        $ratio = $total > 0 ? round($this->hits / $total * 100, 1) : 0;
        echo "  Cache: size=" . count($this->store) . " hits={$this->hits} misses={$this->misses} ratio={$ratio}%\n";
    }
}

// ─── Namespaced Cache (Decorator) ─────────────────────────────
class NamespacedCache implements CacheInterface {
    public function __construct(
        private CacheInterface $inner,
        private string         $namespace
    ) {}

    private function prefixKey(string $key): string { return "{$this->namespace}:{$key}"; }

    public function get(string $key): mixed    { return $this->inner->get($this->prefixKey($key)); }
    public function set(string $key, mixed $v, float $ttl = 0): void { $this->inner->set($this->prefixKey($key), $v, $ttl); }
    public function delete(string $key): void  { $this->inner->delete($this->prefixKey($key)); }
    public function has(string $key): bool     { return $this->inner->has($this->prefixKey($key)); }
    public function clear(): void              { $this->inner->clear(); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B13. Cache with TTL ===\n\n";

$cache = new InMemoryCache(5);

$cache->set('user:1', ['name' => 'Alice', 'email' => 'alice@x.com'], 3600);
$cache->set('session:abc', 'session_data', 1800);
$cache->set('config:theme', 'dark');  // No TTL

echo "user:1  = " . json_encode($cache->get('user:1')) . "\n";
echo "missing = " . var_export($cache->get('nonexistent'), true) . "\n";
$cache->stats();

// Namespaced caches
$userCache    = new NamespacedCache($cache, 'users');
$productCache = new NamespacedCache($cache, 'products');

$userCache->set('profile:100', ['name' => 'Bob'], 60);
$productCache->set('item:42', ['price' => 999], 120);

echo "\nNamespaced user:profile:100 = " . json_encode($userCache->get('profile:100')) . "\n";
echo "Namespaced prod:item:42     = " . json_encode($productCache->get('item:42')) . "\n";
$cache->stats();
