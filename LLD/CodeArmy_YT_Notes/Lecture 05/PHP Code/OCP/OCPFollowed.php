<?php

// Product class representing any item in eCommerce.
class Product
{
    public string $name;
    public float $price;

    public function __construct(string $name, float $price)
    {
        $this->name = $name;
        $this->price = $price;
    }
}

// 1. ShoppingCart: Only responsible for Cart related business logic.
class ShoppingCart
{
    /** @var Product[] */
    private array $products = [];

    public function addProduct(Product $p): void
    {
        $this->products[] = $p;
    }

    /** @return Product[] */
    public function getProducts(): array
    {
        return $this->products;
    }

    // Calculates total price in cart.
    public function calculateTotal(): float
    {
        $total = 0;
        foreach ($this->products as $p) {
            $total += $p->price;
        }
        return $total;
    }
}

// 2. ShoppingCartPrinter: Only responsible for printing invoices
class ShoppingCartPrinter
{
    private ShoppingCart $cart;

    public function __construct(ShoppingCart $cart)
    {
        $this->cart = $cart;
    }

    public function printInvoice(): void
    {
        echo "Shopping Cart Invoice:" . PHP_EOL;
        foreach ($this->cart->getProducts() as $p) {
            echo $p->name . " - Rs " . $p->price . PHP_EOL;
        }
        echo "Total: Rs " . $this->cart->calculateTotal() . PHP_EOL;
    }
}

interface Persistence
{
    public function save(ShoppingCart $cart): void;
}

class SQLPersistence implements Persistence
{
    public function save(ShoppingCart $cart): void
    {
        echo "Saving shopping cart to SQL DB..." . PHP_EOL;
    }
}

class MongoPersistence implements Persistence
{
    public function save(ShoppingCart $cart): void
    {
        echo "Saving shopping cart to MongoDB..." . PHP_EOL;
    }
}

class FilePersistence implements Persistence
{
    public function save(ShoppingCart $cart): void
    {
        echo "Saving shopping cart to a file..." . PHP_EOL;
    }
}

class OCPFollowed
{
    public static function main(): void
    {
        $cart = new ShoppingCart();
        $cart->addProduct(new Product("Laptop", 50000));
        $cart->addProduct(new Product("Mouse", 2000));

        $printer = new ShoppingCartPrinter($cart);
        $printer->printInvoice();

        /** @var Persistence $db */
        $db    = new SQLPersistence();
        $mongo = new MongoPersistence();
        $file  = new FilePersistence();

        $db->save($cart);    // Save to SQL database
        $mongo->save($cart); // Save to MongoDB
        $file->save($cart);  // Save to File
    }
}

OCPFollowed::main();
