<?php

// Exception Rule:
// A subclass should throw fewer or narrower exceptions
// (but not additional or broader exceptions) than the parent.
// PHP does NOT enforce this at all (PHP has no checked exceptions / "throws" clause),
// so it is purely a design discipline you must follow yourself.

/*
PHP's built-in hierarchy (relevant part):
Throwable
├── Error                                  // Engine errors (TypeError, DivisionByZeroError...)
│   └── ArithmeticError
│       └── DivisionByZeroError
└── Exception
    ├── LogicException                     // Bugs in program logic
    │   ├── InvalidArgumentException
    │   ├── DomainException
    │   ├── LengthException
    │   └── OutOfRangeException
    └── RuntimeException                   // Errors only detectable at runtime
        ├── OutOfBoundsException
        ├── OverflowException
        ├── RangeException
        ├── UnderflowException
        └── UnexpectedValueException
*/

// A narrower exception than RuntimeException (mirrors Java's ArithmeticException).
class ArithmeticException extends RuntimeException {}

class ParentClass  // "Parent" is a reserved word in PHP, so the class is called ParentClass
{
    /** @throws RuntimeException */
    public function getValue(): void
    {
        throw new RuntimeException("Parent error");
    }
}

// Subclass overrides getValue and throws the narrower ArithmeticException
class Child extends ParentClass
{
    /** @throws ArithmeticException */
    public function getValue(): void
    {
        throw new ArithmeticException("Child error");
        // throw new Exception("Child error"); // This is wrong (broader) & should not be done
    }
}

// Client that invokes getValue and catches the parent exception type
class Client
{
    private ParentClass $p;

    public function __construct(ParentClass $p)
    {
        $this->p = $p;
    }

    public function takeValue(): void
    {
        try {
            $this->p->getValue();
        } catch (RuntimeException $e) {
            echo "RuntimeException occurred: " . $e->getMessage() . PHP_EOL;
        }
    }
}

class ExceptionRule
{
    public static function main(): void
    {
        $parent = new ParentClass();
        $child  = new Child();

        $client = new Client($parent);
        // $client = new Client($child);

        $client->takeValue();
    }
}

ExceptionRule::main();
