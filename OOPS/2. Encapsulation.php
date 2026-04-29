<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              OOP CONCEPT #2 — ENCAPSULATION                      ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Beginner → Advanced                                 ║
 * ║  FREQUENCY : ★★★★★  (Top 3 most asked OOP question)             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 1: CONCEPT DEFINITION
// ═══════════════════════════════════════════════════════════════
/**
 * BEGINNER EXPLANATION:
 *   Encapsulation = wrapping data (properties) and behavior (methods)
 *   together inside a class AND restricting direct external access.
 *   Think of it like a MEDICINE CAPSULE: the drug (data) is inside,
 *   you can't touch the drug directly — you take the capsule as a unit.
 *
 * TECHNICAL DEFINITION:
 *   The bundling of data and the methods that operate on that data
 *   into a single unit (class), with access control via visibility
 *   modifiers (private/protected/public) to hide internal state.
 *
 * TWO ASPECTS:
 *   1. DATA HIDING   : Make properties private/protected. External
 *                      code cannot read/write them directly.
 *   2. DATA BUNDLING : Keep related data and behavior in one class.
 *
 * REAL-WORLD ANALOGY:
 *   An ATM machine — you can only interact via the keypad (public methods).
 *   You cannot reach inside and touch the cash or circuit board (private data).
 *   The bank controls WHAT you can do and validates every action.
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 2: VISUAL DIAGRAM
// ═══════════════════════════════════════════════════════════════
/**
 *  ┌─────────────────────────────────────────────────────────┐
 *  │              BankAccount (Capsule)                       │
 *  │                                                          │
 *  │  ╔══════════════════════════════╗  ← PRIVATE (hidden)   │
 *  │  ║  $balance  : float           ║                        │
 *  │  ║  $pin      : string          ║                        │
 *  │  ║  $transactions: array        ║                        │
 *  │  ╚══════════════════════════════╝                        │
 *  │                                                          │
 *  │  ┌──────────────────────────────┐  ← PUBLIC (interface) │
 *  │  │  + deposit(amount): void     │                        │
 *  │  │  + withdraw(amount): bool    │                        │
 *  │  │  + getBalance(): float       │                        │
 *  │  └──────────────────────────────┘                        │
 *  └─────────────────────────────────────────────────────────┘
 *        ↑                           ↑
 *   External code can ONLY          Internal data is
 *   use public methods              PROTECTED from outside
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 3: WITHOUT Encapsulation — BAD example
// ═══════════════════════════════════════════════════════════════

class BadBankAccount
{
    public float $balance = 0;   // ❌ Public — anyone can set it to anything!
    public string $pin    = '';  // ❌ Public — completely exposed
}

echo "=== ENCAPSULATION DEMO ===\n\n";
echo "--- ❌ Without Encapsulation ---\n";
$bad = new BadBankAccount();
$bad->balance = -99999;   // No validation! Setting negative balance
$bad->pin     = 'hacked'; // PIN exposed and modifiable
echo "  Bad balance set to: {$bad->balance} (invalid!)\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 4: WITH Encapsulation — CORRECT example
// ═══════════════════════════════════════════════════════════════

class BankAccount
{
    // DATA HIDING — private: only accessible inside this class
    private float  $balance      = 0.0;
    private string $pin;
    private array  $transactions = [];
    private string $accountNumber;

    public function __construct(string $accountNumber, string $pin, float $initialDeposit = 0)
    {
        $this->accountNumber = $accountNumber;
        $this->pin           = password_hash($pin, PASSWORD_BCRYPT); // Never store plain PIN
        if ($initialDeposit > 0) {
            $this->balance = $initialDeposit;
            $this->logTransaction('INITIAL_DEPOSIT', $initialDeposit);
        }
    }

    // PUBLIC interface — controlled, validated access to private data
    public function deposit(float $amount): void
    {
        // VALIDATION happens inside the class — not the caller's job
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }
        $this->balance += $amount;
        $this->logTransaction('DEPOSIT', $amount);
        echo "  ✓ Deposited ₹" . number_format($amount, 2) . " | Balance: ₹" . number_format($this->balance, 2) . "\n";
    }

    public function withdraw(float $amount, string $pin): bool
    {
        if (!password_verify($pin, $this->pin)) {
            echo "  ✗ Withdrawal failed: Invalid PIN\n";
            return false;
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }
        if ($amount > $this->balance) {
            echo "  ✗ Withdrawal failed: Insufficient funds\n";
            return false;
        }
        $this->balance -= $amount;
        $this->logTransaction('WITHDRAWAL', $amount);
        echo "  ✓ Withdrew ₹" . number_format($amount, 2) . " | Balance: ₹" . number_format($this->balance, 2) . "\n";
        return true;
    }

    // READ-ONLY access via getter — balance can be read but not directly set
    public function getBalance(): float  { return $this->balance; }
    public function getAccountNumber(): string { return '****' . substr($this->accountNumber, -4); }

    public function getTransactionHistory(): array { return $this->transactions; }

    // PRIVATE helper — internal logic hidden from outside
    private function logTransaction(string $type, float $amount): void
    {
        $this->transactions[] = [
            'type'   => $type,
            'amount' => $amount,
            'time'   => date('Y-m-d H:i:s'),
        ];
    }
}

echo "\n--- ✅ With Encapsulation ---\n";

$account = new BankAccount('9876543210', '1234', 5000.0);
$account->deposit(2000.0);
$account->withdraw(1500.0, '1234');
$account->withdraw(1500.0, '0000'); // Wrong PIN — blocked
$account->withdraw(99999.0, '1234'); // Insufficient — blocked

echo "\n  Balance: ₹" . number_format($account->getBalance(), 2) . "\n";
echo "  Account: " . $account->getAccountNumber() . "\n";

// ❌ This will fail — private property inaccessible
// $account->balance = 999999; // Fatal error: Cannot access private property

echo "\n  Transaction history:\n";
foreach ($account->getTransactionHistory() as $txn) {
    echo "  [{$txn['time']}] {$txn['type']}: ₹" . number_format($txn['amount'], 2) . "\n";
}

// ═══════════════════════════════════════════════════════════════
// SECTION 5: ENCAPSULATION WITH READONLY (PHP 8.1+)
// ═══════════════════════════════════════════════════════════════

class Product
{
    // PHP 8.1: readonly properties — can be set once in constructor, never changed
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        private float          $price   // Can be updated via method (with validation)
    ) {}

    public function setPrice(float $price): void
    {
        if ($price <= 0) throw new \InvalidArgumentException("Price must be positive.");
        $this->price = $price;
    }

    public function getPrice(): float { return $this->price; }
}

echo "\n--- PHP 8.1 readonly properties ---\n";
$product = new Product('SKU-001', 'Laptop', 75000.0);
echo "  Product: {$product->name} ({$product->sku}) ₹" . number_format($product->getPrice(), 2) . "\n";

// $product->sku = 'NEW'; // Fatal: Cannot modify readonly property
echo "  SKU is readonly — cannot be changed after construction ✓\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is encapsulation?                                       │
 * │ A: Bundling data and methods into one class AND restricting      │
 * │    direct access to internal data via visibility modifiers.      │
 * │    Two parts: data HIDING + data BUNDLING.                       │
 * │                                                                  │
 * │ Q2: Encapsulation vs Abstraction — what's the difference?        │
 * │ A: Encapsulation: HOW data is hidden (implementation detail).   │
 * │    Abstraction: WHAT is exposed to the outside (interface).      │
 * │    Encapsulation is the mechanism; abstraction is the design.    │
 * │    Analogy: Car — Abstraction says "I need a drive() method".   │
 * │    Encapsulation hides the engine internals behind that method.  │
 * │                                                                  │
 * │ Q3: Why use getters/setters instead of public properties?        │
 * │ A: Getters/setters allow VALIDATION before accepting a value,    │
 * │    let you add logic (logging, computation) transparently,       │
 * │    and allow you to change internal representation without       │
 * │    breaking external code (e.g., storing cents instead of float).│
 * │                                                                  │
 * │ Q4: Can encapsulation be broken in PHP?                          │
 * │ A: Via Reflection API (ReflectionProperty::setAccessible(true)) │
 * │    — but this is only for testing/framework use, not production. │
 * │                                                                  │
 * │ PITFALLS:                                                        │
 * │ ✗ Anemic models — all getters/setters with no real behavior.    │
 * │   Just exposing all private fields via getters/setters is NOT   │
 * │   real encapsulation — it's just indirection with extra steps.  │
 * │ ✗ Making everything public "for simplicity" — breaks safety.    │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ private = hidden; public = interface; protected = inherited   │
 * │ ✓ Encapsulation = data hiding + bundling                        │
 * │ ✓ Getters/setters provide controlled access with validation      │
 * │ ✓ PHP 8.1 readonly properties enforce write-once encapsulation  │
 * └─────────────────────────────────────────────────────────────────┘
 */
