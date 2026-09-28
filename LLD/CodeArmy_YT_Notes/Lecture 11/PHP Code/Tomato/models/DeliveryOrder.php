<?php

namespace Models;

require_once __DIR__ . '/Order.php';

class DeliveryOrder extends Order
{
    private string $userAddress;

    public function __construct()
    {
        parent::__construct();
        $this->userAddress = "";
    }

    public function getType(): string
    {
        return "Delivery";
    }

    public function setUserAddress(string $addr): void
    {
        $this->userAddress = $addr;
    }

    public function getUserAddress(): string
    {
        return $this->userAddress;
    }

    // Implement remaining Order methods with actual fields
}
