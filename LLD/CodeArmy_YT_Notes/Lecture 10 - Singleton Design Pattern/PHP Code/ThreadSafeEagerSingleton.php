<?php

/*
NOTE (PHP): PHP does not allow "new" in a static property initializer, so
  private static $instance = new ThreadSafeEagerSingleton();   // not allowed
Instead we create the instance eagerly as soon as the class is loaded,
by calling init() right after the class definition (see bottom of class).
*/
class ThreadSafeEagerSingleton
{
    private static ?ThreadSafeEagerSingleton $instance = null;

    private function __construct()
    {
        echo "Singleton Constructor Called!" . PHP_EOL;
    }

    private function __clone() {}

    // Called once, immediately when this file is loaded (eager initialization).
    public static function init(): void
    {
        if (self::$instance === null) {
            self::$instance = new ThreadSafeEagerSingleton();
        }
    }

    public static function getInstance(): ThreadSafeEagerSingleton
    {
        return self::$instance;
    }

    public static function main(): void
    {
        $s1 = ThreadSafeEagerSingleton::getInstance();
        $s2 = ThreadSafeEagerSingleton::getInstance();

        echo var_export($s1 === $s2, true) . PHP_EOL;
    }
}
ThreadSafeEagerSingleton::init(); // Eager creation at class-load time

ThreadSafeEagerSingleton::main();
