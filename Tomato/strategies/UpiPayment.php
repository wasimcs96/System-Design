<?php

class UpiPayment implements PaymentStrategy{
    public string $mobile;

    public function __construct(string $mobile){
        $this->mobile = $mobile;
    }
    public function pay(float $amount){
        echo "Payment has been done with UPI";
    }
}