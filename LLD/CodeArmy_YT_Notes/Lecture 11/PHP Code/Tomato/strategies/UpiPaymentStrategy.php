<?php

namespace Strategies;

require_once __DIR__ . '/PaymentStrategy.php';

class UpiPaymentStrategy implements PaymentStrategy
{
    private string $mobile;

    public function __construct(string $mob)
    {
        $this->mobile = $mob;
    }

    public function pay(float $amount): void
    {
        echo "Paid ₹" . $amount . " using UPI (" . $this->mobile . ")" . PHP_EOL;
    }
}
