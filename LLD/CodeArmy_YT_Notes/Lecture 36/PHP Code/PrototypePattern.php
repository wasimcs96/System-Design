<?php

/*
NOTE (PHP): PHP has prototype support built into the language:
   $copy = clone $original;   // shallow copy of all properties
and the magic method __clone() runs on the NEW copy right after cloning
(this plays the role of Java's copy-constructor NPC(NPC other)).
We still expose a clone() method through a Cloneable interface to mirror the lecture.
*/

// Cloneable (aka Prototype) interface
interface Cloneable
{
    public function clone(): Cloneable;
}

class NPC implements Cloneable
{
    public string $name;
    public int $health;
    public int $attack;
    public int $defense;

    public function __construct(string $name, int $health, int $attack, int $defense)
    {
        // call database
        // complex calc
        $this->name = $name;
        $this->health = $health;
        $this->attack = $attack;
        $this->defense = $defense;
        echo "Setting up template NPC '" . $name . "'" . PHP_EOL;
    }

    // Runs on the copy created by "clone $this" (copy-constructor equivalent).
    // For object properties you would deep-copy them here, e.g. $this->weapon = clone $this->weapon;
    public function __clone()
    {
        echo "Cloning NPC '" . $this->name . "'" . PHP_EOL;
    }

    // the clone method required by Prototype
    public function clone(): Cloneable
    {
        return clone $this;
    }

    public function describe(): void
    {
        echo "NPC " . $this->name . " [HP=" . $this->health . " ATK=" . $this->attack
            . " DEF=" . $this->defense . "]" . PHP_EOL;
    }

    // setters to tweak the clone…
    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function setHealth(int $h): void
    {
        $this->health = $h;
    }

    public function setAttack(int $a): void
    {
        $this->attack = $a;
    }

    public function setDefense(int $d): void
    {
        $this->defense = $d;
    }
}

class PrototypePattern
{
    public static function main(): void
    {
        // 1) build one "heavy" template
        $alien = new NPC("Alien", 30, 5, 2);

        // 2) quickly clone + tweak as many variants as you like:
        /** @var NPC $alienCopied1 */
        $alienCopied1 = $alien->clone();
        $alienCopied1->describe();

        /** @var NPC $alienCopied2 */
        $alienCopied2 = $alien->clone();
        $alienCopied2->setName("Powerful Alien");
        $alienCopied2->setHealth(50);
        $alienCopied2->describe();

        // cleanup
        $alien = null;
        $alienCopied1 = null;
        $alienCopied2 = null;
    }
}

PrototypePattern::main();
