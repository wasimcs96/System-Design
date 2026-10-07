<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/DeliveryOrder.php';
require_once __DIR__ . '/../models/PickUpOrder.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../strategies/PaymentStrategy.php';



class NowDeliveryFactory implements IOrderFactory{

    public function __construct(){ }

    public function createOrder(
        User $user,
        Restaurent $restaurent,
        Cart $cart,
        PaymentStrategy $paymentStrategy,
        string $orderType
    ){
        $order = null;
        if($orderType == 'Delivery'){
            $order = new DeliveryOrder();
        }else{
            $order = new PickUpOrder();
        }

        $order->setUser($user);
        $order->setRestaurent($restaurent);
        $order->setCart($cart);
        $order->setPaymentStategy($paymentStrategy);

        return $order;

    }
}