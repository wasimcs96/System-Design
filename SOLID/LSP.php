<!-- Liskov Substitution Principle (LSP) By Using PHP : SOLID Principle

“Objects of super classes must be replaceable with the objects of its sub-classes without affecting the correctness.”

What is the Liskov Substitution Principle?
Imagine you’re in a coffee shop. You have a coffee machine that can make different types of coffee, 
like espresso, latte, and cappuccino. Each type of coffee machine is a bit different, 
but they all have a common method: brewCoffee(). 
The Liskov Substitution Principle is like the rule that says no matter what kind of coffee machine you use, 
you can always expect it to brew coffee without causing a coffee-spillage disaster. -->
<?php
//https://blog.devgenius.io/liskov-substitution-principle-lsp-by-using-php-solid-principle-e2c14991945e
//Exampel without LSP

abstract class Employee{
    Protected int $id;
    Protected string $name;
    Protected int  $salery;

    public function __construct(int $id, string $name, int $salery){
    }

    abstract public function calculateSalery(int $salery);
    abstract public function calculateBonus(int $bonus);
}

class ParmanentEmployee extends Employee{
    public function calculateSalery(int $salery){
        $this->salery = $salery;
        return $this->salery;
    }

    public function calculateBonus(int $bonus){
        $this->salery += $bonus;
        return $this->salery;
    }
}

class TemproryEmployee extends Employee{
    public function calculateSalery(int $salery){
        $this->salery = $salery;
        return $this->salery;
    }

    public function calculateBonus(int $bonus){
        throw new Exception('Not applicable.');
        // The TemproryEmployee class violates LSP by overriding calculateBonus to throw an exception. 
        // This breaks the expected behavior of the base class method, 
        // causing issues if a function expects an Employee object to handle a bonus.
    }
}

//Using LSP in above code

interface ISaleryCalculator{
    public function calculateSalery():int;
}

interface IBonusCalculator{
    public function calculateBonus(int $bonus):int;
}

class Employee implements ISaleryCalculator{
    Protected int $id;
    Protected string $name;
    Protected int  $salery;

    public function __construct(int $id, string $name, int $salery){
        $this->id=$id;
        $this->name= $name;
        $this->salery = $salery;
    }

    public function calculateSalery():int{
        return $this->salery;
    }

}

class ParmanentEmployee extends Employee implements IBonusCalculator{
    public function calculateBonus(int $bonus) :int{
        return $this->salery + $bonus;
    }
}

//Just implements only ISaleryCalculator  interface and use it as normal Employee
class TemproryEmployee implements ISaleryCalculator { //can use Employee class also
    Protected int $id;
    Protected string $name;
    Protected int  $salery;

    public function __construct(int $id, string $name, int $salery){
        $this->id=$id;
        $this->name= $name;
        $this->salery = $salery;
    }

    public function calculateSalery():int{
        return $this->salery;
    }
}

$parmanentEmployee = new ParmanentEmployee(1, "wasim", 6000000);
echo $parmanentEmployee->calculateSalery();
echo $parmanentEmployee->calculateBonus(1000000);

$TempEmployee = new TemproryEmployee(1, "wasim", 1000000);
echo $TempEmployee->calculateSalery();


//Another Example

class Shape {
    public function calculateArea() {
        // Default implementation (not relevant for our example)
    }
}

class Rectangle extends Shape {
    private $width;
    private $height;

    public function __construct(float $width, float $height) {
        $this->width = $width;
        $this->height = $height;
    }

    public function calculateArea() {
        return $this->width * $this->height;
    }
}

class Circle extends Shape {
    private $radius;

    public function __construct(float $radius) {
        $this->radius = $radius;
    }

    public function calculateArea() {
        return pi() * $this->radius ** 2;
    }
}
function printArea(Shape $shape) {
    echo "Area: " . $shape->calculateArea() . PHP_EOL; //if we have class , super to subbclass then it should be work. it is possible due to overwrite function in subclass is exits
}

$rectangle = new Rectangle(5, 3);
$circle = new Circle(2);

printArea($rectangle); // Output: "Area: 15"
printArea($circle);    // Output: "Area: 12.566370614359"




?>