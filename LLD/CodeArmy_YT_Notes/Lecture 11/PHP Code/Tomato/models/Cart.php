<?php

namespace Models;

require_once __DIR__ . '/MenuItem.php';
require_once __DIR__ . '/Restaurant.php';

class Cart
{
    private ?Restaurant $restaurant;
    /** @var MenuItem[] */
    private array $items = [];

    public function __construct()
    {
        $this->restaurant = null;
    }

    public function addItem(MenuItem $item): void
    {
        if ($this->restaurant === null) {
            file_put_contents('php://stderr', "Cart: Set a restaurant before adding items." . PHP_EOL);
            return;
        }
        $this->items[] = $item;
    }

    public function getTotalCost(): float
    {
        $sum = 0;
        foreach ($this->items as $it) {
            $sum += $it->getPrice();
        }
        return $sum;
    }

    public function isEmpty(): bool
    {
        return $this->restaurant === null || empty($this->items);
    }

    public function clear(): void
    {
        $this->items = [];
        $this->restaurant = null;
    }

    public function setRestaurant(?Restaurant $r): void
    {
        $this->restaurant = $r;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    /** @return MenuItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
