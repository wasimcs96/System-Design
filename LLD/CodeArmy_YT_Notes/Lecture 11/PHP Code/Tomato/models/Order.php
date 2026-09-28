<?php

namespace Models;

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Restaurant.php';
require_once __DIR__ . '/MenuItem.php';
require_once __DIR__ . '/../strategies/PaymentStrategy.php';

use Strategies\PaymentStrategy;

abstract class Order
{
    private static int $nextOrderId = 0;

    protected int $orderId;
    protected ?User $user;
    protected ?Restaurant $restaurant;
    /** @var MenuItem[] */
    protected array $items = [];
    protected ?PaymentStrategy $paymentStrategy;
    protected float $total;
    protected string $scheduled;

    public function __construct()
    {
        $this->user = null;
        $this->restaurant = null;
        $this->paymentStrategy = null;
        $this->total = 0.0;
        $this->scheduled = "";
        $this->orderId = ++self::$nextOrderId;
    }

    public function processPayment(): bool
    {
        if ($this->paymentStrategy !== null) {
            $this->paymentStrategy->pay($this->total);
            return true;
        } else {
            echo "Please choose a payment mode first" . PHP_EOL;
            return false;
        }
    }

    abstract public function getType(): string;

    // Getters and Setters
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setUser(User $u): void
    {
        $this->user = $u;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setRestaurant(Restaurant $r): void
    {
        $this->restaurant = $r;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    /** @param MenuItem[] $its */
    public function setItems(array $its): void
    {
        $this->items = $its;
        $this->total = 0;
        foreach ($this->items as $i) {
            $this->total += $i->getPrice();
        }
    }

    /** @return MenuItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setPaymentStrategy(PaymentStrategy $p): void
    {
        $this->paymentStrategy = $p;
    }

    public function setScheduled(string $s): void
    {
        $this->scheduled = $s;
    }

    public function getScheduled(): string
    {
        return $this->scheduled;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}
