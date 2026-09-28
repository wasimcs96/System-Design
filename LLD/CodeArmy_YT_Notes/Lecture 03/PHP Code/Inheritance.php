<?php

/*
We know that real world Objects show inheritance relationship where we
have parent object and child object. child object have all the characters
or behaviours that parent have plus some additional characters/behaviours.
Like all cars in real world have a brand, model etc and can start, stop,
accelerate etc. But some specific cars like manual car have gear System
while other specific cars like Electric cars have battery system.

We represent this scenario of real world in programming by creating a parent class and
defining all the characters(variables) or behaviours(methods) that all cars
have in parent class. Then we create different child classes that inherits
from this parent class and define only those characters and behaviours
that are specific to them. Although objects of these child classes can
access or call parent class characters(variables) and behaviours(methods).
Hence providing code reusability.
*/
class Car
{
    protected string $brand;
    protected string $model;
    protected bool $isEngineOn;
    protected int $currentSpeed;

    public function __construct(string $brand, string $model)
    {
        $this->brand = $brand;
        $this->model = $model;
        $this->isEngineOn = false;
        $this->currentSpeed = 0;
    }

    // Common methods for all cars
    public function startEngine(): void
    {
        $this->isEngineOn = true;
        echo $this->brand . " " . $this->model . " : Engine started." . PHP_EOL;
    }

    public function stopEngine(): void
    {
        $this->isEngineOn = false;
        $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model . " : Engine turned off." . PHP_EOL;
    }

    public function accelerate(): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
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
}

class ManualCar extends Car  // Inherits from Car
{
    private int $currentGear; // specific to Manual Car.

    public function __construct(string $brand, string $model)
    {
        parent::__construct($brand, $model);
        $this->currentGear = 0;
    }

    // Specialized method for Manual Car
    public function shiftGear(int $gear): void
    {
        $this->currentGear = $gear;
        echo $this->brand . " " . $this->model . " : Shifted to gear " . $this->currentGear . PHP_EOL;
    }
}

class ElectricCar extends Car  // Inherits from Car
{
    private int $batteryLevel; // specific to Electric Car.

    public function __construct(string $brand, string $model)
    {
        parent::__construct($brand, $model);
        $this->batteryLevel = 100;
    }

    // Specialized method for Electric Car
    public function chargeBattery(): void
    {
        $this->batteryLevel = 100;
        echo $this->brand . " " . $this->model . " : Battery fully charged!" . PHP_EOL;
    }
}

// Main Class
class Inheritance
{
    public static function main(): void
    {
        $myManualCar = new ManualCar("Suzuki", "WagonR");
        $myManualCar->startEngine();
        $myManualCar->shiftGear(1); // Specific to Manual Car
        $myManualCar->accelerate();
        $myManualCar->brake();
        $myManualCar->stopEngine();

        echo "----------------------" . PHP_EOL;

        $myElectricCar = new ElectricCar("Tesla", "Model S");
        $myElectricCar->chargeBattery(); // Specific to Electric Car
        $myElectricCar->startEngine();
        $myElectricCar->accelerate();
        $myElectricCar->brake();
        $myElectricCar->stopEngine();
    }
}

Inheritance::main();
