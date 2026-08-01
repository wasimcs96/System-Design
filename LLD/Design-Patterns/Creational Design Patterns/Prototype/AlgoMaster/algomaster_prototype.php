<?php

/**
 * algomaster_prototype.php
 * ---------------------------------------------------------------------------
 * Standalone runnable file collecting every PHP code example from
 * Prototype-AlgoMaster-Bilingual-Study.md, in the same order they appear in
 * that document. All code is original (AlgoMaster's own code sits behind a
 * subscription — see the sourcing note at the top of that document) — this
 * file just makes the examples runnable independent of the markdown/PDF.
 *
 * Sections:
 *   1. EnemyPrototype interface
 *   2. Shallow-clone-only Enemy (illustrative — fine while every field is a
 *      primitive; kept here, renamed, so the shallow-vs-deep narrative from
 *      the document is runnable and comparable side by side)
 *   3. Item + deep-clone-aware Enemy (adds a mutable inventory field)
 *   4. EnemyRegistry
 *   5. Client code (spawning enemies purely by cloning)
 *   6. Email-templates example (RecipientList deep copy)
 *
 * Run with: php algomaster_prototype.php
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace AlgoMaster\Prototype\EnemyGame {

    echo "=== Section 1+2: EnemyPrototype interface + shallow-clone-only Enemy (illustrative) ===\n\n";

    interface EnemyPrototype
    {
        public function clone(): EnemyPrototype;
    }

    // Renamed from the document's "Enemy" to avoid clashing with the
    // deep-clone-aware Enemy in Section 3 below — in the document these are
    // two successive versions of the SAME class name, shown one at a time.
    class EnemyShallowDemo implements EnemyPrototype
    {
        public function __construct(
            public string $type,
            public int $health,
            public int $speed,
            public bool $armored,
            public string $weapon
        ) {
        }

        // Shallow clone: fine here because every field above is a primitive.
        public function clone(): EnemyPrototype
        {
            return new self($this->type, $this->health, $this->speed, $this->armored, $this->weapon);
        }
    }

    // Quick demo proving the shallow clone is already independent for
    // primitive-only fields — this is the "why shallow is fine, for now"
    // half of the document's narrative.
    $shallowOriginal = new EnemyShallowDemo(type: "Basic", health: 40, speed: 4, armored: false, weapon: "Knife");
    $shallowClone = $shallowOriginal->clone();
    $shallowClone->health = 10;
    printf(
        "Shallow demo -> original health: %d, clone health: %d (independent, as expected)\n\n",
        $shallowOriginal->health,
        $shallowClone->health
    );

    echo "=== Section 3: Item + deep-clone-aware Enemy ===\n\n";

    class Item
    {
        public function __construct(public string $name, public int $power)
        {
        }

        public function clone(): self
        {
            return new self($this->name, $this->power);
        }
    }

    class Enemy implements EnemyPrototype
    {
        /** @var Item[] */
        private array $inventory = [];

        public function __construct(
            public string $type,
            public int $health,
            public int $speed,
            public bool $armored,
            public string $weapon
        ) {
        }

        public function addItem(Item $item): void
        {
            $this->inventory[] = $item;
        }

        /** @return Item[] */
        public function getInventory(): array
        {
            return $this->inventory;
        }

        // Deep clone: every Item object in $inventory is cloned individually,
        // so the clone's inventory list is fully independent of the original's.
        public function clone(): EnemyPrototype
        {
            $copy = new self($this->type, $this->health, $this->speed, $this->armored, $this->weapon);
            foreach ($this->inventory as $item) {
                $copy->addItem($item->clone());
            }
            return $copy;
        }
    }

    echo "=== Section 4: EnemyRegistry ===\n\n";

    class EnemyRegistry
    {
        /** @var array<string, EnemyPrototype> */
        private array $prototypes = [];

        public function register(string $key, EnemyPrototype $prototype): void
        {
            $this->prototypes[$key] = $prototype;
        }

        public function get(string $key): EnemyPrototype
        {
            if (!isset($this->prototypes[$key])) {
                throw new \InvalidArgumentException("No prototype registered for key: {$key}");
            }
            // Always return a clone — the registry's own copy must never be
            // handed out and never be mutated by a caller.
            return $this->prototypes[$key]->clone();
        }
    }

    echo "=== Section 5: Client code ===\n\n";

    // Configure each prototype once.
    $flying = new Enemy(type: "Flying", health: 60, speed: 9, armored: false, weapon: "Laser");
    $flying->addItem(new Item("Speed Boost", 5));

    $armored = new Enemy(type: "Armored", health: 150, speed: 3, armored: true, weapon: "Cannon");
    $armored->addItem(new Item("Shield Plate", 10));

    $registry = new EnemyRegistry();
    $registry->register("flying", $flying);
    $registry->register("armored", $armored);

    // Spawn enemies purely by cloning — never by calling `new Enemy(...)` again.
    $enemy1 = $registry->get("flying");
    $enemy2 = $registry->get("flying");

    // Customize one clone without touching the original prototype or the other clone.
    $enemy2->health = 30; // a "weakened" flying enemy

    printf("Enemy 1: %s, health=%d, inventory=%d item(s)\n", $enemy1->type, $enemy1->health, count($enemy1->getInventory()));
    printf("Enemy 2: %s, health=%d, inventory=%d item(s)\n", $enemy2->type, $enemy2->health, count($enemy2->getInventory()));
    printf("Original prototype health untouched: %d\n\n", $flying->health);
}

namespace AlgoMaster\Prototype\EmailTemplates {

    echo "=== Section 6: Email-templates example (RecipientList deep copy) ===\n\n";

    class RecipientList
    {
        /** @var string[] */
        private array $to;

        /** @var string[] */
        private array $cc;

        public function __construct(array $to = [], array $cc = [])
        {
            $this->to = $to;
            $this->cc = $cc;
        }

        public function addTo(string $email): void
        {
            $this->to[] = $email;
        }

        public function addCc(string $email): void
        {
            $this->cc[] = $email;
        }

        public function summary(): string
        {
            return sprintf("to=[%s], cc=[%s]", implode(", ", $this->to), implode(", ", $this->cc));
        }

        // Deep copy: new arrays, so mutating the copy's lists never touches the original's.
        public function deepCopy(): self
        {
            return new self($this->to, $this->cc);
        }
    }

    class EmailTemplate
    {
        public function __construct(
            public string $subject,
            public string $body,
            private RecipientList $recipients
        ) {
        }

        public function addRecipient(string $email): void
        {
            $this->recipients->addTo($email);
        }

        public function addCc(string $email): void
        {
            $this->recipients->addCc($email);
        }

        public function recipientSummary(): string
        {
            return $this->recipients->summary();
        }

        // clone() calls recipients->deepCopy() — without this line, every
        // department clone would share and corrupt the same recipient list.
        public function clone(): self
        {
            return new self($this->subject, $this->body, $this->recipients->deepCopy());
        }
    }

    // Base template configured once.
    $base = new EmailTemplate(
        subject: "Monthly Newsletter",
        body: "Here's what happened across the company this month...",
        recipients: new RecipientList(to: ["all-staff@company.com"])
    );

    $hr = $base->clone();
    $hr->subject = "Monthly Newsletter — HR Edition";
    $hr->addRecipient("hr-team@company.com");
    $hr->addCc("hr-lead@company.com");

    $marketing = $base->clone();
    $marketing->subject = "Monthly Newsletter — Marketing Edition";
    $marketing->addRecipient("marketing-team@company.com");

    $engineering = $base->clone();
    $engineering->subject = "Monthly Newsletter — Engineering Edition";
    $engineering->addRecipient("engineering-team@company.com");

    printf("Base:        %s\n", $base->recipientSummary());
    printf("HR:          %s\n", $hr->recipientSummary());
    printf("Marketing:   %s\n", $marketing->recipientSummary());
    printf("Engineering: %s\n", $engineering->recipientSummary());
}
