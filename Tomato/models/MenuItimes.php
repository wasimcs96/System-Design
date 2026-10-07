<?php 

class MenuItem{
    public Restaurent $restaurent;
    public string $itemCode;
    public string $itemName;
    public string $price;

    public function _construct(string $itemCode, string $name, string $price){
        $this->itemCode = $itemCode;
        $this->itemName = $name;
        $this->price = $price;
    }
}