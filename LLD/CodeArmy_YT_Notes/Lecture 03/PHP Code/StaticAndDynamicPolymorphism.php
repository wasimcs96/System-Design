<?php

/*
NOTE (PHP): PHP does not support method overloading (two methods with the same
name but different parameters). The closest PHP equivalent of Java's
accelerate() + accelerate(int speed) is ONE method with an optional parameter:
    accelerate(?int $speed = null)
So "static polymorphism" is emulated here by checking whether $speed was passed.
*/

// Base Car class
abstract class Car
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

    // Common methods for All cars.
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

    // Abstract method for Dynamic Polymorphism (no argument)
    // + Static Polymorphism (with speed argument) combined into one signature.
    abstract public function accelerate(?int $speed = null): void;

    abstract public function brake(): void;  // Abstract method for Dynamic Polymorphism
}

class ManualCar extends Car
{
    private int $currentGear;

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

    // Overriding accelerate - Dynamic Polymorphism
    // and "overloading" accelerate(speed) at the same time.
    public function accelerate(?int $speed = null): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
            return;
        }
        $this->currentSpeed += ($speed === null) ? 20 : $speed;
        echo $this->brand . " " . $this->model . " : Accelerating to " . $this->currentSpeed . " km/h" . PHP_EOL;
    }

    // Overriding brake - Dynamic Polymorphism
    public function brake(): void
    {
        $this->currentSpeed -= 20;
        if ($this->currentSpeed < 0) $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model . " : Braking! Speed is now " . $this->currentSpeed . " km/h" . PHP_EOL;
    }
}

class ElectricCar extends Car
{
    private int $batteryLevel;

    public function __construct(string $brand, string $model)
    {
        parent::__construct($brand, $model);
        $this->batteryLevel = 100;
    }

    // specialized method for Electric Car
    public function chargeBattery(): void
    {
        $this->batteryLevel = 100;
        echo $this->brand . " " . $this->model . " : Battery fully charged!" . PHP_EOL;
    }

    // Overriding accelerate - Dynamic Polymorphism (+ overloaded version with speed)
    public function accelerate(?int $speed = null): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
            return;
        }
        if ($this->batteryLevel <= 0) {
            echo $this->brand . " " . $this->model . " : Battery dead! Cannot accelerate." . PHP_EOL;
            return;
        }
        if ($speed === null) {
            $this->batteryLevel -= 10;
            $this->currentSpeed += 15;
        } else {
            $this->batteryLevel -= 10 + $speed;
            $this->currentSpeed += $speed;
        }
        echo $this->brand . " " . $this->model . " : Accelerating to " . $this->currentSpeed
            . " km/h. Battery at " . $this->batteryLevel . "%." . PHP_EOL;
    }

    // Overriding brake - Dynamic Polymorphism
    public function brake(): void
    {
        $this->currentSpeed -= 15;
        if ($this->currentSpeed < 0) $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model
            . " : Regenerative braking! Speed is now " . $this->currentSpeed
            . " km/h. Battery at " . $this->batteryLevel . "%." . PHP_EOL;
    }
}

// Main function
class StaticAndDynamicPolymorphism
{
    public static function main(): void
    {
        /** @var Car $myManualCar */
        $myManualCar = new ManualCar("Ford", "Mustang");
        $myManualCar->startEngine();
        $myManualCar->accelerate();
        $myManualCar->accelerate();
        $myManualCar->brake();
        $myManualCar->stopEngine();

        echo "----------------------" . PHP_EOL;

        /** @var Car $myElectricCar */
        $myElectricCar = new ElectricCar("Tesla", "Model S");
        $myElectricCar->startEngine();
        $myElectricCar->accelerate();
        $myElectricCar->accelerate();
        $myElectricCar->brake();
        $myElectricCar->stopEngine();
    }
}

StaticAndDynamicPolymorphism::main();
