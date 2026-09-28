<?php

// ----------------------------
// Data structure for payment details
// ----------------------------
class PaymentRequest
{
    public string $sender;
    public string $reciever;
    public float $amount;
    public string $currency;

    public function __construct(string $sender, string $reciever, float $amt, string $curr)
    {
        $this->sender   = $sender;
        $this->reciever = $reciever;
        $this->amount   = $amt;
        $this->currency = $curr;
    }
}

// ----------------------------
// Banking System interface and implementations (Strategy for actual payment logic)
// ----------------------------
interface BankingSystem
{
    public function processPayment(float $amount): bool;
}

class PaytmBankingSystem implements BankingSystem
{
    public function __construct() {}

    public function processPayment(float $amount): bool
    {
        // Simulate 80% success
        $r = random_int(0, 99);
        return $r < 80;
    }
}

class RazorpayBankingSystem implements BankingSystem
{
    public function __construct() {}

    public function processPayment(float $amount): bool
    {
        echo "[BankingSystem-Razorpay] Processing payment of " . $amount . "..." . PHP_EOL;
        // Simulate 90% success
        $r = random_int(0, 99);
        return $r < 90;
    }
}

// ----------------------------
// Abstract base class for Payment Gateway (Template Method Pattern)
// ----------------------------
abstract class PaymentGateway
{
    protected ?BankingSystem $bankingSystem;

    public function __construct()
    {
        $this->bankingSystem = null;
    }

    // Template method defining the standard payment flow
    public function processPayment(PaymentRequest $request): bool
    {
        if (!$this->validatePayment($request)) {
            echo "[PaymentGateway] Validation failed for " . $request->sender . "." . PHP_EOL;
            return false;
        }
        if (!$this->initiatePayment($request)) {
            echo "[PaymentGateway] Initiation failed for " . $request->sender . "." . PHP_EOL;
            return false;
        }
        if (!$this->confirmPayment($request)) {
            echo "[PaymentGateway] Confirmation failed for " . $request->sender . "." . PHP_EOL;
            return false;
        }
        return true;
    }

    // Steps to be implemented by concrete gateways
    abstract protected function validatePayment(PaymentRequest $request): bool;
    abstract protected function initiatePayment(PaymentRequest $request): bool;
    abstract protected function confirmPayment(PaymentRequest $request): bool;
}

// ----------------------------
// Concrete Payment Gateway for Paytm
// ----------------------------
class PaytmGateway extends PaymentGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->bankingSystem = new PaytmBankingSystem();
    }

    protected function validatePayment(PaymentRequest $request): bool
    {
        echo "[Paytm] Validating payment for " . $request->sender . "." . PHP_EOL;
        if ($request->amount <= 0 || $request->currency !== "INR") {
            return false;
        }
        return true;
    }

    protected function initiatePayment(PaymentRequest $request): bool
    {
        echo "[Paytm] Initiating payment of " . $request->amount
            . " " . $request->currency . " for " . $request->sender . "." . PHP_EOL;
        return $this->bankingSystem->processPayment($request->amount);
    }

    protected function confirmPayment(PaymentRequest $request): bool
    {
        echo "[Paytm] Confirming payment for " . $request->sender . "." . PHP_EOL;
        // Confirmation always succeeds in this simulation
        return true;
    }
}

// ----------------------------
// Concrete Payment Gateway for Razorpay
// ----------------------------
class RazorpayGateway extends PaymentGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->bankingSystem = new RazorpayBankingSystem();
    }

    protected function validatePayment(PaymentRequest $request): bool
    {
        echo "[Razorpay] Validating payment for " . $request->sender . "." . PHP_EOL;
        if ($request->amount <= 0) {
            return false;
        }
        return true;
    }

    protected function initiatePayment(PaymentRequest $request): bool
    {
        echo "[Razorpay] Initiating payment of " . $request->amount
            . " " . $request->currency . " for " . $request->sender . "." . PHP_EOL;
        return $this->bankingSystem->processPayment($request->amount);
    }

    protected function confirmPayment(PaymentRequest $request): bool
    {
        echo "[Razorpay] Confirming payment for " . $request->sender . "." . PHP_EOL;
        // Confirmation always succeeds in this simulation
        return true;
    }
}

// ----------------------------
// Proxy class that wraps a PaymentGateway to add retries (Proxy Pattern)
// ----------------------------
class PaymentGatewayProxy extends PaymentGateway
{
    private PaymentGateway $realGateway;
    private int $retries;

    public function __construct(PaymentGateway $gateway, int $maxRetries)
    {
        parent::__construct();
        $this->realGateway = $gateway;
        $this->retries     = $maxRetries;
    }

    public function processPayment(PaymentRequest $request): bool
    {
        $result = false;
        for ($attempt = 0; $attempt < $this->retries; ++$attempt) {
            if ($attempt > 0) {
                echo "[Proxy] Retrying payment (attempt " . ($attempt + 1)
                    . ") for " . $request->sender . "." . PHP_EOL;
            }
            $result = $this->realGateway->processPayment($request);
            if ($result) break;
        }
        if (!$result) {
            echo "[Proxy] Payment failed after " . $this->retries
                . " attempts for " . $request->sender . "." . PHP_EOL;
        }
        return $result;
    }

    // PHP (like Java) allows calling protected methods of another object
    // when both classes share the same parent that declares them.
    protected function validatePayment(PaymentRequest $request): bool
    {
        return $this->realGateway->validatePayment($request);
    }

    protected function initiatePayment(PaymentRequest $request): bool
    {
        return $this->realGateway->initiatePayment($request);
    }

    protected function confirmPayment(PaymentRequest $request): bool
    {
        return $this->realGateway->confirmPayment($request);
    }
}

// ----------------------------
// Gateway Factory for creating gateway (Singleton)
// ----------------------------
enum GatewayType
{
    case PAYTM;
    case RAZORPAY;
}

class GatewayFactory
{
    private static ?GatewayFactory $instance = null;

    private function __construct() {}

    public static function getInstance(): GatewayFactory
    {
        return self::$instance ??= new GatewayFactory();
    }

    public function getGateway(GatewayType $type): PaymentGateway
    {
        if ($type === GatewayType::PAYTM) {
            $paymentGateway = new PaytmGateway();
            return new PaymentGatewayProxy($paymentGateway, 3);
        } else {
            $paymentGateway = new RazorpayGateway();
            return new PaymentGatewayProxy($paymentGateway, 1);
        }
    }
}

// ----------------------------
// Unified API service (Singleton)
// ----------------------------
class PaymentService
{
    private static ?PaymentService $instance = null;
    private ?PaymentGateway $gateway;

    private function __construct()
    {
        $this->gateway = null;
    }

    public static function getInstance(): PaymentService
    {
        return self::$instance ??= new PaymentService();
    }

    public function setGateway(PaymentGateway $g): void
    {
        $this->gateway = $g;
    }

    public function processPayment(PaymentRequest $request): bool
    {
        if ($this->gateway === null) {
            echo "[PaymentService] No payment gateway selected." . PHP_EOL;
            return false;
        }
        return $this->gateway->processPayment($request);
    }
}

// ----------------------------
// Controller class for all client requests (Singleton)
// ----------------------------
class PaymentController
{
    private static ?PaymentController $instance = null;

    private function __construct() {}

    public static function getInstance(): PaymentController
    {
        return self::$instance ??= new PaymentController();
    }

    public function handlePayment(GatewayType $type, PaymentRequest $req): bool
    {
        $paymentGateway = GatewayFactory::getInstance()->getGateway($type);
        PaymentService::getInstance()->setGateway($paymentGateway);
        return PaymentService::getInstance()->processPayment($req);
    }
}

// ----------------------------
// Main: Client code now goes through controller
// ----------------------------
class PaymentGatewayApplication
{
    public static function main(): void
    {
        $req1 = new PaymentRequest("Aditya", "Shubham", 1000.0, "INR");

        echo "Processing via Paytm" . PHP_EOL;
        echo "------------------------------" . PHP_EOL;
        $res1 = PaymentController::getInstance()->handlePayment(GatewayType::PAYTM, $req1);
        echo "Result: " . ($res1 ? "SUCCESS" : "FAIL") . PHP_EOL;
        echo "------------------------------" . PHP_EOL . PHP_EOL;

        $req2 = new PaymentRequest("Shubham", "Aditya", 500.0, "USD");

        echo "Processing via Razorpay" . PHP_EOL;
        echo "------------------------------" . PHP_EOL;
        $res2 = PaymentController::getInstance()->handlePayment(GatewayType::RAZORPAY, $req2);
        echo "Result: " . ($res2 ? "SUCCESS" : "FAIL") . PHP_EOL;
        echo "------------------------------" . PHP_EOL;
    }
}

PaymentGatewayApplication::main();
