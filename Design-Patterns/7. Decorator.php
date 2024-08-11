<?php
//Decorator is a structural pattern that allows adding new behaviors to objects dynamically by placing them inside special wrapper objects, called decorators.

//Using decorators you can wrap objects countless number of times since both target objects and decorators follow the same interface. 
//The resulting object will get a stacking behavior of all wrappers.

interface Component{
    public function operation();
} 

class ConcreateComponent implements Component{
    public function operation(){
        return "1-->ConcreateComponent Operation";
    }
}

class Decorate implements Component{
    private $component;
    public function __construct(Component $component){
        $this->component = $component;
    }

    public function operation(){
        return $this->component->operation();
    }
}

// $concreteClass = new ConcreateComponent();
// $decorate = new Decorate($concreteClass);
// $decorate->operation();
// echo "\n";

class concreateDecoratoreA extends Decorate{
    public function operation(){
        return "2-->concreateDecoratoreA-->".parent::operation();
    }
}

class concreateDecoratoreB extends Decorate{
    public function operation(){
        return "3-->concreateDecoratoreB-->".parent::operation();
    }
}

// $concreteClass = new ConcreateComponent();
// $concreateDecoratoreA = new concreateDecoratoreA($concreteClass);
// $concreateDecoratoreB = new concreateDecoratoreB($concreateDecoratoreA);
// echo $concreateDecoratoreB->operation();



//Pizaa making Software
//Pizza making software is a good example of the decorator pattern. The software can be used to make
//different types of pizzas. The software can be extended to make different types of pizzas by adding
//new decorators. The software can be used to make different types of pizzas by combining different

abstract class BasePizza{
    public abstract function getCost();
}

class FarHousePizaa extends BasePizza{
    public function getCost(){
        return 100;
    }
}

abstract class DecoratePizaa extends BasePizza{
    public abstract function getCost();
}

class DecorateExtraChees extends DecoratePizaa{
    public $basePizza;
    public function __construct(BasePizza $basePizza){
        $this->basePizza = $basePizza;
    }

    public function getCost(){
        return $this->basePizza->getCost() + 10;
    }
}

class DecorateExtraPaneer extends DecoratePizaa{
    public $basePizza;
    public function __construct(BasePizza $basePizza){
        $this->basePizza = $basePizza;
    }

    public function getCost(){
        return $this->basePizza->getCost() + 20;
    }
}

$basePizza = new FarHousePizaa();
$extraCheesPizza = new DecorateExtraChees($basePizza);
$extraPaneerPizza = new DecorateExtraPaneer($extraCheesPizza);

echo $extraPaneerPizza->getCost();
?>