<?php
// Interface Segregation Principle

//Without ISP
interface EmployeeInterface {
    public function takeOrder();
    public function washDishes();
    public function cookFood();
}

class Waiter implements EmployeeInterface {
    public function takeOrder() {
      echo "take order.";  
    }
    
    public function washDish() {
      echo "not my job.";  
    }
    
    public function cookFood() {
      echo "not my job.";  
    }
}
// Interface segregation principle states:
// A client should never be forced to implement an interface that it doesn’t use, 
// or clients shouldn’t be forced to depend on methods they do not use
$foo = new Waiter();
$foo->takeOrder();


//Using ISP 
//interfaces
interface WaiterInterface {
    public function takeOrder();
}

interface ChefInterface {
    public function cookFood();
}

interface DishWasherInterface {
    public function washDish();
}

// In this updated code snippet, we’ve applied the Interface Segregation Principle (ISP) by breaking down the large Employee interface into smaller, 
// more specialized interfaces: WaiterInterface, ChefInterface, and DishWasherInterface. Each interface contains only the methods relevant to the specific responsibilities of the corresponding class.

//classes
class Waiter implements WaiterInterface {
    public function takeOrder() {
        echo "take order.";
    }
}

class Chef implements ChefInterface {
    public function cookFood() {
        echo "cook food.";
    }
}

class DishWasher implements DishWasherInterface {
    public function washDish() {
        echo "wash dish.";
    }
}
?>