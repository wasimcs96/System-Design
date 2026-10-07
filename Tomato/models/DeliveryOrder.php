<?php 

require_once __DIR__ . '/Order.php';

class DeliveryOrder extends Order{
    public string $deliveryAddress;

    public function __construct(){
        $this->deliveryAddress = '';
    }

    public function getType(){
        return 'Delivery';
    }

    public function setAddress(string $add){
        $this->deliveryAddress = $add;
    }

    public function getAddress(){
        return $this->deliveryAddress;
    }

}