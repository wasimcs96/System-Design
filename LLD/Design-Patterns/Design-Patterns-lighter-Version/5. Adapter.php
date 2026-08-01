<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #5 — ADAPTER                         ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Structural Pattern                                 ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★☆                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You want to use an existing class (Adaptee), but its   │
 * │ interface is INCOMPATIBLE with what your system expects (Target).│
 * │ You cannot modify the Adaptee (it's a third-party library, or   │
 * │ legacy code, or you don't have access).                          │
 * │                                                                  │
 * │ Real-life analogy: A power adapter converts a 3-pin plug to a   │
 * │ 2-pin socket — same electricity, different interface.            │
 * │                                                                  │
 * │ Example:                                                         │
 * │  - Your app expects a Logger interface with log(string $msg)    │
 * │  - Third-party library has MonologLogger with addRecord(...)    │
 * │  - Adapter wraps MonologLogger and implements your Logger        │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client ──► Target Interface                                     │
 * │                   │                                              │
 * │                   └── Adapter ──wraps──► Adaptee               │
 * │                         (translates calls)                       │
 * │                                                                  │
 * │  Client only knows Target interface.                             │
 * │  Adapter translates Target calls into Adaptee calls.            │
 * │  Adaptee never changes.                                          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE ADAPTER                              │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define the Target interface (what your system expects)  │
 * │ STEP 2: Identify the Adaptee (incompatible existing class)       │
 * │ STEP 3: Create Adapter class that:                               │
 * │   a) Implements Target interface                                 │
 * │   b) Wraps the Adaptee (composition, preferred) OR               │
 * │      Extends the Adaptee (class adapter, less common)            │
 * │ STEP 4: Each method in Adapter translates to Adaptee's method   │
 * │ STEP 5: Client uses Target interface — never sees Adaptee        │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Payment Gateway Adapter
// Your system expects: PaymentProcessor interface
// Third-party SDK: Stripe SDK has its own API
// ═══════════════════════════════════════════════════════════════

// STEP 1: Target interface — what YOUR system expects
interface PaymentProcessor
{
    public function charge(string $customerId, float $amount, string $currency): string;
    public function refund(string $transactionId, float $amount): bool;
}

// STEP 2: Adaptee — Stripe SDK (third-party, cannot be modified)
class StripeSDK
{
    public function createCharge(array $params): array
    {
        // Simulate Stripe's actual API response structure
        echo "  [Stripe SDK] createCharge called with: " . json_encode($params) . "\n";
        return [
            'id'     => 'ch_' . substr(md5(microtime()), 0, 12),
            'status' => 'succeeded',
            'amount' => $params['amount'],
        ];
    }

    public function createRefund(string $chargeId, int $amountCents): array
    {
        echo "  [Stripe SDK] createRefund: chargeId=$chargeId, amount=" . ($amountCents/100) . "\n";
        return ['id' => 're_' . substr(md5($chargeId), 0, 8), 'status' => 'succeeded'];
    }
}

// Another Adaptee — Razorpay SDK (different API)
class RazorpaySDK
{
    public function capturePayment(string $paymentId, int $amountPaise): array
    {
        echo "  [Razorpay SDK] capturePayment: id=$paymentId, paise=$amountPaise\n";
        return ['razorpay_payment_id' => $paymentId, 'status' => 'captured'];
    }

    public function initiateRefund(string $paymentId, array $refundData): array
    {
        echo "  [Razorpay SDK] refund: id=$paymentId\n";
        return ['id' => 'rfnd_' . substr(md5($paymentId), 0, 8), 'status' => 'processed'];
    }
}

// STEP 3: Stripe Adapter — translates PaymentProcessor → Stripe SDK
class StripeAdapter implements PaymentProcessor
{
    // Composition: wrap the Adaptee
    public function __construct(private StripeSDK $stripe) {}

    public function charge(string $customerId, float $amount, string $currency): string
    {
        // Translate: our interface uses float (e.g., 10.99)
        //            Stripe uses integer cents (e.g., 1099)
        $response = $this->stripe->createCharge([
            'customer' => $customerId,
            'amount'   => (int)($amount * 100), // Convert to cents
            'currency' => strtolower($currency),
        ]);
        return $response['id']; // Return our standardized transaction ID
    }

    public function refund(string $transactionId, float $amount): bool
    {
        $response = $this->stripe->createRefund(
            $transactionId,
            (int)($amount * 100) // Stripe also uses cents
        );
        return $response['status'] === 'succeeded';
    }
}

// STEP 3: Razorpay Adapter — translates PaymentProcessor → Razorpay SDK
class RazorpayAdapter implements PaymentProcessor
{
    public function __construct(private RazorpaySDK $razorpay) {}

    public function charge(string $customerId, float $amount, string $currency): string
    {
        $paymentId = 'pay_' . substr(md5($customerId . $amount), 0, 12);
        $response  = $this->razorpay->capturePayment(
            $paymentId,
            (int)($amount * 100) // Razorpay uses paise (1/100 of INR)
        );
        return $response['razorpay_payment_id'];
    }

    public function refund(string $transactionId, float $amount): bool
    {
        $response = $this->razorpay->initiateRefund($transactionId, [
            'amount' => (int)($amount * 100),
            'notes'  => ['reason' => 'customer_request'],
        ]);
        return $response['status'] === 'processed';
    }
}

// STEP 5: Client — only uses PaymentProcessor interface
class OrderService
{
    public function __construct(private PaymentProcessor $payment) {}

    public function placeOrder(string $customerId, float $total): string
    {
        echo "  Charging customer $customerId for ₹$total\n";
        $txId = $this->payment->charge($customerId, $total, 'INR');
        echo "  Transaction ID: $txId\n";
        return $txId;
    }

    public function cancelOrder(string $txId, float $amount): void
    {
        $success = $this->payment->refund($txId, $amount);
        echo "  Refund " . ($success ? "successful ✓" : "failed ✗") . "\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Legacy Cache → PSR-16 Adapter
// Your new code expects PSR-16 CacheInterface
// Old code uses a custom ApcCache class
// ═══════════════════════════════════════════════════════════════

interface CacheInterface  // Target (simplified PSR-16)
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function has(string $key): bool;
}

class LegacyApcCache  // Adaptee — old API, cannot change
{
    private array $store = []; // Simulate APC

    public function fetch(string $key): mixed      { return $this->store[$key] ?? false; }
    public function store(string $key, mixed $val, int $ttl = 3600): bool
    {
        $this->store[$key] = ['val' => $val, 'exp' => time() + $ttl];
        return true;
    }
    public function remove(string $key): bool      { unset($this->store[$key]); return true; }
    public function exists(string $key): bool
    {
        return isset($this->store[$key]) && $this->store[$key]['exp'] > time();
    }
}

class LegacyCacheAdapter implements CacheInterface  // Adapter
{
    public function __construct(private LegacyApcCache $legacy) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $val = $this->legacy->fetch($key);
        return ($val !== false) ? $val['val'] : $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->legacy->store($key, $value, $ttl);
    }

    public function delete(string $key): bool { return $this->legacy->remove($key); }
    public function has(string $key): bool    { return $this->legacy->exists($key); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== ADAPTER PATTERN DEMO ===\n\n";

echo "--- Stripe Adapter ---\n";
$stripeService = new OrderService(new StripeAdapter(new StripeSDK()));
$txId = $stripeService->placeOrder('cust_abc123', 1499.99);
$stripeService->cancelOrder($txId, 1499.99);

echo "\n--- Razorpay Adapter ---\n";
$razorpayService = new OrderService(new RazorpayAdapter(new RazorpaySDK()));
$txId2 = $razorpayService->placeOrder('cust_xyz789', 899.50);

echo "\n--- Legacy Cache Adapter ---\n";
$cache = new LegacyCacheAdapter(new LegacyApcCache());
$cache->set('user:1', ['name' => 'Alice', 'age' => 30], 300);
$user = $cache->get('user:1');
echo "Cached user: " . json_encode($user) . "\n";
echo "Has 'user:1': " . ($cache->has('user:1') ? 'Yes ✓' : 'No') . "\n";
$cache->delete('user:1');
echo "After delete: " . ($cache->has('user:1') ? 'Yes' : 'No ✓') . "\n";
echo "Default for missing: " . ($cache->get('user:999', 'NOT FOUND')) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Adapter pattern?                                 │
 * │ A: Converts the interface of a class into another interface that │
 * │    clients expect. Lets incompatible interfaces work together    │
 * │    without modifying the original classes.                       │
 * │                                                                  │
 * │ Q2: What are the two types of Adapters?                          │
 * │ A: Object Adapter (composition): Adapter wraps Adaptee with a   │
 * │    `private $adaptee` field. Preferred — more flexible.         │
 * │    Class Adapter (inheritance): Adapter extends both Target and  │
 * │    Adaptee. PHP doesn't support multiple inheritance — use a     │
 * │    trait or use object adapter instead.                          │
 * │                                                                  │
 * │ Q3: Adapter vs Facade — what's the difference?                   │
 * │ A: Adapter: Changes the interface to match what client expects.  │
 * │    Facade: Simplifies a complex interface — doesn't change it.  │
 * │    Adapter: 1-to-1 interface mapping.                            │
 * │    Facade: N-to-1 simplification.                                │
 * │                                                                  │
 * │ Q4: Adapter vs Decorator — what's the difference?                │
 * │ A: Adapter: Changes the interface (makes incompatible → compat.) │
 * │    Decorator: Keeps SAME interface, adds new behavior.           │
 * │                                                                  │
 * │ Q5: When would you use Adapter in a real project?               │
 * │ A: - Integrating third-party libraries (Stripe, Razorpay)       │
 * │    - Migrating from old to new API without breaking existing code│
 * │    - Making legacy code work with modern interfaces (PSR-16)    │
 * │    - Testing: Wrap real external services with test adapters     │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Data type conversion (cents ↔ float, paise ↔ INR)            │
 * │ ✓ Error mapping: Adaptee's exceptions → Target's exceptions      │
 * │ ✓ Performance: Adapter adds one layer — keep it thin            │
 * └─────────────────────────────────────────────────────────────────┘
 */
