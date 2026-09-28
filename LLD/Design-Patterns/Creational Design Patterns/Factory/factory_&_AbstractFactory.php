<?php

interface Burger{
    public function prepare();
}
########
class BasicBurger implements Burger{
    public function prepare(){
        echo "Basic Burger is preparring....";
    }
}
class StanderedBurger implements Burger{
    public function prepare(){
        echo "Standered Burger is preparring....";
    }
}
class PremiumBurger implements Burger{
    public function prepare(){
        echo "Premium Burger is preparring....";
    }
}
########
class BasicNonVegBurger implements Burger{
    public function prepare(){
        echo "Basic NonVeg Burger is preparring....";
    }
}
class StanderedNonVegBurger implements Burger{
    public function prepare(){
        echo "Standered NonVeg Burger is preparring....";
    }
}
class PremiumNonVegBurger implements Burger{
    public function prepare(){
        echo "Premium NonVeg Burger is preparring....";
    }
}
##########

interface Pizza{
    public function prepare();
}
######
class BasicVegPizaa implements Pizza{
    public function prepare(){
        echo "Basic Veg Pizaa is preparing..";
    } 
}
class StanderedVegPizaa implements Pizza{
    public function prepare(){
        echo "Standered Veg Pizaa is preparing..";
    } 
}
class PremiumVegPizaa implements Pizza{
    public function prepare(){
        echo "Premium Veg Pizaa is preparing..";
    } 
}
#######
class BasicNonVegPizaa implements Pizza{
    public function prepare(){
        echo "Basic Non Veg Pizaa is preparing..";
    } 
}
class StanderedNonVegPizaa implements Pizza{
    public function prepare(){
        echo "Standered Non Veg Pizaa is preparing..";
    } 
}
class PremiumNonVegPizaa implements Pizza{
    public function prepare(){
        echo "Premium NON Veg Pizaa is preparing..";
    } 
}
#####

//Simple Factory
class SimpleBurgerFactory{
    public string $burgerType;

    public function __construct(string $type){
        $this->burgerType = $type;
    } 

    public function createBurger(): ?Burger
    {
        Switch($this->burgerType){
            case "Simple":    return new BasicBurger();
            case "Standered": return new StanderedBurger();
            case "Premium":   return new PremiumBurger();
            deafult :         return null;
        }
    }
}

//Factory Method :
//Have Onetype of products
interface FactoryMethod{
    public function createBurger();
}

class VegBurgerFactory{
    public string $burgerType;

    public function __construct(string $type){
        $this->burgerType = $type;
    } 

    public function createBurger(): ?Burger
    {
        Switch($this->burgerType){
            case "Simple":    return new BasicBurger();
            case "Standered": return new StanderedBurger();
            case "Premium":   return new PremiumBurger();
            deafult :         return null;
        }
    }
}

class NonVegBurgerFactory{
    public string $burgerType;

    public function __construct(string $type){
        $this->burgerType = $type;
    } 

    public function createBurger(): ?Burger
    {
        Switch($this->burgerType){
            case "Simple":    return new BasicNonVegBurger();
            case "Standered": return new StanderedNonVegBurger();
            case "Premium":   return new PremiumNonVegBurger();
            deafult :         return null;
        }
    }
}

//AbstractFactory Method
//Have Multitype of products
interface Meal{
    public function createBurger();
    public function createPizza();
}

class VegMeal implements Meal {
    public string $mealType;

    public function __construct(string $type){
        $this->mealType = $type;
    } 

    public function createBurger(): ?Burger
    {
        Switch($this->mealType){
            case "Simple":    return new BasicBurger();
            case "Standered": return new StanderedBurger();
            case "Premium":   return new PremiumBurger();
            deafult :         return null;
        }
    }

    public function createPizza(): ?Pizza
    {
        Switch($this->mealType){
            case "Simple":    return new BasicVegPizaa();
            case "Standered": return new StanderedVegPizaa();
            case "Premium":   return new PremiumVegPizaa();
            deafult :         return null;
        }
    }
}
class NonVegMeal implements Meal {
    public string $mealType;

    public function __construct(string $type){
        $this->mealType = $type;
    } 

    public function createBurger(): ?Burger
    {
        Switch($this->mealType){
            case "Simple":    return new BasicNonVegBurger();
            case "Standered": return new StanderedNonVegBurger();
            case "Premium":   return new PremiumNonVegBurger();
            deafult :         return null;
        }
    }

    public function createPizza(): ?Pizza
    {
        Switch($this->mealType){
            case "Simple":    return new BasicNonVegPizaa();
            case "Standered": return new StanderedNonVegPizaa();
            case "Premium":   return new PremiumNonVegPizaa();
            deafult :         return null;
        }
    }
}

class client{
    function main(){
        //Simple Factory
        echo "Simple Factory Exampl---------------";echo "\n";
        $burgerFactoryObject = new SimpleBurgerFactory('Simple');
        $burgerObject = $burgerFactoryObject->createBurger();
        $burgerObject->prepare();
        echo "\n";

        //Factory Method
        echo "Factory Method Exampl---------------";echo "\n";
        $vegBurgerFactoryObject = new VegBurgerFactory('Standered');
        $vegBurgerObject = $vegBurgerFactoryObject->createBurger();
        $vegBurgerObject->prepare();
        echo "\n";

        $nnVegburgerFactoryObject = new NonVegBurgerFactory('Premium');
        $nonVegburgerObject = $nnVegburgerFactoryObject->createBurger();
        $nonVegburgerObject->prepare();
        echo "\n";

        //Abstract Factory Method
        echo "Abstract FactoryExampl---------------";echo "\n";
        $vegMealFactoryObject = new VegMeal('Standered');
        $vegMealBurgerObject = $vegMealFactoryObject->createBurger();
        $vegMealBurgerObject->prepare();
        echo "\n";
        $vegMealPizzaObject = $vegMealFactoryObject->createPizza();
        $vegMealPizzaObject->prepare();
        echo "\n";

        $nonVegMealFactoryObject = new NonVegMeal('Standered');
        $nonVegMealBurgerObject = $nonVegMealFactoryObject->createBurger();
        $nonVegMealBurgerObject->prepare();
        echo "\n";
        $nonVegMealPizzaObject = $nonVegMealFactoryObject->createPizza();
        $nonVegMealPizzaObject->prepare();
        echo "\n";
    }
}
//Simple Factory
$client = new client();
$client->main();
