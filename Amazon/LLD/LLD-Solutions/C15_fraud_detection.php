<?php
/**
 * C15. FRAUD DETECTION ENGINE
 * ============================================================
 * PROBLEM: Score transactions in real-time based on rule-based
 * signals: velocity, device fingerprint, geo-anomaly, amount.
 * Block or flag suspicious transactions.
 *
 * PATTERNS:
 *  - Chain of Responsibility : FraudRules applied in sequence
 *  - Strategy                : RiskScoringStrategy (weighted sum, max)
 * ============================================================
 */

enum TransactionDecision: string { case ALLOW='Allow'; case REVIEW='Review'; case BLOCK='Block'; }

// ─── Transaction ──────────────────────────────────────────────
class Transaction {
    public readonly string $txId;
    public function __construct(
        public readonly string $userId,
        public readonly float  $amount,
        public readonly string $country,         // ISO 2-letter
        public readonly string $deviceId,
        public readonly string $ipAddress,
        public readonly string $merchantCategory  // e.g., 'travel', 'groceries'
    ) {
        $this->txId = uniqid('TX-');
    }
}

// ─── Fraud Signal ─────────────────────────────────────────────
class FraudSignal {
    public function __construct(
        public readonly string $ruleName,
        public readonly float  $score,      // 0.0 to 1.0
        public readonly string $reason
    ) {}
}

// ─── Abstract Fraud Rule (Chain of Responsibility) ────────────
abstract class FraudRule {
    private ?FraudRule $next = null;

    public function setNext(FraudRule $rule): FraudRule { $this->next = $rule; return $rule; }

    /** Each rule adds signals; pass to next rule as well */
    public function evaluate(Transaction $tx, UserProfile $profile, array &$signals): void {
        $this->check($tx, $profile, $signals);
        $this->next?->evaluate($tx, $profile, $signals);
    }

    abstract protected function check(Transaction $tx, UserProfile $profile, array &$signals): void;
}

// ─── User Profile ─────────────────────────────────────────────
class UserProfile {
    private array $txHistory  = [];
    private array $knownDevices = [];
    private array $knownCountries = [];
    public  int   $accountAgeDays = 365;

    public function __construct(public readonly string $userId) {}

    public function recordTransaction(Transaction $tx): void { $this->txHistory[] = $tx; }
    public function addKnownDevice(string $deviceId): void   { $this->knownDevices[]   = $deviceId; }
    public function addKnownCountry(string $country): void   { $this->knownCountries[] = $country; }

    public function getRecentTotal(int $windowMinutes = 60): float {
        // Simulate: sum all amounts (all assumed recent in this simulation)
        return array_sum(array_map(fn($t) => $t->amount, $this->txHistory));
    }

    public function isKnownDevice(string $deviceId): bool   { return in_array($deviceId, $this->knownDevices); }
    public function isKnownCountry(string $country): bool   { return in_array($country, $this->knownCountries); }
    public function getTransactionCount(): int               { return count($this->txHistory); }
}

// ─── Concrete Fraud Rules ─────────────────────────────────────
class HighAmountRule extends FraudRule {
    public function __construct(private float $threshold = 10000.0) {}
    protected function check(Transaction $tx, UserProfile $profile, array &$signals): void {
        if ($tx->amount > $this->threshold) {
            $signals[] = new FraudSignal('HighAmount', 0.7, "Amount ₹{$tx->amount} > threshold ₹{$this->threshold}");
        }
    }
}

class VelocityRule extends FraudRule {
    public function __construct(private float $maxWindowAmount = 5000.0) {}
    protected function check(Transaction $tx, UserProfile $profile, array &$signals): void {
        $total = $profile->getRecentTotal(60);
        if ($total + $tx->amount > $this->maxWindowAmount) {
            $ratio    = ($total + $tx->amount) / $this->maxWindowAmount;
            $score    = min(1.0, $ratio - 1.0);
            $signals[] = new FraudSignal('VelocityCheck', round($score, 2), "Velocity ₹" . round($total + $tx->amount) . " > limit");
        }
    }
}

class NewDeviceRule extends FraudRule {
    protected function check(Transaction $tx, UserProfile $profile, array &$signals): void {
        if (!$profile->isKnownDevice($tx->deviceId)) {
            $signals[] = new FraudSignal('NewDevice', 0.5, "Unknown device: {$tx->deviceId}");
        }
    }
}

class GeoAnomalyRule extends FraudRule {
    protected function check(Transaction $tx, UserProfile $profile, array &$signals): void {
        if (!$profile->isKnownCountry($tx->country)) {
            $signals[] = new FraudSignal('GeoAnomaly', 0.6, "Transaction from unknown country: {$tx->country}");
        }
    }
}

class NewAccountRule extends FraudRule {
    protected function check(Transaction $tx, UserProfile $profile, array &$signals): void {
        if ($profile->accountAgeDays < 30 && $tx->amount > 1000) {
            $signals[] = new FraudSignal('NewAccount', 0.8, "New account (<30d) with large transaction");
        }
    }
}

// ─── Risk Scorer ──────────────────────────────────────────────
class RiskScorer {
    /** Weighted sum capped at 1.0 */
    public function score(array $signals): float {
        if (empty($signals)) return 0.0;
        $total = array_sum(array_map(fn($s) => $s->score, $signals));
        return min(1.0, $total / max(1, count($signals)));
    }

    public function decide(float $score): TransactionDecision {
        return match(true) {
            $score >= 0.7 => TransactionDecision::BLOCK,
            $score >= 0.4 => TransactionDecision::REVIEW,
            default       => TransactionDecision::ALLOW,
        };
    }
}

// ─── Fraud Engine ─────────────────────────────────────────────
class FraudDetectionEngine {
    private FraudRule  $ruleChain;
    private RiskScorer $scorer;

    public function __construct() {
        $this->scorer = new RiskScorer();

        // Build chain
        $high     = new HighAmountRule(5000.0);
        $velocity = new VelocityRule(3000.0);
        $device   = new NewDeviceRule();
        $geo      = new GeoAnomalyRule();
        $newAcct  = new NewAccountRule();

        $high->setNext($velocity)->setNext($device)->setNext($geo)->setNext($newAcct);
        $this->ruleChain = $high;
    }

    public function evaluate(Transaction $tx, UserProfile $profile): array {
        $signals = [];
        $this->ruleChain->evaluate($tx, $profile, $signals);
        $score    = $this->scorer->score($signals);
        $decision = $this->scorer->decide($score);
        return compact('signals', 'score', 'decision');
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C15. Fraud Detection Engine ===\n\n";

$engine  = new FraudDetectionEngine();

// Normal user with history
$profile = new UserProfile('U001');
$profile->addKnownDevice('DEV-iPhone-001');
$profile->addKnownCountry('IN');
$profile->accountAgeDays = 730;

$transactions = [
    ['Normal groceries',   200,   'IN', 'DEV-iPhone-001'],
    ['Large travel spend', 8000,  'IN', 'DEV-iPhone-001'],
    ['Unknown device',     1500,  'IN', 'DEV-Android-999'],
    ['Foreign country',    2000,  'US', 'DEV-iPhone-001'],
];

foreach ($transactions as [$desc, $amount, $country, $device]) {
    $tx     = new Transaction('U001', $amount, $country, $device, '1.2.3.4', 'retail');
    $result = $engine->evaluate($tx, $profile);
    $profile->recordTransaction($tx);

    $decision = $result['decision']->value;
    $score    = round($result['score'], 2);
    echo "{$desc}:\n";
    echo "  Decision: {$decision} | Score: {$score}\n";
    foreach ($result['signals'] as $sig) {
        echo "  ⚡ {$sig->ruleName}: {$sig->reason}\n";
    }
    echo "\n";
}
