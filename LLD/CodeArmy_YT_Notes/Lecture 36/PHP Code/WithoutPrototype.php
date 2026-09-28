<?php

// Simple NPC class — no Prototype
class NPC
{
    public string $name;
    public int $health;
    public int $attack;
    public int $defense;

    // "Heavy" constructor: every field must be provided
    public function __construct(string $name, int $health, int $attack, int $defense)
    {
        // call database
        // complex calc
        $this->name = $name;
        $this->health = $health;
        $this->attack = $attack;
        $this->defense = $defense;
        echo "Creating NPC '" . $name . "' [HP:" . $health . ", ATK:"
            . $attack . ", DEF:" . $defense . "]" . PHP_EOL;
    }

    public function describe(): void
    {
        echo "  NPC: " . $this->name . " | HP=" . $this->health . " ATK=" . $this->attack
            . " DEF=" . $this->defense . PHP_EOL;
    }
}

class WithoutPrototype
{
    public static function main(): void
    {
        // Base Alien
        $alien = new NPC("Alien", 30, 5, 2);
        $alien->describe();

        // Powerful Alien — must re-pass all stats, easy to make mistakes
        $alien2 = new NPC("Powerful Alien", 30, 5, 5);
        $alien2->describe();

        // If you want 100 aliens, you'd repeat this 100 times…
    }
}

WithoutPrototype::main();
