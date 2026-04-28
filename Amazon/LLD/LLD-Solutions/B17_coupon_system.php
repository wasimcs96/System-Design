<?php
/**
 * B17. COUPON / DISCOUNT CODE SYSTEM
 * ============================================================
 * PROBLEM: Create coupons with usage limits, expiry, eligibility
 * rules, and stacking behavior.
 *
 * PATTERNS:
 *  - Strategy : CouponType (percentage, flat, BOGO, free-shipping)
 *  - Chain of Responsibility: Coupon validators
 * ============================================================
 */

enum CouponType: string { case PERCENTAGE='Percentage'; case FLAT='Flat'; case FREE_SHIPPING='FreeShipping'; }

// ─── Coupon ───────────────────────────────────────────────────
class Coupon {
    private int $usageCount = 0;

    public function __construct(
        public readonly string     $code,
        public readonly CouponType $type,
        public readonly float      $value,         // % or flat amount
        public readonly float      $minOrderValue = 0,
        public readonly int        $maxUsage      = PHP_INT_MAX,
        public readonly ?\DateTime $expiresAt     = null,
        public readonly ?string    $applicableFor = null  // null=all, 'new_user', etc.
    ) {}

    public function isValid(float $orderTotal, string $userId): bool {
        if ($this->expiresAt && new \DateTime() > $this->expiresAt) return false;
        if ($this->usageCount >= $this->maxUsage) return false;
        if ($orderTotal < $this->minOrderValue) return false;
        return true;
    }

    public function apply(float $orderTotal): float {
        return match($this->type) {
            CouponType::PERCENTAGE    => $orderTotal * (1 - $this->value / 100),
            CouponType::FLAT          => max(0, $orderTotal - $this->value),
            CouponType::FREE_SHIPPING => $orderTotal, // Handled at shipping level
        };
    }

    public function redeem(): void { $this->usageCount++; }
    public function getUsageCount(): int { return $this->usageCount; }
}

// ─── Coupon Repository ────────────────────────────────────────
class CouponRepository {
    /** @var array<string,Coupon> code → Coupon */
    private array $coupons = [];

    public function save(Coupon $c): void      { $this->coupons[strtoupper($c->code)] = $c; }
    public function find(string $code): ?Coupon { return $this->coupons[strtoupper($code)] ?? null; }
}

// ─── Coupon Service ───────────────────────────────────────────
class CouponService {
    public function __construct(private CouponRepository $repo) {}

    public function apply(string $code, float $orderTotal, string $userId): array {
        $coupon = $this->repo->find($code);
        if (!$coupon) return ['success' => false, 'message' => 'Invalid coupon code', 'discount' => 0];

        if (!$coupon->isValid($orderTotal, $userId)) {
            $reason = '';
            if ($coupon->expiresAt && new \DateTime() > $coupon->expiresAt) $reason = 'Coupon expired';
            elseif ($orderTotal < $coupon->minOrderValue) $reason = "Min order ₹{$coupon->minOrderValue} required";
            else $reason = 'Usage limit exceeded';
            return ['success' => false, 'message' => $reason, 'discount' => 0];
        }

        $discountedTotal = $coupon->apply($orderTotal);
        $savings         = round($orderTotal - $discountedTotal, 2);
        $coupon->redeem();

        return [
            'success'   => true,
            'message'   => "Coupon applied: {$coupon->type->value}",
            'discount'  => $savings,
            'new_total' => $discountedTotal,
        ];
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B17. Coupon System ===\n\n";

$repo = new CouponRepository();
$repo->save(new Coupon('SAVE10',  CouponType::PERCENTAGE, 10, 500));
$repo->save(new Coupon('FLAT200', CouponType::FLAT, 200, 1000));
$repo->save(new Coupon('ONCE',    CouponType::PERCENTAGE, 20, 0, 1));

$service = new CouponService($repo);

$cases = [
    ['SAVE10',  800,  'user1'],
    ['FLAT200', 1500, 'user2'],
    ['FLAT200', 300,  'user3'],  // Below min order
    ['ONCE',    500,  'user1'],
    ['ONCE',    500,  'user2'],  // Limit exceeded
    ['INVALID', 500,  'user1'],
];

foreach ($cases as [$code, $total, $uid]) {
    $result = $service->apply($code, $total, $uid);
    $status = $result['success'] ? "✓" : "✗";
    echo "  $status [{$code}] ₹{$total} → {$result['message']}";
    if ($result['success']) echo " | Saved: ₹{$result['discount']} | New total: ₹{$result['new_total']}";
    echo "\n";
}
