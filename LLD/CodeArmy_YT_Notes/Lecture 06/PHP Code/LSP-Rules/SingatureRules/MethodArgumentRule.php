<?php

// Method Argument Rule :
// Subtype method arguments can be identical or wider than the supertype.
// PHP supports this directly (parameter contravariance): a child could declare
// print(string|int $msg) or print(mixed $msg), but never a narrower type.

class ParentClass  // "Parent" is a reserved word in PHP, so the class is called ParentClass
{
    public function print(string $msg): void
    {
        echo "Parent: " . $msg . PHP_EOL;
    }
}

class Child extends ParentClass
{
    public function print(string $msg): void
    {
        echo "Child: " . $msg . PHP_EOL;
    }
}

// Client that passes a String msg as the client expects.
class Client
{
    private ParentClass $p;

    public function __construct(ParentClass $p)
    {
        $this->p = $p;
    }

    public function printMsg(): void
    {
        $this->p->print("Hello");
    }
}

class MethodArgumentRule
{
    public static function main(): void
    {
        $parent = new ParentClass();
        $child  = new Child();

        $client = new Client($parent);
        // $client = new Client($child);

        $client->printMsg();
    }
}

MethodArgumentRule::main();
