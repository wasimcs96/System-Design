<?php

// Product Interface and subclasses
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

// Factory Interface and Concrete Factories
interface BurgerFactory
{
    public function createBurger(string $type): ?Burger;
}

class SinghBurger implements BurgerFactory
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

class KingBurger implements BurgerFactory
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
}

// Main Class
class FactoryMethod
{
    public static function main(): void
    {
        $type = "basic";

        /** @var BurgerFactory $myFactory */
        $myFactory = new SinghBurger();
        $burger = $myFactory->createBurger($type);

        if ($burger !== null) {
            $burger->prepare();
        }
    }
}

FactoryMethod::main();
