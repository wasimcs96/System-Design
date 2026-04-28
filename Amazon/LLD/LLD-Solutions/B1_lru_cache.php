<?php
/**
 * B1. LRU CACHE (LeetCode 146)
 * ============================================================
 * PROBLEM: Design a data structure that follows LRU eviction policy.
 *   get(key)       – O(1), returns value or -1
 *   put(key, val)  – O(1), inserts; evicts least-recently-used if over capacity
 *
 * PATTERNS:
 *  - Decorator : LRUCache wraps a doubly-linked list + hashmap
 *  - Strategy  : Eviction policy (extensible to LFU)
 *
 * DATA STRUCTURE: HashMap<key → DLL Node> + Doubly Linked List
 *   HEAD ↔ [most recent] ↔ ... ↔ [least recent] ↔ TAIL
 *   On access/insert: move node to front
 *   On eviction: remove node from tail end
 * ============================================================
 */

// ─── DLL Node ──────────────────────────────────────────────────
class LRUNode {
    public ?LRUNode $prev = null;
    public ?LRUNode $next = null;
    public function __construct(public int $key, public int $value) {}
}

// ─── LRU Cache ─────────────────────────────────────────────────
class LRUCache {
    /** @var array<int,LRUNode> */
    private array   $map  = [];
    private LRUNode $head;   // Dummy head (most recent side)
    private LRUNode $tail;   // Dummy tail (least recent side)
    private int     $count = 0;

    public function __construct(private int $capacity) {
        // Sentinel nodes eliminate null checks at boundaries
        $this->head       = new LRUNode(-1, -1);
        $this->tail       = new LRUNode(-1, -1);
        $this->head->next = $this->tail;
        $this->tail->prev = $this->head;
    }

    /** Get value by key, mark as recently used. O(1) */
    public function get(int $key): int {
        if (!isset($this->map[$key])) return -1;
        $node = $this->map[$key];
        $this->moveToFront($node);  // Mark recently used
        return $node->value;
    }

    /** Insert or update. Evict LRU if over capacity. O(1) */
    public function put(int $key, int $value): void {
        if (isset($this->map[$key])) {
            // Update existing
            $this->map[$key]->value = $value;
            $this->moveToFront($this->map[$key]);
            return;
        }
        if ($this->count === $this->capacity) {
            // Evict least recently used (node before tail)
            $lru = $this->tail->prev;
            $this->removeNode($lru);
            unset($this->map[$lru->key]);
            $this->count--;
        }
        $node = new LRUNode($key, $value);
        $this->addToFront($node);
        $this->map[$key] = $node;
        $this->count++;
    }

    private function removeNode(LRUNode $node): void {
        $node->prev->next = $node->next;
        $node->next->prev = $node->prev;
    }

    private function addToFront(LRUNode $node): void {
        $node->next            = $this->head->next;
        $node->prev            = $this->head;
        $this->head->next->prev = $node;
        $this->head->next      = $node;
    }

    private function moveToFront(LRUNode $node): void {
        $this->removeNode($node);
        $this->addToFront($node);
    }

    public function display(): void {
        $result = [];
        $cur = $this->head->next;
        while ($cur !== $this->tail) { $result[] = "{$cur->key}:{$cur->value}"; $cur = $cur->next; }
        echo "Cache (MRU→LRU): [" . implode(', ', $result) . "]\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B1. LRU Cache ===\n\n";
$cache = new LRUCache(3);
$cache->put(1, 10); $cache->display();
$cache->put(2, 20); $cache->display();
$cache->put(3, 30); $cache->display();
echo "get(1) = " . $cache->get(1) . "\n"; // 10 (1 is now MRU)
$cache->display();
$cache->put(4, 40); // Evicts 2 (LRU)
$cache->display();
echo "get(2) = " . $cache->get(2) . "\n"; // -1 (evicted)
echo "get(3) = " . $cache->get(3) . "\n"; // 30
$cache->display();
