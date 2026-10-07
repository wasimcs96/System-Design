<?php

// --- Product 1 --> Burger ---
interface Burger
{
    public function prepare(): void;
}

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

class BasicWheatBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Basic Wheat Burger with bun, patty, and ketchup!" . PHP_EOL;
    }
}

class StandardWheatBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Standard Wheat Burger with bun, patty, cheese, and lettuce!" . PHP_EOL;
    }
}

class PremiumWheatBurger implements Burger
{
    public function prepare(): void
    {
        echo "Preparing Premium Wheat Burger with gourmet bun, premium patty, cheese, lettuce, and secret sauce!" . PHP_EOL;
    }
}

// --- Product 2 --> GarlicBread ---
interface GarlicBread
{
    public function prepare(): void;
}

class BasicGarlicBread implements GarlicBread
{
    public function prepare(): void
    {
        echo "Preparing Basic Garlic Bread with butter and garlic!" . PHP_EOL;
    }
}

class CheeseGarlicBread implements GarlicBread
{
    public function prepare(): void
    {
        echo "Preparing Cheese Garlic Bread with extra cheese and butter!" . PHP_EOL;
    }
}

class BasicWheatGarlicBread implements GarlicBread
{
    public function prepare(): void
    {
        echo "Preparing Basic Wheat Garlic Bread with butter and garlic!" . PHP_EOL;
    }
}

class CheeseWheatGarlicBread implements GarlicBread
{
    public function prepare(): void
    {
        echo "Preparing Cheese Wheat Garlic Bread with extra cheese and butter!" . PHP_EOL;
    }
}

// --- Abstract Factory ---
interface MealFactory
{
    public function createBurger(string $type): ?Burger;
    public function createGarlicBread(string $type): ?GarlicBread;
}

// --- Concrete Factory 1 ---
class SinghBurger implements MealFactory
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

    public function createGarlicBread(string $type): ?GarlicBread
    {
        if (strcasecmp($type, "basic") === 0) {
            return new BasicGarlicBread();
        } elseif (strcasecmp($type, "cheese") === 0) {
            return new CheeseGarlicBread();
        } else {
            echo "Invalid Garlic bread type!" . PHP_EOL;
            return null;
        }
    }
}

// --- Concrete Factory 2 ---
class KingBurger implements MealFactory
{
    public function createBurger(string $type): ?Burger
    {
        if (strcasecmp($type, "basic") === 0) {
            return new BasicWheatBurger();
        } elseif (strcasecmp($type, "standard") === 0) {
            return new StandardWheatBurger();
        } elseif (strcasecmp($type, "premium") === 0) {
            return new PremiumWheatBurger();
        } else {
            echo "Invalid burger type!" . PHP_EOL;
            return null;
        }
    }

    public function createGarlicBread(string $type): ?GarlicBread
    {
        if (strcasecmp($type, "basic") === 0) {
            return new BasicWheatGarlicBread();
        } elseif (strcasecmp($type, "cheese") === 0) {
            return new CheeseWheatGarlicBread();
        } else {
            echo "Invalid Garlic bread type!" . PHP_EOL;
            return null;
        }
    }
}

// --- Main Class ---
class AbstractFactory
{
    public static function main(): void
    {
        $burgerType = "basic";
        $garlicBreadType = "cheese";

        /** @var MealFactory $mealFactory */
        $mealFactory = new SinghBurger();

        $burger = $mealFactory->createBurger($burgerType);
        $garlicBread = $mealFactory->createGarlicBread($garlicBreadType);

        if ($burger !== null) $burger->prepare();
        if ($garlicBread !== null) $garlicBread->prepare();
    }
}

AbstractFactory::main();
