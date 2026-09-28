<?php

abstract class MoneyHandler
{
    protected ?MoneyHandler $nextHandler;

    public function __construct()
    {
        $this->nextHandler = null;
    }

    public function setNextHandler(MoneyHandler $next): void
    {
        $this->nextHandler = $next;
    }

    abstract public function dispense(int $amount): void;
}

class ThousandHandler extends MoneyHandler
{
    private int $numNotes;

    public function __construct(int $numNotes)
    {
        parent::__construct();
        $this->numNotes = $numNotes;
    }

    public function dispense(int $amount): void
    {
        $notesNeeded = intdiv($amount, 1000);

        if ($notesNeeded > $this->numNotes) {
            $notesNeeded = $this->numNotes;
            $this->numNotes = 0;
        } else {
            $this->numNotes -= $notesNeeded;
        }

        if ($notesNeeded > 0)
            echo "Dispensing " . $notesNeeded . " x ₹1000 notes." . PHP_EOL;

        $remainingAmount = $amount - ($notesNeeded * 1000);
        if ($remainingAmount > 0) {
            if ($this->nextHandler !== null) $this->nextHandler->dispense($remainingAmount);
            else {
                echo "Remaining amount of " . $remainingAmount . " cannot be fulfilled (Insufficinet fund in ATM)" . PHP_EOL;
            }
        }
    }
}

class FiveHundredHandler extends MoneyHandler
{
    private int $numNotes;

    public function __construct(int $numNotes)
    {
        parent::__construct();
        $this->numNotes = $numNotes;
    }

    public function dispense(int $amount): void
    {
        $notesNeeded = intdiv($amount, 500);

        if ($notesNeeded > $this->numNotes) {
            $notesNeeded = $this->numNotes;
            $this->numNotes = 0;
        } else {
            $this->numNotes -= $notesNeeded;
        }

        if ($notesNeeded > 0)
            echo "Dispensing " . $notesNeeded . " x ₹500 notes." . PHP_EOL;

        $remainingAmount = $amount - ($notesNeeded * 500);
        if ($remainingAmount > 0) {
            if ($this->nextHandler !== null) $this->nextHandler->dispense($remainingAmount);
            else {
                echo "Remaining amount of " . $remainingAmount . " cannot be fulfilled (Insufficinet fund in ATM)" . PHP_EOL;
            }
        }
    }
}

class TwoHundredHandler extends MoneyHandler
{
    private int $numNotes;

    public function __construct(int $numNotes)
    {
        parent::__construct();
        $this->numNotes = $numNotes;
    }

    public function dispense(int $amount): void
    {
        $notesNeeded = intdiv($amount, 200);

        if ($notesNeeded > $this->numNotes) {
            $notesNeeded = $this->numNotes;
            $this->numNotes = 0;
        } else {
            $this->numNotes -= $notesNeeded;
        }

        if ($notesNeeded > 0)
            echo "Dispensing " . $notesNeeded . " x ₹200 notes." . PHP_EOL;

        $remainingAmount = $amount - ($notesNeeded * 200);
        if ($remainingAmount > 0) {
            if ($this->nextHandler !== null) $this->nextHandler->dispense($remainingAmount);
            else {
                echo "Remaining amount of " . $remainingAmount . " cannot be fulfilled (Insufficinet fund in ATM)" . PHP_EOL;
            }
        }
    }
}

class HundredHandler extends MoneyHandler
{
    private int $numNotes;

    public function __construct(int $numNotes)
    {
        parent::__construct();
        $this->numNotes = $numNotes;
    }

    public function dispense(int $amount): void
    {
        $notesNeeded = intdiv($amount, 100);

        if ($notesNeeded > $this->numNotes) {
            $notesNeeded = $this->numNotes;
            $this->numNotes = 0;
        } else {
            $this->numNotes -= $notesNeeded;
        }

        if ($notesNeeded > 0)
            echo "Dispensing " . $notesNeeded . " x ₹100 notes." . PHP_EOL;

        $remainingAmount = $amount - ($notesNeeded * 100);
        if ($remainingAmount > 0) {
            if ($this->nextHandler !== null) $this->nextHandler->dispense($remainingAmount);
            else {
                echo "Remaining amount of " . $remainingAmount . " cannot be fulfilled (Insufficinet fund in ATM)" . PHP_EOL;
            }
        }
    }
}

class COR
{
    public static function main(): void
    {
        $thousandHandler = new ThousandHandler(3);
        $fiveHundredHandler = new FiveHundredHandler(5);
        $twoHundredHandler = new TwoHundredHandler(10);
        $hundredHandler = new HundredHandler(20);

        $thousandHandler->setNextHandler($fiveHundredHandler);
        $fiveHundredHandler->setNextHandler($twoHundredHandler);
        $twoHundredHandler->setNextHandler($hundredHandler);

        $amountToWithdraw = 4000;

        echo PHP_EOL . "Dispensing amount: ₹" . $amountToWithdraw . PHP_EOL;
        $thousandHandler->dispense($amountToWithdraw);
    }
}

COR::main();
