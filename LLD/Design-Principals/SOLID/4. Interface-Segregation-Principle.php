<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║      SOLID PRINCIPLE #4 — INTERFACE SEGREGATION PRINCIPLE (ISP) ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  ACRONYM    : I in SOLID                                         ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★☆ (Often tested with LSP and DIP)             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DEFINITION                                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ "A client should never be forced to implement an interface that  │
 * │  it doesn't use. No client should depend on methods it does NOT  │
 * │  need."                                                          │
 * │                                                                  │
 * │ The solution: replace one FAT interface with many SMALL,         │
 * │ cohesive, role-based interfaces.                                 │
 * │                                                                  │
 * │ ANALOGY: A TV remote has 40 buttons. You use 5 daily. The remote │
 * │ is fine. But if every app HAD TO implement all 40 buttons just   │
 * │ to control the volume — that's an ISP violation.                 │
 * │                                                                  │
 * │ RELATIONSHIP TO OTHER PRINCIPLES:                                │
 * │  • ISP prevents LSP violations (subclass won't throw on unused  │
 * │    methods it was forced to implement)                           │
 * │  • ISP enables DIP (lean interfaces are easier to inject)        │
 * │  • ISP is SRP applied to interfaces                              │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ❌ FAT interface — all implementers carry unused methods:       │
 * │  ┌──────────────────────────────────────────┐                   │
 * │  │  WorkerInterface                          │                   │
 * │  │  + work()   + eat()  + sleep()            │                   │
 * │  └──────────────────────────────────────────┘                   │
 * │       ▲                       ▲                                  │
 * │  HumanWorker              RobotWorker                            │
 * │  + work() ✓               + work() ✓                            │
 * │  + eat()  ✓               + eat()  ✗ (robots don't eat!)        │
 * │  + sleep() ✓              + sleep() ✗ (robots don't sleep!)     │
 * │                                                                  │
 * │  ✅ Segregated interfaces:                                       │
 * │  Workable  Eatable  Sleepable                                     │
 * │  + work()  + eat()  + sleep()                                    │
 * │  HumanWorker: implements all 3 ✓                                  │
 * │  RobotWorker: implements only Workable ✓                         │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO APPLY ISP                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Identify classes forced to implement methods they can't  │
 * │         support (stub/throw/empty implementation = red flag)     │
 * │ STEP 2: Group interface methods by the client that uses them     │
 * │ STEP 3: Split into small, role-specific interfaces               │
 * │ STEP 4: Classes implement only the interfaces they need          │
 * │ STEP 5: Callers depend on the smallest interface that serves them│
 * └─────────────────────────────────────────────────────────────────┘
 */

echo "=== INTERFACE SEGREGATION PRINCIPLE ===\n\n";

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION: Fat interface forces Robot to implement eat/sleep
// ═══════════════════════════════════════════════════════════════
interface BadWorkerInterface
{
    public function work(): void;
    public function eat(): void;   // Not all workers eat
    public function sleep(): void; // Not all workers sleep
    public function attendMeeting(): void;
    public function submitTimesheet(): void;
}

class HumanWorker implements BadWorkerInterface
{
    public function work(): void           { echo "  [Human] Working on tasks\n"; }
    public function eat(): void            { echo "  [Human] Eating lunch\n"; }
    public function sleep(): void          { echo "  [Human] Sleeping 8 hours\n"; }
    public function attendMeeting(): void  { echo "  [Human] In standup meeting\n"; }
    public function submitTimesheet(): void { echo "  [Human] Submitted timesheet\n"; }
}

class RobotWorker implements BadWorkerInterface
{
    public function work(): void { echo "  [Robot] Processing tasks 24/7\n"; }

    // ❌ ISP violated: robot FORCED to implement methods it can't use
    public function eat(): void  { /* Robots don't eat — empty stub */ }
    public function sleep(): void { /* Robots don't sleep — empty stub */ }
    public function attendMeeting(): void  { /* Robots don't attend meetings */ }
    public function submitTimesheet(): void { /* Robots don't submit timesheets */ }
}

echo "--- ❌ ISP Violation: Fat WorkerInterface ---\n";
$robot = new RobotWorker();
$robot->work();
$robot->eat();   // Silently does nothing — caller has no way to know it's a stub
$robot->sleep(); // Same — hidden no-op, dangerous in production

// ═══════════════════════════════════════════════════════════════
// ✅ CORRECT: Split into small role-based interfaces
// ═══════════════════════════════════════════════════════════════

// Segregated, role-specific interfaces
interface Workable
{
    public function work(): void;
}

interface Eatable
{
    public function eat(): void;
    public function takeLunchBreak(): void;
}

interface Sleepable
{
    public function sleep(int $hours): void;
}

interface MeetingAttendable
{
    public function attendMeeting(string $meetingName): void;
}

interface TimesheetSubmittable
{
    public function submitTimesheet(int $hoursWorked): void;
}

// Human implements ALL roles it actually performs
class GoodHumanWorker implements Workable, Eatable, Sleepable, MeetingAttendable, TimesheetSubmittable
{
    public function __construct(private string $name) {}

    public function work(): void               { echo "  [{$this->name}] Working on tasks\n"; }
    public function eat(): void                { echo "  [{$this->name}] Eating lunch\n"; }
    public function takeLunchBreak(): void     { echo "  [{$this->name}] On lunch break\n"; }
    public function sleep(int $hours): void    { echo "  [{$this->name}] Sleeping {$hours} hours\n"; }
    public function attendMeeting(string $m): void { echo "  [{$this->name}] In: $m\n"; }
    public function submitTimesheet(int $h): void  { echo "  [{$this->name}] Submitted: {$h}h\n"; }
}

// Robot implements ONLY what it can actually do
class GoodRobotWorker implements Workable
{
    public function __construct(private string $robotId) {}

    public function work(): void { echo "  [Robot {$this->robotId}] Processing tasks 24/7/365\n"; }
    // No eat, sleep, meeting, timesheet — honest about its nature
}

// Contractor: works, eats, attends meetings — but no timesheets!
class ContractorWorker implements Workable, Eatable, MeetingAttendable
{
    public function __construct(private string $name) {}

    public function work(): void                   { echo "  [{$this->name}] Working as contractor\n"; }
    public function eat(): void                    { echo "  [{$this->name}] Eating at cafe\n"; }
    public function takeLunchBreak(): void         { echo "  [{$this->name}] Flexible lunch break\n"; }
    public function attendMeeting(string $m): void { echo "  [{$this->name}] Attending: $m\n"; }
}

// Client code depends on the SMALLEST interface it needs
function processWorkload(Workable $worker): void
{
    // Only needs Workable — works for Human, Robot, AND Contractor
    $worker->work();
}

function runDailySchedule(Workable&Eatable&MeetingAttendable $worker): void
{
    // Uses intersection type — only for workers with all three
    $worker->attendMeeting("Daily Standup");
    $worker->work();
    $worker->takeLunchBreak();
}

echo "\n--- ✅ ISP Compliant ---\n";

$alice    = new GoodHumanWorker("Alice");
$robot    = new GoodRobotWorker("R2D2");
$freelancer = new ContractorWorker("Bob");

echo "\n  processWorkload() — works for all:\n";
processWorkload($alice);
processWorkload($robot);
processWorkload($freelancer);

echo "\n  runDailySchedule() — only for Workable+Eatable+MeetingAttendable:\n";
runDailySchedule($alice);
runDailySchedule($freelancer);
// runDailySchedule($robot); // Type error — Robot is not Eatable ← caught at compile time!

// ─── REAL-WORLD EXAMPLE: Document Management System ──────────────────────────

echo "\n--- Real-World: Document Management (ISP) ---\n";

// ❌ FAT interface: ReadOnlyUser forced to implement write/delete
interface BadDocumentManager
{
    public function read(int $id): string;
    public function write(int $id, string $content): void;
    public function delete(int $id): void;
    public function share(int $id, string $email): void;
    public function print(int $id): void;
    public function export(int $id, string $format): string;
}

// ✅ Segregated by capability/role
interface Readable
{
    public function read(int $id): string;
}

interface Writable
{
    public function write(int $id, string $content): void;
}

interface Deletable
{
    public function delete(int $id): void;
}

interface Shareable
{
    public function share(int $id, string $email): void;
}

interface Exportable
{
    public function export(int $id, string $format): string;
}

// Admin: full access — implements all relevant interfaces
class AdminDocumentService implements Readable, Writable, Deletable, Shareable, Exportable
{
    private array $docs = [1 => 'Q1 Report', 2 => 'Budget'];

    public function read(int $id): string         { return $this->docs[$id] ?? 'Not found'; }
    public function write(int $id, string $c): void { $this->docs[$id] = $c; echo "  [Admin] Written doc $id\n"; }
    public function delete(int $id): void           { unset($this->docs[$id]); echo "  [Admin] Deleted doc $id\n"; }
    public function share(int $id, string $e): void { echo "  [Admin] Shared doc $id with $e\n"; }
    public function export(int $id, string $f): string { return "  [Admin] Exported doc $id as $f"; }
}

// Viewer: read + export only — not forced to implement write/delete
class ViewerDocumentService implements Readable, Exportable
{
    private array $docs = [1 => 'Q1 Report', 2 => 'Budget'];

    public function read(int $id): string { return $this->docs[$id] ?? 'Not found'; }
    public function export(int $id, string $f): string { return "  [Viewer] Exported doc $id as $f"; }
}

// Client depends only on what it actually needs
function displayDocument(Readable $service, int $id): void
{
    echo "  Document: " . $service->read($id) . "\n";
}

$admin  = new AdminDocumentService();
$viewer = new ViewerDocumentService();

displayDocument($admin, 1);  // Works ✓
displayDocument($viewer, 2); // Works ✓ — Viewer is Readable

$admin->delete(2);
$admin->share(1, "team@company.com");
echo $viewer->export(1, 'PDF') . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Define ISP.                                                  │
 * │ A: No client should be forced to depend on methods it does not  │
 * │    use. Instead of one fat interface, use many small, focused   │
 * │    interfaces tailored to specific clients/roles.               │
 * │                                                                  │
 * │ Q2: How do you identify an ISP violation?                        │
 * │ A: • A class implements an interface but leaves some methods    │
 * │      empty, throws UnsupportedOperationException, or returns null│
 * │    • Clients import/use only part of an interface               │
 * │    • Interface has methods that serve completely different       │
 * │      client types                                                │
 * │                                                                  │
 * │ Q3: What's the difference between ISP and SRP?                  │
 * │ A: SRP is about classes having one responsibility.              │
 * │    ISP is about interfaces being lean — clients shouldn't be    │
 * │    forced to depend on what they don't use. ISP is essentially  │
 * │    SRP applied to interfaces.                                   │
 * │                                                                  │
 * │ Q4: Can implementing multiple interfaces cause issues?          │
 * │ A: Multiple small interfaces are fine in PHP. The concern is   │
 * │    method name conflicts (two interfaces declare same method    │
 * │    with different signatures) — PHP doesn't allow this.         │
 * │    Use careful naming to avoid collisions.                      │
 * │                                                                  │
 * │ Q5: How does ISP help with testing?                             │
 * │ A: Small interfaces are easier to mock. If a function depends  │
 * │    only on Readable, your test mock only needs to implement     │
 * │    read() — not the entire DocumentManager interface.           │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ PHP 8.1 intersection types (A&B) let you require multiple    │
 * │   small interfaces simultaneously — best of both worlds.        │
 * │ ✓ Don't go too granular: one method per interface is overkill.  │
 * │   Group by cohesive use case (all read operations, all write).  │
 * └─────────────────────────────────────────────────────────────────┘
 */
