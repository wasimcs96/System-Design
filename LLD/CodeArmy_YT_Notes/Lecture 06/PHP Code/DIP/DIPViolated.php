<?php

class MySQLDatabase  // Low-level module
{
    public function saveToSQL(string $data): void
    {
        echo "Executing SQL Query: INSERT INTO users VALUES('" . $data . "');" . PHP_EOL;
    }
}

class MongoDBDatabase  // Low-level module
{
    public function saveToMongo(string $data): void
    {
        echo "Executing MongoDB Function: db.users.insert({name: '" . $data . "'})" . PHP_EOL;
    }
}

class UserService  // High-level module (Tightly coupled)
{
    private readonly MySQLDatabase $sqlDb;
    private readonly MongoDBDatabase $mongoDb;

    public function __construct()
    {
        // PHP does not allow "new" in property defaults, so we create them here.
        $this->sqlDb = new MySQLDatabase();
        $this->mongoDb = new MongoDBDatabase();
    }

    public function storeUserToSQL(string $user): void
    {
        // MySQL-specific code
        $this->sqlDb->saveToSQL($user);
    }

    public function storeUserToMongo(string $user): void
    {
        // MongoDB-specific code
        $this->mongoDb->saveToMongo($user);
    }
}

class DIPViolated
{
    public static function main(): void
    {
        $service = new UserService();
        $service->storeUserToSQL("Aditya");
        $service->storeUserToMongo("Rohit");
    }
}

DIPViolated::main();
