<?php

class creditCardPayment implements PaymentStrategy{

    public string $card = '';

    public function __construct(string $card){
        $this->card = $card;
    }
    public function pay(float $amount){
        echo "Payment has been donw with credit card";
    }
}