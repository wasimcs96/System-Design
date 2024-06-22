<?php
//Builder is a creational design pattern, which allows constructing complex objects step by step.

//Unlike other creational patterns, Builder doesn’t require products to have a common interface. That makes it possible to produce different products using the same construction process.

interface BuilderInterface{
    public function ProductPartA();
    public function ProductPartB();
    public function ProductPartC();
    public function ProductPartD();
}

class ProductBuilder implements BuilderInterface{
    public $product;

    function __construct(){
        $this->reset();
    }

    public function reset(){
        $this->product = new BaseProduct();
    }

    public function ProductPartA(){
        $this->product->partA[]= "PartA added into Product";
    }
    public function ProductPartB(){
        $this->product->partA[]= "PartB added into Product";
    }
    public function ProductPartC(){
        $this->product->partA[]= "PartC added into Product";
    }
    public function ProductPartD(){
        $this->product->partA[]= "PartD added into Product";
    }
    public function getFinalBuildProduct(){
        return $this->product;
    }
}

class BaseProduct{
    public $partA;
    public function getproduct() { 
        return $this->partA; 
    }
}

class Director{
    public $builder;
    public function __construct(BuilderInterface $build){
        $this->builder = $build;
    }

    function buildMinimalViableProduct(){
        $this->builder->ProductPartA();
        $this->builder->ProductPartB();
    }

    function buildAdvancedViableProduct(){
        $this->builder->ProductPartC();
        $this->builder->ProductPartD();
    }
}

$builderClass = new ProductBuilder();
$director = new Director($builderClass);

//setminimalProductfrom base product
$director->buildMinimalViableProduct();
print_r($director->builder->getFinalBuildProduct()->getproduct());

//setAdvanceProductfrom base product
$director->buildAdvancedViableProduct();
print_r($director->builder->getFinalBuildProduct()->getproduct());

//customize without director
$CustomBuilderClass = new ProductBuilder();
$CustomBuilderClass->ProductPartA();
$CustomBuilderClass->ProductPartC();
print_r($CustomBuilderClass->getFinalBuildProduct()->getproduct());


?>