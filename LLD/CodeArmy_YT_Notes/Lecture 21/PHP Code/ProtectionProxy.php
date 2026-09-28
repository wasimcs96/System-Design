<?php

interface IDocumentReader
{
    public function unlockPDF(string $filePath, string $password): void;
}

class RealDocumentReader implements IDocumentReader
{
    public function unlockPDF(string $filePath, string $password): void
    {
        echo "[RealDocumentReader] Unlocking PDF at: " . $filePath . PHP_EOL;
        echo "[RealDocumentReader] PDF unlocked successfully with password: " . $password . PHP_EOL;
        echo "[RealDocumentReader] Displaying PDF content..." . PHP_EOL;
    }
}

class User
{
    public string $name;
    public bool $premiumMembership;

    public function __construct(string $name, bool $isPremium)
    {
        $this->name = $name;
        $this->premiumMembership = $isPremium;
    }
}

class DocumentProxy implements IDocumentReader
{
    private RealDocumentReader $realReader;
    private User $user;

    public function __construct(User $user)
    {
        $this->realReader = new RealDocumentReader();
        $this->user = $user;
    }

    public function unlockPDF(string $filePath, string $password): void
    {
        if (!$this->user->premiumMembership) {
            echo "[DocumentProxy] Access denied. Only premium members can unlock PDFs." . PHP_EOL;
            return;
        }
        $this->realReader->unlockPDF($filePath, $password);
    }
}

class ProtectionProxy
{
    public static function main(): void
    {
        $user1 = new User("Rohan", false);
        $user2 = new User("Rashmi", true);

        echo "== Rohan (Non-Premium) tries to unlock PDF ==" . PHP_EOL;
        /** @var IDocumentReader $docReader */
        $docReader = new DocumentProxy($user1);
        $docReader->unlockPDF("protected_document.pdf", "secret123");

        echo PHP_EOL . "== Rashmi (Premium) unlocks PDF ==" . PHP_EOL;
        $docReader = new DocumentProxy($user2);
        $docReader->unlockPDF("protected_document.pdf", "secret123");
    }
}

ProtectionProxy::main();
