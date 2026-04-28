<?php
/**
 * C20. CONSISTENT HASHING
 * ============================================================
 * PROBLEM: Distribute keys across nodes such that adding/removing
 * a node only reassigns O(K/N) keys instead of all keys.
 *
 * APPROACH:
 *  - Hash ring (sorted array of virtual nodes)
 *  - Each real node has VIRTUAL_REPLICAS virtual positions
 *  - Key → find next virtual node clockwise → real node
 *
 * TC: Add/remove node O(V log V), Lookup O(log V)
 *     V = total virtual nodes = N * REPLICAS
 * ============================================================
 */

class ConsistentHashRing {
    /** @var array<int,string> position → nodeId (sorted by position) */
    private array $ring      = [];
    /** @var array<int> sorted positions */
    private array $positions = [];
    /** @var array<string,string[]> nodeId → virtual positions */
    private array $nodeMap   = [];

    public function __construct(private int $virtualReplicas = 150) {}

    /** Add a node to the ring with virtual replicas */
    public function addNode(string $nodeId): void {
        for ($i = 0; $i < $this->virtualReplicas; $i++) {
            $hash = $this->hash("{$nodeId}:vn{$i}");
            $this->ring[$hash]   = $nodeId;
            $this->nodeMap[$nodeId][] = (string)$hash;
        }
        ksort($this->ring);
        $this->positions = array_keys($this->ring);
        echo "  [Ring] Added node '{$nodeId}' ({$this->virtualReplicas} virtual nodes)\n";
    }

    /** Remove a node and all its virtual replicas */
    public function removeNode(string $nodeId): void {
        if (!isset($this->nodeMap[$nodeId])) return;
        foreach ($this->nodeMap[$nodeId] as $hash) {
            unset($this->ring[(int)$hash]);
        }
        unset($this->nodeMap[$nodeId]);
        ksort($this->ring);
        $this->positions = array_keys($this->ring);
        echo "  [Ring] Removed node '{$nodeId}'\n";
    }

    /** Route a key to its responsible node */
    public function getNode(string $key): ?string {
        if (empty($this->ring)) return null;
        $hash = $this->hash($key);

        // Binary search: find first position >= hash
        $lo = 0; $hi = count($this->positions) - 1;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($this->positions[$mid] < $hash) $lo = $mid + 1;
            else $hi = $mid;
        }

        // Wrap around if hash > all positions
        if ($this->positions[$lo] < $hash) $lo = 0;

        return $this->ring[$this->positions[$lo]];
    }

    /** Get N distinct nodes for replication */
    public function getNodes(string $key, int $n): array {
        if (empty($this->ring)) return [];
        $nodes = [];
        $hash  = $this->hash($key);
        $total = count($this->positions);

        // Find start position
        $start = 0;
        foreach ($this->positions as $i => $pos) { if ($pos >= $hash) { $start = $i; break; } }

        for ($i = 0; $i < $total && count($nodes) < $n; $i++) {
            $idx  = ($start + $i) % $total;
            $node = $this->ring[$this->positions[$idx]];
            if (!in_array($node, $nodes)) $nodes[] = $node;
        }
        return $nodes;
    }

    /** Simulate key distribution across nodes */
    public function getDistribution(int $keyCount = 1000): array {
        $dist = [];
        foreach (array_keys($this->nodeMap) as $node) $dist[$node] = 0;
        for ($i = 0; $i < $keyCount; $i++) {
            $node = $this->getNode("key-{$i}");
            if ($node) $dist[$node]++;
        }
        return $dist;
    }

    private function hash(string $key): int {
        return (int)(hexdec(substr(md5($key), 0, 8)));
    }

    public function getNodeCount(): int { return count($this->nodeMap); }
    public function getRingSize(): int  { return count($this->ring); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C20. Consistent Hashing ===\n\n";

$ring = new ConsistentHashRing(100); // 100 virtual replicas per node

echo "--- Add 3 nodes ---\n";
$ring->addNode('cache-node-1');
$ring->addNode('cache-node-2');
$ring->addNode('cache-node-3');
echo "Ring size: " . $ring->getRingSize() . " virtual nodes\n";

echo "\n--- Key routing ---\n";
$keys = ['user:alice', 'product:123', 'session:xyz', 'order:999'];
foreach ($keys as $key) {
    $node = $ring->getNode($key);
    echo "  '{$key}' → {$node}\n";
}

echo "\n--- Replication (N=2) ---\n";
foreach (['user:alice', 'order:999'] as $key) {
    $nodes = $ring->getNodes($key, 2);
    echo "  '{$key}' → [" . implode(', ', $nodes) . "]\n";
}

echo "\n--- Distribution (1000 keys) ---\n";
$dist = $ring->getDistribution(1000);
foreach ($dist as $node => $count) {
    $bar = str_repeat('█', intdiv($count, 15));
    echo "  {$node}: {$count} keys {$bar}\n";
}

echo "\n--- Add node-4 (rebalancing) ---\n";
$before = $ring->getNode('user:alice');
$ring->addNode('cache-node-4');
$after  = $ring->getNode('user:alice');
echo "  user:alice: {$before} → {$after} (" . ($before !== $after ? 'moved' : 'same') . ")\n";

echo "\n--- Remove node-2 (rebalancing) ---\n";
$before2 = $ring->getNode('product:123');
$ring->removeNode('cache-node-2');
$after2  = $ring->getNode('product:123');
echo "  product:123: {$before2} → {$after2}\n";

$dist2 = $ring->getDistribution(1000);
echo "New distribution:\n";
foreach ($dist2 as $node => $count) {
    $bar = str_repeat('█', intdiv($count, 15));
    echo "  {$node}: {$count} keys {$bar}\n";
}
