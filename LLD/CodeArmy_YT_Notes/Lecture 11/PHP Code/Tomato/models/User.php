<?php

namespace Models;

require_once __DIR__ . '/Cart.php';

class User
{
    private int $userId;
    private string $name;
    private string $address;
    private Cart $cart;

    public function __construct(int $userId, string $name, string $address)
    {
        $this->userId = $userId;
        $this->name = $name;
        $this->address = $address;
        $this->cart = new Cart();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $a): void
    {
        $this->address = $a;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}
