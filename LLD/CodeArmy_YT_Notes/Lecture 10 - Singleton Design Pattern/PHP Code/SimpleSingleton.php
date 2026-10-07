<?php

class SimpleSingleton
{
    private static ?SimpleSingleton $instance = null;

    private function __construct()
    {
        echo "Singleton Constructor called" . PHP_EOL;
    }

    // Prevent cloning / unserializing, which would otherwise create a second instance in PHP.
    private function __clone() {}

    public function __wakeup()
    {
        throw new LogicException("Cannot unserialize a singleton.");
    }

    public static function getInstance(): SimpleSingleton
    {
        if (self::$instance === null) {
            self::$instance = new SimpleSingleton();
        }
        return self::$instance;
    }

    public static function main(): void
    {
        $s1 = SimpleSingleton::getInstance();
        $s2 = SimpleSingleton::getInstance();

        echo var_export($s1 === $s2, true) . PHP_EOL;
    }
}

SimpleSingleton::main();
