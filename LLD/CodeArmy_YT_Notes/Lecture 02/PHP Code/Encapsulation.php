<?php

/*
Encapsulation says 2 things:
1. An Object's Characteristics and its behaviour are encapsulated together
within that Object.
2. All the characteristics or behaviours are not for everyone to access.
Object should provide data security.

We follow above 2 pointers about Object of real world in programming by:
1. Creating a class that act as a blueprint for Object creation. Class contain
all the characteristics (class variable) and behaviour (class methods) in one block,
encapsulating it together.
2. We introduce access modifiers (public, private, protected) etc to provide data
security to the class members.
*/
class SportsCar
{
    private string $brand;
    private string $model;
    private bool $isEngineOn = false;
    private int $currentSpeed = 0;
    private int $currentGear = 0;

    // Introduce new variable to explain setters
    private ?string $tyreCompany = null;

    public function __construct(string $brand, string $model)
    {
        $this->brand = $brand;
        $this->model = $model;
    }

    public function getSpeed(): int
    {
        return $this->currentSpeed;
    }

    public function getTyreCompany(): ?string
    {
        return $this->tyreCompany;
    }

    public function setTyreCompany(string $tyreCompany): void
    {
        $this->tyreCompany = $tyreCompany;
    }

    public function startEngine(): void
    {
        $this->isEngineOn = true;
        echo $this->brand . " " . $this->model . " : Engine starts with a roar!" . PHP_EOL;
    }

    public function shiftGear(int $gear): void
    {
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
class Encapsulation
{
    public static function main(): void
    {
        $mySportsCar = new SportsCar("Ford", "Mustang");

        $mySportsCar->startEngine();
        $mySportsCar->shiftGear(1);
        $mySportsCar->accelerate();
        $mySportsCar->shiftGear(2);
        $mySportsCar->accelerate();
        $mySportsCar->brake();
        $mySportsCar->stopEngine();

        // Setting arbitrary value to speed.
        // $mySportsCar->currentSpeed = 500;   // Error: Cannot access private property

        // echo "Current Speed of My Sports Car is set to " . $mySportsCar->currentSpeed . PHP_EOL;

        echo "Current Speed of My Sports Car is " . $mySportsCar->getSpeed() . PHP_EOL;
    }
}

Encapsulation::main();
