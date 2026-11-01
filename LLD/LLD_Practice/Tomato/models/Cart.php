<?php

class Cart{
    public $itemList = [];
    public User $user;
    public ?Restaurent $restaurent;

    public function __construct(){}

    public function addItemIntoCart(MenuItem $item){
        $this->itemList[] =  $item;
    }

    public function itemList(){
        return $this->itemList;
    }

    public function totalAmount(){
        return '1000';
    }

    public function setRestaurent(Restaurent $r){
        $this->restaurent = $r;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurent;
    }

    public function clearCart(){
        $this->restaurent = null;
        $this->itemList = [];
    }
}