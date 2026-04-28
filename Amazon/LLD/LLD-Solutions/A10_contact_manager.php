<?php
/**
 * A10. CONTACT MANAGER
 * ============================================================
 * PROBLEM: In-memory CRUD for contacts with search and filtering.
 *
 * PATTERNS:
 *  - Repository : ContactRepository abstracts storage
 *  - Builder    : ContactBuilder for optional fields
 * ============================================================
 */

// ─── Value Objects ─────────────────────────────────────────────
class PhoneNumber {
    public function __construct(
        public readonly string $number,
        public readonly string $label = 'Mobile'  // Mobile, Home, Work
    ) {}
}

class EmailAddress {
    public function __construct(
        public readonly string $address,
        public readonly string $label = 'Personal'
    ) {}
}

// ─── Contact Entity ─────────────────────────────────────────────
class Contact {
    public readonly string   $contactId;
    /** @var PhoneNumber[] */
    private array $phones = [];
    /** @var EmailAddress[] */
    private array $emails = [];

    public function __construct(
        public string  $firstName,
        public string  $lastName,
        public ?string $company  = null,
        public ?string $notes    = null
    ) {
        $this->contactId = uniqid('C-');
    }

    public function addPhone(PhoneNumber $p): void  { $this->phones[] = $p; }
    public function addEmail(EmailAddress $e): void { $this->emails[] = $e; }
    public function getPhones(): array  { return $this->phones; }
    public function getEmails(): array  { return $this->emails; }
    public function getFullName(): string { return trim("{$this->firstName} {$this->lastName}"); }

    public function __toString(): string {
        $phones = implode(', ', array_map(fn($p) => $p->number, $this->phones));
        $emails = implode(', ', array_map(fn($e) => $e->address, $this->emails));
        return "[{$this->contactId}] {$this->getFullName()} | {$phones} | {$emails}";
    }
}

// ─── Builder ───────────────────────────────────────────────────
class ContactBuilder {
    private string  $firstName = '';
    private string  $lastName  = '';
    private ?string $company   = null;
    private ?string $notes     = null;
    private array   $phones    = [];
    private array   $emails    = [];

    public function firstName(string $v): self   { $this->firstName = $v; return $this; }
    public function lastName(string $v): self    { $this->lastName  = $v; return $this; }
    public function company(string $v): self     { $this->company   = $v; return $this; }
    public function notes(string $v): self       { $this->notes     = $v; return $this; }
    public function phone(string $number, string $label = 'Mobile'): self {
        $this->phones[] = new PhoneNumber($number, $label); return $this;
    }
    public function email(string $address, string $label = 'Personal'): self {
        $this->emails[] = new EmailAddress($address, $label); return $this;
    }

    public function build(): Contact {
        if (empty($this->firstName)) throw new \InvalidArgumentException('First name required');
        $c = new Contact($this->firstName, $this->lastName, $this->company, $this->notes);
        foreach ($this->phones as $p) $c->addPhone($p);
        foreach ($this->emails as $e) $c->addEmail($e);
        return $c;
    }
}

// ─── Search Filter ─────────────────────────────────────────────
class SearchFilter {
    private ?string $name    = null;
    private ?string $phone   = null;
    private ?string $email   = null;
    private ?string $company = null;

    public function byName(string $v): self    { $this->name    = $v; return $this; }
    public function byPhone(string $v): self   { $this->phone   = $v; return $this; }
    public function byEmail(string $v): self   { $this->email   = $v; return $this; }
    public function byCompany(string $v): self { $this->company = $v; return $this; }

    public function matches(Contact $c): bool {
        if ($this->name && stripos($c->getFullName(), $this->name) === false) return false;
        if ($this->company && stripos($c->company ?? '', $this->company) === false) return false;
        if ($this->phone) {
            $found = false;
            foreach ($c->getPhones() as $p) if (stripos($p->number, $this->phone) !== false) { $found = true; break; }
            if (!$found) return false;
        }
        if ($this->email) {
            $found = false;
            foreach ($c->getEmails() as $e) if (stripos($e->address, $this->email) !== false) { $found = true; break; }
            if (!$found) return false;
        }
        return true;
    }
}

// ─── Repository ─────────────────────────────────────────────────
class ContactRepository {
    /** @var Contact[] contactId → Contact */
    private array $store = [];

    public function save(Contact $c): void {
        $this->store[$c->contactId] = $c;
        echo "✓ Saved: {$c->getFullName()} [{$c->contactId}]\n";
    }

    public function findById(string $id): ?Contact { return $this->store[$id] ?? null; }

    public function search(SearchFilter $filter): array {
        return array_values(array_filter($this->store, fn($c) => $filter->matches($c)));
    }

    public function delete(string $id): bool {
        if (!isset($this->store[$id])) return false;
        unset($this->store[$id]);
        return true;
    }

    public function findAll(): array { return array_values($this->store); }
    public function count(): int     { return count($this->store); }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A10. Contact Manager ===\n\n";

$repo = new ContactRepository();

$c1 = (new ContactBuilder())
    ->firstName('Alice')->lastName('Smith')
    ->company('Amazon')->notes('Senior SDE')
    ->phone('+91-9000000001')->phone('+91-9000000002', 'Work')
    ->email('alice@amazon.com', 'Work')->email('alice@gmail.com')
    ->build();

$c2 = (new ContactBuilder())
    ->firstName('Bob')->lastName('Jones')
    ->phone('+91-9111111111')
    ->email('bob@flipkart.com', 'Work')
    ->build();

$repo->save($c1);
$repo->save($c2);

echo "\n--- Search by name ---\n";
$results = $repo->search((new SearchFilter())->byName('alice'));
foreach ($results as $c) echo "  " . $c . "\n";

echo "\n--- Search by company ---\n";
$results = $repo->search((new SearchFilter())->byCompany('amazon'));
foreach ($results as $c) echo "  Found: {$c->getFullName()}\n";

echo "\n--- Update (edit in place) ---\n";
$c1->company = 'Google'; // Direct update
$repo->save($c1);

echo "\n--- Delete ---\n";
$repo->delete($c2->contactId);
echo "Total contacts: {$repo->count()}\n";
