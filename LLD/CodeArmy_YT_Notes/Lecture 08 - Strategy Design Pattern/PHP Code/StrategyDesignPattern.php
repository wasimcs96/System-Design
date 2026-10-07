<?php

// --- Strategy Interface for Walk ---
interface WalkableRobot
{
    public function walk(): void;
}

// --- Concrete Strategies for walk ---
class NormalWalk implements WalkableRobot
{
    public function walk(): void
    {
        echo "Walking normally..." . PHP_EOL;
    }
}

class NoWalk implements WalkableRobot
{
    public function walk(): void
    {
        echo "Cannot walk." . PHP_EOL;
    }
}

// --- Strategy Interface for Talk ---
interface TalkableRobot
{
    public function talk(): void;
}

// --- Concrete Strategies for Talk ---
class NormalTalk implements TalkableRobot
{
    public function talk(): void
    {
        echo "Talking normally..." . PHP_EOL;
    }
}

class NoTalk implements TalkableRobot
{
    public function talk(): void
    {
        echo "Cannot talk." . PHP_EOL;
    }
}

// --- Strategy Interface for Fly ---
interface FlyableRobot
{
    public function fly(): void;
}

class NormalFly implements FlyableRobot
{
    public function fly(): void
    {
        echo "Flying normally..." . PHP_EOL;
    }
}

class NoFly implements FlyableRobot
{
    public function fly(): void
    {
        echo "Cannot fly." . PHP_EOL;
    }
}

// --- Robot Base Class ---
abstract class Robot
{
    protected WalkableRobot $walkBehavior;
    protected TalkableRobot $talkBehavior;
    protected FlyableRobot $flyBehavior;

    public function __construct(WalkableRobot $w, TalkableRobot $t, FlyableRobot $f)
    {
        $this->walkBehavior = $w;
        $this->talkBehavior = $t;
        $this->flyBehavior = $f;
    }

    public function walk(): void
    {
        $this->walkBehavior->walk();
    }

    public function talk(): void
    {
        $this->talkBehavior->talk();
    }

    public function fly(): void
    {
        $this->flyBehavior->fly();
    }

    abstract public function projection(): void; // Abstract method for subclasses
}

// --- Concrete Robot Types ---
class CompanionRobot extends Robot
{
    public function __construct(WalkableRobot $w, TalkableRobot $t, FlyableRobot $f)
    {
        parent::__construct($w, $t, $f);
    }

    public function projection(): void
    {
        echo "Displaying friendly companion features..." . PHP_EOL;
    }
}

class WorkerRobot extends Robot
{
    public function __construct(WalkableRobot $w, TalkableRobot $t, FlyableRobot $f)
    {
        parent::__construct($w, $t, $f);
    }

    public function projection(): void
    {
        echo "Displaying worker efficiency stats..." . PHP_EOL;
    }
}

// --- Main Function ---
class StrategyDesignPattern
{
    public static function main(): void
    {
        $robot1 = new CompanionRobot(new NormalWalk(), new NormalTalk(), new NoFly());
        $robot1->walk();
        $robot1->talk();
        $robot1->fly();
        $robot1->projection();

        echo "--------------------" . PHP_EOL;

        $robot2 = new WorkerRobot(new NoWalk(), new NoTalk(), new NormalFly());
        $robot2->walk();
        $robot2->talk();
        $robot2->fly();
        $robot2->projection();
    }
}

StrategyDesignPattern::main();
