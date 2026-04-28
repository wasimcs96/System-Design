<?php
/**
 * C4. AUTOCOMPLETE SYSTEM (using Trie)
 * ============================================================
 * PROBLEM: Real-time search suggestions as user types.
 * Support insert, search, prefix suggestions, and ranked results.
 *
 * DATA STRUCTURE: Trie (prefix tree)
 *   Each node: char → children, isEndOfWord, frequency
 *
 * TC: Insert O(L), Search O(L), Suggest O(L + N) where L=word length, N=suggestions
 * SC: O(ALPHABET_SIZE * L * N)
 * ============================================================
 */

// ─── Trie Node ────────────────────────────────────────────────
class TrieNode {
    /** @var array<string,TrieNode> char → child */
    public array  $children  = [];
    public bool   $isEnd     = false;
    public int    $frequency = 0;   // How often this word was searched
    public string $word      = '';  // Full word at terminal nodes
}

// ─── Trie ─────────────────────────────────────────────────────
class Trie {
    private TrieNode $root;

    public function __construct() { $this->root = new TrieNode(); }

    /** Insert word with optional frequency boost */
    public function insert(string $word, int $freq = 1): void {
        $node = $this->root;
        foreach (str_split(strtolower($word)) as $char) {
            if (!isset($node->children[$char])) {
                $node->children[$char] = new TrieNode();
            }
            $node = $node->children[$char];
        }
        $node->isEnd     = true;
        $node->frequency += $freq;
        $node->word       = strtolower($word);
    }

    /** Check if exact word exists */
    public function search(string $word): bool {
        $node = $this->findNode($word);
        return $node !== null && $node->isEnd;
    }

    /** Check if any word starts with this prefix */
    public function startsWith(string $prefix): bool {
        return $this->findNode($prefix) !== null;
    }

    /** Get top-k suggestions for a prefix, sorted by frequency */
    public function suggest(string $prefix, int $k = 5): array {
        $node = $this->findNode(strtolower($prefix));
        if (!$node) return [];

        $results = [];
        $this->dfs($node, $results);
        usort($results, fn($a, $b) => $b['freq'] - $a['freq']);
        return array_column(array_slice($results, 0, $k), 'word');
    }

    private function findNode(string $prefix): ?TrieNode {
        $node = $this->root;
        foreach (str_split(strtolower($prefix)) as $char) {
            if (!isset($node->children[$char])) return null;
            $node = $node->children[$char];
        }
        return $node;
    }

    private function dfs(TrieNode $node, array &$results): void {
        if ($node->isEnd) $results[] = ['word' => $node->word, 'freq' => $node->frequency];
        foreach ($node->children as $child) $this->dfs($child, $results);
    }
}

// ─── Autocomplete Service ─────────────────────────────────────
class AutocompleteService {
    private Trie $trie;

    public function __construct() { $this->trie = new Trie(); }

    public function addWord(string $word, int $freq = 1): void { $this->trie->insert($word, $freq); }

    public function search(string $prefix, int $limit = 5): array {
        return $this->trie->suggest($prefix, $limit);
    }

    public function recordSearch(string $word): void { $this->trie->insert($word, 1); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C4. Autocomplete System (Trie) ===\n\n";

$svc = new AutocompleteService();
$words = [
    ['amazon', 100], ['amazon prime', 90], ['amazon fresh', 85], ['amazon pay', 70],
    ['apple', 80],  ['application', 60], ['apply', 40],
    ['app store', 75], ['append', 30],
    ['linkedin', 50], ['link', 45],
];
foreach ($words as [$word, $freq]) $svc->addWord($word, $freq);

$prefixes = ['am', 'app', 'a', 'li', 'xyz'];
foreach ($prefixes as $prefix) {
    $suggestions = $svc->search($prefix, 3);
    echo "Prefix '{$prefix}': " . (empty($suggestions) ? '(none)' : implode(', ', $suggestions)) . "\n";
}
