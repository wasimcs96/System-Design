# J9 — Design Search Autocomplete / Typeahead

> **Section:** Case Studies | **Difficulty:** Medium | **Interview Frequency:** ★★★★☆

---

## 1. Problem Statement

Design a real-time search autocomplete system (like Google Search suggestions or Amazon product search).

**Functional Requirements:**
- As user types, show top 5 matching search suggestions
- Suggestions ranked by search frequency (most popular first)
- Results must feel instant (< 100ms response)
- Support millions of unique queries

**Non-Functional Requirements:**
- 10M DAU, each user types ~5 queries/day = 50M searches/day
- Autocomplete request per keystroke: ~50M x 20 keystrokes = 1B requests/day
- 1B / 86400 = ~11,574 autocomplete requests/sec (peak: ~100K/sec)
- Suggestions must be updated daily (new trending searches)

---

## 2. Capacity Estimation

```
Autocomplete QPS: ~100K/sec peak -- must use cache aggressively
Storage:
  Unique search terms: ~10M common terms x 50 bytes avg = 500 MB (tiny)
  With frequency counts: ~1 GB

Trie size (in-memory):
  10M terms, avg 5 chars = 50M nodes x ~50 bytes = 2.5 GB
  Fits in memory -- can serve from RAM
```

---

## 3. Two-Level Architecture

```
LEVEL 1: Client-side cache (browser/app)
  Cache last 20 prefix -> suggestions mappings locally
  "app" -> ["apple", "application", "app store", "apparel", "appetizer"]
  If user types "app" then "appl", "appl" is a new prefix -> request to server
  If user backspaces to "app" -> serve from client cache

LEVEL 2: CDN / Redis cache (edge)
  Most typed prefixes are common: "am", "app", "goo", "you"
  Cache top prefixes at CDN (TTL: 5 minutes) -- serves ~80% of traffic
  CDN miss -> Redis -> Trie service

[User keystroke] -> [Client cache] -> [CDN] -> [Redis] -> [Trie Service]
```

---

## 4. Trie Data Structure

```php
// Classic implementation for understanding; production uses Radix Trie or Redis

class TrieNode {
    public array $children = [];   // char -> TrieNode
    public array $topK     = [];   // top K suggestions at this prefix (cached!)
    public bool  $isWord   = false;
    public int   $frequency = 0;
}

class Trie {
    private TrieNode $root;
    private int $k = 5;  // top K suggestions

    public function insert(string $word, int $frequency): void {
        $node = $this->root;
        foreach (str_split($word) as $char) {
            $node->children[$char] ??= new TrieNode();
            $node = $node->children[$char];
        }
        $node->isWord    = true;
        $node->frequency = $frequency;
    }

    public function search(string $prefix): array {
        $node = $this->root;
        foreach (str_split($prefix) as $char) {
            if (!isset($node->children[$char])) {
                return [];
            }
            $node = $node->children[$char];
        }
        // DFS from this node to find all words, return top K by frequency
        return $this->getTopK($node, $prefix);
    }

    private function getTopK(TrieNode $node, string $prefix): array {
        // If pre-computed, return cached top K (optimization)
        if (!empty($node->topK)) {
            return $node->topK;
        }
        $results = [];
        $this->dfs($node, $prefix, $results);
        usort($results, fn($a, $b) => $b['freq'] - $a['freq']);
        return array_slice($results, 0, $this->k);
    }
}

// OPTIMIZATION: Pre-compute top K at every node during trie build
// Tradeoff: more memory (store top 5 at each node) vs faster reads (O(prefix length))
// Without pre-compute: O(prefix_length + all_matching_words)
// With pre-compute: O(prefix_length) -- optimal for autocomplete
```

---

## 5. Redis Sorted Set Approach (Production)

```php
// Simpler than in-memory Trie, distributed, supports real-time updates

// Key insight: for prefix "app", store all words starting with "app" in a sorted set
// Key: "autocomplete:app"
// Member: "apple", Score: search_frequency

// Problem: 10M terms x avg 5 chars = 50M Redis keys -- too many!
// Better: store prefixes lazily (only popular ones) or use Redis Search module

// Practical approach: use sorted set per prefix, rebuild nightly for top prefixes
// Only cache prefixes that are queried frequently (hot prefixes)

// Real-time search frequency tracking:
function recordSearch(string $query): void {
    // Increment global search count
    $this->redis->zIncrBy('search:counts', 1, strtolower(trim($query)));

    // Add all prefixes of this query to prefix->query sorted sets
    $normalized = strtolower(trim($query));
    for ($i = 1; $i <= strlen($normalized); $i++) {
        $prefix = substr($normalized, 0, $i);
        $this->redis->zIncrBy("autocomplete:{$prefix}", 1, $normalized);

        // Keep only top 10 per prefix (remove anything below rank 10)
        $this->redis->zRemRangeByRank("autocomplete:{$prefix}", 0, -11);
    }
}

function getSuggestions(string $prefix, int $limit = 5): array {
    return $this->redis->zRevRange(
        "autocomplete:" . strtolower($prefix),
        0, $limit - 1,
        ['WITHSCORES' => true]
    );
}
```

---

## 6. Handling Scale (Trie Service)

```
PROBLEM: Trie is in memory -- single server can't handle 100K QPS

SOLUTION: Shard the trie by first character
  - Server A: handles prefixes starting with a-f
  - Server B: handles prefixes starting with g-m
  - Server C: handles prefixes starting with n-z
  - Load balancer routes by prefix[0]

PROBLEM: Trie must be updated with new trending searches

SOLUTION: Daily offline rebuild
  1. MapReduce / Spark job: aggregate all searches from logs, compute frequencies
  2. Build new trie with updated frequencies
  3. Serialize trie to file, upload to S3
  4. Each trie server downloads new trie, hot-swaps in-memory (atomic pointer swap)
  5. Live searches still served during rebuild (blue-green deployment of trie)

REAL-TIME UPDATES (trending searches):
  Problem: "earthquake in Tokyo" needs to appear in autocomplete within minutes
  Solution: Real-time layer on top of static trie
    - Redis sorted set: ZINCRBY trending {score} {term} for every search
    - Autocomplete results = merge(trie results, redis trending results)
    - Redis trending decays over time (scheduled job removes old terms)
```

---

## 7. Filtering (Safe Search, Spam)

```
Before returning suggestions:
  - Filter banned/inappropriate terms (blocklist in Redis SET)
  - Filter spam patterns (unusual character sequences, URLs in queries)
  - Personalization (optional): boost terms from user's search history
```

---

## 8. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Data structure | Trie with pre-computed top K | O(prefix length) lookup |
| Real-time updates | Redis ZINCRBY + daily trie rebuild | Balance freshness vs complexity |
| Scale | Shard by first character | Simple, even distribution |
| CDN cache | Top 1K common prefixes, TTL 5 min | Absorb 80% of traffic at edge |
| Trending | Separate Redis layer, merged at read | Decouple real-time from static trie |

---

## 9. Interview Q&A

**Q: How do you handle different languages / non-ASCII characters?**
> Normalize input: lowercase, trim, Unicode NFC normalization. For languages with large character sets (Chinese, Japanese): use character n-grams instead of prefixes (since characters themselves are meaningful units). Alternatively: use a language-specific tokenizer. For multi-language, maintain separate tries per language, detected from user's locale. The trie key space is independent per language.

**Q: What if a term suddenly goes viral (e.g., a celebrity name)?**
> The daily trie rebuild won't catch it for up to 24 hours. Solution: real-time trending layer in Redis. Every search is recorded with ZINCRBY. A separate process computes "velocity" (searches in last hour vs 24-hour baseline). Terms with high velocity are added to a Redis "trending" sorted set. The autocomplete service merges trie results + trending results, deduplicates, and returns top 5. This provides near-real-time (<1 minute) trending suggestion coverage.

---

## 10. Key Takeaways

```
+--------------------------------------------------------------------+
| Trie with pre-computed top K = O(prefix length) autocomplete     |
| Shard trie by first character = horizontal scaling               |
| Redis ZINCRBY per prefix = real-time frequency tracking          |
| Daily batch rebuild + real-time Redis layer = fresh + fast       |
| CDN cache top prefixes with 5-min TTL = absorb 80% of QPS        |
+--------------------------------------------------------------------+
```
