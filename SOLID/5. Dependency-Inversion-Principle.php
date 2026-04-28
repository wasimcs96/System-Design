<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║      SOLID PRINCIPLE #5 — DEPENDENCY INVERSION PRINCIPLE (DIP)  ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  ACRONYM    : D in SOLID                                         ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★★ (Foundation of Dependency Injection / DI)   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DEFINITION                                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ TWO rules:                                                       │
 * │  1. HIGH-LEVEL modules should NOT depend on LOW-LEVEL modules.  │
 * │     Both should depend on ABSTRACTIONS (interfaces).             │
 * │  2. ABSTRACTIONS should NOT depend on DETAILS.                  │
 * │     DETAILS (concrete classes) should depend on abstractions.   │
 * │                                                                  │
 * │ In plain English:                                                │
 * │  Your business logic (OrderService) should NOT directly create  │
 * │  or reference a concrete class (MySQLDatabase, StripePayment).  │
 * │  Instead, depend on an interface (DatabaseInterface,            │
 * │  PaymentGateway). The concrete class is injected from outside.  │
 * │                                                                  │
 * │ TERMINOLOGY:                                                     │
 * │  • Dependency Inversion — the PRINCIPLE (SOLID)                  │
 * │  • Dependency Injection — a TECHNIQUE to implement DIP           │
 * │    (inject via constructor, setter, or method parameter)        │
 * │  • IoC Container — a FRAMEWORK feature (Laravel, Symfony)       │
 * │    that automates dependency injection                           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM — BEFORE vs AFTER                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ❌ BEFORE (violates DIP — high level depends on low level):     │
 * │                                                                  │
 * │  OrderService ──────► MySQLDatabase   (concrete)                │
 * │  (high-level)  ──────► StripePayment  (concrete)                │
 * │                ──────► SmtpMailer     (concrete)                │
 * │                                                                  │
 * │  ✅ AFTER (DIP — both depend on abstraction):                    │
 * │                                                                  │
 * │  OrderService ──────► DatabaseInterface  (abstraction)          │
 * │  (high-level)  ──────► PaymentGateway    (abstraction)          │
 * │                ──────► MailerInterface   (abstraction)          │
 * │                             ▲                  ▲                │
 * │                    MySQLDatabase          StripePayment         │
 * │                    PostgresDatabase       RazorpayPayment       │
 * │                   (low-level, details)   (low-level, details)   │
 * │                                                                  │
 * │  The DEPENDENCY ARROW is INVERTED — low-level now depends on    │
 * │  the abstraction, not the other way round.                       │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO APPLY DIP                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Identify high-level class that creates low-level objects │
 * │         (look for `new ConcreteClass()` inside business logic)  │
 * │ STEP 2: Define an interface for each dependency                  │
 * │ STEP 3: Make low-level classes implement those interfaces        │
 * │ STEP 4: Change high-level class to depend on the interface       │
 * │ STEP 5: Inject the concrete dependency from outside              │
 * │         (constructor injection is the most common + testable)   │
 * └─────────────────────────────────────────────────────────────────┘
 */

echo "=== DEPENDENCY INVERSION PRINCIPLE ===\n\n";

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION: OrderService directly depends on concrete classes
// ═══════════════════════════════════════════════════════════════
class BadMySQLDatabase
{
    public function save(array $data): bool
    {
        echo "  [MySQL] INSERT INTO orders ...\n";
        return true;
    }
}

class BadStripePayment
{
    public function charge(float $amount): string
    {
        echo "  [Stripe] Charging ₹$amount via Stripe SDK\n";
        return 'stripe_' . uniqid();
    }
}

class BadOrderService
{
    // ❌ Creates concrete dependencies itself — tightly coupled
    private BadMySQLDatabase $db;
    private BadStripePayment  $payment;

    public function __construct()
    {
        // High-level module knows about AND creates low-level modules
        $this->db      = new BadMySQLDatabase(); // ← tight coupling
        $this->payment = new BadStripePayment(); // ← tight coupling
    }

    public function placeOrder(array $items, float $total): void
    {
        $txnId = $this->payment->charge($total);
        $this->db->save(['items' => $items, 'total' => $total, 'txn' => $txnId]);
        echo "  [BadOrderService] Order placed. TxnID: $txnId\n";
    }
    // PROBLEMS:
    // • To use PostgreSQL instead, must modify OrderService
    // • To use Razorpay instead, must modify OrderService
    // • Unit testing requires actual MySQL + Stripe connections!
    // • Multiple reasons to change → also violates SRP
}

echo "--- ❌ DIP Violation ---\n";
$badService = new BadOrderService();
$badService->placeOrder(['Laptop', 'Mouse'], 76200.00);

// ═══════════════════════════════════════════════════════════════
// ✅ CORRECT: High-level depends on abstractions; details injected
// ═══════════════════════════════════════════════════════════════

// STEP 2 & 3: Define interfaces (abstractions)
interface OrderRepository
{
    public function save(array $orderData): int; // Returns new order ID
    public function findById(int $id): ?array;
    public function updateStatus(int $id, string $status): void;
}

interface PaymentGateway
{
    public function charge(float $amount, string $currency): string; // Returns txn ID
    public function refund(string $transactionId, float $amount): bool;
}

interface OrderMailer
{
    public function sendConfirmation(array $order, string $customerEmail): void;
    public function sendShippingUpdate(int $orderId, string $trackingCode): void;
}

// ─── Low-level implementations ────────────────────────────────

// MySQL implementation
class MySQLOrderRepository implements OrderRepository
{
    public function save(array $data): int
    {
        echo "  [MySQL] INSERT INTO orders: total=₹{$data['total']}\n";
        return rand(1000, 9999); // Simulated order ID
    }

    public function findById(int $id): ?array
    {
        echo "  [MySQL] SELECT * FROM orders WHERE id=$id\n";
        return ['id' => $id, 'status' => 'pending', 'total' => 5000.00];
    }

    public function updateStatus(int $id, string $status): void
    {
        echo "  [MySQL] UPDATE orders SET status='$status' WHERE id=$id\n";
    }
}

// Redis + MySQL fallback implementation (drop-in replacement)
class CachedOrderRepository implements OrderRepository
{
    private array $cache = [];

    public function __construct(private OrderRepository $inner) {}

    public function save(array $data): int
    {
        $id = $this->inner->save($data);
        $this->cache[$id] = $data; // Cache the newly saved order
        return $id;
    }

    public function findById(int $id): ?array
    {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->inner->findById($id);
            echo "  [Cache] Stored order $id in cache\n";
        } else {
            echo "  [Cache] HIT for order $id\n";
        }
        return $this->cache[$id];
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->inner->updateStatus($id, $status);
        unset($this->cache[$id]); // Invalidate cache on update
    }
}

// Stripe payment gateway
class StripeGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): string
    {
        echo "  [Stripe] Charging {$currency}₹" . number_format($amount, 2) . "\n";
        return 'stripe_' . uniqid();
    }

    public function refund(string $txnId, float $amount): bool
    {
        echo "  [Stripe] Refunding ₹" . number_format($amount, 2) . " for $txnId\n";
        return true;
    }
}

// Razorpay — drop-in replacement, zero changes to OrderService
class RazorpayGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency): string
    {
        echo "  [Razorpay] Charging {$currency}₹" . number_format($amount, 2) . "\n";
        return 'rpay_' . uniqid();
    }

    public function refund(string $txnId, float $amount): bool
    {
        echo "  [Razorpay] Refunding ₹" . number_format($amount, 2) . " for $txnId\n";
        return true;
    }
}

// SMTP mailer
class SmtpMailer implements OrderMailer
{
    public function sendConfirmation(array $order, string $email): void
    {
        echo "  [SMTP] Confirmation sent to $email for order #{$order['id']}\n";
    }

    public function sendShippingUpdate(int $orderId, string $tracking): void
    {
        echo "  [SMTP] Shipping update sent. Order #$orderId, Track: $tracking\n";
    }
}

// ─── High-level module: depends ONLY on interfaces ────────────

// STEP 4 & 5: Constructor injection — dependencies come from OUTSIDE
class OrderService
{
    // All dependencies are INTERFACES — knows nothing about MySQL, Stripe, etc.
    public function __construct(
        private OrderRepository $repository, // Could be MySQL, Postgres, Redis+MySQL
        private PaymentGateway  $payment,    // Could be Stripe, Razorpay, PayPal
        private OrderMailer     $mailer      // Could be SMTP, SendGrid, SES
    ) {}

    public function placeOrder(
        string $customerId,
        string $customerEmail,
        array  $items,
        float  $total
    ): array {
        echo "\n  [OrderService] Processing order for $customerEmail...\n";

        // Step 1: Charge payment
        $txnId = $this->payment->charge($total, 'INR');

        // Step 2: Persist order
        $orderId = $this->repository->save([
            'customer_id' => $customerId,
            'items'       => $items,
            'total'       => $total,
            'txn_id'      => $txnId,
            'status'      => 'confirmed',
        ]);

        $order = ['id' => $orderId, 'total' => $total, 'txn_id' => $txnId];

        // Step 3: Notify customer
        $this->mailer->sendConfirmation($order, $customerEmail);

        echo "  [OrderService] Order #$orderId placed successfully ✓\n";
        return $order;
    }

    public function cancelOrder(int $orderId, float $amount, string $txnId): void
    {
        $this->repository->updateStatus($orderId, 'cancelled');
        $this->payment->refund($txnId, $amount);
        echo "  [OrderService] Order #$orderId cancelled ✓\n";
    }
}

echo "\n--- ✅ DIP Compliant: with Stripe + MySQL ---\n";

// WIRING — happens in bootstrap/container, not in business logic
$mysql   = new MySQLOrderRepository();
$cached  = new CachedOrderRepository($mysql); // Decorator wrapping MySQL
$stripe  = new StripeGateway();
$mailer  = new SmtpMailer();

$orderService = new OrderService($cached, $stripe, $mailer);
$order = $orderService->placeOrder(
    'CUST-001',
    'alice@example.com',
    ['Laptop' => 75000, 'USB Mouse' => 1200],
    76200.00
);

// Fetch again — served from cache
$cached->findById($order['id']);

echo "\n--- ✅ Swap to Razorpay — OrderService untouched ---\n";

// Just change the wiring — OrderService code is IDENTICAL
$razorpay     = new RazorpayGateway(); // New implementation
$orderService2 = new OrderService($cached, $razorpay, $mailer); // Injected

$orderService2->placeOrder(
    'CUST-002',
    'bob@example.com',
    ['Keyboard' => 3500],
    3500.00
);

// ─── TESTING EXAMPLE: Using mock/stub dependencies ────────────────────────────

echo "\n--- Unit Test Scenario: In-Memory Stubs ---\n";

// For unit testing: in-memory implementations (no DB, no payment gateway!)
class InMemoryOrderRepository implements OrderRepository
{
    private array $store = [];
    private int   $nextId = 1;

    public function save(array $data): int
    {
        $id = $this->nextId++;
        $this->store[$id] = array_merge($data, ['id' => $id]);
        echo "  [InMemory] Order saved with id=$id\n";
        return $id;
    }

    public function findById(int $id): ?array { return $this->store[$id] ?? null; }
    public function updateStatus(int $id, string $s): void { $this->store[$id]['status'] = $s; }
}

class FakePaymentGateway implements PaymentGateway
{
    public bool $shouldFail = false;
    public array $charges   = [];

    public function charge(float $amount, string $currency): string
    {
        if ($this->shouldFail) throw new \RuntimeException("Payment declined");
        $this->charges[] = $amount;
        echo "  [FakePayment] Approved: $currency₹$amount\n";
        return 'fake_' . count($this->charges);
    }

    public function refund(string $txnId, float $amount): bool
    {
        echo "  [FakePayment] Refund processed for $txnId\n";
        return true;
    }
}

class NullMailer implements OrderMailer
{
    public array $sent = [];
    public function sendConfirmation(array $order, string $email): void
    {
        $this->sent[] = $email;
        echo "  [NullMailer] Email queued to $email\n";
    }
    public function sendShippingUpdate(int $orderId, string $tracking): void {}
}

$testRepo    = new InMemoryOrderRepository();
$fakePayment = new FakePaymentGateway();
$nullMailer  = new NullMailer();

$testService = new OrderService($testRepo, $fakePayment, $nullMailer);
$testOrder   = $testService->placeOrder('TEST-1', 'test@test.com', ['Item'], 500.0);

echo "  Charges recorded: " . implode(', ', $fakePayment->charges) . "\n";
echo "  Emails sent to: " . implode(', ', $nullMailer->sent) . "\n";
echo "  Total charges: " . count($fakePayment->charges) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Define the Dependency Inversion Principle.                   │
 * │ A: (1) High-level modules should not depend on low-level modules;│
 * │    both should depend on abstractions. (2) Abstractions should   │
 * │    not depend on details; details should depend on abstractions. │
 * │                                                                  │
 * │ Q2: What's the difference between DIP, DI, and IoC?             │
 * │ A: DIP is the PRINCIPLE: depend on abstractions, not concretions.│
 * │    Dependency Injection (DI) is a PATTERN/technique that        │
 * │    implements DIP by passing dependencies from outside.          │
 * │    Inversion of Control (IoC) is a broader design principle:    │
 * │    control flow is inverted — the framework calls your code     │
 * │    (not your code calling the framework). DI containers (Laravel │
 * │    IoC, Symfony DI) automate the wiring.                        │
 * │                                                                  │
 * │ Q3: What's the key benefit of DIP for testing?                  │
 * │ A: When OrderService depends on interfaces, you can inject      │
 * │    in-memory stubs (FakePaymentGateway, NullMailer) in tests.   │
 * │    No real DB or payment gateway needed. Tests are fast, isolated│
 * │    and deterministic.                                            │
 * │                                                                  │
 * │ Q4: Why is constructor injection preferred over setter injection?│
 * │ A: Constructor injection ensures the object is ALWAYS in a valid │
 * │    state after creation — dependencies can't be null. Setter     │
 * │    injection allows partial construction. Constructor injection  │
 * │    also makes dependencies explicit (visible in method signature)│
 * │                                                                  │
 * │ Q5: When would you NOT apply DIP?                               │
 * │ A: Very simple scripts, one-off utilities, when you're 100%     │
 * │    certain a concrete class will never change (e.g., PHP's own  │
 * │    built-in classes). Over-abstracting adds complexity without   │
 * │    benefit. Apply DIP at architectural boundaries.              │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ New + DIP: `new` in factory/container is fine.                │
 * │   Avoid `new ConcreteClass()` in the middle of business logic.  │
 * │ ✓ Service Locator: also decouples, but hides dependencies.      │
 * │   Prefer constructor injection (explicit) over service locator. │
 * └─────────────────────────────────────────────────────────────────┘
 */
