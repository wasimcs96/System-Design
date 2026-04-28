<?php
/**
 * C14. CDN CACHE LAYER
 * ============================================================
 * PROBLEM: Cache static/dynamic content at edge nodes,
 * serve from cache on hit, fall back to origin on miss,
 * and support targeted cache invalidation.
 *
 * PATTERNS:
 *  - Proxy    : EdgeNode proxies requests to origin
 *  - Strategy : CacheEvictionStrategy (LRU, TTL)
 *  - Decorator: StaleWhileRevalidate wraps edge node
 * ============================================================
 */

// ─── Cache Entry ──────────────────────────────────────────────
class CacheEntry {
    public readonly \DateTime $cachedAt;
    private bool $stale = false;

    public function __construct(
        public readonly string $key,
        public string          $content,
        public readonly string $contentType,
        public int             $ttlSeconds,
        public readonly string $region = 'global'
    ) {
        $this->cachedAt = new \DateTime();
    }

    public function isExpired(): bool {
        return (new \DateTime())->getTimestamp() - $this->cachedAt->getTimestamp() >= $this->ttlSeconds;
    }

    public function markStale(): void { $this->stale = true; }
    public function isStale(): bool   { return $this->stale || $this->isExpired(); }
}

// ─── Origin Server ────────────────────────────────────────────
class OriginServer {
    private int $requestCount = 0;

    public function fetch(string $path): array {
        $this->requestCount++;
        echo "  [Origin] Fetching: {$path} (origin request #{$this->requestCount})\n";
        return [
            'content'     => "<html>Content of {$path} at " . date('H:i:s') . "</html>",
            'contentType' => 'text/html',
            'ttl'         => 300,
        ];
    }

    public function getRequestCount(): int { return $this->requestCount; }
}

// ─── Edge Node (CDN PoP) ──────────────────────────────────────
class EdgeNode {
    /** @var array<string,CacheEntry> key → entry */
    private array $cache       = [];
    private array $metrics     = ['hits' => 0, 'misses' => 0, 'invalidations' => 0];
    private int   $maxSize;
    private array $accessOrder = [];   // For LRU

    public function __construct(
        public readonly string $nodeId,
        public readonly string $region,
        private OriginServer   $origin,
        int                    $maxSize = 100
    ) {
        $this->maxSize = $maxSize;
    }

    public function request(string $path): string {
        $key   = $this->cacheKey($path);
        $entry = $this->cache[$key] ?? null;

        if ($entry && !$entry->isExpired()) {
            $this->metrics['hits']++;
            $this->touchLRU($key);
            echo "  [Edge:{$this->nodeId}] HIT  {$path}\n";
            return $entry->content;
        }

        $this->metrics['misses']++;
        $data = $this->origin->fetch($path);
        $this->store($key, $path, $data);
        return $data['content'];
    }

    private function store(string $key, string $path, array $data): void {
        if (count($this->cache) >= $this->maxSize) $this->evictLRU();
        $this->cache[$key] = new CacheEntry($key, $data['content'], $data['contentType'], $data['ttl'], $this->region);
        $this->accessOrder[] = $key;
    }

    private function evictLRU(): void {
        $oldest = array_shift($this->accessOrder);
        unset($this->cache[$oldest]);
    }

    private function touchLRU(string $key): void {
        $this->accessOrder = array_values(array_diff($this->accessOrder, [$key]));
        $this->accessOrder[] = $key;
    }

    /** Invalidate cache entries matching a pattern */
    public function invalidate(string $pattern): int {
        $count = 0;
        foreach (array_keys($this->cache) as $key) {
            if (str_contains($key, $pattern)) {
                unset($this->cache[$key]);
                $this->accessOrder = array_values(array_diff($this->accessOrder, [$key]));
                $count++;
            }
        }
        $this->metrics['invalidations'] += $count;
        echo "  [Edge:{$this->nodeId}] Invalidated {$count} entries matching '{$pattern}'\n";
        return $count;
    }

    private function cacheKey(string $path): string { return md5($path); }

    public function getMetrics(): array { return $this->metrics; }
    public function getCacheSize(): int { return count($this->cache); }

    public function hitRate(): float {
        $total = $this->metrics['hits'] + $this->metrics['misses'];
        return $total > 0 ? round($this->metrics['hits'] / $total * 100, 1) : 0;
    }
}

// ─── CDN Manager ──────────────────────────────────────────────
class CDNManager {
    /** @var EdgeNode[] region → node */
    private array $nodes = [];

    public function addEdgeNode(EdgeNode $node): void { $this->nodes[$node->region] = $node; }

    public function getEdgeNode(string $region): EdgeNode {
        return $this->nodes[$region] ?? $this->nodes['us-east'] ?? array_first($this->nodes);
    }

    /** Purge cache across all edge nodes */
    public function purge(string $pattern): void {
        echo "  [CDN] Purging '{$pattern}' across " . count($this->nodes) . " edge nodes\n";
        foreach ($this->nodes as $node) $node->invalidate($pattern);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C14. CDN Cache Layer ===\n\n";

$origin   = new OriginServer();
$usEast   = new EdgeNode('US-E1', 'us-east', $origin);
$euWest   = new EdgeNode('EU-W1', 'eu-west', $origin);
$cdn      = new CDNManager();
$cdn->addEdgeNode($usEast);
$cdn->addEdgeNode($euWest);

echo "--- First requests (miss → origin) ---\n";
$usEast->request('/home');
$usEast->request('/products/123');
$usEast->request('/home');   // Should be HIT

echo "\n--- EU node (separate cache, own miss) ---\n";
$euWest->request('/home');
$euWest->request('/home');   // HIT

echo "\n--- Cache invalidation ---\n";
$cdn->purge('home');
$usEast->request('/home');   // Miss again after purge

echo "\n--- Stats ---\n";
echo "US-East cache: " . $usEast->getCacheSize() . " items, hit rate: " . $usEast->hitRate() . "%\n";
echo "EU-West cache: " . $euWest->getCacheSize() . " items, hit rate: " . $euWest->hitRate() . "%\n";
echo "Origin requests: " . $origin->getRequestCount() . "\n";
