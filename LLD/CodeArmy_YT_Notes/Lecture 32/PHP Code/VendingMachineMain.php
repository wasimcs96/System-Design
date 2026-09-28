<?php

// Abstract State Interface
interface VendingState
{
    public function insertCoin(VendingMachine $machine, int $coin): VendingState;
    public function selectItem(VendingMachine $machine): VendingState;
    public function dispense(VendingMachine $machine): VendingState;
    public function returnCoin(VendingMachine $machine): VendingState;
    public function refill(VendingMachine $machine, int $quantity): VendingState;
    public function getStateName(): string;
}

// Context Class - Vending Machine
class VendingMachine
{
    private VendingState $currentState;
    private int $itemCount;
    private int $itemPrice;
    private int $insertedCoins;

    // State objects (we'll initialize these)
    private VendingState $noCoinState;
    private VendingState $hasCoinState;
    private VendingState $dispenseState;
    private VendingState $soldOutState;

    public function __construct(int $itemCount, int $itemPrice)
    {
        $this->itemCount = $itemCount;
        $this->itemPrice = $itemPrice;
        $this->insertedCoins = 0;

        // Create state objects
        $this->noCoinState = new NoCoinState();
        $this->hasCoinState = new HasCoinState();
        $this->dispenseState = new DispenseState();
        $this->soldOutState = new SoldOutState();

        // Set initial state
        if ($itemCount > 0) {
            $this->currentState = $this->noCoinState;
        } else {
            $this->currentState = $this->soldOutState;
        }
    }

    // Delegate to current state and update state based on return value
    public function insertCoin(int $coin): void
    {
        $this->currentState = $this->currentState->insertCoin($this, $coin);
    }

    public function selectItem(): void
    {
        $this->currentState = $this->currentState->selectItem($this);
    }

    public function dispense(): void
    {
        $this->currentState = $this->currentState->dispense($this);
    }

    public function returnCoin(): void
    {
        $this->currentState = $this->currentState->returnCoin($this);
    }

    public function refill(int $quantity): void
    {
        $this->currentState = $this->currentState->refill($this, $quantity);
    }

    // Print the status of Vending Machine
    public function printStatus(): void
    {
        echo PHP_EOL . "--- Vending Machine Status ---" . PHP_EOL;
        echo "Items remaining: " . $this->itemCount . PHP_EOL;
        echo "Inserted coin: Rs " . $this->insertedCoins . PHP_EOL;
        echo "Current state: " . $this->currentState->getStateName() . "\n" . PHP_EOL;
    }

    // Getters for states
    public function getNoCoinState(): VendingState
    {
        return $this->noCoinState;
    }

    public function getHasCoinState(): VendingState
    {
        return $this->hasCoinState;
    }

    public function getDispenseState(): VendingState
    {
        return $this->dispenseState;
    }

    public function getSoldOutState(): VendingState
    {
        return $this->soldOutState;
    }

    // Data access methods
    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function decrementItemCount(): void
    {
        $this->itemCount--;
    }

    // Java had incrementItemCount() and incrementItemCount(int) -> default parameter in PHP
    public function incrementItemCount(int $count = 1): void
    {
        $this->itemCount += $count;
    }

    public function getInsertedCoin(): int
    {
        return $this->insertedCoins;
    }

    public function setInsertedCoin(int $coin): void
    {
        $this->insertedCoins = $coin;
    }

    public function addCoin(int $coin): void
    {
        $this->insertedCoins += $coin;
    }

    public function getPrice(): int
    {
        return $this->itemPrice;
    }

    public function setPrice(int $itemPrice): void
    {
        $this->itemPrice = $itemPrice;
    }
}

// Concrete State: No Coin Inserted
class NoCoinState implements VendingState
{
    public function insertCoin(VendingMachine $machine, int $coin): VendingState
    {
        $machine->setInsertedCoin($coin); // Rs 10
        echo "Coin inserted. Current balance: Rs " . $coin . PHP_EOL;
        return $machine->getHasCoinState(); // Transition to HasCoinState
    }

    public function selectItem(VendingMachine $machine): VendingState
    {
        echo "Please insert coin first!" . PHP_EOL;
        return $machine->getNoCoinState(); // Stay in same state
    }

    public function dispense(VendingMachine $machine): VendingState
    {
        echo "Please insert coin and select item first!" . PHP_EOL;
        return $machine->getNoCoinState(); // Stay in same state
    }

    public function returnCoin(VendingMachine $machine): VendingState
    {
        echo "No coin to return!" . PHP_EOL;
        return $machine->getNoCoinState(); // Stay in same state
    }

    public function refill(VendingMachine $machine, int $quantity): VendingState
    {
        echo "Items refilling" . PHP_EOL;
        $machine->incrementItemCount($quantity);
        return $machine->getNoCoinState(); // Stay in same state
    }

    public function getStateName(): string
    {
        return "NO_COIN";
    }
}

// Concrete State: Coin Inserted
class HasCoinState implements VendingState
{
    public function insertCoin(VendingMachine $machine, int $coin): VendingState
    {
        $machine->addCoin($coin);
        echo "Additional coin inserted. Current balance: Rs " . $machine->getInsertedCoin() . PHP_EOL;
        return $machine->getHasCoinState(); // Stay in same state
    }

    public function selectItem(VendingMachine $machine): VendingState
    {
        if ($machine->getInsertedCoin() >= $machine->getPrice()) {
            echo "Item selected. Dispensing..." . PHP_EOL;

            $change = $machine->getInsertedCoin() - $machine->getPrice();
            if ($change > 0) {
                echo "Change returned: Rs " . $change . PHP_EOL;
            }
            $machine->setInsertedCoin(0);

            return $machine->getDispenseState(); // Transition to DispenseState
        } else {
            $needed = $machine->getPrice() - $machine->getInsertedCoin();
            echo "Insufficient funds. Need Rs " . $needed . " more." . PHP_EOL;
            return $machine->getHasCoinState(); // Stay in same state
        }
    }

    public function dispense(VendingMachine $machine): VendingState
    {
        echo "Please select an item first!" . PHP_EOL;
        return $machine->getHasCoinState(); // Stay in same state
    }

    public function returnCoin(VendingMachine $machine): VendingState
    {
        echo "Coin returned: Rs " . $machine->getInsertedCoin() . PHP_EOL;
        $machine->setInsertedCoin(0);
        return $machine->getNoCoinState(); // Transition to NoCoinState
    }

    public function refill(VendingMachine $machine, int $quantity): VendingState
    {
        echo "Can't refil in this state" . PHP_EOL;
        return $machine->getHasCoinState(); // Stay in same state
    }

    public function getStateName(): string
    {
        return "HAS_COIN";
    }
}

// Concrete State: Item Sold
class DispenseState implements VendingState
{
    public function insertCoin(VendingMachine $machine, int $coin): VendingState
    {
        echo "Please wait, already dispensing item. Coin returned: Rs " . $coin . PHP_EOL;
        return $machine->getDispenseState();  // Stay in same state
    }

    public function selectItem(VendingMachine $machine): VendingState
    {
        echo "Already dispensing item. Please wait." . PHP_EOL;
        return $machine->getDispenseState(); // Stay in same state
    }

    public function dispense(VendingMachine $machine): VendingState
    {
        echo "Item dispensed!" . PHP_EOL;
        $machine->decrementItemCount();

        if ($machine->getItemCount() > 0) {
            return $machine->getNoCoinState(); // Transition to NoCoinState
        } else {
            echo "Machine is now sold out!" . PHP_EOL;
            return $machine->getSoldOutState(); // Transition to SoldOutState
        }
    }

    public function returnCoin(VendingMachine $machine): VendingState
    {
        echo "Cannot return coin while dispensing item!" . PHP_EOL;
        return $machine->getDispenseState(); // Stay in same state
    }

    public function refill(VendingMachine $machine, int $quantity): VendingState
    {
        echo "Can't refil in this state" . PHP_EOL;
        return $machine->getDispenseState(); // Stay in same state
    }

    public function getStateName(): string
    {
        return "DISPENSING";
    }
}

// Concrete State: Sold Out
class SoldOutState implements VendingState
{
    public function insertCoin(VendingMachine $machine, int $coin): VendingState
    {
        echo "Machine is sold out. Coin returned: Rs " . $coin . PHP_EOL;
        return $machine->getSoldOutState(); // Stay in same state
    }

    public function selectItem(VendingMachine $machine): VendingState
    {
        echo "Machine is sold out!" . PHP_EOL;
        return $machine->getSoldOutState(); // Stay in same state
    }

    public function dispense(VendingMachine $machine): VendingState
    {
        echo "Machine is sold out!" . PHP_EOL;
        return $machine->getSoldOutState(); // Stay in same state
    }

    public function returnCoin(VendingMachine $machine): VendingState
    {
        echo "Machine is sold out. No coin inserted." . PHP_EOL;
        return $machine->getSoldOutState(); // Stay in same state
    }

    public function refill(VendingMachine $machine, int $quantity): VendingState
    {
        echo "Items refilling" . PHP_EOL;
        $machine->incrementItemCount($quantity);
        return $machine->getNoCoinState();
    }

    public function getStateName(): string
    {
        return "SOLD_OUT";
    }
}

// Main class for Vending Machine
class VendingMachineMain
{
    public static function main(): void
    {
        echo "=== Water Bottle VENDING MACHINE ===" . PHP_EOL;

        $itemCount = 2;
        $itemPrice = 20;

        $machine = new VendingMachine($itemCount, $itemPrice);
        $machine->printStatus();

        // Test scenarios - each operation potentially changes state
        echo "1. Trying to select item without coin:" . PHP_EOL;
        $machine->selectItem();  // Should ask for coin, no state change
        $machine->printStatus();

        echo "2. Inserting coin:" . PHP_EOL;
        $machine->insertCoin(10);  // State changes to HAS_COIN
        $machine->printStatus();

        echo "3. Selecting item with insufficient funds:" . PHP_EOL;
        $machine->selectItem();  // Insufficient funds, stays in HAS_COIN
        $machine->printStatus();

        echo "4. Adding more coins:" . PHP_EOL;
        $machine->insertCoin(10);  // Add more money, stays in HAS_COIN
        $machine->printStatus();

        echo "5. Selecting item Now" . PHP_EOL;
        $machine->selectItem();  // State changes to SOLD
        $machine->printStatus();

        echo "6. Dispensing item:" . PHP_EOL;
        $machine->dispense(); // State changes to NO_COIN (items remaining)
        $machine->printStatus();

        echo "7. Buying last item:" . PHP_EOL;
        $machine->insertCoin(20);  // State changes to HAS_COIN
        $machine->selectItem();  // State changes to SOLD
        $machine->dispense(); // State changes to SOLD_OUT (no items left)
        $machine->printStatus();

        echo "8. Trying to use sold out machine:" . PHP_EOL;
        $machine->insertCoin(5);  // Coin returned, stays in SOLD_OUT

        echo "9. Trying to use sold out machine:" . PHP_EOL;
        $machine->refill(2);
        $machine->printStatus(); // State changes NO_COIN
    }
}

VendingMachineMain::main();
