<?php

// Component Interface: defines a common interface for Mario and all power-up decorators.
interface Character
{
    public function getAbilities(): string;
}

// Concrete Component: Basic Mario character with no power-ups.
class Mario implements Character
{
    public function getAbilities(): string
    {
        return "Mario";
    }
}
 
// Abstract Decorator: CharacterDecorator "is-a" Character and "has-a" Character.
abstract class CharacterDecorator implements Character
{
    protected Character $character;  // Wrapped component

    public function __construct(Character $c)
    {
        $this->character = $c;
    }
}

// Concrete Decorator: Height-Increasing Power-Up.
class HeightUp extends CharacterDecorator
{
    public function __construct(Character $c)
    {
        parent::__construct($c);
    }

    public function getAbilities(): string
    {
        return $this->character->getAbilities() . " with HeightUp";
    }
}

// Concrete Decorator: Gun Shooting Power-Up.
class GunPowerUp extends CharacterDecorator
{
    public function __construct(Character $c)
    {
        parent::__construct($c);
    }

    public function getAbilities(): string
    {
        return $this->character->getAbilities() . " with Gun";
    }
}

// Concrete Decorator: Star Power-Up (temporary ability).
class StarPowerUp extends CharacterDecorator
{
    public function __construct(Character $c)
    {
        parent::__construct($c);
    }

    public function getAbilities(): string
    {
        return $this->character->getAbilities() . " with Star Power (Limited Time)";
    }
}

class DecoratorPattern
{
    public static function main(): void
    {
        // Create a basic Mario character.
        /** @var Character $mario */
        $mario = new Mario();
        echo "Basic Character: " . $mario->getAbilities() . PHP_EOL;

        // Decorate Mario with a HeightUp power-up.
        $mario = new HeightUp($mario);
        echo "After HeightUp: " . $mario->getAbilities() . PHP_EOL;

        // Decorate Mario further with a GunPowerUp.
        $mario = new GunPowerUp($mario);
        echo "After GunPowerUp: " . $mario->getAbilities() . PHP_EOL;

        // Finally, add a StarPowerUp decoration.
        $mario = new StarPowerUp($mario);
        echo "After StarPowerUp: " . $mario->getAbilities() . PHP_EOL;
    }
}

DecoratorPattern::main();
