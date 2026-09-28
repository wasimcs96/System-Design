<?php

// A Postcondition must be satisfied after a method is executed.
// Subclasses can strengthen the Postcondition but cannot weaken it.

class Car
{
    protected int $speed;

    public function __construct()
    {
        $this->speed = 0;
    }

    public function accelerate(): void
    {
        echo "Accelerating" . PHP_EOL;
        $this->speed += 20;
    }

    // PostCondition: Speed must reduce after brake
    public function brake(): void
    {
        echo "Applying brakes" . PHP_EOL;
        $this->speed -= 20;
    }
}

// Subclass can strengthen postcondition - Does not violate LSP
class HybridCar extends Car
{
    private int $charge;

    public function __construct()
    {
        parent::__construct();
        $this->charge = 0;
    }

    // PostCondition: Speed must reduce after brake
    // PostCondition: Charge must increase.
    public function brake(): void
    {
        echo "Applying brakes" . PHP_EOL;
        $this->speed -= 20;
        $this->charge += 10;
    }
}

class PostConditions
{
    public static function main(): void
    {
        /** @var Car $hybridCar */
        $hybridCar = new HybridCar();
        $hybridCar->brake();  // Works fine: HybridCar reduces speed and also increases charge.

        // Client feels no difference in substituting Hybrid car in place of Car.
    }
}

PostConditions::main();
