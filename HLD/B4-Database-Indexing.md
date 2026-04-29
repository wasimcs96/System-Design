# B4 — Database Indexing

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A database index is like the index at the back of a book — instead of reading every page to find a word, you jump directly to the right page. Without an index, the database scans every row; with an index, it jumps directly to the matching rows.

**Technical:** An index is a data structure (typically B-Tree or Hash) that maintains sorted pointers to rows in a table, enabling the database engine to locate rows matching a query predicate without a full table scan. Indexes trade write overhead and storage for dramatically faster reads.

---

## 2. Real-World Analogy

**Library Card Catalog:**
- Full table scan = walking through every book in the library to find one
- Index = the card catalog (sorted by title/author/subject) that points to the shelf location
- B-Tree index = a sorted tree that narrows down location in O(log N) steps
- Composite index = a catalog sorted first by author, then by title within each author
- Covering index = a catalog that contains not just location, but also the full title, year, and ISBN — you don't need to go to the shelf at all

---

## 3. Visual Diagram

```
B-TREE INDEX STRUCTURE (PostgreSQL default):
                    ┌─────────────┐
                    │  Root Node  │
                    │  [50 | 80]  │
                    └──────┬──────┘
              ┌────────────┼────────────┐
              ▼            ▼            ▼
         [20|35]       [60|70]       [90|95]
         /  |  \       /  |  \       /  |         [10][25][40] [55][65][75]   [85][92][98]
        ↓   ↓   ↓   ↓   ↓   ↓     ↓   ↓   ↓
      Row Pointers (ctid/rowid → actual heap page)

Search for id=65: Root→[60|70]→[65] = 3 comparisons (O(log N))
Full scan without index: Check every row = O(N)

COMPOSITE INDEX COLUMN ORDER MATTERS:
INDEX ON (status, created_at, user_id)
 ✓ WHERE status = 'active'                    → uses index
 ✓ WHERE status = 'active' AND created_at > X → uses index
 ✗ WHERE created_at > X                       → cannot use index (skipped leading column)
 ✗ WHERE user_id = 123                        → cannot use index
```

---

## 4. Deep Technical Explanation

### B-Tree Index
- Balanced tree, keeps itself balanced on insert/delete
- Height = O(log N) — typical height is 3–4 for millions of rows
- Supports: equality (`=`), range (`>`, `<`, `BETWEEN`), sorted order (`ORDER BY`)
- **Default for:** most databases (PostgreSQL, MySQL, Oracle)

### Hash Index
- Key → bucket mapping, O(1) lookup
- Supports: **equality only** (`=`)
- Does NOT support: range, ordering
- Stored in memory (PostgreSQL WAL doesn't log them before v10)
- **Use for:** exact-match only lookups (UUID, email lookup)

### Composite Index
- Index on multiple columns: `INDEX ON (col1, col2, col3)`
- **Left-prefix rule:** Query must include columns from the left
  - `WHERE col1 = X` ✓
  - `WHERE col1 = X AND col2 = Y` ✓
  - `WHERE col2 = Y` ✗ (skipped col1)
- **Column order:** Put highest cardinality column first (most selective)
- **Equality before range:** `INDEX ON (status, created_at)` for `WHERE status='active' AND created_at > X`

### Covering Index
- Index contains ALL columns needed by the query
- No need to access the actual table rows (heap)
- Called "index-only scan" in PostgreSQL
```sql
-- Without covering index: index lookup + heap access
SELECT id, name FROM users WHERE email = 'alice@test.com';

-- With covering index ON (email, id, name): index-only scan
CREATE INDEX idx_users_email_cover ON users (email) INCLUDE (id, name);
```

### Partial Index
- Only index rows matching a condition
- Smaller, faster, saves space
```sql
-- Only index active users (not the 80% who are inactive)
CREATE INDEX idx_active_users ON users (last_login) WHERE status = 'active';
```

### Full-Text Index
- PostgreSQL: `CREATE INDEX ON articles USING gin(to_tsvector('english', body))`
- MySQL: `CREATE FULLTEXT INDEX ON articles(body)`
- Elasticsearch: inverted index with tokenization, stemming, TF-IDF
- Use for: search boxes, content search

### Index Cardinality
- **High cardinality:** many distinct values (user_id, email, UUID) → index very selective → fast
- **Low cardinality:** few distinct values (status, boolean, gender) → index less useful → DB may prefer full scan
- **Rule:** Index columns used in WHERE clause, ORDER BY, and JOIN conditions with high cardinality first

### N+1 Query Problem
```php
// ❌ N+1: 1 query for orders + N queries for each product
$orders = Order::all();  // 1 query
foreach ($orders as $order) {
    echo $order->product->name;  // N queries (one per order)
}
// 100 orders = 101 database queries

// ✅ Eager loading: 2 queries total
$orders = Order::with('product')->get();  // 2 queries
foreach ($orders as $order) {
    echo $order->product->name;  // No DB hit — already loaded
}
```

### EXPLAIN ANALYZE — Diagnosing Slow Queries
```sql
EXPLAIN ANALYZE SELECT * FROM orders WHERE user_id = 123 AND status = 'placed';
-- Output shows:
-- Seq Scan on orders (cost=0.00..1850.00 rows=3 width=64) (actual time=0.021..15.3 rows=3)
--   Filter: (user_id = 123 AND status = 'placed')
--   Rows Removed by Filter: 99997

-- Fix: CREATE INDEX ON orders (user_id, status)
-- Result:
-- Index Scan using idx_orders_user_status on orders
--   (cost=0.29..8.32 rows=3 width=64) (actual time=0.019..0.025 rows=3)
```

---

## 5. Code Example

```php
// Laravel migrations — proper index design
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('product_id');
    $table->string('status', 20);      // 'pending', 'placed', 'shipped'
    $table->decimal('total', 10, 2);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    
    // Single column indexes
    $table->index('user_id');           // Used in: WHERE user_id = X
    $table->index('created_at');        // Used in: ORDER BY created_at
    
    // Composite index — equality before range, high-cardinality first
    $table->index(['user_id', 'status']);      // WHERE user_id=X AND status=Y
    $table->index(['status', 'created_at']);   // WHERE status=X AND created_at > Y
    
    // Partial index (via raw SQL — not natively in Laravel migrations)
    // DB::statement('CREATE INDEX idx_active ON orders (created_at) WHERE status != 'cancelled'');
});
```

```php
// Detecting slow queries in Laravel
// config/logging.php
DB::listen(function($query) {
    if ($query->time > 100) {  // > 100ms
        Log::warning("Slow query: {$query->sql}", [
            'time'     => $query->time,
            'bindings' => $query->bindings,
        ]);
    }
});

// Force EXPLAIN for debugging
$results = DB::select("EXPLAIN ANALYZE " . 
    Order::where('user_id', 123)->where('status', 'placed')->toSql()
);
```

---

## 6. Trade-offs

| Index Type | Supports | Storage | Write Overhead | Best For |
|-----------|---------|---------|---------------|----------|
| B-Tree | =, <, >, BETWEEN, ORDER BY | Medium | Medium | Most columns |
| Hash | = only | Small | Low | Exact-match UUID, email |
| Composite | Multi-column queries | Larger | Higher | Combined WHERE/ORDER BY |
| Covering | Index-only scan | Largest | Highest | Read-heavy, specific queries |
| Partial | Subset of rows | Smallest | Lowest | Filtered queries (active only) |
| Full-text | Text search | Large | High | Search boxes |

---

## 7. Interview Q&A

**Q1: Why can't you just index every column?**
> Every index adds write overhead — INSERT/UPDATE/DELETE must update all indexes on that table. For a table with 10 indexes, every row insert writes to 11 places. Indexes also consume disk space and memory (buffer pool). Too many indexes can slow down write-heavy tables (like high-frequency event logs) more than they help reads. Index only what's actually queried.

**Q2: Why does column order in a composite index matter?**
> The B-tree is sorted by columns left-to-right. A query can use the index only if it provides values for the leftmost columns in sequence. `INDEX ON (a, b, c)` can be used for queries on `(a)`, `(a, b)`, `(a, b, c)` — but NOT `(b)` or `(c)` alone. Think of it like a phone book sorted by last name then first name — you can find "Smith, John" but you can't efficiently find all "Johns" without scanning the whole book.

**Q3: What is an index-only scan (covering index)?**
> When a query needs columns that are ALL present in the index, the database can answer it by reading only the index — never touching the actual table rows (heap). This is significantly faster because indexes are typically much smaller and more cache-friendly than tables. Example: `SELECT email, name FROM users WHERE email = 'a@b.com'` with `INDEX ON (email) INCLUDE (name)` — no table access needed.

**Q4: How do you find and fix slow queries in production?**
> Use slow query log (MySQL `slow_query_log`, PostgreSQL `log_min_duration_statement`). Analyze with `EXPLAIN ANALYZE`. Look for: Seq Scan on large tables (missing index), high rows removed by filter (index ineffective), nested loop joins on large tables (missing join index). Fix: add appropriate index, rewrite query, or denormalize. For ORM apps, use Laravel Telescope or Debugbar to catch N+1.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ B-Tree: default; Hash: equality-only; GIN/GiST: text/JSON      │
│ ✓ Composite index: left-prefix rule, equality before range        │
│ ✓ Covering index = index-only scan = no heap access               │
│ ✓ High cardinality first in composite index                       │
│ ✓ Too many indexes slow writes — index only what's queried        │
│ ✓ EXPLAIN ANALYZE is your best friend for slow query diagnosis    │
│ ✓ N+1 = fetch N children with N separate queries — use eager load │
└────────────────────────────────────────────────────────────────────┘
```
