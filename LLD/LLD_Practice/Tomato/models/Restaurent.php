<?php

class Restaurent{
    public static $ids = 0;
    public int $id;
    public string $name;
    public string $location;
    
    public function __construct(string $name, string $location){
        $this->name = $name;
        $this->location = $location;
        $this->id = ++self::$ids;
        
    }

    public function getAddrees(){
        return $this->location;
    }
}