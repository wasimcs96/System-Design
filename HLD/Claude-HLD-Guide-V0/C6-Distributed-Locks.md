# C6 — Distributed Locks

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** A distributed lock prevents multiple servers from performing the same operation simultaneously. Like a bathroom key in a shared office — only one person can hold it at a time, even if multiple people want to use the bathroom.

**Technical:** A distributed lock (mutex) provides mutual exclusion across multiple processes on different machines, ensuring a critical section is executed by only one process at a time. Unlike in-process locks (mutex, semaphore), distributed locks must handle network partitions, clock drift, and process crashes.

---

## 2. Real-World Analogy

**Only one janitor can service a floor at a time.** The janitor picks up the master key (acquires lock), services all rooms, returns the key (releases lock). If the janitor gets sick mid-shift (process crash), the key has an expiry — it auto-returns after N minutes (TTL). Another janitor can then acquire it.

---

## 3. Visual Diagram

```
SINGLE REDIS DISTRIBUTED LOCK:
Process A: SET lock:resource "A" NX PX 30000
           → OK (lock acquired)
Process B: SET lock:resource "B" NX PX 30000
           → nil (lock NOT acquired — A holds it)
...A does work...
Process A: DEL lock:resource (via Lua — verify owner first)

RACE CONDITION — WHY VERIFY OWNER:
1. A acquires lock with TTL 30s
2. A is slow — lock expires after 30s
3. B acquires lock (A's expired)
4. A finishes work and tries to release — but should NOT release B's lock!
Solution: store owner token in lock value, verify before DELETE

REDLOCK (multi-node):
Process tries to acquire lock on N/2+1 Redis nodes within TTL/2:
Node 1: SET ✓
Node 2: SET ✓
Node 3: SET ✓ (acquired on majority in time window → lock held)
Node 4: SET ✗ (down)
Node 5: SET ✗ (timeout)
→ Majority acquired → lock is valid
```

---

## 4. Deep Technical Explanation

### Single Redis Lock (Basic)
```
SET lock:{resource} {unique_token} NX PX {ttl_ms}
```
- `NX` = only set if not exists (atomic check + set)
- `PX {ttl_ms}` = expire after N milliseconds (auto-release if process crashes)
- Value = unique token (UUID) to verify ownership on release

**Critical:** Release must be atomic (check owner + delete in one operation) — use Lua script.

### Why TTL is Critical
Without TTL: if process crashes while holding lock, no other process can ever acquire it → deadlock.
With TTL: lock auto-expires after N milliseconds, allowing recovery.

**TTL must be >> max operation time.** If TTL = 5s and operation takes 10s, lock expires mid-operation.

### Redlock Algorithm (Redis Cluster)
For higher availability (single Redis = SPOF):
1. Get current timestamp T1
2. Try to acquire lock on N Redis nodes (N = 5 recommended) with TTL
3. Count successful acquisitions and elapsed time
4. If acquired on majority (≥ N/2+1) and elapsed < TTL: lock is valid
5. Effective TTL = original TTL - elapsed time
6. If not majority: release all acquired locks and fail

**Controversy:** Martin Kleppmann argued Redlock is not safe under certain conditions (process pauses, clock drift). For strongest guarantees, use ZooKeeper (Zab consensus) or etcd (Raft consensus).

### ZooKeeper-based Locks
- Create ephemeral sequential znode: `/locks/my-lock-0000001`
- Watch the znode with next lower sequence number
- When that znode disappears: your turn to hold the lock
- On session expire: ephemeral znode automatically deleted (no TTL needed)

---

## 5. Code Example

```php
class RedisDistributedLock {
    private Redis  $redis;
    private string $resource;
    private string $token;
    private int    $ttlMs;
    
    public function acquire(int $ttlMs = 30000): bool {
        $this->token = bin2hex(random_bytes(16));
        $this->ttlMs = $ttlMs;
        
        $result = $this->redis->set(
            "lock:{$this->resource}",
            $this->token,
            ['NX', 'PX' => $ttlMs]
        );
        
        return $result !== null;
    }
    
    public function release(): bool {
        // Lua script: atomic check-then-delete
        // Prevents releasing another process's lock
        $script = <<<'LUA'
        if redis.call("get", KEYS[1]) == ARGV[1] then
            return redis.call("del", KEYS[1])
        else
            return 0
        end
        LUA;
        
        return (bool) $this->redis->eval(
            $script,
            ["lock:{$this->resource}", $this->token],
            1
        );
    }
    
    // Execute critical section with lock
    public function withLock(callable $fn): mixed {
        if (!$this->acquire()) {
            throw new LockNotAcquiredException("Could not acquire lock for {$this->resource}");
        }
        
        try {
            return $fn();
        } finally {
            $this->release();
        }
    }
}

// Usage: prevent double-charging
$lock = new RedisDistributedLock($redis, "payment:{$orderId}");
$lock->withLock(function() use ($orderId) {
    if (!Order::find($orderId)->is_paid) {
        $this->paymentService->charge($orderId);
        Order::where('id', $orderId)->update(['is_paid' => true]);
    }
});
```

---

## 6. Trade-offs

| Tool | Consistency | Availability | Complexity | Use Case |
|------|-------------|-------------|-----------|----------|
| Single Redis | Medium | SPOF risk | Low | Most cases |
| Redlock (5 Redis) | Medium-High | High | Medium | Distributed |
| ZooKeeper | High | High | High | Strict requirements |
| etcd | High | High | Medium | Kubernetes, service mesh |
| DB row lock | High (with DB) | DB-dependent | Low | When DB is available |

---

## 7. Interview Q&A

**Q1: What are the failure modes of distributed locks?**
> (1) Lock holder crashes: TTL saves you — lock auto-expires. (2) Lock expires before operation completes: operation may run with another lock holder simultaneously — mitigate by increasing TTL and tracking operation progress. (3) Redis single-node failure: use Redlock on 5 nodes or ZooKeeper. (4) Network partition: one side thinks it holds the lock, another side's lock expired and was reacquired — fencing tokens help (monotonically increasing number issued with lock; resources reject requests with stale fence token).

**Q2: Why should distributed lock release use a Lua script?**
> The release operation requires: (1) GET lock value, (2) verify it matches our token, (3) DELETE lock. Without atomicity, there's a race: after step 2 but before step 3, our lock TTL expires and another process acquires the lock. Then our DELETE removes the other process's lock. Lua scripts in Redis execute atomically — no other command runs between GET and DEL.

**Q3: When should you use a database row lock instead of Redis?**
> If your operation is already in a database transaction, use SELECT FOR UPDATE (row-level lock). It's simpler, consistent with your transaction, and auto-released on transaction end. Use Redis distributed locks when: multiple services need the lock, operations span multiple databases, or you need a lock outside database context (background job, external API call).

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ SET key token NX PX ttl — atomic acquire                        │
│ ✓ Lua script for release — atomic verify + delete                 │
│ ✓ TTL must be >> max operation time (prevent premature expiry)    │
│ ✓ Store owner token — prevent releasing another process's lock    │
│ ✓ Redlock for multi-node; ZooKeeper/etcd for strict guarantees    │
│ ✓ Fencing tokens protect against clock drift + GC pauses          │
└────────────────────────────────────────────────────────────────────┘
```
