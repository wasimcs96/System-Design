<?php

namespace Models;

require_once __DIR__ . '/MenuItem.php';

class Restaurant
{
    private static int $nextRestaurantId = 0;
    private int $restaurantId;
    private string $name;
    private string $location;
    /** @var MenuItem[] */
    private array $menu = [];

    public function __construct(string $name, string $location)
    {
        $this->name = $name;
        $this->location = $location;
        $this->restaurantId = ++self::$nextRestaurantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $loc): void
    {
        $this->location = $loc;
    }

    public function addMenuItem(MenuItem $item): void
    {
        $this->menu[] = $item;
    }

    /** @return MenuItem[] */
    public function getMenu(): array
    {
        return $this->menu;
    }
}
