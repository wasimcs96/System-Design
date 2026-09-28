<?php

// Subclass methods should not be allowed state changes that
// the base class never allowed.

class BankAccount
{
    protected float $balance;

    public function __construct(float $b)
    {
        if ($b < 0) throw new InvalidArgumentException("Balance can't be negative");
        $this->balance = $b;
    }

    // History Constraint: withdraw should be allowed
    public function withdraw(float $amount): void
    {
        if ($this->balance - $amount < 0) throw new RuntimeException("Insufficient funds");
        $this->balance -= $amount;
        echo "Amount withdrawn. Remaining balance is " . $this->balance . PHP_EOL;
    }
}

class FixedDepositAccount extends BankAccount
{
    public function __construct(float $b)
    {
        parent::__construct($b);
    }

    // LSP break! History constraint broken!
    // Parent class behavior changed: Now withdraw is not allowed.
    // This class will break client code that relies on withdraw.
    public function withdraw(float $amount): void
    {
        throw new RuntimeException("Withdraw not allowed in Fixed Deposit");
    }
}

class HistoryConstraint
{
    public static function main(): void
    {
        $bankAccount = new BankAccount(100);
        $bankAccount->withdraw(100);
    }
}

HistoryConstraint::main();
