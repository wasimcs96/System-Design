<?php

// Return Type Rule :
// Subtype overridden method return type should be either identical
// or narrower than the parent method's return type.
// This is also called return type covariance.
// PHP (7.4+) supports this out of the box, e.g. Child::getAnimal(): Dog is allowed.

class Animal
{
    // some common Animal methods
}

class Dog extends Animal
{
    // Additional Dog methods specific to Dogs.
}

class ParentClass  // "Parent" is a reserved word in PHP, so the class is called ParentClass
{
    public function getAnimal(): Animal
    {
        echo "Parent : Returning Animal instance" . PHP_EOL;
        return new Animal();
    }
}

class Child extends ParentClass
{
    // Narrower (covariant) return type is allowed in PHP.
    public function getAnimal(): Dog
    {
        echo "Child : Returning Dog instance" . PHP_EOL;
        return new Dog();
    }
}

class Client
{
    private ParentClass $p;

    public function __construct(ParentClass $p)
    {
        $this->p = $p;
    }

    public function takeAnimal(): void
    {
        $this->p->getAnimal();
    }
}

class ReturnTypeRule
{
    public static function main(): void
    {
        $parent = new ParentClass();
        $child  = new Child();

        $client = new Client($child);
        // $client = new Client($parent);
        $client->takeAnimal();
    }
}

ReturnTypeRule::main();
