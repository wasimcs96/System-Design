<?php
require_once __DIR__ . '/Cart.php';
 
class User{
    public static $ids = 0;
    public int $id;
    public string $name;
    public string $address;
    public Cart $cart;
    
    public function __construct(string $name){
        $this->name = $name;
        $this->id = ++self::$ids;
        $this->cart = new Cart();
    }

    public function getAddrees(){
        return $this->address;
    }

    public function getCart(){
        return $this->cart;
    }
}