<?php
// Dependency Inversion Principle
//https://blog.devgenius.io/dependency-inversion-principle-dip-by-using-php-solid-principle-e8745d60550f

class Store{
    private $paymentGateway;
    function __construct(Stripe $paymentGateway){
        $this->paymentGateway = $paymentGateway;
    }

    function paymentProcessCharge(int $chargeAmount){
        return $this->paymentGateway->makeCharge($chargeAmount);
    }
}
class Stripe{
    private $card;
    public function __construct(string $card){
        $this->card = $card;
    }
    public function makeCharge(int $chargeAmount){
        echo "Process Amount has been deducted $chargeAmount from your card $this->card";
    }
}

$stripe = new Stripe("41111111111");
$store = new Store($stripe);
$store->paymentProcessCharge(100);

// Issues in this pattern
// Tight Coupling: The Store class is tightly coupled to the Stripe class. This means that if you ever want to change the payment processor (for example, switching from Stripe to PayPal), you would need to modify the Store class directly. This violates the principle of code flexibility and maintainability.

// Limited Extensibility: Since the Store class directly depends on the Stripe class, it cannot easily accommodate other payment processors without modification. Adding support for a new payment processor would require changing the Store class, which could introduce errors or unexpected behavior.

// Difficulty in Testing: Testing the Store class in isolation becomes challenging because it's tightly coupled to the Stripe class. This makes it harder to write unit tests for the Store class without also testing the Stripe class.

// Code Change With DIP

interface PaymentGateway{
    public function makeCharge(int $chargeAmount);
}

class Store{
    private $paymentGateway;
    function __construct(PaymentGateway $paymentGateway){
        $this->paymentGateway = $paymentGateway;
    }

    function paymentProcessCharge(int $chargeAmount){
        return $this->paymentGateway->makeCharge($chargeAmount);
    }
}

class Stripe implements PaymentGateway {
    private $card;
    public function __construct(string $card){
        $this->card = $card;
    }
    public function makeCharge(int $chargeAmount){
        echo "Stripe Process Amount has been deducted $chargeAmount from your card $this->card ".'/n';
    }
}

class Paypal implements PaymentGateway {
    private $card;
    public function __construct(string $card){
        $this->card = $card;
    }
    public function makeCharge(int $chargeAmount){
        echo "Paypal Process Amount has been deducted $chargeAmount from your card $this->card ".'/n';
    }
}

$stripe = new Stripe("41111111111");
$store = new Store($stripe);
$store->paymentProcessCharge(100);

$paypal = new Paypal("4999999999");
$store2 = new Store($paypal);
$store2->paymentProcessCharge(200);


?>