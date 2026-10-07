<?php

class NoSingleton
{
    public function __construct()
    {
        echo "Singleton Constructor called. New Object created." . PHP_EOL;
    }

    public static function main(): void
    {
        $s1 = new NoSingleton();
        $s2 = new NoSingleton();

        // === checks if both variables point to the SAME object instance
        echo var_export($s1 === $s2, true) . PHP_EOL;
    }
}

NoSingleton::main();
