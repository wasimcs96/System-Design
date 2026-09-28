<?php

namespace Strategies;

require_once __DIR__ . '/PaymentStrategy.php';

class CreditCardPaymentStrategy implements PaymentStrategy
{
    private string $cardNumber;

    public function __construct(string $card)
    {
        $this->cardNumber = $card;
    }

    public function pay(float $amount): void
    {
        echo "Paid ₹" . $amount . " using Credit Card (" . $this->cardNumber . ")" . PHP_EOL;
    }
}
