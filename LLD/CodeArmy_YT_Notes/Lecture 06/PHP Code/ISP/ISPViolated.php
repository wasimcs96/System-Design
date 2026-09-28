<?php

// PHP has no built-in UnsupportedOperationException, so we define one (same as Java's).
class UnsupportedOperationException extends RuntimeException {}

// Single interface for all shapes (Violates ISP)
interface Shape
{
    public function area(): float;
    public function volume(): float; // 2D shapes don't have volume!
}

// Square is a 2D shape but is forced to implement volume()
class Square implements Shape
{
    private float $side;

    public function __construct(float $s)
    {
        $this->side = $s;
    }

    public function area(): float
    {
        return $this->side * $this->side;
    }

    public function volume(): float
    {
        throw new UnsupportedOperationException("Volume not applicable for Square"); // Unnecessary method
    }
}

// Rectangle is also a 2D shape but is forced to implement volume()
class Rectangle implements Shape
{
    private float $length;
    private float $width;

    public function __construct(float $l, float $w)
    {
        $this->length = $l;
        $this->width  = $w;
    }

    public function area(): float
    {
        return $this->length * $this->width;
    }

    public function volume(): float
    {
        throw new UnsupportedOperationException("Volume not applicable for Rectangle"); // Unnecessary method
    }
}

// Cube is a 3D shape, so it actually has a volume
class Cube implements Shape
{
    private float $side;

    public function __construct(float $s)
    {
        $this->side = $s;
    }

    public function area(): float
    {
        return 6 * $this->side * $this->side;
    }

    public function volume(): float
    {
        return $this->side * $this->side * $this->side;
    }
}

class ISPViolated
{
    public static function main(): void
    {
        /** @var Shape $square */
        $square    = new Square(5);
        $rectangle = new Rectangle(4, 6);
        $cube      = new Cube(3);

        echo "Square Area: "    . $square->area() . PHP_EOL;
        echo "Rectangle Area: " . $rectangle->area() . PHP_EOL;
        echo "Cube Area: "      . $cube->area() . PHP_EOL;
        echo "Cube Volume: "    . $cube->volume() . PHP_EOL;

        try {
            echo "Square Volume: " . $square->volume() . PHP_EOL; // Will throw an exception
        } catch (UnsupportedOperationException $e) {
            echo "Exception: " . $e->getMessage() . PHP_EOL;
        }
    }
}

ISPViolated::main();
