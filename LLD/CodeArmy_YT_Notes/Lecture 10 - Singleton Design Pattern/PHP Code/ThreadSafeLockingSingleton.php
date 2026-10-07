<?php

/*
NOTE (PHP): A normal PHP script runs in a single thread, so a race inside
getInstance() cannot happen within one process. To mirror Java's
"synchronized" block we use an exclusive file lock (flock).
Here the lock is taken on EVERY call (simple but slower), exactly like the Java version.
*/
class ThreadSafeLockingSingleton
{
    private static ?ThreadSafeLockingSingleton $instance = null;

    private function __construct()
    {
        echo "Singleton Constructor Called!" . PHP_EOL;
    }

    private function __clone() {}

    public static function getInstance(): ThreadSafeLockingSingleton
    {
        $lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ThreadSafeLockingSingleton.lock", "c");
        flock($lock, LOCK_EX); // Lock for thread safety (== synchronized)
        try {
            if (self::$instance === null) {
                self::$instance = new ThreadSafeLockingSingleton();
            }
            return self::$instance;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function main(): void
    {
        $s1 = ThreadSafeLockingSingleton::getInstance();
        $s2 = ThreadSafeLockingSingleton::getInstance();

        echo var_export($s1 === $s2, true) . PHP_EOL;
    }
}

ThreadSafeLockingSingleton::main();
