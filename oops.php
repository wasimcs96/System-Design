<?php

// DiscountInterface.php
interface DiscountInterface {
    public function applyDiscount($price);
}

// PercentageDiscount.php
class PercentageDiscount implements DiscountInterface {
    private $percentage;

    public function __construct($percentage) {
        $this->percentage = $percentage;
    }

    public function applyDiscount($price) {
        return $price - ($price * $this->percentage / 100);
    }
}

// FixedAmountDiscount.php
class FixedAmountDiscount implements DiscountInterface {
    private $amount;

    public function __construct($amount) {
        $this->amount = $amount;
    }

    public function applyDiscount($price) {
        return $price - $this->amount;
    }
}

// DiscountProcessor.php
class DiscountProcessor {
    public function applyDiscount($price, DiscountInterface $discount) {
        return $discount->applyDiscount($price);
    }
}

$discountProcess = new DiscountProcessor();
$productPrice = 100;

$percentageDiscount = new PercentageDiscount(25); //  25% off
echo "After percentage discount: ". $discountProcess->applyDiscount($productPrice, $percentageDiscount). "\n";

$fixedDiscount = new FixedAmountDiscount(20);
echo "Original price: ", $productPrice, "\n";
echo "After percentage discount: ", $discountProcess->applyDiscount($productPrice, $fixedDiscount);






?>
