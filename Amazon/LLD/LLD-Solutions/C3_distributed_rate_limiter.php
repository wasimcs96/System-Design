<?php
/**
 * C3. DISTRIBUTED RATE LIMITER (Sliding Window Counter)
 * ============================================================
 * PROBLEM: Rate limit across multiple API gateway nodes using
 * a shared counter (simulated Redis). Handles distributed
 * environments where a single node can't see all traffic.
 *
 * PATTERNS:
 *  - Strategy   : RateLimitAlgorithm (sliding window counter, token bucket)
 *  - Proxy      : RateLimitProxy wraps service calls with rate check
 * ============================================================
 */

// ─── Shared Counter (simulates Redis for distributed environment)
class SharedCounter {
    /** @var array<string,array{count:int,window:int}> */
    private array $counters = [];

    public function increment(string $key, int $windowSec): int {
        $windowId = intdiv((int)time(), $windowSec); // Current window bucket
        $fullKey  = "{$key}:{$windowId}";

        if (!isset($this->counters[$fullKey])) {
            $this->counters[$fullKey] = ['count' => 0, 'window' => $windowId];
        }
        return ++$this->counters[$fullKey]['count'];
    }

    public function getCount(string $key, int $windowSec): int {
        $windowId = intdiv((int)time(), $windowSec);
        return $this->counters["{$key}:{$windowId}"]['count'] ?? 0;
    }

    // Sliding window: combine current + partial previous window
    public function getSlidingCount(string $key, int $windowSec): float {
        $now          = microtime(true);
        $windowId     = intdiv((int)$now, $windowSec);
        $prevWindowId = $windowId - 1;
        $elapsed      = fmod($now, $windowSec);        // Seconds into current window
        $prevWeight   = 1 - ($elapsed / $windowSec);  // How much of prev window counts

        $curr = $this->counters["{$key}:{$windowId}"]['count'] ?? 0;
        $prev = $this->counters["{$key}:{$prevWindowId}"]['count'] ?? 0;

        return $curr + ($prev * $prevWeight);
    }
}

// ─── Rate Limit Policy ────────────────────────────────────────
class RateLimitPolicy {
    public function __construct(
        public readonly int    $maxRequests,
        public readonly int    $windowSeconds,
        public readonly string $key = 'global'  // Can be user-specific, IP-specific
    ) {}
}

// ─── Distributed Rate Limiter ─────────────────────────────────
class DistributedRateLimiter {
    public function __construct(private SharedCounter $counter) {}

    public function isAllowed(string $userId, RateLimitPolicy $policy): bool {
        $key   = "ratelimit:{$policy->key}:{$userId}";
        $count = $this->counter->getSlidingCount($key, $policy->windowSeconds);

        if ($count >= $policy->maxRequests) return false;

        $this->counter->increment($key, $policy->windowSeconds);
        return true;
    }

    public function getRemainingQuota(string $userId, RateLimitPolicy $policy): int {
        $key   = "ratelimit:{$policy->key}:{$userId}";
        $count = (int)$this->counter->getSlidingCount($key, $policy->windowSeconds);
        return max(0, $policy->maxRequests - $count);
    }
}

// ─── Rate Limit Proxy ─────────────────────────────────────────
class ApiGatewayProxy {
    public function __construct(
        private DistributedRateLimiter $limiter,
        private RateLimitPolicy        $policy
    ) {}

    public function handleRequest(string $userId, string $endpoint): void {
        if (!$this->limiter->isAllowed($userId, $this->policy)) {
            $retry = $this->policy->windowSeconds;
            echo "  ✗ [{$userId}] 429 Too Many Requests. Retry after {$retry}s\n";
            return;
        }
        $remaining = $this->limiter->getRemainingQuota($userId, $this->policy);
        echo "  ✓ [{$userId}] {$endpoint} | X-RateLimit-Remaining: {$remaining}\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C3. Distributed Rate Limiter ===\n\n";

$counter  = new SharedCounter();
$limiter  = new DistributedRateLimiter($counter);
$policy   = new RateLimitPolicy(5, 60, 'api_v1');   // 5 req/60s
$gateway  = new ApiGatewayProxy($limiter, $policy);

echo "--- User1: 7 requests (limit=5) ---\n";
for ($i = 1; $i <= 7; $i++) {
    $gateway->handleRequest('user1', '/api/products');
}

echo "\n--- User2: independent limit ---\n";
for ($i = 1; $i <= 3; $i++) {
    $gateway->handleRequest('user2', '/api/search');
}
