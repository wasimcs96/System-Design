<?php

// A Precondition must be satisfied before a method can be executed.
// Sub classes can weaken the precondition but cannot strengthen it.

class User
{
    // Precondition: Password must be at least 8 characters long
    public function setPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters long!");
        }
        echo "Password set successfully" . PHP_EOL;
    }
}

class AdminUser extends User
{
    // Precondition: Password must be at least 6 characters
    public function setPassword(string $password): void
    {
        if (strlen($password) < 6) {
            throw new InvalidArgumentException("Password must be at least 6 characters long!");
        }
        echo "Password set successfully" . PHP_EOL;
    }
}

class PreConditions
{
    public static function main(): void
    {
        /** @var User $user */
        $user = new AdminUser();
        $user->setPassword("Admin1");  // Works fine: AdminUser allows shorter passwords
    }
}

PreConditions::main();
