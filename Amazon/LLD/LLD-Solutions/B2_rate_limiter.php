<?php
/**
 * B2. RATE LIMITER
 * ============================================================
 * PROBLEM: Prevent API abuse by limiting requests per user/IP
 * within a time window.
 *
 * PATTERNS:
 *  - Strategy : RateLimitAlgorithm (Token Bucket, Sliding Window)
 *
 * ALGORITHMS IMPLEMENTED:
 *  1. Token Bucket  – allows bursts; refills at constant rate
 *  2. Sliding Window Log – precise; stores timestamps per user
 * ============================================================
 */

// ─── Strategy Interface ────────────────────────────────────────
interface RateLimitAlgorithm {
    /** Returns true if request is allowed */
    public function isAllowed(string $userId): bool;
}

// ─── 1. Token Bucket ───────────────────────────────────────────
class TokenBucket implements RateLimitAlgorithm {
    /** @var array<string,array{tokens:float,lastRefill:float}> */
    private array $buckets = [];

    /**
     * @param int   $capacity  Max tokens (burst size)
     * @param float $refillRate Tokens added per second
     */
    public function __construct(
        private int   $capacity,
        private float $refillRate
    ) {}

    public function isAllowed(string $userId): bool {
        $now = microtime(true);
        if (!isset($this->buckets[$userId])) {
            $this->buckets[$userId] = ['tokens' => $this->capacity, 'lastRefill' => $now];
        }

        $bucket  = &$this->buckets[$userId];
        $elapsed = $now - $bucket['lastRefill'];

        // Refill tokens based on elapsed time
        $bucket['tokens']    = min($this->capacity, $bucket['tokens'] + $elapsed * $this->refillRate);
        $bucket['lastRefill'] = $now;

        if ($bucket['tokens'] >= 1.0) {
            $bucket['tokens'] -= 1.0;
            return true;  // Request allowed
        }
        return false;  // Token bucket empty
    }

    public function getTokens(string $userId): float {
        return round($this->buckets[$userId]['tokens'] ?? $this->capacity, 2);
    }
}

// ─── 2. Sliding Window Log ─────────────────────────────────────
class SlidingWindowLog implements RateLimitAlgorithm {
    /** @var array<string,float[]> userId → timestamps of requests */
    private array $logs = [];

    /**
     * @param int $maxRequests Max requests per window
     * @param int $windowSec   Window size in seconds
     */
    public function __construct(
        private int $maxRequests,
        private int $windowSec
    ) {}

    public function isAllowed(string $userId): bool {
        $now      = microtime(true);
        $windowStart = $now - $this->windowSec;

        if (!isset($this->logs[$userId])) $this->logs[$userId] = [];

        // Remove timestamps outside the window
        $this->logs[$userId] = array_values(
            array_filter($this->logs[$userId], fn($ts) => $ts > $windowStart)
        );

        if (count($this->logs[$userId]) < $this->maxRequests) {
            $this->logs[$userId][] = $now;
            return true;
        }
        return false;
    }

    public function getRequestCount(string $userId): int {
        return count($this->logs[$userId] ?? []);
    }
}

// ─── Rate Limiter Service (Context) ────────────────────────────
class RateLimiterService {
    public function __construct(private RateLimitAlgorithm $algorithm) {}

    public function handleRequest(string $userId, string $endpoint): void {
        if ($this->algorithm->isAllowed($userId)) {
            echo "  ✓ [{$userId}] {$endpoint} — ALLOWED\n";
        } else {
            echo "  ✗ [{$userId}] {$endpoint} — RATE LIMITED (429)\n";
        }
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B2. Rate Limiter ===\n\n";

echo "--- Token Bucket (capacity=3, refill=1/s) ---\n";
$tb = new RateLimiterService(new TokenBucket(3, 1.0));
for ($i = 1; $i <= 5; $i++) $tb->handleRequest('user1', '/api/search');

echo "\n--- Sliding Window Log (3 req/2s) ---\n";
$sw = new RateLimiterService(new SlidingWindowLog(3, 2));
for ($i = 1; $i <= 5; $i++) $sw->handleRequest('user2', '/api/checkout');
