<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #14 — PROXY                          ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Structural Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆ (Caching, access control, lazy loading)    ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You want to control access to an object — add logic    │
 * │ BEFORE or AFTER the real object's method — without changing     │
 * │ the real object or the client.                                   │
 * │                                                                  │
 * │ Three most common types:                                         │
 * │                                                                  │
 * │  1. VIRTUAL PROXY (Lazy Loading)                                 │
 * │     Delays expensive initialization until first use.             │
 * │     Example: Load a 50MB image only when displayed, not on       │
 * │     object creation.                                             │
 * │                                                                  │
 * │  2. PROTECTION PROXY (Access Control)                            │
 * │     Controls access based on user role/permissions.              │
 * │     Example: Only admins can delete; regular users can only read.│
 * │                                                                  │
 * │  3. CACHING PROXY                                                │
 * │     Caches results of expensive calls. Returns cached data on    │
 * │     repeated calls instead of going to the real service.        │
 * │     Example: Cache DB query results for 60 seconds.              │
 * │                                                                  │
 * │ KEY RULE: Proxy implements the SAME interface as the real subject│
 * │ — client code doesn't know it's talking to a proxy.             │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client ──► Subject (interface)                                  │
 * │                  │                                               │
 * │                  ├── RealSubject  (does actual work)             │
 * │                  └── Proxy        (wraps RealSubject)            │
 * │                       ├─ pre-processing  (auth, logging)        │
 * │                       ├─ real.method()   (delegate or skip)     │
 * │                       └─ post-processing (caching, auditing)    │
 * │                                                                   │
 * │  Client always programs to Subject interface.                    │
 * │  At runtime, receives Proxy instead of RealSubject.              │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE PROXY                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Subject interface (shared by real + proxy)        │
 * │ STEP 2: Create RealSubject implementing Subject                  │
 * │ STEP 3: Create Proxy implementing Subject, wrapping RealSubject  │
 * │ STEP 4: Proxy adds pre/post logic, then delegates to real        │
 * │ STEP 5: Client receives Subject — proxy is transparent           │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Caching Proxy — Database User Repository
// ═══════════════════════════════════════════════════════════════

// STEP 1: Subject Interface
interface UserRepository
{
    public function findById(int $id): ?array;
    public function findByEmail(string $email): ?array;
    public function save(array $user): void;
    public function delete(int $id): void;
    public function findAll(): array;
}

// STEP 2: Real Subject — expensive DB calls
class DatabaseUserRepository implements UserRepository
{
    private array $db = [
        1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'user'],
        2 => ['id' => 2, 'name' => 'Bob',   'email' => 'bob@example.com',   'role' => 'admin'],
        3 => ['id' => 3, 'name' => 'Carol', 'email' => 'carol@example.com', 'role' => 'user'],
    ];

    public function findById(int $id): ?array
    {
        echo "  [DB] SELECT * FROM users WHERE id=$id\n";
        return $this->db[$id] ?? null;
    }

    public function findByEmail(string $email): ?array
    {
        echo "  [DB] SELECT * FROM users WHERE email='$email'\n";
        foreach ($this->db as $user) {
            if ($user['email'] === $email) return $user;
        }
        return null;
    }

    public function save(array $user): void
    {
        echo "  [DB] INSERT/UPDATE user id={$user['id']}\n";
        $this->db[$user['id']] = $user;
    }

    public function delete(int $id): void
    {
        echo "  [DB] DELETE FROM users WHERE id=$id\n";
        unset($this->db[$id]);
    }

    public function findAll(): array
    {
        echo "  [DB] SELECT * FROM users\n";
        return array_values($this->db);
    }
}

// STEP 3 & 4: Caching Proxy
class CachingUserRepository implements UserRepository
{
    private array  $cache    = [];      // Key → user data
    private ?array $allCache = null;    // Cached findAll result
    private int   $hits     = 0;
    private int   $misses   = 0;

    public function __construct(private UserRepository $real) {}

    public function findById(int $id): ?array
    {
        $key = "id:$id";
        if (!array_key_exists($key, $this->cache)) {
            $this->misses++;
            $this->cache[$key] = $this->real->findById($id); // Fetch + cache
        } else {
            $this->hits++;
            echo "  [Cache] HIT for user id=$id\n";
        }
        return $this->cache[$key];
    }

    public function findByEmail(string $email): ?array
    {
        $key = "email:$email";
        if (!array_key_exists($key, $this->cache)) {
            $this->misses++;
            $this->cache[$key] = $this->real->findByEmail($email);
        } else {
            $this->hits++;
            echo "  [Cache] HIT for email=$email\n";
        }
        return $this->cache[$key];
    }

    public function save(array $user): void
    {
        $this->real->save($user);
        // Invalidate stale cache entries for this user
        unset($this->cache["id:{$user['id']}"]);
        unset($this->cache["email:{$user['email']}"]);
        $this->allCache = null;
        echo "  [Cache] Invalidated cache for user id={$user['id']}\n";
    }

    public function delete(int $id): void
    {
        $this->real->delete($id);
        unset($this->cache["id:$id"]);
        $this->allCache = null;
        echo "  [Cache] Invalidated cache for user id=$id\n";
    }

    public function findAll(): array
    {
        if ($this->allCache === null) {
            $this->misses++;
            $this->allCache = $this->real->findAll();
        } else {
            $this->hits++;
            echo "  [Cache] HIT for findAll\n";
        }
        return $this->allCache;
    }

    public function getCacheStats(): array
    {
        return ['hits' => $this->hits, 'misses' => $this->misses,
                'ratio' => $this->hits + $this->misses > 0
                    ? round($this->hits / ($this->hits + $this->misses) * 100, 1) . '%'
                    : 'N/A'];
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Protection Proxy — Role-Based Access Control
// ═══════════════════════════════════════════════════════════════

interface DocumentService
{
    public function read(int $docId): ?array;
    public function write(int $docId, array $content): void;
    public function delete(int $docId): void;
    public function publish(int $docId): void;
}

class RealDocumentService implements DocumentService
{
    private array $docs = [
        1 => ['id' => 1, 'title' => 'Q1 Report',    'status' => 'draft'],
        2 => ['id' => 2, 'title' => 'Budget Plan',   'status' => 'draft'],
    ];

    public function read(int $docId): ?array
    {
        echo "  [DocService] Reading doc $docId\n";
        return $this->docs[$docId] ?? null;
    }

    public function write(int $docId, array $content): void
    {
        echo "  [DocService] Writing doc $docId\n";
        $this->docs[$docId] = array_merge($this->docs[$docId] ?? [], $content);
    }

    public function delete(int $docId): void
    {
        echo "  [DocService] Deleting doc $docId\n";
        unset($this->docs[$docId]);
    }

    public function publish(int $docId): void
    {
        echo "  [DocService] Publishing doc $docId\n";
        if (isset($this->docs[$docId])) {
            $this->docs[$docId]['status'] = 'published';
        }
    }
}

class ProtectionProxy implements DocumentService
{
    private array $permissions = [
        'viewer' => ['read'],
        'editor' => ['read', 'write'],
        'admin'  => ['read', 'write', 'delete', 'publish'],
    ];

    public function __construct(
        private DocumentService $real,
        private string          $userRole,
        private string          $userName
    ) {}

    private function checkPermission(string $action): void
    {
        $allowed = $this->permissions[$this->userRole] ?? [];
        if (!in_array($action, $allowed, true)) {
            throw new \RuntimeException(
                "Access denied: {$this->userName} (role={$this->userRole}) cannot '$action'"
            );
        }
        echo "  [Proxy] Access granted: {$this->userName} → $action\n";
    }

    public function read(int $docId): ?array
    {
        $this->checkPermission('read');
        return $this->real->read($docId);
    }

    public function write(int $docId, array $content): void
    {
        $this->checkPermission('write');
        $this->real->write($docId, $content);
    }

    public function delete(int $docId): void
    {
        $this->checkPermission('delete');
        $this->real->delete($docId);
    }

    public function publish(int $docId): void
    {
        $this->checkPermission('publish');
        $this->real->publish($docId);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== PROXY PATTERN DEMO ===\n\n";

echo "--- Example 1: Caching Proxy ---\n";

$realRepo   = new DatabaseUserRepository();
$cachedRepo = new CachingUserRepository($realRepo); // Wrap with proxy

echo "  First call (cache MISS):\n";
$user = $cachedRepo->findById(1);
echo "  Got: {$user['name']}\n";

echo "\n  Second call (cache HIT):\n";
$user = $cachedRepo->findById(1); // Served from cache
echo "  Got: {$user['name']}\n";

echo "\n  Update user → cache invalidated:\n";
$cachedRepo->save(['id' => 1, 'name' => 'Alice Updated', 'email' => 'alice@example.com', 'role' => 'user']);

echo "\n  After update (cache MISS again):\n";
$user = $cachedRepo->findById(1);
echo "  Got: {$user['name']}\n";

$stats = $cachedRepo->getCacheStats();
echo "\n  Cache stats: hits={$stats['hits']}, misses={$stats['misses']}, ratio={$stats['ratio']}\n";

echo "\n--- Example 2: Protection Proxy ---\n";

$docService = new RealDocumentService();

// Alice is a viewer
$aliceProxy = new ProtectionProxy($docService, 'viewer', 'Alice');
$doc = $aliceProxy->read(1);
echo "  Alice read: {$doc['title']}\n";

try {
    $aliceProxy->write(1, ['title' => 'Hacked!']); // Viewer can't write
} catch (\RuntimeException $e) {
    echo "  Blocked: {$e->getMessage()} ✓\n";
}

// Bob is an editor
$bobProxy = new ProtectionProxy($docService, 'editor', 'Bob');
$bobProxy->write(1, ['title' => 'Q1 Updated Report']);

try {
    $bobProxy->delete(1); // Editor can't delete
} catch (\RuntimeException $e) {
    echo "  Blocked: {$e->getMessage()} ✓\n";
}

// Carol is admin — can do everything
$carolProxy = new ProtectionProxy($docService, 'admin', 'Carol');
$carolProxy->publish(2);
$carolProxy->delete(2);

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Proxy pattern?                                   │
 * │ A: Provides a surrogate/placeholder for another object to        │
 * │    control access to it. Proxy and RealSubject implement the    │
 * │    same interface — client doesn't know it's using a proxy.     │
 * │                                                                  │
 * │ Q2: What are the 3 main types of Proxy?                          │
 * │ A: Virtual Proxy: delays expensive initialization (lazy load).  │
 * │    Protection Proxy: controls access (RBAC, auth checks).       │
 * │    Caching Proxy: stores results of expensive operations.        │
 * │    Also: Remote Proxy (represents object in another process/JVM) │
 * │           Logging Proxy (records all method calls + params)      │
 * │                                                                  │
 * │ Q3: Proxy vs Decorator — what's the difference?                  │
 * │ A: Decorator: ADDS new behavior, same interface. Client knows   │
 * │    it's using a decorator and intentionally stacks them.         │
 * │    Proxy: CONTROLS access to real subject. Client may NOT know  │
 * │    it's using a proxy (transparent). Focus is on access/control,│
 * │    not feature addition.                                         │
 * │                                                                  │
 * │ Q4: Proxy vs Facade?                                             │
 * │ A: Proxy: same interface as the real object; 1-to-1 wrapper.   │
 * │    Facade: simplified interface to a complex subsystem (N-to-1).│
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: Doctrine ORM lazy-loading proxy (Virtual Proxy).              │
 * │    Laravel Gate/Policy (Protection Proxy).                       │
 * │    Laravel HTTP Client (wraps Guzzle, adds retry/logging).       │
 * │    PHP's __get/__set magic methods for property-level proxying.  │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Cache invalidation: invalidate on write/delete operations      │
 * │ ✓ Thread-safe caching: use locks for concurrent cache writes     │
 * │ ✓ Proxy should not change behavior beyond access/caching logic   │
 * └─────────────────────────────────────────────────────────────────┘
 */
