<?php

namespace Factories;

require_once __DIR__ . '/OrderFactory.php';
require_once __DIR__ . '/../models/DeliveryOrder.php';
require_once __DIR__ . '/../models/PickupOrder.php';

use Models\Cart;
use Models\DeliveryOrder;
use Models\Order;
use Models\PickupOrder;
use Models\Restaurant;
use Models\User;
use Strategies\PaymentStrategy;

class ScheduledOrderFactory implements OrderFactory
{
    private string $scheduleTime;

    public function __construct(string $scheduleTime)
    {
        $this->scheduleTime = $scheduleTime;
    }

    public function createOrder(User $user, Cart $cart, Restaurant $restaurant, array $menuItems,
                                PaymentStrategy $paymentStrategy, float $totalCost, string $orderType): Order
    {
        $order = null;

        if ($orderType === "Delivery") {
            $deliveryOrder = new DeliveryOrder();
            $deliveryOrder->setUserAddress($user->getAddress());
            $order = $deliveryOrder;
        } else {
            $pickupOrder = new PickupOrder();
            $pickupOrder->setRestaurantAddress($restaurant->getLocation());
            $order = $pickupOrder;
        }

        $order->setUser($user);
        $order->setRestaurant($restaurant);
        $order->setItems($menuItems);
        $order->setPaymentStrategy($paymentStrategy);
        $order->setScheduled($this->scheduleTime);
        $order->setTotal($totalCost);
        return $order;
    }
}
