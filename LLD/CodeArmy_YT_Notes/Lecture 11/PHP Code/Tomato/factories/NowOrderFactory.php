<?php

namespace Factories;

require_once __DIR__ . '/OrderFactory.php';
require_once __DIR__ . '/../models/DeliveryOrder.php';
require_once __DIR__ . '/../models/PickupOrder.php';
require_once __DIR__ . '/../utils/TimeUtils.php';

use Models\Cart;
use Models\DeliveryOrder;
use Models\Order;
use Models\PickupOrder;
use Models\Restaurant;
use Models\User;
use Strategies\PaymentStrategy;
use Utils\TimeUtils;

class NowOrderFactory implements OrderFactory
{
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
        $order->setScheduled(TimeUtils::getCurrentTime());
        $order->setTotal($totalCost);
        return $order;
    }
}
