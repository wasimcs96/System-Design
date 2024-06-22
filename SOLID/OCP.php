<?php
//https://blog.devgenius.io/open-closed-principle-ocp-by-using-php-solid-principle-f0ceae519bcf
//https://medium.com/@aiman.asfia/embracing-the-open-closed-principle-in-laravel-a-real-life-example-2cbfce4b78d6
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

// $discountProcess = new DiscountProcessor();
// $productPrice = 100;

// $percentageDiscount = new PercentageDiscount(25); //  25% off //class is also interface type DiscountInterface
// echo "After percentage discount: ". $discountProcess->applyDiscount($productPrice, $percentageDiscount). "\n";

// $fixedDiscount = new FixedAmountDiscount(20);
// echo "Original price: ", $productPrice, "\n";
// echo "After percentage discount: ", $discountProcess->applyDiscount($productPrice, $fixedDiscount);


//Another Example Without OCP
class Payment{
    protected string $customerName;
    protected string $cardNumber;
    protected int $balance = 1000;
    protected int $amount;

    // protected $crediteCardServiceCharge = 50;
    // protected $paypalCardServiceCharge = 40;
    protected $paymentTypes = array(1 => 'creditCard', 2 => 'paypal');
    protected $paymentModeServiceCharges = array(1 => 50, 2 => 40);

    protected int $paymentMode; 
    protected int $serviceCharge;

    public function __construct(string $customerName, string $cardNumber, int $amount, int $paymentType){
        $this->customerName = $customerName;
        $this->cardNumber = $cardNumber;
        $this->amount = $amount;
        $this->setPaymentType($paymentType);
    }

    public function setPaymentType(int $paymentType){
        if(!in_array($paymentType, array_keys($this->paymentTypes))){
            throw new Exception("Invalid payment type");
        }else{
          $this->paymentMode = $paymentType;
          $this->serviceCharge = $this->paymentModeServiceCharges[$paymentType];
        }
    }

    public function pay(){
        $this->balance = $this->balance - $this->amount-$this->serviceCharge; 
        echo "Paid successfully through ".$this->paymentTypes[$this->paymentMode].". Your current balance is ". $this->balance ."\n";
    }

}

// $payment = new Payment("Wasim", "432143214321", 100, 1);
// $payment->pay();
// $payment1 = new Payment("Wasim", "432143214321", 100, 2);
// $payment1->pay();

//used OCP in above code

abstract class PaymentProcess{
    protected string $customerName;
    protected string $cardNumber;
    protected int $balance = 1000;

    public function __construct(string $customerName, string $cardNumber){
        $this->customerName = $customerName;
        $this->cardNumber = $cardNumber;
    }

    public abstract function pay(int $amount); 
}

class CreditCardPayment extends  PaymentProcess {
    private $serviceCharge = 25;

    public function pay(int $amount){
        $this->balance = $this->balance - $amount-$this->serviceCharge; 
        echo "Paid successfully through CreditCard. Your current balance is ". $this->balance ."\n";
    }
}

class PaypalPayment extends  PaymentProcess {
    private $serviceCharge = 50;

    public function pay(int $amount){
        $this->balance = $this->balance - $amount - $this->serviceCharge; 
        echo "Paid successfully through Paypal. Your current balance is ". $this->balance ."\n";
    }
}

class MakePayment{
    Private $paymentProcess;
    public function __construct(PaymentProcess $paymentProcess){
        $this->paymentProcess = $paymentProcess;
    }
    public function pay(int $amount){
        $this->paymentProcess->pay($amount);
    }
}


$CreditCardPayment = new CreditCardPayment("Wasim", "432143214321");
$makeCreditPayment = new MakePayment($CreditCardPayment);
$makeCreditPayment->pay(100);

$PaypalPayment = new PaypalPayment("Wasim", "432143214321");
$makePaypalPayment = new MakePayment($PaypalPayment);
$makePaypalPayment->pay(100);


?>
