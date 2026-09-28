<?php

// Each User knows *all* the others directly.
// If you have N users, you wind up wiring N*(N–1)/2 connections,
// and every new feature (mute, private send, logging...) lives in User too.
class User
{
    private string $name;
    /** @var User[] */
    private array $peers;
    /** @var string[] */
    private array $mutedUsers;

    public function __construct(string $n)
    {
        $this->name = $n;
        $this->peers = [];
        $this->mutedUsers = [];
    }

    // must manually connect every pair -> N^2 wiring
    public function addPeer(User $u): void
    {
        $this->peers[] = $u;
    }

    // duplication: everyone has its own mute list
    public function mute(string $userToMute): void
    {
        $this->mutedUsers[] = $userToMute;
    }

    // broadcast to all peers
    public function send(string $msg): void
    {
        echo "[" . $this->name . " broadcasts]: " . $msg . PHP_EOL;
        foreach ($this->peers as $peer) {

            // if they have muted me dont send.
            if (!$peer->isMuted($this->name)) {
                $peer->receive($this->name, $msg);
            }
        }
    }

    public function isMuted(string $userName): bool
    {
        foreach ($this->mutedUsers as $name) {
            if ($name === $userName) {
                return true;
            }
        }
        return false;
    }

    // private send - duplicated in every class
    public function sendTo(User $target, string $msg): void
    {
        echo "[" . $this->name . "→" . $target->name . "]: " . $msg . PHP_EOL;
        if (!$target->isMuted($this->name)) {
            $target->receive($this->name, $msg);
        }
    }

    public function receive(string $from, string $msg): void
    {
        echo "    " . $this->name . " got from " . $from . ": " . $msg . PHP_EOL;
    }
}

class WithoutMediator
{
    public static function main(): void
    {
        // create users
        $user1 = new User("Rohan");
        $user2 = new User("Neha");
        $user3 = new User("Mohan");

        // wire up peers (each knows each other) -> n*(n-1)/2 connections
        $user1->addPeer($user2);
        $user2->addPeer($user1);

        $user1->addPeer($user3);
        $user3->addPeer($user1);

        $user2->addPeer($user3);
        $user3->addPeer($user2);

        // mute example: Mohan mutes Rohan (Hence Rohan add Mohan to its muted list).
        $user1->mute("Mohan");

        // broadcast
        $user1->send("Hello everyone!");

        // private
        $user3->sendTo($user2, "Hey Neha!");
    }
}

WithoutMediator::main();
