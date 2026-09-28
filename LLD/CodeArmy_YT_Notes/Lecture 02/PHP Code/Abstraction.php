<?php

/*
Car Interface --> Act as an interface for Outside world to operate the car.
This interface tells 'WHAT' all it can do rather then 'HOW' it does that.
Since this is an interface we cannot directly create Objects of this. We
need to implement it first and then that child class will have the responsibility to
provide implementation details of all the methods in the interface.

In our real world example of Car, imagine you sitting in the car and able to operate
the car (startEngine, accelerate, brake, turn) just by pressing or moving some
pedals/buttons/stearing wheel etc. You dont need to know how these things work, and
also they are hidden under the hood.
This Interface 'Car' denotes that (pedals/buttons/stearing wheel etc).
*/
interface Car
{
    public function startEngine(): void;
    public function shiftGear(int $gear): void;
    public function accelerate(): void;
    public function brake(): void;
    public function stopEngine(): void;
}

/*
This is a Concrete class (A class that provide implementation details of an interface/abstract class).
Now anyone can make an Object of 'SportsCar' and can assign it to 'Car' reference.
(See main method for this)

In our real world example of Car, as you cannot have a real car by just having its body only
(all these buttons or pedals). You need to have the actual implementation of 'What' happens
when we press these buttons. 'SportsCar' class denotes that actual implementation.

Hence we can conclude, to denote a real world car in programming we created 2 classes.
One to denote all the user-interface like pedals, buttons, stearing wheels etc ('Car' interface).
And another one to denote the actual car with all the implementations of these buttons ('SportsCar' class).
 */
class SportsCar implements Car
{
    public string $brand;
    public string $model;
    public bool $isEngineOn = false;
    public int $currentSpeed = 0;
    public int $currentGear = 0;

    public function __construct(string $brand, string $model)
    {
        $this->brand = $brand;
        $this->model = $model;
    }

    public function startEngine(): void
    {
        $this->isEngineOn = true;
        echo $this->brand . " " . $this->model . " : Engine starts with a roar!" . PHP_EOL;
    }

    public function shiftGear(int $gear): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Engine is off! Cannot Shift Gear." . PHP_EOL;
            return;
        }
        $this->currentGear = $gear;
        echo $this->brand . " " . $this->model . " : Shifted to gear " . $this->currentGear . PHP_EOL;
    }

    public function accelerate(): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Engine is off! Cannot accelerate." . PHP_EOL;
            return;
        }
        $this->currentSpeed += 20;
        echo $this->brand . " " . $this->model . " : Accelerating to " . $this->currentSpeed . " km/h" . PHP_EOL;
    }

    public function brake(): void
    {
        $this->currentSpeed -= 20;
        if ($this->currentSpeed < 0) $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model . " : Braking! Speed is now " . $this->currentSpeed . " km/h" . PHP_EOL;
    }

    public function stopEngine(): void
    {
        $this->isEngineOn = false;
        $this->currentGear = 0;
        $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model . " : Engine turned off." . PHP_EOL;
    }
}

// Main Method
class Abstraction
{
    public static function main(): void
    {
        /** @var Car $myCar */
        $myCar = new SportsCar("Ford", "Mustang");

        $myCar->startEngine();
        $myCar->shiftGear(1);
        $myCar->accelerate();
        $myCar->shiftGear(2);
        $myCar->accelerate();
        $myCar->brake();
        $myCar->stopEngine();
    }
}

Abstraction::main();
