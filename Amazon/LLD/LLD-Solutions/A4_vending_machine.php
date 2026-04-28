<?php
/**
 * A4. VENDING MACHINE
 * ============================================================
 * PROBLEM: Design a vending machine with state management,
 * product inventory, coin handling, and change dispensing.
 *
 * PATTERNS:
 *  - State   : Machine transitions (Idle → HasMoney → Dispensing)
 *  - Command : Button press actions
 * ============================================================
 */

// ─── Product ────────────────────────────────────────────────────
class Product {
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly float  $price,
        private int            $quantity
    ) {}

    public function isInStock(): bool      { return $this->quantity > 0; }
    public function getQuantity(): int     { return $this->quantity; }
    public function dispense(): void       { if ($this->quantity > 0) $this->quantity--; }
    public function restock(int $qty): void { $this->quantity += $qty; }
}

// ─── Coin ────────────────────────────────────────────────────────
enum Coin: int { case ONE=1; case TWO=2; case FIVE=5; case TEN=10; }

// ─── State Interface (State Pattern) ──────────────────────────
interface MachineState {
    public function insertCoin(VendingMachine $machine, Coin $coin): void;
    public function selectProduct(VendingMachine $machine, string $code): void;
    public function dispense(VendingMachine $machine): void;
    public function refund(VendingMachine $machine): void;
}

// ─── Concrete States ──────────────────────────────────────────
class IdleState implements MachineState {
    public function insertCoin(VendingMachine $m, Coin $coin): void {
        $m->addCredit($coin->value);
        echo "  Coin accepted: ₹{$coin->value} | Credit: ₹{$m->getCredit()}\n";
        $m->setState(new HasMoneyState());
    }
    public function selectProduct(VendingMachine $m, string $code): void { echo "  ✗ Insert coin first\n"; }
    public function dispense(VendingMachine $m): void  { echo "  ✗ No product selected\n"; }
    public function refund(VendingMachine $m): void    { echo "  Nothing to refund\n"; }
}

class HasMoneyState implements MachineState {
    public function insertCoin(VendingMachine $m, Coin $coin): void {
        $m->addCredit($coin->value);
        echo "  More coin: ₹{$coin->value} | Total credit: ₹{$m->getCredit()}\n";
    }
    public function selectProduct(VendingMachine $m, string $code): void {
        $product = $m->getProduct($code);
        if (!$product) { echo "  ✗ Invalid product code: $code\n"; return; }
        if (!$product->isInStock()) { echo "  ✗ Out of stock\n"; return; }
        if ($m->getCredit() < $product->price) {
            echo "  ✗ Insufficient credit. Need ₹{$product->price}, have ₹{$m->getCredit()}\n";
            return;
        }
        $m->setSelectedProduct($product);
        $m->setState(new DispensingState());
        $m->dispense(); // Trigger dispensing
    }
    public function dispense(VendingMachine $m): void  { echo "  ✗ Select a product first\n"; }
    public function refund(VendingMachine $m): void {
        echo "  ✓ Refunded ₹{$m->getCredit()}\n";
        $m->resetCredit();
        $m->setState(new IdleState());
    }
}

class DispensingState implements MachineState {
    public function insertCoin(VendingMachine $m, Coin $coin): void { echo "  ✗ Dispensing in progress\n"; }
    public function selectProduct(VendingMachine $m, string $code): void { echo "  ✗ Dispensing in progress\n"; }
    public function dispense(VendingMachine $m): void {
        $product = $m->getSelectedProduct();
        $product->dispense();
        $change  = $m->getCredit() - $product->price;
        $m->resetCredit();
        echo "  ✓ Dispensed: {$product->name}\n";
        if ($change > 0) echo "  ✓ Change returned: ₹{$change}\n";
        $m->setState($product->isInStock() ? new IdleState() : new OutOfStockState());
    }
    public function refund(VendingMachine $m): void { echo "  ✗ Dispensing in progress\n"; }
}

class OutOfStockState implements MachineState {
    public function insertCoin(VendingMachine $m, Coin $coin): void {
        echo "  ✗ Machine out of stock. Returning ₹{$coin->value}\n";
    }
    public function selectProduct(VendingMachine $m, string $code): void { echo "  ✗ Out of stock\n"; }
    public function dispense(VendingMachine $m): void  { echo "  ✗ Out of stock\n"; }
    public function refund(VendingMachine $m): void    { echo "  Nothing to refund\n"; }
}

// ─── Vending Machine (Context) ────────────────────────────────
class VendingMachine {
    private MachineState $state;
    private float        $credit  = 0.0;
    private ?Product     $selected = null;
    /** @var Product[] code → Product */
    private array        $inventory = [];

    public function __construct() { $this->state = new IdleState(); }

    public function addProduct(Product $p): void  { $this->inventory[$p->code] = $p; }
    public function getProduct(string $code): ?Product { return $this->inventory[$code] ?? null; }
    public function getCredit(): float            { return $this->credit; }
    public function addCredit(float $amount): void{ $this->credit += $amount; }
    public function resetCredit(): void           { $this->credit = 0.0; }
    public function setState(MachineState $s): void { $this->state = $s; }
    public function setSelectedProduct(?Product $p): void { $this->selected = $p; }
    public function getSelectedProduct(): ?Product { return $this->selected; }

    // Delegate to current state
    public function insertCoin(Coin $coin): void           { $this->state->insertCoin($this, $coin); }
    public function selectProduct(string $code): void      { $this->state->selectProduct($this, $code); }
    public function dispense(): void                       { $this->state->dispense($this); }
    public function refund(): void                         { $this->state->refund($this); }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A4. Vending Machine ===\n\n";

$vm = new VendingMachine();
$vm->addProduct(new Product('A1', 'Chips',   20.0, 3));
$vm->addProduct(new Product('B2', 'Cola',    30.0, 1));

echo "--- Buy Chips (₹20) ---\n";
$vm->insertCoin(Coin::TEN);
$vm->insertCoin(Coin::TEN);
$vm->selectProduct('A1');

echo "\n--- Buy Cola (₹30) with extra coin ---\n";
$vm->insertCoin(Coin::TEN);
$vm->insertCoin(Coin::TEN);
$vm->insertCoin(Coin::TEN);
$vm->insertCoin(Coin::TEN); // Will get ₹10 change
$vm->selectProduct('B2');

echo "\n--- Try to buy when idle (no coin) ---\n";
$vm->selectProduct('A1');

echo "\n--- Insert coin and refund ---\n";
$vm->insertCoin(Coin::FIVE);
$vm->refund();

/**
 * INTERVIEW FOLLOW-UPS:
 *  1. Multiple denominations for change? → GreedyChangeDispenser strategy
 *  2. Network-connected machine? → Observer sends telemetry on each state change
 *  3. Admin restocking? → AdminCommand pattern; restricted access
 *  4. Receipts? → Decorator around dispense()
 */
