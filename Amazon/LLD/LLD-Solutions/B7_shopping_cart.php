<?php
/**
 * B7. SHOPPING CART
 * ============================================================
 * PROBLEM: Shopping cart with item management, discounts,
 * tax calculation, and checkout flow.
 *
 * PATTERNS:
 *  - Strategy  : DiscountStrategy (percentage, fixed, buy-one-get-one)
 *  - Decorator : TaxDecorator wraps CartTotal
 *  - Observer  : Notify inventory on checkout
 * ============================================================
 */

// ─── Product ────────────────────────────────────────────────────
class Product {
    public function __construct(
        public readonly string $productId,
        public readonly string $name,
        public readonly float  $price,
        public int             $stockQuantity
    ) {}
}

// ─── Cart Item ──────────────────────────────────────────────────
class CartItem {
    public function __construct(
        public readonly Product $product,
        public int              $quantity
    ) {}

    public function getSubtotal(): float { return $this->product->price * $this->quantity; }
}

// ─── Discount Strategy ─────────────────────────────────────────
interface DiscountStrategy {
    public function apply(float $total): float;
    public function describe(): string;
}

class PercentageDiscount implements DiscountStrategy {
    public function __construct(private float $percent) {}
    public function apply(float $total): float  { return $total * (1 - $this->percent / 100); }
    public function describe(): string          { return "{$this->percent}% off"; }
}

class FixedDiscount implements DiscountStrategy {
    public function __construct(private float $amount) {}
    public function apply(float $total): float  { return max(0, $total - $this->amount); }
    public function describe(): string          { return "₹{$this->amount} off"; }
}

class NoDiscount implements DiscountStrategy {
    public function apply(float $total): float  { return $total; }
    public function describe(): string          { return "No discount"; }
}

// ─── Checkout Observer ─────────────────────────────────────────
interface CheckoutObserver {
    public function onCheckout(Cart $cart): void;
}

class InventoryUpdater implements CheckoutObserver {
    public function onCheckout(Cart $cart): void {
        foreach ($cart->getItems() as $item) {
            $item->product->stockQuantity -= $item->quantity;
            echo "  📦 Inventory: [{$item->product->name}] stock={$item->product->stockQuantity}\n";
        }
    }
}

// ─── Cart ───────────────────────────────────────────────────────
class Cart {
    /** @var array<string,CartItem> productId → CartItem */
    private array $items     = [];
    private DiscountStrategy $discount;
    /** @var CheckoutObserver[] */
    private array $observers = [];
    private float $taxRate   = 0.18; // 18% GST

    public function __construct(private string $userId) {
        $this->discount = new NoDiscount();
    }

    public function addItem(Product $product, int $qty = 1): void {
        if ($qty > $product->stockQuantity) {
            echo "  ✗ Insufficient stock for {$product->name}\n"; return;
        }
        if (isset($this->items[$product->productId])) {
            $this->items[$product->productId]->quantity += $qty;
        } else {
            $this->items[$product->productId] = new CartItem($product, $qty);
        }
        echo "  ✓ Added: {$product->name} ×{$qty}\n";
    }

    public function removeItem(string $productId): void {
        unset($this->items[$productId]);
    }

    public function applyDiscount(DiscountStrategy $d): void {
        $this->discount = $d;
        echo "  Discount applied: {$d->describe()}\n";
    }

    public function getItems(): array { return $this->items; }

    public function getSubtotal(): float {
        return array_sum(array_map(fn($i) => $i->getSubtotal(), $this->items));
    }

    public function getTotal(): float {
        $afterDiscount = $this->discount->apply($this->getSubtotal());
        return $afterDiscount * (1 + $this->taxRate);
    }

    public function addObserver(CheckoutObserver $obs): void { $this->observers[] = $obs; }

    public function checkout(): void {
        if (empty($this->items)) { echo "  Cart is empty\n"; return; }
        $subtotal = $this->getSubtotal();
        $after    = $this->discount->apply($subtotal);
        $tax      = $after * $this->taxRate;
        echo "\n  === CHECKOUT ===\n";
        foreach ($this->items as $item)
            echo "  {$item->product->name} ×{$item->quantity} = ₹{$item->getSubtotal()}\n";
        echo "  Subtotal   : ₹{$subtotal}\n";
        echo "  Discount   : {$this->discount->describe()}\n";
        echo "  After disc : ₹{$after}\n";
        echo "  Tax (18%)  : ₹{$tax}\n";
        echo "  TOTAL      : ₹{$this->getTotal()}\n";
        foreach ($this->observers as $obs) $obs->onCheckout($this);
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B7. Shopping Cart ===\n\n";

$phone   = new Product('P001', 'Phone',   30000.0, 10);
$case_   = new Product('P002', 'Case',    500.0,   50);
$charger = new Product('P003', 'Charger', 1200.0,  20);

$cart = new Cart('user_alice');
$cart->addObserver(new InventoryUpdater());
$cart->addItem($phone, 1);
$cart->addItem($case_, 2);
$cart->addItem($charger, 1);

$cart->applyDiscount(new PercentageDiscount(10));
$cart->checkout();
