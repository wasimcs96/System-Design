<?php

class CheckAvailibility{
    function check(){
        echo "Item is available in Store" .PHP_EOL;
    }
}

class CalculateDeliveryTime{
    function calculate(){
        echo "Calculation is complete".PHP_EOL;
    }
}

class Order{
    function cart(){
        echo "Cart is not empty".PHP_EOL;
    }

    function totolAmount(){
        return 100;
    }
}

class Payment{

    public function __construct(){} 

    function pay(Order $order){
        echo "Payment has been done of - ". $order->totolAmount().PHP_EOL;
    }
}

class Notification{
    public function sendNotificatiob(){
        echo "Notification has been send".PHP_EOL;
    }
}

class FacadeOrderProcess{
        public CheckAvailibility $checkAvailibility;
        public calculateDeliveryTime $calculateDeliveryTime;
        public Order $order;
        public Payment $payment;
        public Notification $notification;
    public function __construct(){
        $this->checkAvailibility = new CheckAvailibility();
        $this->calculateDeliveryTime =new CalculateDeliveryTime();
        $this->order = new Order();
        $this->payment = new Payment();
        $this->notification = new Notification();
    }

    public function process(){
        echo "------PaymentProcessStart--------".PHP_EOL;
        $this->checkAvailibility->check();
        $this->calculateDeliveryTime->calculate();
        $this->payment->pay($this->order);
        $this->notification->sendNotificatiob();

    }
}

class Client{
    static function main(){
        $facede = new FacadeOrderProcess();
        $facede->process();
    }
}

Client::main();