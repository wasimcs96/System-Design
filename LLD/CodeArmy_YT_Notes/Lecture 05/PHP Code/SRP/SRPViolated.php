<?php

// Product class representing any item of any ECommerce.
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

// Violating SRP: ShoppingCart is handling multiple responsibilities
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

    // 1. Calculates total price in cart.
    public function calculateTotal(): float
    {
        $total = 0;
        foreach ($this->products as $p) {
            $total += $p->price;
        }
        return $total;
    }

    // 2. Violating SRP - Prints invoice (Should be in a separate class)
    public function printInvoice(): void
    {
        echo "Shopping Cart Invoice:" . PHP_EOL;
        foreach ($this->products as $p) {
            echo $p->name . " - Rs " . $p->price . PHP_EOL;
        }
        echo "Total: Rs " . $this->calculateTotal() . PHP_EOL;
    }

    // 3. Violating SRP - Saves to DB (Should be in a separate class)
    public function saveToDatabase(): void
    {
        echo "Saving shopping cart to database..." . PHP_EOL;
    }
}

class SRPViolated
{
    public static function main(): void
    {
        $cart = new ShoppingCart();

        $cart->addProduct(new Product("Laptop", 50000));
        $cart->addProduct(new Product("Mouse", 2000));

        $cart->printInvoice();
        $cart->saveToDatabase();
    }
}

SRPViolated::main();
