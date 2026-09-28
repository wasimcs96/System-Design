<?php

namespace Strategies;

interface PaymentStrategy
{
    public function pay(float $amount): void;
}
