<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #6 — FACADE                          ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Structural Pattern                                 ║
 * ║  DIFFICULTY : Easy                                               ║
 * ║  FREQUENCY  : ★★★★☆                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: A complex subsystem has many classes and requires a     │
 * │ specific initialization sequence. The client must know about     │
 * │ ALL subsystems and call them in the right order.                  │
 * │                                                                  │
 * │ Without Facade:                                                  │
 * │   $codec   = new VideoCodec('H264');                             │
 * │   $audio   = new AudioExtractor($file);                          │
 * │   $buffer  = new BitrateReader->read($file, 128);               │
 * │   $output  = $codec->encode($buffer, $audio);                   │
 * │   ... 10 more steps the client must remember and do correctly    │
 * │                                                                  │
 * │ With Facade:                                                     │
 * │   $converter->convertVideo($file, 'mp4');  // 1 call, done ✓   │
 * │                                                                  │
 * │ Facade provides a SIMPLE, HIGH-LEVEL interface to a complex      │
 * │ subsystem. The subsystem still exists — Facade just hides it.   │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client ──────► Facade ──┬──► SubsystemA                        │
 * │    (simple                │──► SubsystemB                        │
 * │     interface)            └──► SubsystemC                        │
 * │                                                                   │
 * │  Client doesn't know A, B, C exist.                              │
 * │  Facade coordinates them and exposes one clean method.          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE FACADE                               │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Identify complex subsystem classes                       │
 * │ STEP 2: Create Facade class that holds references to subsystems  │
 * │ STEP 3: Add high-level methods that orchestrate subsystems       │
 * │ STEP 4: Client only interacts with Facade                        │
 * │ STEP 5 (optional): Subsystems can still be used directly if needed│
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// REAL-WORLD EXAMPLE: E-commerce Order Placement
//
// Subsystems: InventoryService, PaymentService, ShippingService,
//             NotificationService, InvoiceService
// Facade: OrderFacade provides placeOrder() — one call does it all
// ═══════════════════════════════════════════════════════════════

// ── STEP 1: Complex Subsystem Classes ───────────────────────────────────────

class InventoryService
{
    private array $stock = ['PROD-001' => 10, 'PROD-002' => 5, 'PROD-003' => 0];

    public function checkAvailability(string $productId, int $qty): bool
    {
        $available = $this->stock[$productId] ?? 0;
        echo "  [Inventory] Product $productId: $available available, need $qty\n";
        return $available >= $qty;
    }

    public function reserve(string $productId, int $qty): void
    {
        if (!isset($this->stock[$productId])) return;
        $this->stock[$productId] -= $qty;
        echo "  [Inventory] Reserved $qty of $productId (remaining: {$this->stock[$productId]})\n";
    }

    public function release(string $productId, int $qty): void
    {
        $this->stock[$productId] = ($this->stock[$productId] ?? 0) + $qty;
        echo "  [Inventory] Released $qty of $productId\n";
    }
}

class PaymentService
{
    public function authorize(string $customerId, float $amount): ?string
    {
        echo "  [Payment] Authorizing ₹$amount for customer $customerId\n";
        if ($amount <= 0) return null;
        return 'AUTH_' . strtoupper(substr(md5($customerId . $amount), 0, 8));
    }

    public function capture(string $authId): string
    {
        echo "  [Payment] Capturing authorization $authId\n";
        return 'TXN_' . strtoupper(substr(md5($authId), 0, 10));
    }

    public function void(string $authId): void
    {
        echo "  [Payment] Voiding authorization $authId\n";
    }
}

class ShippingService
{
    public function calculateRate(string $address, float $weightKg): float
    {
        $baseRate = 50.0;
        return round($baseRate + ($weightKg * 15), 2);
    }

    public function createShipment(string $orderId, string $address): string
    {
        $tracking = 'TRACK' . strtoupper(substr(md5($orderId), 0, 8));
        echo "  [Shipping] Created shipment for order $orderId → $tracking\n";
        return $tracking;
    }
}

class NotificationService
{
    public function sendOrderConfirmation(string $email, string $orderId): void
    {
        echo "  [Notification] Order confirmation sent to $email (order: $orderId)\n";
    }

    public function sendShippingUpdate(string $email, string $tracking): void
    {
        echo "  [Notification] Shipping update sent to $email (tracking: $tracking)\n";
    }

    public function sendFailureAlert(string $email, string $reason): void
    {
        echo "  [Notification] Failure alert sent to $email: $reason\n";
    }
}

class InvoiceService
{
    public function generate(string $orderId, float $total): string
    {
        $invoiceId = 'INV-' . strtoupper(substr(md5($orderId), 0, 6));
        echo "  [Invoice] Generated $invoiceId for order $orderId (₹$total)\n";
        return $invoiceId;
    }
}

// ── STEP 2–4: Facade ─────────────────────────────────────────────────────────

class OrderResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $orderId       = '',
        public readonly string $transactionId = '',
        public readonly string $trackingNo    = '',
        public readonly string $invoiceId     = '',
        public readonly string $errorMessage  = ''
    ) {}
}

class OrderFacade
{
    /**
     * Constructor injection — Facade delegates to subsystems.
     * This makes it testable (inject mocks) and loosely coupled.
     */
    public function __construct(
        private InventoryService    $inventory,
        private PaymentService      $payment,
        private ShippingService     $shipping,
        private NotificationService $notification,
        private InvoiceService      $invoice
    ) {}

    /**
     * HIGH-LEVEL METHOD — Client calls this ONE method.
     * Internally, it orchestrates 6 subsystems in the right order.
     * Client has NO idea any of these subsystems exist.
     *
     * Order placement flow:
     *  1. Check inventory
     *  2. Authorize payment
     *  3. Reserve inventory
     *  4. Capture payment
     *  5. Create shipment
     *  6. Generate invoice
     *  7. Send confirmation
     */
    public function placeOrder(
        string $customerId,
        string $email,
        string $productId,
        int    $qty,
        float  $amount,
        string $shippingAddress
    ): OrderResult {
        $orderId = 'ORD-' . strtoupper(substr(md5($customerId . time()), 0, 8));
        echo "  [Facade] Placing order $orderId...\n";

        // Step 1: Check inventory
        if (!$this->inventory->checkAvailability($productId, $qty)) {
            $this->notification->sendFailureAlert($email, "Product $productId out of stock");
            return new OrderResult(false, errorMessage: "Insufficient stock for $productId");
        }

        // Step 2: Authorize payment (don't charge yet)
        $authId = $this->payment->authorize($customerId, $amount);
        if (!$authId) {
            return new OrderResult(false, errorMessage: "Payment authorization failed");
        }

        // Step 3: Reserve inventory (reduces stock count)
        $this->inventory->reserve($productId, $qty);

        // Step 4: Capture payment (now actually charge)
        $txId = $this->payment->capture($authId);

        // Step 5: Create shipment
        $trackingNo = $this->shipping->createShipment($orderId, $shippingAddress);

        // Step 6: Generate invoice
        $invoiceId = $this->invoice->generate($orderId, $amount);

        // Step 7: Send confirmation
        $this->notification->sendOrderConfirmation($email, $orderId);

        return new OrderResult(
            success:       true,
            orderId:       $orderId,
            transactionId: $txId,
            trackingNo:    $trackingNo,
            invoiceId:     $invoiceId
        );
    }

    /**
     * CANCEL ORDER — another high-level method, hides complex rollback
     */
    public function cancelOrder(string $orderId, string $authId, string $email,
                                string $productId, int $qty): void
    {
        echo "  [Facade] Cancelling order $orderId...\n";
        $this->payment->void($authId);
        $this->inventory->release($productId, $qty);
        $this->notification->sendFailureAlert($email, "Order $orderId cancelled");
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== FACADE PATTERN DEMO ===\n\n";

// Compose subsystems
$facade = new OrderFacade(
    new InventoryService(),
    new PaymentService(),
    new ShippingService(),
    new NotificationService(),
    new InvoiceService()
);

echo "--- Successful order ---\n";
$result = $facade->placeOrder(
    customerId:      'CUST-001',
    email:           'alice@example.com',
    productId:       'PROD-001',
    qty:             2,
    amount:          1998.00,
    shippingAddress: 'Koramangala, Bangalore'
);

if ($result->success) {
    echo "\n  Order placed successfully!\n";
    echo "  Order ID:    {$result->orderId}\n";
    echo "  Transaction: {$result->transactionId}\n";
    echo "  Tracking:    {$result->trackingNo}\n";
    echo "  Invoice:     {$result->invoiceId}\n";
}

echo "\n--- Out of stock order ---\n";
$result2 = $facade->placeOrder('CUST-002', 'bob@example.com', 'PROD-003', 1, 500.0, 'Mumbai');
if (!$result2->success) {
    echo "  Order failed: {$result2->errorMessage}\n";
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Facade pattern?                                  │
 * │ A: Provides a simplified interface to a complex subsystem.       │
 * │    The client uses ONE simple method; the Facade coordinates     │
 * │    multiple subsystems in the right order internally.            │
 * │                                                                  │
 * │ Q2: Does Facade prevent access to the subsystem?                 │
 * │ A: No! Facade is a convenience, not a restriction. Advanced      │
 * │    clients can still use subsystems directly if needed.          │
 * │                                                                  │
 * │ Q3: Facade vs Adapter vs Decorator?                              │
 * │ A: Facade:    Simplifies N subsystems into 1 interface.         │
 * │    Adapter:   Translates 1 interface into another compatible one.│
 * │    Decorator: Wraps 1 object to ADD behavior, same interface.    │
 * │                                                                  │
 * │ Q4: What SOLID principle does Facade support?                    │
 * │ A: Single Responsibility: Client only deals with one thing       │
 * │    (order placement), not 6 subsystems.                          │
 * │    Dependency Inversion: Client depends on Facade abstraction,  │
 * │    not concrete subsystems.                                      │
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: Laravel's `Mail::send()` — hides SMTP, queue, template engine │
 * │    Laravel's `DB::table()->get()` — hides connection, query,     │
 * │    hydration                                                     │
 * │    PHP's session_start() — hides file lock, cookie, storage     │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Transaction rollback on failure — void auth, release inventory │
 * │ ✓ Facade can have multiple high-level methods (placeOrder,      │
 * │   cancelOrder, trackOrder)                                       │
 * │ ✓ Inject subsystems via constructor for testability              │
 * └─────────────────────────────────────────────────────────────────┘
 */
