<?php
/**
 * C7. CIRCUIT BREAKER PATTERN
 * ============================================================
 * PROBLEM: Prevent cascading failures when a downstream service
 * is unavailable. Auto-recover after a cool-down period.
 *
 * STATES: Closed (normal) → Open (failing) → Half-Open (testing)
 *
 * PATTERNS:
 *  - State    : CircuitBreaker state machine
 *  - Proxy    : CircuitBreakerProxy wraps service calls
 * ============================================================
 */

enum CircuitState: string { case CLOSED='Closed'; case OPEN='Open'; case HALF_OPEN='HalfOpen'; }

// ─── Circuit Breaker ──────────────────────────────────────────
class CircuitBreaker {
    private CircuitState $state          = CircuitState::CLOSED;
    private int          $failureCount   = 0;
    private int          $successCount   = 0;
    private float        $lastFailureTime = 0.0;

    public function __construct(
        private int   $failureThreshold  = 3,    // Failures before opening
        private float $timeoutSeconds    = 5.0,  // How long to stay open
        private int   $halfOpenSuccesses = 2     // Successes to close from half-open
    ) {}

    public function call(callable $service): mixed {
        if ($this->state === CircuitState::OPEN) {
            if (microtime(true) - $this->lastFailureTime >= $this->timeoutSeconds) {
                $this->state        = CircuitState::HALF_OPEN;
                $this->successCount = 0;
                echo "  [CB] Circuit → HALF-OPEN (testing)\n";
            } else {
                $remaining = round($this->timeoutSeconds - (microtime(true) - $this->lastFailureTime), 1);
                throw new \RuntimeException("[CB] Circuit OPEN — retry in {$remaining}s");
            }
        }

        try {
            $result = $service();
            $this->onSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }

    private function onSuccess(): void {
        $this->failureCount = 0;
        if ($this->state === CircuitState::HALF_OPEN) {
            $this->successCount++;
            if ($this->successCount >= $this->halfOpenSuccesses) {
                $this->state = CircuitState::CLOSED;
                echo "  [CB] Circuit → CLOSED (recovered)\n";
            }
        }
    }

    private function onFailure(): void {
        $this->failureCount++;
        $this->lastFailureTime = microtime(true);
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = CircuitState::OPEN;
            echo "  [CB] Circuit → OPEN after {$this->failureCount} failures\n";
        }
    }

    public function getState(): CircuitState    { return $this->state; }
    public function getFailureCount(): int       { return $this->failureCount; }

    /** Force timeout to test recovery (for testing only) */
    public function forceExpiry(): void { $this->lastFailureTime = 0; }
}

// ─── External Service Simulator ───────────────────────────────
class PaymentServiceClient {
    private int $callCount = 0;
    private int $failUntil;  // Fail for first N calls

    public function __construct(int $failUntil = 3) { $this->failUntil = $failUntil; }

    public function charge(float $amount): string {
        $this->callCount++;
        if ($this->callCount <= $this->failUntil) {
            throw new \RuntimeException("Payment service timeout (call #{$this->callCount})");
        }
        return "Payment of ₹{$amount} successful (call #{$this->callCount})";
    }
}

// ─── Service with Circuit Breaker ─────────────────────────────
class OrderService {
    public function __construct(
        private PaymentServiceClient $client,
        private CircuitBreaker       $cb
    ) {}

    public function processPayment(float $amount): void {
        try {
            $result = $this->cb->call(fn() => $this->client->charge($amount));
            echo "  ✓ {$result}\n";
        } catch (\RuntimeException $e) {
            echo "  ✗ {$e->getMessage()}\n";
        }
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C7. Circuit Breaker ===\n\n";

$client  = new PaymentServiceClient(failUntil: 3);
$cb      = new CircuitBreaker(failureThreshold: 3, timeoutSeconds: 2.0, halfOpenSuccesses: 2);
$service = new OrderService($client, $cb);

echo "--- Calls 1-3: service failing, circuit opens ---\n";
$service->processPayment(500);
$service->processPayment(600);
$service->processPayment(700);

echo "\n--- Call 4: circuit OPEN, fast-fail ---\n";
$service->processPayment(800);

echo "\n--- Simulate timeout expiry, then recover ---\n";
$cb->forceExpiry();
$service->processPayment(900);  // Half-open: test call — succeeds
$service->processPayment(1000); // Half-open: 2nd success → CLOSED
$service->processPayment(1100); // Normal again
echo "\nFinal circuit state: " . $cb->getState()->value . "\n";
