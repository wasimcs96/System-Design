<?php

/////////////////////////////////////////////
// Product & Factory
/////////////////////////////////////////////

class Product
{
    private int $sku;
    private string $name;
    private float $price;

    public function __construct(int $id, string $nm, float $pr)
    {
        $this->sku   = $id;
        $this->name  = $nm;
        $this->price = $pr;
    }

    // Getters & Setters
    public function getSku(): int
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}

class ProductFactory
{
    public static function createProduct(int $sku): Product
    {
        // In reality product comes from DB
        if ($sku === 101) {
            $name  = "Apple";
            $price = 20;
        } elseif ($sku === 102) {
            $name  = "Banana";
            $price = 10;
        } elseif ($sku === 103) {
            $name  = "Chocolate";
            $price = 50;
        } elseif ($sku === 201) {
            $name  = "T-Shirt";
            $price = 500;
        } elseif ($sku === 202) {
            $name  = "Jeans";
            $price = 1000;
        } else {
            $name  = "Item" . $sku;
            $price = 100;
        }
        return new Product($sku, $name, $price);
    }
}

/////////////////////////////////////////////
// InventoryStore (Interface) & DbInventoryStore
/////////////////////////////////////////////

interface InventoryStore
{
    public function addProduct(Product $prod, int $qty): void;
    public function removeProduct(int $sku, int $qty): void;
    public function checkStock(int $sku): int;
    /** @return Product[] */
    public function listAvailableProducts(): array;
}

class DbInventoryStore implements InventoryStore
{
    /** @var array<int,int> SKU -> quantity */
    private array $stock;
    /** @var array<int,Product> SKU -> Product */
    private array $products;

    public function __construct()
    {
        $this->stock    = [];
        $this->products = [];
    }

    public function addProduct(Product $prod, int $qty): void
    {
        $sku = $prod->getSku();
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $prod;
        }
        // else drop the extra prod instance
        $this->stock[$sku] = ($this->stock[$sku] ?? 0) + $qty;
    }

    public function removeProduct(int $sku, int $qty): void
    {
        if (!array_key_exists($sku, $this->stock))
            return;

        $currentQuantity   = $this->stock[$sku];
        $remainingQuantity = $currentQuantity - $qty;
        if ($remainingQuantity > 0) {
            $this->stock[$sku] = $remainingQuantity;
        } else {
            unset($this->stock[$sku]);
            unset($this->products[$sku]);
        }
    }

    public function checkStock(int $sku): int
    {
        return $this->stock[$sku] ?? 0;
    }

    /** @return Product[] */
    public function listAvailableProducts(): array
    {
        $available = [];
        $sortedStock = $this->stock;
        ksort($sortedStock); // Java's HashMap<Integer,..> iterates small int keys in ascending order
        foreach ($sortedStock as $sku => $qty) {
            if ($qty > 0 && array_key_exists($sku, $this->products)) {
                $available[] = $this->products[$sku];
            }
        }
        return $available;
    }
}

/////////////////////////////////////////////
// InventoryManager
/////////////////////////////////////////////

class InventoryManager
{
    private InventoryStore $store;

    public function __construct(InventoryStore $store)
    {
        $this->store = $store;
    }

    public function addStock(int $sku, int $qty): void
    {
        $prod = ProductFactory::createProduct($sku);
        $this->store->addProduct($prod, $qty);
        echo "[InventoryManager] Added SKU " . $sku . " Qty " . $qty . PHP_EOL;
    }

    public function removeStock(int $sku, int $qty): void
    {
        $this->store->removeProduct($sku, $qty);
    }

    public function checkStock(int $sku): int
    {
        return $this->store->checkStock($sku);
    }

    /** @return Product[] */
    public function getAvailableProducts(): array
    {
        return $this->store->listAvailableProducts();
    }
}

/////////////////////////////////////////////
// Replenishment Strategy (Strategy Pattern)
/////////////////////////////////////////////

interface ReplenishStrategy
{
    /** @param array<int,int> $itemsToReplenish SKU -> qty */
    public function replenish(InventoryManager $manager, array $itemsToReplenish): void;
}

class ThresholdReplenishStrategy implements ReplenishStrategy
{
    private int $threshold;

    public function __construct(int $threshold)
    {
        $this->threshold = $threshold;
    }

    public function replenish(InventoryManager $manager, array $itemsToReplenish): void
    {
        echo "[ThresholdReplenish] Checking threshold..." . PHP_EOL;
        foreach ($itemsToReplenish as $sku => $qtyToAdd) {
            $current = $manager->checkStock($sku);
            if ($current < $this->threshold) {
                $manager->addStock($sku, $qtyToAdd);
                echo "  -> SKU " . $sku . " was " . $current
                    . ", replenished by " . $qtyToAdd . PHP_EOL;
            }
        }
    }
}

class WeeklyReplenishStrategy implements ReplenishStrategy
{
    public function __construct() {}

    public function replenish(InventoryManager $manager, array $itemsToReplenish): void
    {
        echo "[WeeklyReplenish] Weekly replenishment triggered for inventory." . PHP_EOL;
    }
}

/////////////////////////////////////////////
// DarkStore (formerly Warehouse)
/////////////////////////////////////////////

class DarkStore
{
    private string $name;
    private float $x;                 // location coordinates
    private float $y;
    private InventoryManager $inventoryManager;
    private ?ReplenishStrategy $replenishStrategy = null;

    public function __construct(string $n, float $x_coord, float $y_coord)
    {
        $this->name = $n;
        $this->x    = $x_coord;
        $this->y    = $y_coord;

        // We could have made another factory called InventoryStoreFactory to get
        // DbInventoryStore by enum and hence make it loosely coupled.
        $this->inventoryManager = new InventoryManager(new DbInventoryStore());
    }

    public function distanceTo(float $ux, float $uy): float
    {
        return sqrt(($this->x - $ux) * ($this->x - $ux) + ($this->y - $uy) * ($this->y - $uy));
    }

    /** @param array<int,int> $itemsToReplenish */
    public function runReplenishment(array $itemsToReplenish): void
    {
        if ($this->replenishStrategy !== null) {
            $this->replenishStrategy->replenish($this->inventoryManager, $itemsToReplenish);
        }
    }

    // Delegation Methods
    /** @return Product[] */
    public function getAllProducts(): array
    {
        return $this->inventoryManager->getAvailableProducts();
    }

    public function checkStock(int $sku): int
    {
        return $this->inventoryManager->checkStock($sku);
    }

    public function removeStock(int $sku, int $qty): void
    {
        $this->inventoryManager->removeStock($sku, $qty);
    }

    public function addStock(int $sku, int $qty): void
    {
        $this->inventoryManager->addStock($sku, $qty);
    }

    // Getters & Setters
    public function setReplenishStrategy(ReplenishStrategy $strategy): void
    {
        $this->replenishStrategy = $strategy;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getXCoordinate(): float
    {
        return $this->x;
    }

    public function getYCoordinate(): float
    {
        return $this->y;
    }

    public function getInventoryManager(): InventoryManager
    {
        return $this->inventoryManager;
    }
}

/////////////////////////////////////////////
// DarkStoreManager (Singleton)
/////////////////////////////////////////////

class DarkStoreManager
{
    private static ?DarkStoreManager $instance = null;
    /** @var DarkStore[] */
    private array $darkStores;

    private function __construct()
    {
        $this->darkStores = [];
    }

    public static function getInstance(): DarkStoreManager
    {
        if (self::$instance === null) {
            self::$instance = new DarkStoreManager();
        }
        return self::$instance;
    }

    public function registerDarkStore(DarkStore $ds): void
    {
        $this->darkStores[] = $ds;
    }

    /** @return DarkStore[] */
    public function getNearbyDarkStores(float $ux, float $uy, float $maxDistance): array
    {
        /** @var Pair[] $distList  Pair<float distance, DarkStore> */
        $distList = [];
        foreach ($this->darkStores as $ds) {
            $d = $ds->distanceTo($ux, $uy);
            if ($d <= $maxDistance) {
                $distList[] = new Pair($d, $ds);
            }
        }
        usort($distList, fn(Pair $a, Pair $b) => $a->getKey() <=> $b->getKey());
        $result = [];
        foreach ($distList as $p) {
            $result[] = $p->getValue();
        }
        return $result;
    }
}

// Simple helper Pair class (PHP has no built-in tuple/pair type)
class Pair
{
    private mixed $key;
    private mixed $value;

    public function __construct(mixed $k, mixed $v)
    {
        $this->key = $k;
        $this->value = $v;
    }

    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}

/////////////////////////////////////////////
// User & Cart
/////////////////////////////////////////////

class Cart
{
    /** @var Pair[] Pair<Product, int qty> */
    public array $items = [];

    public function addItem(int $sku, int $qty): void
    {
        $prod = ProductFactory::createProduct($sku);
        $this->items[] = new Pair($prod, $qty);
        echo "[Cart] Added SKU " . $sku . " (" . $prod->getName()
            . ") x" . $qty . PHP_EOL;
    }

    public function getTotal(): float
    {
        $sum = 0.0;
        foreach ($this->items as $it) {
            $sum += ($it->getKey()->getPrice() * $it->getValue());
        }
        return $sum;
    }

    /** @return Pair[] */
    public function getItems(): array
    {
        return $this->items;
    }
}

class User
{
    public string $name;
    public float $x;
    public float $y;
    private Cart $cart;  // User owns a cart

    public function __construct(string $n, float $x_coord, float $y_coord)
    {
        $this->name = $n;
        $this->x    = $x_coord;
        $this->y    = $y_coord;
        $this->cart = new Cart();
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}

/////////////////////////////////////////////
// DeliveryPartner
/////////////////////////////////////////////

class DeliveryPartner
{
    public string $name;

    public function __construct(string $n)
    {
        $this->name = $n;
    }
}

/////////////////////////////////////////////
// Order & OrderManager (Singleton)
/////////////////////////////////////////////

class Order
{
    private static int $nextId = 1;
    public int $orderId;
    public User $user;
    /** @var Pair[] Pair<Product, int qty> */
    public array $items = [];
    /** @var DeliveryPartner[] */
    public array $partners = [];
    public float $totalAmount;

    public function __construct(User $u)
    {
        $this->orderId = self::$nextId++;
        $this->user    = $u;
        $this->totalAmount = 0.0;
    }
}

class OrderManager
{
    private static ?OrderManager $instance = null;
    /** @var Order[] */
    private array $orders;

    private function __construct()
    {
        $this->orders = [];
    }

    public static function getInstance(): OrderManager
    {
        if (self::$instance === null) {
            self::$instance = new OrderManager();
        }
        return self::$instance;
    }

    public function placeOrder(User $user, Cart $cart): void
    {
        echo PHP_EOL . "[OrderManager] Placing Order for: " . $user->name . PHP_EOL;

        $requestedItems = $cart->getItems();

        // 1) Find nearby dark stores within 5 KM
        $maxDist = 5.0;
        $nearbyDarkStores =
            DarkStoreManager::getInstance()->getNearbyDarkStores($user->x, $user->y, $maxDist);

        if (empty($nearbyDarkStores)) {
            echo "  No dark stores within 5 KM. Cannot fulfill order." . PHP_EOL;
            return;
        }

        // 2) Check if closest store has all items
        $firstStore = $nearbyDarkStores[0];
        $allInFirst = true;
        foreach ($requestedItems as $item) {
            $sku = $item->getKey()->getSku();
            $qty = $item->getValue();
            if ($firstStore->checkStock($sku) < $qty) {
                $allInFirst = false;
                break;
            }
        }

        $order = new Order($user);

        // One delivery partner required...
        if ($allInFirst) {
            echo "  All items at: " . $firstStore->getName() . PHP_EOL;

            foreach ($requestedItems as $item) {
                $sku = $item->getKey()->getSku();
                $qty = $item->getValue();
                $firstStore->removeStock($sku, $qty);
                $order->items[] = new Pair($item->getKey(), $qty);
            }

            $order->totalAmount = $cart->getTotal();
            $order->partners[] = new DeliveryPartner("Partner1");
            echo "  Assigned Delivery Partner: Partner1" . PHP_EOL;
        }

        // Multiple delivery partners required
        else {
            echo "  Splitting order across stores..." . PHP_EOL;
            /** @var array<int,int> $allItems SKU -> qty still needed */
            $allItems = [];
            foreach ($requestedItems as $item) {
                $allItems[$item->getKey()->getSku()] = $item->getValue();
            }

            $partnerId = 1;
            foreach ($nearbyDarkStores as $store) {
                if (empty($allItems)) break;
                echo "   Checking: " . $store->getName() . PHP_EOL;
                $toErase = [];
                // foreach iterates over a copy, so updating $allItems inside is safe
                foreach ($allItems as $sku => $qtyNeeded) {
                    $availableQty = $store->checkStock($sku);
                    if ($availableQty <= 0) continue;
                    $takenQty = min($availableQty, $qtyNeeded);
                    $store->removeStock($sku, $takenQty);
                    echo "     " . $store->getName() . " supplies SKU " . $sku
                        . " x" . $takenQty . PHP_EOL;
                    $order->items[] = new Pair(ProductFactory::createProduct($sku), $takenQty);
                    if ($qtyNeeded > $takenQty) {
                        $allItems[$sku] = $qtyNeeded - $takenQty;
                    } else {
                        $toErase[] = $sku;
                    }
                }
                foreach ($toErase as $sku) {
                    unset($allItems[$sku]);
                }
                if (!empty($toErase)) {
                    $pname = "Partner" . $partnerId++;
                    $order->partners[] = new DeliveryPartner($pname);
                    echo "     Assigned: " . $pname . " for " . $store->getName() . PHP_EOL;
                }
            }
            if (!empty($allItems)) {
                echo "  Could not fulfill:" . PHP_EOL;
                foreach ($allItems as $sku => $qty) {
                    echo "    SKU " . $sku . " x" . $qty . PHP_EOL;
                }
            }
            $sum = 0;
            foreach ($order->items as $it) {
                $sum += $it->getKey()->getPrice() * $it->getValue();
            }
            $order->totalAmount = $sum;
        }

        // Printing Order Summary
        echo PHP_EOL . "[OrderManager] Order #" . $order->orderId . " Summary:" . PHP_EOL;
        echo "  User: " . $user->name . "\n  Items:" . PHP_EOL;
        foreach ($order->items as $item) {
            echo "    SKU " . $item->getKey()->getSku()
                . " (" . $item->getKey()->getName() . ") x" . $item->getValue()
                . " @ ₹" . $item->getKey()->getPrice() . PHP_EOL;
        }
        echo "  Total: ₹" . $order->totalAmount . "\n  Partners:" . PHP_EOL;
        foreach ($order->partners as $dp) {
            echo "    " . $dp->name . PHP_EOL;
        }
        echo PHP_EOL;

        $this->orders[] = $order;
    }

    /** @return Order[] */
    public function getAllOrders(): array
    {
        return $this->orders;
    }
}

/////////////////////////////////////////////
// Zepto Initialization & Main
/////////////////////////////////////////////

class ZeptoHelper
{
    public static function showAllItems(User $user): void
    {
        echo PHP_EOL . "[Zepto] All Available products within 5 KM for " . $user->name . ":" . PHP_EOL;
        $dsManager = DarkStoreManager::getInstance();
        $nearbyStores = $dsManager->getNearbyDarkStores($user->x, $user->y, 5.0);
        $skuToPrice = [];
        $skuToName  = [];

        foreach ($nearbyStores as $ds) {
            foreach ($ds->getAllProducts() as $product) {
                $sku = $product->getSku();
                if (!array_key_exists($sku, $skuToPrice)) {
                    $skuToPrice[$sku] = $product->getPrice();
                    $skuToName[$sku]  = $product->getName();
                }
            }
        }

        ksort($skuToPrice); // same order Java's HashMap gives for these int keys
        foreach ($skuToPrice as $sku => $price) {
            echo "  SKU " . $sku . " - "
                . $skuToName[$sku]
                . " @ ₹" . $price . PHP_EOL;
        }
    }

    public static function initialize(): void
    {
        $dsManager = DarkStoreManager::getInstance();

        // DarkStore A.......
        $darkStoreA = new DarkStore("DarkStoreA", 0.0, 0.0);
        $darkStoreA->setReplenishStrategy(new ThresholdReplenishStrategy(3));
        echo PHP_EOL . "Adding stocks in DarkStoreA...." . PHP_EOL;
        $darkStoreA->addStock(101, 5);
        $darkStoreA->addStock(102, 2);

        // DarkStore B.......
        $darkStoreB = new DarkStore("DarkStoreB", 4.0, 1.0);
        $darkStoreB->setReplenishStrategy(new ThresholdReplenishStrategy(3));
        echo PHP_EOL . "Adding stocks in DarkStoreB...." . PHP_EOL;
        $darkStoreB->addStock(101, 3);
        $darkStoreB->addStock(103, 10);

        // DarkStore C.......
        $darkStoreC = new DarkStore("DarkStoreC", 2.0, 3.0);
        $darkStoreC->setReplenishStrategy(new ThresholdReplenishStrategy(3));
        echo PHP_EOL . "Adding stocks in DarkStoreC...." . PHP_EOL;
        $darkStoreC->addStock(102, 5);
        $darkStoreC->addStock(201, 7);

        $dsManager->registerDarkStore($darkStoreA);
        $dsManager->registerDarkStore($darkStoreB);
        $dsManager->registerDarkStore($darkStoreC);
    }
}

class ZeptoClone
{
    public static function main(): void
    {
        // 1) Initialize.
        ZeptoHelper::initialize();

        // 2) A User comes on Platform
        $user = new User("Aditya", 1.0, 1.0);
        echo PHP_EOL . "User with name " . $user->name . " comes on platform" . PHP_EOL;

        // 3) Show all available items via Zepto
        ZeptoHelper::showAllItems($user);

        // 4) User adds items to cart
        echo PHP_EOL . "Adding items to cart" . PHP_EOL;
        $cart = $user->getCart();
        $cart->addItem(101, 4);
        $cart->addItem(102, 3);
        $cart->addItem(103, 2);

        // 5) Place Order
        OrderManager::getInstance()->placeOrder($user, $cart);

        echo PHP_EOL . "=== Demo Complete ===" . PHP_EOL;
    }
}

ZeptoClone::main();
