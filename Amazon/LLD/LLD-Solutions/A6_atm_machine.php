<?php
/**
 * A6. ATM MACHINE
 * ============================================================
 * PROBLEM: Design an ATM supporting card insertion, PIN validation,
 * cash withdrawal, deposit, and balance inquiry.
 *
 * PATTERNS:
 *  - State   : ATM state machine (Idle→CardInserted→PINEntered→Transaction)
 *  - Command : Withdraw, Deposit, CheckBalance commands
 * ============================================================
 */

// ─── Domain ─────────────────────────────────────────────────────
class BankAccount {
    private float $balance;
    public function __construct(public readonly string $accountId, float $initialBalance) {
        $this->balance = $initialBalance;
    }
    public function getBalance(): float      { return $this->balance; }
    public function debit(float $amt): bool  {
        if ($amt > $this->balance) return false;
        $this->balance -= $amt; return true;
    }
    public function credit(float $amt): void { $this->balance += $amt; }
}

class Card {
    public function __construct(
        public readonly string      $cardNumber,
        public readonly string      $pin,        // In reality: hashed
        public readonly BankAccount $account
    ) {}
    public function validatePin(string $enteredPin): bool { return $this->pin === $enteredPin; }
}

class CashDispenser {
    private float $cashAvailable;
    public function __construct(float $initial = 100000) { $this->cashAvailable = $initial; }
    public function hasCash(float $amount): bool { return $this->cashAvailable >= $amount; }
    public function dispense(float $amount): void {
        $this->cashAvailable -= $amount;
        echo "  💵 Cash dispensed: ₹{$amount}\n";
    }
}

// ─── ATM State Interface ────────────────────────────────────────
interface ATMState {
    public function insertCard(ATM $atm, Card $card): void;
    public function enterPin(ATM $atm, string $pin): void;
    public function withdraw(ATM $atm, float $amount): void;
    public function deposit(ATM $atm, float $amount): void;
    public function checkBalance(ATM $atm): void;
    public function ejectCard(ATM $atm): void;
}

// ─── States ─────────────────────────────────────────────────────
class IdleATMState implements ATMState {
    public function insertCard(ATM $atm, Card $card): void {
        $atm->setCard($card);
        $atm->setState(new CardInsertedState());
        echo "  Card inserted. Please enter PIN.\n";
    }
    public function enterPin(ATM $atm, string $pin): void  { echo "  ✗ Insert card first\n"; }
    public function withdraw(ATM $atm, float $a): void     { echo "  ✗ Insert card first\n"; }
    public function deposit(ATM $atm, float $a): void      { echo "  ✗ Insert card first\n"; }
    public function checkBalance(ATM $atm): void           { echo "  ✗ Insert card first\n"; }
    public function ejectCard(ATM $atm): void              { echo "  No card inserted\n"; }
}

class CardInsertedState implements ATMState {
    private int $attempts = 0;
    public function insertCard(ATM $atm, Card $c): void { echo "  ✗ Card already inserted\n"; }
    public function enterPin(ATM $atm, string $pin): void {
        $this->attempts++;
        if ($atm->getCard()?->validatePin($pin)) {
            echo "  ✓ PIN correct\n";
            $atm->setState(new AuthenticatedState());
        } else {
            echo "  ✗ Incorrect PIN ({$this->attempts}/3)\n";
            if ($this->attempts >= 3) {
                echo "  Card blocked after 3 failed attempts\n";
                $atm->setCard(null);
                $atm->setState(new IdleATMState());
            }
        }
    }
    public function withdraw(ATM $atm, float $a): void { echo "  ✗ Enter PIN first\n"; }
    public function deposit(ATM $atm, float $a): void  { echo "  ✗ Enter PIN first\n"; }
    public function checkBalance(ATM $atm): void       { echo "  ✗ Enter PIN first\n"; }
    public function ejectCard(ATM $atm): void {
        echo "  Card ejected\n";
        $atm->setCard(null);
        $atm->setState(new IdleATMState());
    }
}

class AuthenticatedState implements ATMState {
    public function insertCard(ATM $atm, Card $c): void { echo "  ✗ Card already inserted\n"; }
    public function enterPin(ATM $atm, string $pin): void { echo "  ✗ Already authenticated\n"; }
    public function withdraw(ATM $atm, float $amount): void {
        $account   = $atm->getCard()->account;
        $dispenser = $atm->getDispenser();
        if (!$dispenser->hasCash($amount))   { echo "  ✗ ATM has insufficient cash\n"; return; }
        if (!$account->debit($amount))        { echo "  ✗ Insufficient account balance\n"; return; }
        $dispenser->dispense($amount);
        echo "  ✓ Withdrawal successful. New balance: ₹{$account->getBalance()}\n";
    }
    public function deposit(ATM $atm, float $amount): void {
        $atm->getCard()->account->credit($amount);
        echo "  ✓ Deposited ₹{$amount}. Balance: ₹{$atm->getCard()->account->getBalance()}\n";
    }
    public function checkBalance(ATM $atm): void {
        echo "  Balance: ₹{$atm->getCard()->account->getBalance()}\n";
    }
    public function ejectCard(ATM $atm): void {
        echo "  Card ejected. Thank you!\n";
        $atm->setCard(null);
        $atm->setState(new IdleATMState());
    }
}

// ─── ATM (Context) ──────────────────────────────────────────────
class ATM {
    private ATMState     $state;
    private ?Card        $card      = null;
    private CashDispenser $dispenser;

    public function __construct() {
        $this->state     = new IdleATMState();
        $this->dispenser = new CashDispenser();
    }

    public function setState(ATMState $s): void  { $this->state = $s; }
    public function setCard(?Card $c): void       { $this->card  = $c; }
    public function getCard(): ?Card              { return $this->card; }
    public function getDispenser(): CashDispenser { return $this->dispenser; }

    // Delegate all operations to current state
    public function insertCard(Card $c): void      { $this->state->insertCard($this, $c); }
    public function enterPin(string $pin): void    { $this->state->enterPin($this, $pin); }
    public function withdraw(float $a): void       { $this->state->withdraw($this, $a); }
    public function deposit(float $a): void        { $this->state->deposit($this, $a); }
    public function checkBalance(): void           { $this->state->checkBalance($this); }
    public function ejectCard(): void              { $this->state->ejectCard($this); }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A6. ATM Machine ===\n\n";

$account = new BankAccount('ACC-001', 15000.0);
$card    = new Card('4111-1111', '1234', $account);
$atm     = new ATM();

echo "--- Normal flow ---\n";
$atm->insertCard($card);
$atm->enterPin('0000');   // Wrong PIN
$atm->enterPin('1234');   // Correct
$atm->checkBalance();
$atm->withdraw(5000);
$atm->deposit(2000);
$atm->ejectCard();

echo "\n--- No card operations ---\n";
$atm->withdraw(100);

echo "\n--- PIN block flow ---\n";
$atm->insertCard($card);
$atm->enterPin('xxxx');
$atm->enterPin('yyyy');
$atm->enterPin('zzzz'); // Blocked
$atm->checkBalance();   // Still idle
