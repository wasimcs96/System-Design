<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../strategies/PaymentStrategy.php';


interface IOrderFactory{
    public function createOrder(
        User $user,
        Restaurent $restaurent,
        Cart $cart,
        PaymentStrategy $PaymentStrategy,
        string $orderType
    );
}