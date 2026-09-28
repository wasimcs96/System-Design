<?php

// Memento - Stores database state snapshot
class DatabaseMemento
{
    /** @var array<string,string> */
    private array $data;

    /** @param array<string,string> $dbData */
    public function __construct(array $dbData)
    {
        // PHP arrays are copied by value, so this is already a snapshot (like new HashMap<>(dbData))
        $this->data = $dbData;
    }

    /** @return array<string,string> */
    public function getState(): array
    {
        return $this->data;
    }
}

// Originator - The database whose state we want to save/restore
class Database
{
    /** @var array<string,string> */
    private array $records;

    public function __construct()
    {
        $this->records = [];
    }

    // Insert a record
    public function insert(string $key, string $value): void
    {
        $this->records[$key] = $value;
        echo "Inserted: " . $key . " = " . $value . PHP_EOL;
    }

    // Update a record
    public function update(string $key, string $value): void
    {
        if (array_key_exists($key, $this->records)) {
            $this->records[$key] = $value;
            echo "Updated: " . $key . " = " . $value . PHP_EOL;
        } else {
            echo "Key not found for update: " . $key . PHP_EOL;
        }
    }

    // Delete a record
    public function remove(string $key): void
    {
        if (array_key_exists($key, $this->records)) {
            unset($this->records[$key]);
            echo "Deleted: " . $key . PHP_EOL;
        } else {
            echo "Key not found for deletion: " . $key . PHP_EOL;
        }
    }

    // Create memento - Save current state
    public function createMemento(): DatabaseMemento
    {
        echo "Creating database backup..." . PHP_EOL;
        return new DatabaseMemento($this->records);
    }

    // Restore from memento - Rollback to saved state
    public function restoreFromMemento(DatabaseMemento $memento): void
    {
        $this->records = $memento->getState();
        echo "Database restored from backup!" . PHP_EOL;
    }

    // Display current database state
    public function displayRecords(): void
    {
        echo PHP_EOL . "--- Current Database State ---" . PHP_EOL;
        if (empty($this->records)) {
            echo "Database is empty" . PHP_EOL;
        } else {
            foreach ($this->records as $key => $value) {
                echo $key . " = " . $value . PHP_EOL;
            }
        }
        echo "-----------------------------" . PHP_EOL . PHP_EOL;
    }
}

// Caretaker - Manages the memento (transaction manager)
class TransactionManager
{
    private ?DatabaseMemento $backup;

    public function __construct()
    {
        $this->backup = null;
    }

    // Begin transaction - create backup
    public function beginTransaction(Database $db): void
    {
        echo "=== BEGIN TRANSACTION ===" . PHP_EOL;
        $this->backup = $db->createMemento();
    }

    // Commit transaction - discard backup
    public function commitTransaction(): void
    {
        echo "=== COMMIT TRANSACTION ===" . PHP_EOL;
        if ($this->backup !== null) {
            $this->backup = null;
        }
        echo "Transaction committed successfully!" . PHP_EOL;
    }

    // Rollback transaction - restore from backup
    public function rollbackTransaction(Database $db): void
    {
        echo "=== ROLLBACK TRANSACTION ===" . PHP_EOL;
        if ($this->backup !== null) {
            $db->restoreFromMemento($this->backup);
            $this->backup = null;
            echo "Transaction rolled back!" . PHP_EOL;
        } else {
            echo "No backup available for rollback!" . PHP_EOL;
        }
    }
}

class MementoPattern
{
    public static function main(): void
    {
        $db = new Database();
        $txManager = new TransactionManager();

        // success scenario
        $txManager->beginTransaction($db);
        $db->insert("user1", "Aditya");
        $db->insert("user2", "Rohit");
        $txManager->commitTransaction();

        $db->displayRecords();

        // Failed scenario
        $txManager->beginTransaction($db);
        $db->insert("user3", "Saurav");
        $db->insert("user4", "Manish");

        $db->displayRecords();

        // Some error -> Rollback
        echo "ERROR: Something went wrong during transaction!" . PHP_EOL;
        $txManager->rollbackTransaction($db);

        $db->displayRecords();
    }
}

MementoPattern::main();
