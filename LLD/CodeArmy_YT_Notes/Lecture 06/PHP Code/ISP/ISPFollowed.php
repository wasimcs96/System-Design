<?php

// Separate interface for 2D shapes
interface TwoDimensionalShape
{
    public function area(): float;
}

// Separate interface for 3D shapes
interface ThreeDimensionalShape
{
    public function area(): float;
    public function volume(): float;
}

// Square implements only the 2D interface
class Square implements TwoDimensionalShape
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
}

// Rectangle implements only the 2D interface
class Rectangle implements TwoDimensionalShape
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
}

// Cube implements the 3D interface
class Cube implements ThreeDimensionalShape
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

class ISPFollowed
{
    public static function main(): void
    {
        $square    = new Square(5);
        $rectangle = new Rectangle(4, 6);
        $cube      = new Cube(3);

        echo "Square Area: "    . $square->area() . PHP_EOL;
        echo "Rectangle Area: " . $rectangle->area() . PHP_EOL;
        echo "Cube Area: "      . $cube->area() . PHP_EOL;
        echo "Cube Volume: "    . $cube->volume() . PHP_EOL;
    }
}

ISPFollowed::main();
