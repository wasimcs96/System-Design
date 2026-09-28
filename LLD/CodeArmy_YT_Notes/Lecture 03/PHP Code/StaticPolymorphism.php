<?php

/*
Static Polymorphism (Compile-time polymorphism) in real life says that
the same action can behave differently depending on the input parameters.
For example, a Manual car can accelerate by a fixed amount or by a
specific amount you request. In programming, we achieve this via method
overloading: multiple methods with the same name but different signatures.

NOTE (PHP): PHP does NOT support true method overloading. Declaring two
methods named accelerate() in one class is a fatal error. The idiomatic PHP
way to get the same behaviour is a single method with an optional
parameter (or variadic args / func_get_args()). That is what we use below.
*/

class ManualCar
{
    private string $brand;
    private string $model;
    private bool $isEngineOn;
    private int $currentSpeed;
    private int $currentGear;

    public function __construct(string $brand, string $model)
    {
        $this->brand = $brand;
        $this->model = $model;
        $this->isEngineOn = false;
        $this->currentSpeed = 0;
        $this->currentGear = 0;
    }

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

    // "Overloading" accelerate - Static Polymorphism
    //   accelerate()      -> accelerate by default 20
    //   accelerate(speed) -> accelerate by given speed
    public function accelerate(?int $speed = null): void
    {
        if (!$this->isEngineOn) {
            echo $this->brand . " " . $this->model . " : Cannot accelerate! Engine is off." . PHP_EOL;
            return;
        }
        $this->currentSpeed += ($speed === null) ? 20 : $speed;
        echo $this->brand . " " . $this->model . " : Accelerating to " . $this->currentSpeed . " km/h" . PHP_EOL;
    }

    public function brake(): void
    {
        $this->currentSpeed -= 20;
        if ($this->currentSpeed < 0) {
            $this->currentSpeed = 0;
        }
        echo $this->brand . " " . $this->model . " : Braking! Speed is now "
            . $this->currentSpeed . " km/h" . PHP_EOL;
    }

    public function shiftGear(int $gear): void
    {
        $this->currentGear = $gear;
        echo $this->brand . " " . $this->model . " : Shifted to gear " . $this->currentGear . PHP_EOL;
    }
}

class StaticPolymorphism
{
    public static function main(): void
    {
        $myManualCar = new ManualCar("Suzuki", "WagonR");
        $myManualCar->startEngine();
        $myManualCar->accelerate();
        $myManualCar->accelerate(40);
        $myManualCar->brake();
        $myManualCar->stopEngine();
    }
}

StaticPolymorphism::main();
