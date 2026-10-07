<?php

/*
NOTE (PHP): A normal PHP script runs in a single thread, and every request /
CLI run gets its own memory, so a race on getInstance() cannot happen inside one
process. The locking below is kept to mirror the Java lecture (double-checked
locking). We emulate Java's "synchronized" block with an exclusive file lock
(flock), which is how PHP code usually serialises critical sections.
*/
class ThreadSafeDoubleLockingSingleton
{
    private static ?ThreadSafeDoubleLockingSingleton $instance = null;

    private function __construct()
    {
        echo "Singleton Constructor Called!" . PHP_EOL;
    }

    private function __clone() {}

    // Double check locking..
    public static function getInstance(): ThreadSafeDoubleLockingSingleton
    {
        if (self::$instance === null) { // First check (no locking)
            $lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ThreadSafeDoubleLockingSingleton.lock", "c");
            flock($lock, LOCK_EX); // Lock only if needed  (== synchronized)
            try {
                if (self::$instance === null) { // Second check (after acquiring lock)
                    self::$instance = new ThreadSafeDoubleLockingSingleton();
                }
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
        return self::$instance;
    }

    public static function main(): void
    {
        $s1 = ThreadSafeDoubleLockingSingleton::getInstance();
        $s2 = ThreadSafeDoubleLockingSingleton::getInstance();

        echo var_export($s1 === $s2, true) . PHP_EOL;
    }
}

ThreadSafeDoubleLockingSingleton::main();
