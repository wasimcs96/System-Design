<?php

class PickUpOrder extends Order{
    public string $restaurentAddress;

    public function __construct(){
        $this->restaurentAddress = '';
    }

    public function getType(){
        return 'Delivery';
    }

    public function setAddress(string $add){
        $this->restaurentAddress = $add;
    }

    public function getAddress(){
        return $this->restaurentAddress;
    }

}