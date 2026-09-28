<?php

/*
Dynamic Polymorphism in real life says that 2 Objects coming from same
family will respond to same stimulus differently. Like in real world Manual
car and Electric car will respond to accelerate() differently.

To represent this in programming, we create a parent class that defines all
characters and behaviours that are generic to all child classes and are also same in
all child classes but make those methods abstract that are generic to all
child classes but all child class will behave differently. Then those child class
will provide implementation details of these abstract methods the way they want.
*/
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

    // Common methods for all cars.
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

    // Abstract methods for dynamic polymorphism
    abstract public function accelerate(): void;
    abstract public function brake(): void;
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
    public function accelerate(): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
            return;
        }
        $this->currentSpeed += 20;
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

    // Specialized method for Electric Car
    public function chargeBattery(): void
    {
        $this->batteryLevel = 100;
        echo $this->brand . " " . $this->model . " : Battery fully charged!" . PHP_EOL;
    }

    // Overriding accelerate - Dynamic Polymorphism
    public function accelerate(): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
            return;
        }
        if ($this->batteryLevel <= 0) {
            echo $this->brand . " " . $this->model . " : Battery dead! Cannot accelerate." . PHP_EOL;
            return;
        }
        $this->batteryLevel -= 10;
        $this->currentSpeed += 15;
        echo $this->brand . " " . $this->model . " : Accelerating to " . $this->currentSpeed
            . " km/h. Battery at " . $this->batteryLevel . "%." . PHP_EOL;
    }

    // Overriding brake - Dynamic Polymorphism
    public function brake(): void
    {
        $this->currentSpeed -= 15;
        if ($this->currentSpeed < 0) $this->currentSpeed = 0;
        echo $this->brand . " " . $this->model . " : Regenerative braking! Speed is now "
            . $this->currentSpeed . " km/h. Battery at " . $this->batteryLevel . "%." . PHP_EOL;
    }
}

class DynamicPolymorphism
{
    public static function main(): void
    {
        /** @var Car $myManualCar */
        $myManualCar = new ManualCar("Suzuki", "WagonR");
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

DynamicPolymorphism::main();
