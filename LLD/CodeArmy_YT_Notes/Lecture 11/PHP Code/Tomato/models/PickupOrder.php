<?php

namespace Models;

require_once __DIR__ . '/Order.php';

class PickupOrder extends Order
{
    private string $restaurantAddress;

    public function __construct()
    {
        parent::__construct();
        $this->restaurantAddress = "";
    }

    public function getType(): string
    {
        return "Pickup";
    }

    public function setRestaurantAddress(string $addr): void
    {
        $this->restaurantAddress = $addr;
    }

    public function getRestaurantAddress(): string
    {
        return $this->restaurantAddress;
    }

    // Implement remaining Order methods with actual fields
}
