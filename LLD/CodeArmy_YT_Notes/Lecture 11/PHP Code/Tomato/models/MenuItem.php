<?php

namespace Models;

class MenuItem
{
    private string $code;
    private string $name;
    private int $price;

    public function __construct(string $code, string $name, int $price)
    {
        $this->code = $code;
        $this->name = $name;
        $this->price = $price;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $c): void
    {
        $this->code = $c;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $p): void
    {
        $this->price = $p;
    }
}
