# E3 — Database Internals

> **Section:** Data Processing | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Understanding how databases store and retrieve data internally helps you make better decisions about which databases to use, why some queries are fast or slow, and how to tune performance.

**Technical:** Database internals covers: storage engines (B-Tree vs LSM-Tree), write-ahead logging (WAL), MVCC (multi-version concurrency control), buffer pool management, page cache, and compaction/vacuum processes.

---

## 2. Real-World Analogy

**Library card catalog (B-Tree) vs. append-only notebook (LSM-Tree):**
- B-Tree: sorted card catalog — reads fast (binary search), updating a card in the middle requires reorganizing.
- LSM-Tree: notebook where you always append — writes fast, reads require scanning multiple notebooks, periodic reorganization (compaction) produces clean sorted files.

---

## 3. Visual Diagram

```
B-TREE (PostgreSQL, MySQL InnoDB):
                       [Root: 50]
                      /           \
          [20, 30, 40]           [60, 70, 80]
         / |  |  |  \           / |  |  |  \
      [10][25][35][45][55]  [55][65][75][85][95]
      (leaf pages with actual row data)

Read: O(log N) — traverse from root to leaf
Write: find correct leaf, insert — may cause page split (expensive)

LSM-TREE (Cassandra, RocksDB, LevelDB):
Write path: MemTable (in-memory, sorted) + WAL (durability)
When MemTable full → flush to L0 SSTable (immutable disk file)
            L0: [SSTable1] [SSTable2] [SSTable3]  <- recent writes
                          compaction
            L1: [sorted, merged SSTable]
                          compaction
            L2: [sorted, merged SSTable] <- larger, fewer files

Read: check MemTable -> Bloom filter (is key in SSTable?) -> SSTable binary search
Write: always sequential -> 100-1000x faster writes than B-Tree random writes
```

---

## 4. Deep Technical Explanation

### B-Tree vs LSM-Tree

| Aspect | B-Tree | LSM-Tree |
|--------|--------|---------|
| Write performance | Moderate (random I/O) | High (sequential I/O) |
| Read performance | High (O(log N)) | Moderate (multiple files) |
| Space amplification | Low | High (until compaction) |
| Write amplification | Low-moderate | High (rewrite on compaction) |
| Best for | Read-heavy workloads | Write-heavy workloads |
| Examples | PostgreSQL, MySQL InnoDB | Cassandra, RocksDB, LevelDB |

### Write-Ahead Log (WAL)
- All writes go to WAL first (sequential append — fast)
- If crash: replay WAL to recover state
- PostgreSQL WAL: every INSERT/UPDATE/DELETE logged before modifying data pages
- MySQL binlog: similar, used for replication

### MVCC (Multi-Version Concurrency Control)
- Instead of locking rows for reads, keep multiple versions of each row
- Writers write new version; readers see consistent snapshot of old version
- PostgreSQL: each row has `xmin` (transaction that created) and `xmax` (transaction that deleted)
- `VACUUM` in PostgreSQL: removes dead row versions
- Result: reads never block writes, writes never block reads — high concurrency

### Buffer Pool / Page Cache
- B-Tree pages (typically 8KB or 16KB) cached in memory (buffer pool)
- PostgreSQL: shared_buffers (typically 25% of RAM)
- MySQL InnoDB: innodb_buffer_pool_size (typically 70-80% of RAM)
- Dirty pages: modified in memory but not yet written to disk (write-behind)

---

## 5. Code Example

```php
// MVCC behavior: two concurrent transactions
// T1 (starts first — long-running read)
$pdo->beginTransaction();
$stmt = $pdo->query("SELECT count(*) FROM orders WHERE status = 'pending'");
// Returns 100 — snapshot taken at T1 start

// T2 (concurrent — adds new pending order while T1 runs)
$pdo2->beginTransaction();
$pdo2->exec("INSERT INTO orders (status) VALUES ('pending')");
$pdo2->commit();
// T1 still sees 100 (MVCC snapshot — PostgreSQL REPEATABLE READ)
$pdo->commit();

// EXPLAIN ANALYZE to understand execution plan + buffer pool usage
$result = $pdo->query("
    EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
    SELECT * FROM orders WHERE customer_id = 12345
")->fetchAll();
// Shows: Seq Scan vs Index Scan
// Buffers: shared hit=5 read=2  (5 from buffer pool, 2 from disk)
// Execution time, actual rows

// PostgreSQL VACUUM — removes dead rows from MVCC
// Run automatically by autovacuum daemon
// Manual: VACUUM ANALYZE orders;
// VACUUM FULL: rewrites table (locks table, reclaims space)

// Understanding index selection:
// Small table: seq scan (cheaper than index for full scan)
// Large table + selective predicate (<5% rows): index scan
// Large table + many rows match: bitmap heap scan
```

---

## 6. Trade-offs

| Design Choice | Pros | Cons |
|--------------|------|------|
| B-Tree index | Fast reads, range queries | Slower writes (page splits) |
| LSM-Tree | Fast writes, sequential I/O | Read amplification, compaction overhead |
| MVCC | Non-blocking reads + writes | Dead row accumulation, VACUUM needed |
| Larger buffer pool | More cache hits | Higher RAM cost |
| WAL fsync on every commit | Durability guarantee | Lower write throughput |

---

## 7. Interview Q&A

**Q1: Why is Cassandra (LSM-Tree) better than MySQL for write-heavy workloads?**
> MySQL InnoDB uses B-Tree: each write requires finding the correct leaf page (random I/O), potentially causing page splits. Cassandra uses LSM-Tree: all writes are sequential appends to MemTable (in-memory) -> flushed to SSTable (sequential disk write). SSDs excel at sequential writes (1-3 GB/s) vs random writes (100-300 MB/s). Cassandra can sustain 1M+ writes/sec on a cluster. Trade-off: Cassandra reads are slower (must check multiple SSTables + bloom filters + compaction overhead).

**Q2: What is PostgreSQL VACUUM and why is it needed?**
> PostgreSQL MVCC keeps multiple row versions for concurrent readers. When a row is updated, the old version is marked dead (xmax set) but not immediately removed. Dead rows accumulate: waste space + slow sequential scans. VACUUM removes dead row versions, reclaims space, updates query planner statistics. AUTOVACUUM runs automatically when >20% of table rows are dead. For write-heavy tables: tune autovacuum_vacuum_scale_factor lower. Unvacuumed tables suffer table bloat -> degraded performance.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| B-Tree: fast reads/range queries -- PostgreSQL, MySQL             |
| LSM-Tree: fast writes (sequential) -- Cassandra, RocksDB         |
| WAL: crash recovery via sequential log replay                     |
| MVCC: non-blocking reads + writes -- each sees consistent snapshot|
| Buffer pool: hot pages in RAM -- size = 70-80% RAM for MySQL      |
| VACUUM (PG): remove dead row versions, reclaim space              |
+--------------------------------------------------------------------+
```
