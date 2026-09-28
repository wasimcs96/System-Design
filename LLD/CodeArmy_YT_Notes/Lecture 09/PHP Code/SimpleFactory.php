<?php

// --- Burger Interface ---
interface Burger
{
    public function prepare(): void;
}

// --- Concrete Burger Implementations ---
class BasicBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Basic Burger with bun, patty, and ketchup!" . PHP_EOL;
    }
}

class StandardBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Standard Burger with bun, patty, cheese, and lettuce!" . PHP_EOL;
    }
}

class PremiumBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Premium Burger with gourmet bun, premium patty, cheese, lettuce, and secret sauce!" . PHP_EOL;
    }
}

// --- Burger Factory ---
class BurgerFactory
{
    public function createBurger(string $type): ?Burger
    {
        if (strcasecmp($type, "basic") === 0) {
            return new BasicBurger();
        } elseif (strcasecmp($type, "standard") === 0) {
            return new StandardBurger();
        } elseif (strcasecmp($type, "premium") === 0) {
            return new PremiumBurger();
        } else {
            echo "Invalid burger type!" . PHP_EOL;
            return null;
        }
    }
}

// --- Main Class ---
class SimpleFactory
{
    public static function main(): void
    {
        $type = "standard";

        $myBurgerFactory = new BurgerFactory();

        $burger = $myBurgerFactory->createBurger($type);

        if ($burger !== null) {
            $burger->prepare();
        }
    }
}

SimpleFactory::main();
