<?php

class Customer {
    private $name;
    private $email;
    private $address;

    public function __construct($name, $email, $address) {
        $this->name = $name;
        $this->email = $email;
        $this->address = $address;
    }

    public function customerDeatils(){
        echo "Name : ".$this->name; 
        echo "<br>Email Id : " .$this->email ;
        echo "<br>Address : ". $this->address;  
    }

    public function getName(){
        return $this->name;
    }

    // Other methods for customer-related functionality
}

class Order {
    private $orderId;
    private $totalAmount;
    private $customer; // Reference to Customer object

    public function __construct($orderId, $totalAmount, Customer $customer) {
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->customer = $customer;
    }

    public function getOrderDetails() {
        return "Order ID: {$this->orderId}, Total Amount: {$this->totalAmount}, Customer: {$this->customer->getName()}";
    }

    public function clientDeatils(){
        $this->customer->customerDeatils();
    }
}


// Create a customer
$customer = new Customer("John Doe", "john@example.com", "123 Main St");

// Create an order associated with the customer
$order = new Order(1001, 150.00, $customer);

// Get order details
echo $order->getOrderDetails();
$order->clientDeatils();



?>
