<?php

// Abstraction (Interface)
interface Database
{
    public function save(string $data): void;
}

// MySQL implementation (Low-level module)
class MySQLDatabase implements Database
{
    public function save(string $data): void
    {
        echo "Executing SQL Query: INSERT INTO users VALUES('" . $data . "');" . PHP_EOL;
    }
}

// MongoDB implementation (Low-level module)
class MongoDBDatabase implements Database
{
    public function save(string $data): void
    {
        echo "Executing MongoDB Function: db.users.insert({name: '" . $data . "'})" . PHP_EOL;
    }
}

// High-level module (Now loosely coupled via Dependency Injection)
class UserService
{
    private readonly Database $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function storeUser(string $user): void
    {
        $this->db->save($user);
    }
}

class DIPFollowed
{
    public static function main(): void
    {
        $mysql = new MySQLDatabase();
        $mongodb = new MongoDBDatabase();

        $service1 = new UserService($mysql);
        $service1->storeUser("Aditya");

        $service2 = new UserService($mongodb);
        $service2->storeUser("Rohit");
    }
}

DIPFollowed::main();
