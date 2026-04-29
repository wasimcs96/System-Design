<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║      OOP CONCEPT #21 — LLD INTERVIEW CHEATSHEET                  ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Masterclass (Interview Prep)                        ║
 * ║  FREQUENCY : ★★★★★  (USE THIS FILE THE NIGHT BEFORE INTERVIEW)  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 1 — LLD INTERVIEW METHODOLOGY
 *  "How to approach a Low-Level Design problem in 45 minutes"
 * ═══════════════════════════════════════════════════════════════════
 *
 * STEP 1 — Clarify Requirements (5 min)
 *   Ask: "Who are the actors?" (user, admin, system)
 *   Ask: "What are the core use cases?" (top 3-5 only)
 *   Ask: "What scale? Is this single-machine or distributed?"
 *   Ask: "Any specific constraints I should know about?"
 *   → Narrow scope: don't build everything, build what matters
 *
 * STEP 2 — Identify Core Entities (5 min)
 *   Underline nouns in the requirements → these become classes
 *   Parking Lot: ParkingLot, Floor, Slot, Vehicle, Ticket, Payment
 *   E-Commerce: User, Product, Cart, Order, Payment, Address
 *   ATM: ATM, Account, Card, Transaction, CashDispenser, Receipt
 *
 * STEP 3 — Define Relationships (5 min)
 *   Which entities OWN others? (Composition)
 *   Which entities USE others? (Association/Aggregation)
 *   Which entities SHARE behavior? (Interface/Abstract)
 *   Draw on whiteboard: [ParkingLot] 1──* [Floor] 1──* [Slot]
 *
 * STEP 4 — Identify Behaviors (5 min)
 *   Verb phrases from requirements → methods
 *   "User adds item to cart" → Cart::addItem(Product, int qty)
 *   "System generates ticket" → TicketService::generate(Vehicle)
 *   "Admin marks slot available" → Slot::release()
 *
 * STEP 5 — Apply OOP Principles (5 min before coding)
 *   SRP: One class, one job
 *   OCP: New behavior via new class, not editing existing
 *   LSP: Subtypes must work wherever parent is used
 *   ISP: Interface per capability, not one fat interface
 *   DIP: Depend on interfaces, inject via constructor
 *
 * STEP 6 — Code Core Classes (20 min)
 *   Start with ENTITIES first (User, Order, Product)
 *   Then REPOSITORIES (UserRepository interface + InMemory impl)
 *   Then SERVICES (business logic orchestration)
 *   Then CONTROLLERS (handle user actions, call services)
 *   Show ENUM for states (OrderStatus::PENDING, PLACED, SHIPPED)
 *   Show VALUE OBJECTS for critical fields (Money, Email)
 *
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 2 — COMMON LLD PROBLEMS + CLASS DESIGNS
 * ═══════════════════════════════════════════════════════════════════
 */

// ── PARKING LOT ────────────────────────────────────────────────

echo "=== LLD INTERVIEW CHEATSHEET ===\n\n";
echo "--- LLD #1: Parking Lot (Key classes) ---\n";

enum VehicleType { case MOTORCYCLE; case CAR; case TRUCK; }
enum SlotStatus  { case AVAILABLE; case OCCUPIED; }

class ParkingVehicle
{
    public function __construct(
        public readonly string      $licensePlate,
        public readonly VehicleType $type
    ) {}
}

class ParkingSlot
{
    private SlotStatus      $status = SlotStatus::AVAILABLE;
    private ?ParkingVehicle $vehicle = null;

    public function __construct(
        public readonly string      $slotId,
        public readonly VehicleType $type,      // what vehicle type fits here
        public readonly int         $floor
    ) {}

    public function assign(ParkingVehicle $v): bool
    {
        if ($this->status !== SlotStatus::AVAILABLE) return false;
        if ($v->type !== $this->type) return false;   // simplified type check
        $this->vehicle = $v;
        $this->status  = SlotStatus::OCCUPIED;
        return true;
    }

    public function release(): void
    {
        $this->vehicle = null;
        $this->status  = SlotStatus::AVAILABLE;
    }

    public function isAvailable(): bool         { return $this->status === SlotStatus::AVAILABLE; }
    public function getVehicle(): ?ParkingVehicle { return $this->vehicle; }
    public function getSlotId(): string          { return $this->slotId; }
}

class ParkingLot
{
    private array $slots = [];
    private array $tickets = [];

    public function addSlot(ParkingSlot $slot): void { $this->slots[] = $slot; }

    public function park(ParkingVehicle $vehicle): ?string
    {
        foreach ($this->slots as $slot) {
            if ($slot->isAvailable() && $slot->assign($vehicle)) {
                $ticketId = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $this->tickets[$ticketId] = ['slot' => $slot, 'time' => time()];
                echo "  Parked {$vehicle->licensePlate} → Slot {$slot->getSlotId()} | Ticket: {$ticketId}\n";
                return $ticketId;
            }
        }
        echo "  No available slot for {$vehicle->type->name}\n";
        return null;
    }

    public function unpark(string $ticketId): float
    {
        if (!isset($this->tickets[$ticketId])) throw new \RuntimeException("Invalid ticket");
        $entry    = $this->tickets[$ticketId];
        $slot     = $entry['slot'];
        $hours    = max(1, ceil((time() - $entry['time']) / 3600));
        $fee      = $hours * 50;   // ₹50/hour simplified
        $slot->release();
        unset($this->tickets[$ticketId]);
        echo "  Unparked {$ticketId} | {$hours}h | Fee: ₹{$fee}\n";
        return $fee;
    }
}

$lot = new ParkingLot();
$lot->addSlot(new ParkingSlot('F1-S1', VehicleType::CAR, 1));
$lot->addSlot(new ParkingSlot('F1-S2', VehicleType::CAR, 1));
$lot->addSlot(new ParkingSlot('F1-M1', VehicleType::MOTORCYCLE, 1));

$t1 = $lot->park(new ParkingVehicle('MH-01-AA-1234', VehicleType::CAR));
$t2 = $lot->park(new ParkingVehicle('MH-02-BB-5678', VehicleType::MOTORCYCLE));
if ($t1) $lot->unpark($t1);

// ── LIBRARY MANAGEMENT ────────────────────────────────────────

echo "\n--- LLD #2: Library Management (Key classes) ---\n";

enum BookStatus { case AVAILABLE; case CHECKED_OUT; case RESERVED; }

class Book
{
    private BookStatus $status = BookStatus::AVAILABLE;
    private ?string    $borrowedBy = null;

    public function __construct(
        public readonly string $isbn,
        public readonly string $title,
        public readonly string $author
    ) {}

    public function checkout(string $memberId): void
    {
        if ($this->status !== BookStatus::AVAILABLE) throw new \RuntimeException("Not available");
        $this->status     = BookStatus::CHECKED_OUT;
        $this->borrowedBy = $memberId;
        echo "  '{$this->title}' checked out by member #{$memberId}\n";
    }

    public function returnBook(): void
    {
        $this->status     = BookStatus::AVAILABLE;
        $this->borrowedBy = null;
        echo "  '{$this->title}' returned\n";
    }

    public function isAvailable(): bool  { return $this->status === BookStatus::AVAILABLE; }
    public function getTitle(): string   { return $this->title; }
}

class LibraryCatalog
{
    private array $books = [];

    public function addBook(Book $book): void { $this->books[$book->isbn] = $book; }
    public function findByIsbn(string $isbn): ?Book { return $this->books[$isbn] ?? null; }

    public function searchByTitle(string $query): array
    {
        return array_filter($this->books, fn(Book $b) => str_contains(strtolower($b->getTitle()), strtolower($query)));
    }

    public function availableCount(): int
    {
        return count(array_filter($this->books, fn(Book $b) => $b->isAvailable()));
    }
}

$catalog = new LibraryCatalog();
$catalog->addBook(new Book('978-1', 'Clean Code', 'Robert C. Martin'));
$catalog->addBook(new Book('978-2', 'Design Patterns', 'Gang of Four'));
$catalog->addBook(new Book('978-3', 'DDD', 'Eric Evans'));

$book = $catalog->findByIsbn('978-1');
if ($book) {
    $book->checkout('MEM-001');
    echo "  Available: {$catalog->availableCount()}/3\n";
    $book->returnBook();
    echo "  Available: {$catalog->availableCount()}/3\n";
}

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 3 — OOP CONCEPTS → DESIGN PATTERNS MAPPING
 * ═══════════════════════════════════════════════════════════════════
 *
 * OOP CONCEPT           → DESIGN PATTERNS THAT USE IT
 * ─────────────────────────────────────────────────────────────────
 * Abstraction           → Factory, Abstract Factory, Strategy, Bridge
 * Polymorphism          → Strategy, State, Command, Visitor, Template Method
 * Inheritance           → Template Method, Decorator (base), Composite
 * Composition           → Decorator, Composite, Bridge, Proxy
 * Interface             → Strategy, Observer, Command, Iterator
 * Encapsulation         → Builder (hides construction), Facade (hides complexity)
 * Single Responsibility → Builder (creation ≠ use), Strategy (algorithm ≠ context)
 * Open/Closed           → Observer, Strategy, Decorator, Chain of Responsibility
 * Dependency Inversion  → Factory, DI Container, Abstract Factory
 * Coupling/Cohesion     → Facade (reduce coupling), Mediator (decouple objects)
 *
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 4 — INTERVIEW SCORING RUBRIC
 * ═══════════════════════════════════════════════════════════════════
 *
 * WHAT INTERVIEWERS LOOK FOR (in order of weight):
 *
 * ★★★★★ Class design clarity (5 pts)
 *   - Can you identify the right entities and responsibilities?
 *   - No god classes. Each class has ONE primary job.
 *
 * ★★★★★ Extensibility (5 pts)
 *   - If requirement changes, how much code changes?
 *   - NEW behavior should require NEW class, not editing existing.
 *
 * ★★★★☆ Correct use of OOP (4 pts)
 *   - Appropriate use of abstract vs interface
 *   - Composition preferred over inheritance where applicable
 *   - LSP-safe hierarchies
 *
 * ★★★★☆ Naming and communication (4 pts)
 *   - Class/method names that reveal intent
 *   - Thinking aloud while designing
 *
 * ★★★☆☆ SOLID awareness (3 pts)
 *   - Can you NAME the principle when you apply it?
 *   - "I'm separating this into 2 classes because of SRP"
 *
 * ★★☆☆☆ Edge cases (2 pts)
 *   - No available parking slot? Cart already checked out?
 *   - Guard clauses, proper exceptions
 *
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 5 — RED FLAGS IN CODE REVIEWS
 * ═══════════════════════════════════════════════════════════════════
 *
 * 🚨 God Class — UserManager with 20+ methods (register, login, email, report...)
 * 🚨 new inside business logic — breaks DIP and testability
 * 🚨 switch/if on type — use polymorphism instead
 * 🚨 Public fields — breaks encapsulation; use getters/setters
 * 🚨 Shotgun surgery — one change requires edits in 10 places
 * 🚨 Feature envy — class A uses 5 methods from class B (move them to B)
 * 🚨 Data clumps — same 3 fields always appear together (make a VO/class)
 * 🚨 Primitive obsession — using string for Email, int for Money
 * 🚨 Long parameter lists — >4 params → use DTO/Builder
 * 🚨 Concrete class dependencies — depend on interfaces
 *
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 6 — 30-SECOND ELEVATOR PITCHES FOR EACH CONCEPT
 * ═══════════════════════════════════════════════════════════════════
 *
 * ENCAPSULATION:
 *   "Hide internal state, expose only what's needed. BankAccount hides
 *   balance as private — only deposit() and withdraw() can change it.
 *   Prevents invalid state from outside."
 *
 * ABSTRACTION:
 *   "Show what an object does, hide how it does it. PaymentGateway
 *   interface declares charge() — you don't care if it's Stripe or
 *   Razorpay underneath. Program to the contract."
 *
 * INHERITANCE:
 *   "Reuse behavior via parent-child. Vehicle defines move(); Car and
 *   Bike inherit it. But prefer composition over inheritance when the
 *   relationship isn't truly 'is-a'."
 *
 * POLYMORPHISM:
 *   "Same interface, different behavior. shape->draw() calls Circle::draw
 *   or Square::draw depending on type at runtime. Eliminates if/switch
 *   on type checks."
 *
 * SOLID:
 *   "5 principles: SRP=one job, OCP=open for extension closed for
 *   modification, LSP=subtypes are substitutable, ISP=small interfaces,
 *   DIP=depend on abstractions. Together they make code maintainable."
 *
 * DESIGN PATTERNS:
 *   "Reusable solutions to common design problems. Strategy pattern:
 *   swap algorithm at runtime. Observer: event-driven updates. Factory:
 *   decouple object creation from use."
 *
 * DI / IoC:
 *   "Instead of creating dependencies inside a class, inject them via
 *   constructor. Enables mocking for tests and swapping implementations
 *   without touching business logic. Laravel's container automates this."
 *
 * ═══════════════════════════════════════════════════════════════════
 *  SECTION 7 — DAY-OF-INTERVIEW TIPS
 * ═══════════════════════════════════════════════════════════════════
 *
 * BEFORE YOU CODE:
 *   ✓ Spend 10 min on design — interviewers WANT to see thinking
 *   ✓ Confirm scope: "I'll focus on X, Y, Z. Should I include billing?"
 *   ✓ Draw a quick class diagram on paper/whiteboard
 *   ✓ Name your entities FIRST before writing any code
 *
 * WHILE CODING:
 *   ✓ Think aloud: "I'm making this interface for OCP"
 *   ✓ Start with core entities, not utilities
 *   ✓ Use enums for states (PHP 8.1): OrderStatus::PLACED
 *   ✓ Value objects for domain concepts: Money, Email, PhoneNumber
 *   ✓ Inject dependencies via constructor — don't use new in services
 *   ✓ Show at least ONE design pattern
 *
 * COMMON MISTAKES TO AVOID:
 *   ✗ Don't jump to code without clarifying
 *   ✗ Don't build everything — focus on core
 *   ✗ Don't use arrays for everything — model with classes
 *   ✗ Don't put all logic in controller — have service layer
 *   ✗ Don't use generic names: Manager, Handler, Util
 *
 * PHRASES THAT IMPRESS:
 *   "I'm applying the Strategy pattern here to make this swappable"
 *   "This violates SRP — let me split it"
 *   "I'll use an interface here so we can inject a mock in tests"
 *   "This Money class is a Value Object — immutable and equality by value"
 *   "The Repository pattern abstracts the persistence concern"
 *
 * LLD PROBLEMS BY COMPANY (common):
 *   Flipkart/Amazon : E-commerce cart, Order management, Inventory
 *   Swiggy/Zomato   : Restaurant + delivery + order tracking
 *   Ola/Uber        : Ride booking, driver-rider matching, pricing
 *   Hotstar/Netflix : Content catalog, subscription, streaming
 *   Google          : Calendar, Meeting Room booking
 *   Banking         : ATM, Loan management, Account transfer
 */

echo "\n--- Section 7: LLD Problem Entities Quick Reference ---\n";

$problems = [
    'Parking Lot'    => 'ParkingLot, Floor, ParkingSlot, Vehicle, Ticket, FeeStrategy',
    'Library'        => 'Book, Member, Catalog, Loan, Fine, Reservation',
    'ATM'            => 'ATM, Card, Account, Transaction, CashDispenser, KeyPad',
    'E-Commerce'     => 'User, Product, Cart, Order, Payment, Address, Inventory',
    'Ride Booking'   => 'Rider, Driver, Ride, Location, PricingStrategy, Notification',
    'Hotel Booking'  => 'Hotel, Room, Guest, Booking, Payment, RoomType',
    'Food Delivery'  => 'Restaurant, Menu, Item, Order, DeliveryAgent, Cart',
];

foreach ($problems as $problem => $entities) {
    echo "  {$problem}:\n    → {$entities}\n";
}

echo "\n=== GOOD LUCK IN YOUR INTERVIEW! ===\n";
echo "Remember: Design first. Think aloud. Apply patterns. Stay calm.\n";
