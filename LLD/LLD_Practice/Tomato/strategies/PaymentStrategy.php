<?php

interface PaymentStrategy{
    public function pay(float $amount);
}