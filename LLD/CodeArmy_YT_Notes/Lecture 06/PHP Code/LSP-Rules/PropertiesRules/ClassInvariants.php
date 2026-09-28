<?php

// Class Invariant of a parent class Object should not be broken by child class Object.
// Hence child class can either maintain or strengthen the invariant but never narrows it down.

// Invariant: Balance cannot be negative
class BankAccount
{
    protected float $balance;

    public function __construct(float $b)
    {
        if ($b < 0) throw new InvalidArgumentException("Balance can't be negative");
        $this->balance = $b;
    }

    public function withdraw(float $amount): void
    {
        if ($this->balance - $amount < 0) throw new RuntimeException("Insufficient funds");
        $this->balance -= $amount;
        echo "Amount withdrawn. Remaining balance is " . $this->balance . PHP_EOL;
    }
}

// Breaks invariant: Should not be allowed.
class CheatAccount extends BankAccount
{
    public function __construct(float $b)
    {
        parent::__construct($b);
    }

    public function withdraw(float $amount): void
    {
        $this->balance -= $amount; // LSP break! Negative balance allowed
        echo "Amount withdrawn. Remaining balance is " . $this->balance . PHP_EOL;
    }
}

class ClassInvariants
{
    public static function main(): void
    {
        $bankAccount = new BankAccount(100);
        $bankAccount->withdraw(100);
    }
}

ClassInvariants::main();
