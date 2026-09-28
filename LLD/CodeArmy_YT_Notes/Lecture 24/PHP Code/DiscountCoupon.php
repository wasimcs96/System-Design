<?php

// ----------------------------
// Discount Strategy (Strategy Pattern)
// ----------------------------
interface DiscountStrategy
{
    public function calculate(float $baseAmount): float;
}

class FlatDiscountStrategy implements DiscountStrategy
{
    private float $amount;

    public function __construct(float $amt)
    {
        $this->amount = $amt;
    }

    public function calculate(float $baseAmount): float
    {
        return min($this->amount, $baseAmount);
    }
}

class PercentageDiscountStrategy implements DiscountStrategy
{
    private float $percent;

    public function __construct(float $pct)
    {
        $this->percent = $pct;
    }

    public function calculate(float $baseAmount): float
    {
        return ($this->percent / 100.0) * $baseAmount;
    }
}

class PercentageWithCapStrategy implements DiscountStrategy
{
    private float $percent;
    private float $cap;

    public function __construct(float $pct, float $capVal)
    {
        $this->percent = $pct;
        $this->cap     = $capVal;
    }

    public function calculate(float $baseAmount): float
    {
        $disc = ($this->percent / 100.0) * $baseAmount;
        return $disc > $this->cap ? $this->cap : $disc;
    }
}

enum StrategyType
{
    case FLAT;
    case PERCENT;
    case PERCENT_WITH_CAP;
}

// ----------------------------
// DiscountStrategyManager (Singleton)
// ----------------------------
class DiscountStrategyManager
{
    private static ?DiscountStrategyManager $instance = null;

    private function __construct() {}

    public static function getInstance(): DiscountStrategyManager
    {
        if (self::$instance === null) {
            self::$instance = new DiscountStrategyManager();
        }
        return self::$instance;
    }

    public function getStrategy(StrategyType $type, float $param1, float $param2): ?DiscountStrategy
    {
        return match ($type) {
            StrategyType::FLAT             => new FlatDiscountStrategy($param1),
            StrategyType::PERCENT          => new PercentageDiscountStrategy($param1),
            StrategyType::PERCENT_WITH_CAP => new PercentageWithCapStrategy($param1, $param2),
        };
    }
}

// ----------------------------
// Assume existing Cart and Product classes
// ----------------------------
class Product
{
    private string $name;
    private string $category;
    private float $price;

    public function __construct(string $name, string $category, float $price)
    {
        $this->name     = $name;
        $this->category = $category;
        $this->price    = $price;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}

class CartItem
{
    private Product $product;
    private int $quantity;

    public function __construct(Product $prod, int $qty)
    {
        $this->product  = $prod;
        $this->quantity = $qty;
    }

    public function itemTotal(): float
    {
        return $this->product->getPrice() * $this->quantity;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}

class Cart
{
    /** @var CartItem[] */
    private array $items = [];
    private float $originalTotal = 0.0;
    private float $currentTotal  = 0.0;
    private bool $loyaltyMember;
    private string $paymentBank;

    public function __construct()
    {
        $this->loyaltyMember = false;
        $this->paymentBank   = "";
    }

    public function addProduct(Product $prod, int $qty): void
    {
        $item = new CartItem($prod, $qty);
        $this->items[] = $item;
        $this->originalTotal += $item->itemTotal();
        $this->currentTotal  += $item->itemTotal();
    }

    public function getOriginalTotal(): float
    {
        return $this->originalTotal;
    }

    public function getCurrentTotal(): float
    {
        return $this->currentTotal;
    }

    public function applyDiscount(float $d): void
    {
        $this->currentTotal -= $d;
        if ($this->currentTotal < 0) {
            $this->currentTotal = 0;
        }
    }

    public function setLoyaltyMember(bool $member): void
    {
        $this->loyaltyMember = $member;
    }

    public function isLoyaltyMember(): bool
    {
        return $this->loyaltyMember;
    }

    public function setPaymentBank(string $bank): void
    {
        $this->paymentBank = $bank;
    }

    public function getPaymentBank(): string
    {
        return $this->paymentBank;
    }

    /** @return CartItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}

// ----------------------------
// Coupon base class (Chain of Responsibility)
// ----------------------------
abstract class Coupon
{
    private ?Coupon $next;

    public function __construct()
    {
        $this->next = null;
    }

    public function setNext(Coupon $nxt): void
    {
        $this->next = $nxt;
    }

    public function getNext(): ?Coupon
    {
        return $this->next;
    }

    public function applyDiscount(Cart $cart): void
    {
        if ($this->isApplicable($cart)) {
            $discount = $this->getDiscount($cart);
            $cart->applyDiscount($discount);
            echo $this->name() . " applied: " . $discount . PHP_EOL;
            if (!$this->isCombinable()) {
                return;
            }
        }
        if ($this->next !== null) {
            $this->next->applyDiscount($cart);
        }
    }

    abstract public function isApplicable(Cart $cart): bool;
    abstract public function getDiscount(Cart $cart): float;

    public function isCombinable(): bool
    {
        return true;
    }

    abstract public function name(): string;
}

// ----------------------------
// Concrete Coupons
// ----------------------------
class SeasonalOffer extends Coupon
{
    private float $percent;
    private string $category;
    private DiscountStrategy $strat;

    public function __construct(float $pct, string $cat)
    {
        parent::__construct();
        $this->percent  = $pct;
        $this->category = $cat;
        $this->strat    = DiscountStrategyManager::getInstance()
            ->getStrategy(StrategyType::PERCENT, $this->percent, 0.0);
    }

    public function isApplicable(Cart $cart): bool
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getCategory() === $this->category) {
                return true;
            }
        }
        return false;
    }

    public function getDiscount(Cart $cart): float
    {
        $subtotal = 0.0;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getCategory() === $this->category) {
                $subtotal += $item->itemTotal();
            }
        }
        return $this->strat->calculate($subtotal);
    }

    public function name(): string
    {
        return "Seasonal Offer " . (int)$this->percent . "% off " . $this->category;
    }
}

class LoyaltyDiscount extends Coupon
{
    private float $percent;
    private DiscountStrategy $strat;

    public function __construct(float $pct)
    {
        parent::__construct();
        $this->percent = $pct;
        $this->strat   = DiscountStrategyManager::getInstance()
            ->getStrategy(StrategyType::PERCENT, $this->percent, 0.0);
    }

    public function isApplicable(Cart $cart): bool
    {
        return $cart->isLoyaltyMember();
    }

    public function getDiscount(Cart $cart): float
    {
        return $this->strat->calculate($cart->getCurrentTotal());
    }

    public function name(): string
    {
        return "Loyalty Discount " . (int)$this->percent . "% off";
    }
}

class BulkPurchaseDiscount extends Coupon
{
    private float $threshold;
    private float $flatOff;
    private DiscountStrategy $strat;

    public function __construct(float $thr, float $off)
    {
        parent::__construct();
        $this->threshold = $thr;
        $this->flatOff   = $off;
        $this->strat     = DiscountStrategyManager::getInstance()
            ->getStrategy(StrategyType::FLAT, $this->flatOff, 0.0);
    }

    public function isApplicable(Cart $cart): bool
    {
        return $cart->getOriginalTotal() >= $this->threshold;
    }

    public function getDiscount(Cart $cart): float
    {
        return $this->strat->calculate($cart->getCurrentTotal());
    }

    public function name(): string
    {
        return "Bulk Purchase Rs " . (int)$this->flatOff . " off over " . (int)$this->threshold;
    }
}

class BankingCoupon extends Coupon
{
    private string $bank;
    private float $minSpend;
    private float $percent;
    private float $offCap;
    private DiscountStrategy $strat;

    public function __construct(string $b, float $ms, float $percent, float $offCap)
    {
        parent::__construct();
        $this->bank     = $b;
        $this->minSpend = $ms;
        $this->percent  = $percent;
        $this->offCap   = $offCap;
        $this->strat    = DiscountStrategyManager::getInstance()
            ->getStrategy(StrategyType::PERCENT_WITH_CAP, $percent, $offCap);
    }

    public function isApplicable(Cart $cart): bool
    {
        return $cart->getPaymentBank() === $this->bank
            && $cart->getOriginalTotal() >= $this->minSpend;
    }

    public function getDiscount(Cart $cart): float
    {
        return $this->strat->calculate($cart->getCurrentTotal());
    }

    public function name(): string
    {
        return $this->bank . " Bank Rs " . (int)$this->percent . " off upto " . (int)$this->offCap;
    }
}

// ----------------------------
// CouponManager (Singleton)
// ----------------------------
// NOTE (PHP): Java used a ReentrantLock around registerCoupon/getApplicable/applyAll.
// A PHP script runs single-threaded and each request has its own memory, so no lock
// is needed here. (Across processes you'd use a DB transaction / flock / Redis lock.)
class CouponManager
{
    private static ?CouponManager $instance = null;
    private ?Coupon $head;

    private function __construct()
    {
        $this->head = null;
    }

    public static function getInstance(): CouponManager
    {
        if (self::$instance === null) {
            self::$instance = new CouponManager();
        }
        return self::$instance;
    }

    public function registerCoupon(Coupon $coupon): void
    {
        if ($this->head === null) {
            $this->head = $coupon;
        } else {
            $cur = $this->head;
            while ($cur->getNext() !== null) {
                $cur = $cur->getNext();
            }
            $cur->setNext($coupon);
        }
    }

    /** @return string[] */
    public function getApplicable(Cart $cart): array
    {
        $res = [];
        $cur = $this->head;
        while ($cur !== null) {
            if ($cur->isApplicable($cart)) {
                $res[] = $cur->name();
            }
            $cur = $cur->getNext();
        }
        return $res;
    }

    public function applyAll(Cart $cart): float
    {
        if ($this->head !== null) {
            $this->head->applyDiscount($cart);
        }
        return $cart->getCurrentTotal();
    }
}

// ----------------------------
// Main: Client code
// ----------------------------
class DiscountCoupon
{
    public static function main(): void
    {
        $mgr = CouponManager::getInstance();
        $mgr->registerCoupon(new SeasonalOffer(10, "Clothing"));
        $mgr->registerCoupon(new LoyaltyDiscount(5));
        $mgr->registerCoupon(new BulkPurchaseDiscount(1000, 100));
        $mgr->registerCoupon(new BankingCoupon("ABC", 2000, 15, 500));

        $p1 = new Product("Winter Jacket", "Clothing", 1000);
        $p2 = new Product("Smartphone", "Electronics", 20000);
        $p3 = new Product("Jeans", "Clothing", 1000);
        $p4 = new Product("Headphones", "Electronics", 2000);

        $cart = new Cart();
        $cart->addProduct($p1, 1);
        $cart->addProduct($p2, 1);
        $cart->addProduct($p3, 2);
        $cart->addProduct($p4, 1);
        $cart->setLoyaltyMember(true);
        $cart->setPaymentBank("ABC");

        echo "Original Cart Total: " . $cart->getOriginalTotal() . " Rs" . PHP_EOL;

        $applicable = $mgr->getApplicable($cart);
        echo "Applicable Coupons:" . PHP_EOL;
        foreach ($applicable as $name) {
            echo " - " . $name . PHP_EOL;
        }

        $finalTotal = $mgr->applyAll($cart);
        echo "Final Cart Total after discounts: " . $finalTotal . " Rs" . PHP_EOL;
    }
}

DiscountCoupon::main();
