<?php
/**
 * A2. LIBRARY MANAGEMENT SYSTEM
 * ============================================================
 * PROBLEM: Manage books, members, and book loans in a library.
 *
 * FUNCTIONAL REQUIREMENTS:
 *  - Add/search books (by title, author, ISBN)
 *  - Members borrow and return books
 *  - Track loan history and due dates
 *  - Notify waiting members when a reserved book becomes available
 *
 * PATTERNS: Observer (notify on return), Strategy (search), Repository
 * ============================================================
 */

// ─── Observer Interface ────────────────────────────────────────
interface BookAvailableObserver {
    public function onBookAvailable(BookItem $item): void;
}

// ─── Search Strategy ───────────────────────────────────────────
interface SearchStrategy {
    public function matches(Book $book, string $query): bool;
}
class SearchByTitle  implements SearchStrategy { public function matches(Book $b, string $q): bool { return stripos($b->title, $q) !== false; } }
class SearchByAuthor implements SearchStrategy { public function matches(Book $b, string $q): bool { return stripos($b->author, $q) !== false; } }
class SearchByISBN   implements SearchStrategy { public function matches(Book $b, string $q): bool { return $b->isbn === $q; } }

// ─── Entities ──────────────────────────────────────────────────
class Book {
    public function __construct(
        public readonly string $isbn,
        public readonly string $title,
        public readonly string $author
    ) {}
}

/** A physical copy of a Book that can be loaned */
class BookItem {
    private bool    $available = true;
    /** @var BookAvailableObserver[] */
    private array   $waitList  = [];

    public function __construct(
        public readonly string $barcode,
        public readonly Book   $book
    ) {}

    public function isAvailable(): bool { return $this->available; }

    public function checkout(): void { $this->available = false; }

    public function returnItem(): void {
        $this->available = true;
        // Notify first person on waitlist (Observer pattern)
        if (!empty($this->waitList)) {
            $observer = array_shift($this->waitList);
            $observer->onBookAvailable($this);
        }
    }

    public function addToWaitList(BookAvailableObserver $observer): void {
        $this->waitList[] = $observer;
    }
}

enum LoanStatus: string { case ACTIVE = 'Active'; case RETURNED = 'Returned'; case OVERDUE = 'Overdue'; }

class Loan {
    public readonly string     $loanId;
    public readonly \DateTime  $dueDate;
    public LoanStatus $status = LoanStatus::ACTIVE;
    public ?\DateTime $returnedAt = null;

    public function __construct(
        public readonly Member   $member,
        public readonly BookItem $item,
        int $dueDays = 14
    ) {
        $this->loanId  = uniqid('LOAN-');
        $this->dueDate = (new \DateTime())->modify("+{$dueDays} days");
    }

    public function isOverdue(): bool {
        return $this->status === LoanStatus::ACTIVE && new \DateTime() > $this->dueDate;
    }
}

class Member implements BookAvailableObserver {
    /** @var Loan[] */
    private array $loans = [];

    public function __construct(
        public readonly string $memberId,
        public readonly string $name
    ) {}

    public function addLoan(Loan $loan): void { $this->loans[] = $loan; }
    public function getActiveLoans(): array   { return array_filter($this->loans, fn($l) => $l->status === LoanStatus::ACTIVE); }

    // Observer callback: notify member a reserved book is available
    public function onBookAvailable(BookItem $item): void {
        echo "📧 Notification to {$this->name}: '{$item->book->title}' is now available!\n";
    }
}

// ─── Catalog (Search Service) ──────────────────────────────────
class Catalog {
    /** @var Book[] */
    private array $books = [];

    public function addBook(Book $book): void { $this->books[$book->isbn] = $book; }

    public function search(string $query, SearchStrategy $strategy): array {
        return array_values(array_filter($this->books, fn($b) => $strategy->matches($b, $query)));
    }
}

// ─── Library (Facade) ──────────────────────────────────────────
class Library {
    private Catalog $catalog;
    /** @var BookItem[] barcode → BookItem */
    private array $items   = [];
    /** @var Loan[]    loanId  → Loan     */
    private array $loans   = [];
    /** @var Member[]  memberId → Member  */
    private array $members = [];

    public function __construct() { $this->catalog = new Catalog(); }

    public function addBook(Book $book): void      { $this->catalog->addBook($book); }
    public function addItem(BookItem $item): void  { $this->items[$item->barcode] = $item; }
    public function registerMember(Member $m): void { $this->members[$m->memberId] = $m; }

    public function searchBooks(string $query, SearchStrategy $strategy): array {
        return $this->catalog->search($query, $strategy);
    }

    public function borrowBook(string $memberId, string $barcode): ?Loan {
        $member = $this->members[$memberId] ?? null;
        $item   = $this->items[$barcode]    ?? null;
        if (!$member || !$item) { echo "✗ Invalid member or barcode\n"; return null; }
        if (!$item->isAvailable()) {
            echo "✗ Book not available. Added {$member->name} to waitlist.\n";
            $item->addToWaitList($member);
            return null;
        }
        $item->checkout();
        $loan = new Loan($member, $item);
        $member->addLoan($loan);
        $this->loans[$loan->loanId] = $loan;
        echo "✓ [{$member->name}] borrowed '{$item->book->title}' — due {$loan->dueDate->format('Y-m-d')}\n";
        return $loan;
    }

    public function returnBook(string $loanId): void {
        $loan = $this->loans[$loanId] ?? null;
        if (!$loan) { echo "✗ Loan not found\n"; return; }
        $loan->item->returnItem(); // triggers waitlist notification
        $loan->status     = LoanStatus::RETURNED;
        $loan->returnedAt = new \DateTime();
        echo "✓ '{$loan->item->book->title}' returned by {$loan->member->name}\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A2. Library Management System ===\n\n";

$library = new Library();

$b1 = new Book('978-0132350884', 'Clean Code', 'Robert C. Martin');
$b2 = new Book('978-0201633610', 'Design Patterns', 'Gang of Four');
$library->addBook($b1);
$library->addBook($b2);
$library->addItem(new BookItem('BC-001', $b1));
$library->addItem(new BookItem('BC-002', $b1)); // Second copy
$library->addItem(new BookItem('BC-003', $b2));

$alice = new Member('M001', 'Alice');
$bob   = new Member('M002', 'Bob');
$library->registerMember($alice);
$library->registerMember($bob);

// Search
$results = $library->searchBooks('Clean', new SearchByTitle());
echo "Search 'Clean': " . count($results) . " book(s) found\n";

// Borrow
$loan1 = $library->borrowBook('M001', 'BC-001');
$loan2 = $library->borrowBook('M001', 'BC-002'); // Alice takes both copies
$library->borrowBook('M002', 'BC-001');           // Bob waits → waitlist

// Return (triggers Observer)
if ($loan1) $library->returnBook($loan1->loanId);
