<?php

// Implementation Hierarchy: Engine interface (LLL)
interface Engine
{
    public function start(): void;
}

// Concrete Implementors (LLL)
class PetrolEngine implements Engine
{
    public function start(): void
    {
        echo "Petrol engine starting with ignition!" . PHP_EOL;
    }
}

class DieselEngine implements Engine
{
    public function start(): void
    {
        echo "Diesel engine roaring to life!" . PHP_EOL;
    }
}

class ElectricEngine implements Engine
{
    public function start(): void
    {
        echo "Electric engine powering up silently!" . PHP_EOL;
    }
}

// Abstraction Hierarchy: Car (HLL)
abstract class Car
{
    protected Engine $engine;

    public function __construct(Engine $e)
    {
        $this->engine = $e;
    }

    abstract public function drive(): void;
}

// Refined Abstraction: Sedan
class Sedan extends Car
{
    public function __construct(Engine $e)
    {
        parent::__construct($e);
    }

    public function drive(): void
    {
        $this->engine->start();
        echo "Driving a Sedan on the highway." . PHP_EOL;
    }
}

// Refined Abstraction: SUV
class SUV extends Car
{
    public function __construct(Engine $e)
    {
        parent::__construct($e);
    }

    public function drive(): void
    {
        $this->engine->start();
        echo "Driving an SUV off-road." . PHP_EOL;
    }
}

class BridgePattern
{
    public static function main(): void
    {
        // Create Engine implementations
        $petrolEng = new PetrolEngine();
        $dieselEng = new DieselEngine();
        $electricEng = new ElectricEngine();

        // Create Car abstractions, injecting Engine implementations
        $mySedan = new Sedan($petrolEng);
        $mySUV = new SUV($electricEng);
        $yourSUV = new SUV($dieselEng);

        // Use the cars
        $mySedan->drive();   // Petrol engine + Sedan
        $mySUV->drive();     // Electric engine + SUV
        $yourSUV->drive();   // Diesel engine + SUV

        // No explicit cleanup needed in PHP (garbage collected)
    }
}

BridgePattern::main();
