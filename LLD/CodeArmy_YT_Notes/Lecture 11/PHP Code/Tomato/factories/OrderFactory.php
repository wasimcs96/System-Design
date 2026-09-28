<?php

namespace Factories;

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../strategies/PaymentStrategy.php';

use Models\Cart;
use Models\MenuItem;
use Models\Order;
use Models\Restaurant;
use Models\User;
use Strategies\PaymentStrategy;

interface OrderFactory
{
    /** @param MenuItem[] $menuItems */
    public function createOrder(User $user, Cart $cart, Restaurant $restaurant, array $menuItems,
                                PaymentStrategy $paymentStrategy, float $totalCost, string $orderType): Order;
}
