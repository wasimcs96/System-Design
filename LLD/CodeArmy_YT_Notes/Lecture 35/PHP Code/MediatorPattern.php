<?php

// ─────────────── Mediator Interface ───────────────
interface IMediator
{
    public function registerColleague(Colleague $c): void;
    public function send(string $from, string $msg): void;
    public function sendPrivate(string $from, string $to, string $msg): void;
}

// ─────────────── Colleague Interface ───────────────
abstract class Colleague
{
    protected IMediator $mediator;

    public function __construct(IMediator $m)
    {
        $this->mediator = $m;
        $this->mediator->registerColleague($this);
    }

    abstract public function getName(): string;
    abstract public function send(string $msg): void;
    abstract public function sendPrivate(string $to, string $msg): void;
    abstract public function receive(string $from, string $msg): void;
}

// Simple Pair class
class Pair
{
    public readonly mixed $first;
    public readonly mixed $second;

    public function __construct(mixed $first, mixed $second)
    {
        $this->first = $first;
        $this->second = $second;
    }
}

// ─────────────── Concrete Mediator ───────────────
class ChatMediator implements IMediator
{
    /** @var Colleague[] */
    private array $colleagues;
    /** @var Pair[] (muter, muted) */
    private array $mutes;

    public function __construct()
    {
        $this->colleagues = [];
        $this->mutes = [];
    }

    public function registerColleague(Colleague $c): void
    {
        $this->colleagues[] = $c;
    }

    public function mute(string $who, string $whom): void
    {
        $this->mutes[] = new Pair($who, $whom);
    }

    public function send(string $from, string $msg): void
    {
        echo "[" . $from . " broadcasts]: " . $msg . PHP_EOL;
        foreach ($this->colleagues as $c) {
            // Don't send msg to itself.
            if ($c->getName() === $from) {
                continue;
            }

            $isMuted = false;
            // Ignore if person is muted
            foreach ($this->mutes as $p) {
                if ($from === $p->second && $c->getName() === $p->first) {
                    $isMuted = true;
                    break;
                }
            }
            if (!$isMuted) {
                $c->receive($from, $msg);
            }
        }
    }

    public function sendPrivate(string $from, string $to, string $msg): void
    {
        echo "[" . $from . "→" . $to . "]: " . $msg . PHP_EOL;
        foreach ($this->colleagues as $c) {
            if ($c->getName() === $to) {
                foreach ($this->mutes as $p) {
                    // Dont send if muted
                    if ($from === $p->second && $to === $p->first) {
                        echo PHP_EOL . "[Message is muted]" . PHP_EOL . PHP_EOL;
                        return;
                    }
                }
                $c->receive($from, $msg);
                return;
            }
        }
        echo "[Mediator] User \"" . $to . "\" not found]" . PHP_EOL;
    }
}

// ─────────────── Concrete Colleague ───────────────
class User extends Colleague
{
    private string $name;

    public function __construct(string $n, IMediator $m)
    {
        // Set the name first so it is available as soon as the mediator registers us
        $this->name = $n;
        parent::__construct($m);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function send(string $msg): void
    {
        $this->mediator->send($this->name, $msg);
    }

    public function sendPrivate(string $to, string $msg): void
    {
        $this->mediator->sendPrivate($this->name, $to, $msg);
    }

    public function receive(string $from, string $msg): void
    {
        echo "    " . $this->name . " got from " . $from . ": " . $msg . PHP_EOL;
    }
}

// ─────────────── Demo ───────────────
class MediatorPattern
{
    public static function main(): void
    {
        $chatRoom = new ChatMediator();

        $user1 = new User("Rohan", $chatRoom);
        $user2 = new User("Neha", $chatRoom);
        $user3 = new User("Mohan", $chatRoom);

        // Rohan mutes Mohan
        $chatRoom->mute("Rohan", "Mohan");

        // broadcast from Rohan
        $user1->send("Hello Everyone!");

        // private from Mohan to Neha
        $user3->sendPrivate("Neha", "Hey Neha!");
    }
}

MediatorPattern::main();
