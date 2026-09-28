<?php

namespace Managers;

require_once __DIR__ . '/../models/Order.php';

use Models\Order;

// Singleton
class OrderManager
{
    /** @var Order[] */
    private array $orders = [];
    private static ?OrderManager $instance = null;

    private function __construct()
    {
        // Private Constructor
    }

    public static function getInstance(): OrderManager
    {
        if (self::$instance === null) {
            self::$instance = new OrderManager();
        }
        return self::$instance;
    }

    public function addOrder(Order $order): void
    {
        $this->orders[] = $order;
    }

    public function listOrders(): void
    {
        echo PHP_EOL . "--- All Orders ---" . PHP_EOL;
        foreach ($this->orders as $order) {
            echo $order->getType() . " order for " . $order->getUser()->getName()
                . " | Total: ₹" . $order->getTotal()
                . " | At: " . $order->getScheduled() . PHP_EOL;
        }
    }
}
