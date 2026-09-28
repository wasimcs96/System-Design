<?php

// 1. DepositOnlyAccount interface: only allows deposits
interface DepositOnlyAccount
{
    public function deposit(float $amount): void;
}

// 2. WithdrawableAccount interface: allows deposits and withdrawals
interface WithdrawableAccount extends DepositOnlyAccount
{
    public function withdraw(float $amount): void;
}

class SavingAccount implements WithdrawableAccount
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

class CurrentAccount implements WithdrawableAccount
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

class FixedTermAccount implements DepositOnlyAccount
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
}

class BankClient
{
    /** @var WithdrawableAccount[] */
    private array $withdrawableAccounts;
    /** @var DepositOnlyAccount[] */
    private array $depositOnlyAccounts;

    public function __construct(array $withdrawableAccounts, array $depositOnlyAccounts)
    {
        $this->withdrawableAccounts = $withdrawableAccounts;
        $this->depositOnlyAccounts = $depositOnlyAccounts;
    }

    public function processTransactions(): void
    {
        foreach ($this->withdrawableAccounts as $acc) {
            $acc->deposit(1000);
            $acc->withdraw(500);
        }
        foreach ($this->depositOnlyAccounts as $acc) {
            $acc->deposit(5000);
        }
    }
}

class LSPFollowed
{
    public static function main(): void
    {
        $withdrawableAccounts = [];
        $withdrawableAccounts[] = new SavingAccount();
        $withdrawableAccounts[] = new CurrentAccount();

        $depositOnlyAccounts = [];
        $depositOnlyAccounts[] = new FixedTermAccount();

        $client = new BankClient($withdrawableAccounts, $depositOnlyAccounts);
        $client->processTransactions();
    }
}

LSPFollowed::main();
