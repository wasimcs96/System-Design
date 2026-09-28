<?php

namespace Services;

require_once __DIR__ . '/../models/Order.php';

use Models\Order;

class NotificationService
{
    public static function notify(Order $order): void
    {
        echo PHP_EOL . "Notification: New " . $order->getType() . " order placed!" . PHP_EOL;
        echo "---------------------------------------------" . PHP_EOL;
        echo "Order ID: " . $order->getOrderId() . PHP_EOL;
        echo "Customer: " . $order->getUser()->getName() . PHP_EOL;
        echo "Restaurant: " . $order->getRestaurant()->getName() . PHP_EOL;
        echo "Items Ordered:" . PHP_EOL;

        $items = $order->getItems();
        foreach ($items as $item) {
            echo "   - " . $item->getName() . " (₹" . $item->getPrice() . ")" . PHP_EOL;
        }

        echo "Total: ₹" . $order->getTotal() . PHP_EOL;
        echo "Scheduled For: " . $order->getScheduled() . PHP_EOL;
        echo "Payment: Done" . PHP_EOL;
        echo "---------------------------------------------" . PHP_EOL;
    }
}
