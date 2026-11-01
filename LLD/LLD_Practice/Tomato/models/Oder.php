<?php

require_once __DIR__.'/Cart.php';
require_once __DIR__.'/User.php';
require_once __DIR__.'/Restaurent.php';
require_once __DIR__.'../strategies/PaymentStrategies.php';
require_once __DIR__.'/Cart.php';

abstract class  Order{

    public int $orderId;
    public ?User $user;
    public ?Restaurent $restaurent;
    public ?Cart $cart;
    public ?PaymentStrategy $paymentStrategy;
    public float $totalAmount;
    public string $scheduledOrder;

    public static int $uniqueId = 0;

    public function _construct(){
        $this->orderId = ++self::$uniqueId;
        $this->user = null;
        $this->restaurent = null;
        $this->cart = null;
        $this->paymentStrategy = null;
        $this->totalAmount = "0.0";
        $this->scheduledOrder = "";
    }

    public function setUser(User $user){
        $this->user = $user;
    }
    public function getUser() : User{
        return $this->user;
    }

    public function setRestaurent(Restaurent $restaurent){
        $this->restaurent = $restaurent;
    }
    public function getRestaurent() : Restaurent{
        return $this->restaurent;
    }

    public function setCart(Cart $restaurent){
        $this->cart = $restaurent;
    }
    public function getCart() : Cart{
        return $this->cart;
    }

    public function setPaymentStategy(PaymentStrategy $paymentStrategy){
        $this->paymentStrategy = $paymentStrategy;
    }
    public function getPaymentStategy() : PaymentStrategy{
        return $this->paymentStrategy;
    }

    public function processPayment(){
            $this->paymentStrategy->pay($this->totalAmount);
    }



}