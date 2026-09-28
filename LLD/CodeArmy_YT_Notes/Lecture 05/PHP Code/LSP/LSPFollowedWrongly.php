<?php

// PHP has no built-in UnsupportedOperationException, so we define one (same as Java's).
class UnsupportedOperationException extends RuntimeException {}

// Account interface
interface Account
{
    public function deposit(float $amount): void;
    public function withdraw(float $amount): void;
}

class SavingAccount implements Account
{
    private float $balance;

    public function __construct()
    {
        $this->balance = 0;
    }

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
        echo "Deposited: " . $amount . " in Savings Account. New Balance: " . $this->balance . PHP_EOL;
    }

    public function withdraw(float $amount): void
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            echo "Withdrawn: " . $amount . " from Savings Account. New Balance: " . $this->balance . PHP_EOL;
        } else {
            echo "Insufficient funds in Savings Account!" . PHP_EOL;
        }
    }
}

class CurrentAccount implements Account
{
    private float $balance;

    public function __construct()
    {
        $this->balance = 0;
    }

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
        echo "Deposited: " . $amount . " in Current Account. New Balance: " . $this->balance . PHP_EOL;
    }

    public function withdraw(float $amount): void
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            echo "Withdrawn: " . $amount . " from Current Account. New Balance: " . $this->balance . PHP_EOL;
        } else {
            echo "Insufficient funds in Current Account!" . PHP_EOL;
        }
    }
}

class FixedTermAccount implements Account
{
    private float $balance;

    public function __construct()
    {
        $this->balance = 0;
    }

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
        echo "Deposited: " . $amount . " in Fixed Term Account. New Balance: " . $this->balance . PHP_EOL;
    }

    public function withdraw(float $amount): void
    {
        throw new UnsupportedOperationException("Withdrawal not allowed in Fixed Term Account!");
    }
}

class BankClient
{
    /** @var Account[] */
    private array $accounts;

    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    public function processTransactions(): void
    {
        foreach ($this->accounts as $acc) {
            $acc->deposit(1000);

            // Checking account type explicitly
            if ($acc instanceof FixedTermAccount) {
                echo "Skipping withdrawal for Fixed Term Account." . PHP_EOL;
            } else {
                try {
                    $acc->withdraw(500);
                } catch (UnsupportedOperationException $e) {
                    echo "Exception: " . $e->getMessage() . PHP_EOL;
                }
            }
        }
    }
}

class LSPFollowedWrongly
{
    public static function main(): void
    {
        $accounts = [];
        $accounts[] = new SavingAccount();
        $accounts[] = new CurrentAccount();
        $accounts[] = new FixedTermAccount();

        $client = new BankClient($accounts);
        $client->processTransactions();
    }
}

LSPFollowedWrongly::main();
