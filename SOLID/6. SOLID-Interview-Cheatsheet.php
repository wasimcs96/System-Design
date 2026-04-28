<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║           SOLID PRINCIPLES — COMPLETE INTERVIEW CHEATSHEET              ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  PURPOSE  : Interview Preparation — Tips, Tricks, Common Traps          ║
 * ║  LEVEL    : Mid → Senior Engineer / System Design Round                 ║
 * ║  COVERS   : All 5 SOLID principles + cross-cutting tips                 ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * INDEX
 * ─────────────────────────────────────────────────────────────────────────
 *  SECTION 1 : One-liner recall for all 5 principles
 *  SECTION 2 : Classic "trick questions" interviewers love to ask
 *  SECTION 3 : How to SPOT violations — red flags per principle
 *  SECTION 4 : How principles RELATE to each other
 *  SECTION 5 : How SOLID maps to Design Patterns
 *  SECTION 6 : SOLID violations in real PHP code — spot + fix
 *  SECTION 7 : Interview answer frameworks (how to structure answers)
 *  SECTION 8 : Senior-level discussion topics
 *  SECTION 9 : Quick comparison tables
 *  SECTION 10: Live coding demo — refactor a bad class step-by-step
 * ─────────────────────────────────────────────────────────────────────────
 */

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1: ONE-LINER RECALL — Say this in an interview in under 10 seconds
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ┌───────┬────────────────────────────────────────────────────────────────┐
 * │ S     │ One class = one job = one reason to change                     │
 * │ O     │ Add new behavior by adding new code, not editing old code       │
 * │ L     │ A subclass must be usable wherever its parent is used           │
 * │ I     │ Never force a class to implement methods it doesn't need        │
 * │ D     │ Depend on interfaces, not concrete implementations              │
 * └───────┴────────────────────────────────────────────────────────────────┘
 *
 * MEMORY TRICK: Think of a well-run company.
 *   S → Each department (class) has one job.
 *   O → Hire new staff to add capability; don't retrain existing staff.
 *   L → Any manager (subclass) can fill in for the role (superclass).
 *   I → Don't send irrelevant emails to staff about tasks they never do.
 *   D → A CEO depends on "CFO role" not on "John specifically". Swap John.
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2: CLASSIC TRICK QUESTIONS — Interviewers love these
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q1: "Is a Square a Rectangle?" (LSP)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * WRONG answer: "Yes, mathematically a square IS-A rectangle."
 *
 * CORRECT answer:
 *   "Mathematically yes. But in OOP, IS-A means behavioral substitutability.
 *    Rectangle has an invariant: you can set width and height independently.
 *    Square breaks this — setting width also changes height.
 *    Any code relying on Rectangle's invariant produces wrong results with Square.
 *    So in OOP, Square should NOT extend Rectangle. Both implement Shape interface."
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q2: "What's the difference between SRP and separation of concerns?"
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * CORRECT answer:
 *   "Separation of Concerns (SoC) is a broader architectural principle:
 *    different parts of a system address different problems. SRP is SoC
 *    applied at the CLASS level: each class addresses ONE concern.
 *    SoC is the 'what' (separate concerns). SRP is the 'how' (one class,
 *    one responsibility, one reason to change)."
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q3: "DIP says depend on abstractions. But `new` creates concrete
 *            classes. Does `new` violate DIP?" (DIP)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * CORRECT answer:
 *   "Using `new` is fine in FACTORIES and BOOTSTRAP/WIRING code (DI containers,
 *    service providers). The violation happens when you use `new ConcreteClass()`
 *    INSIDE business logic — because then the high-level module is tightly
 *    coupled to the implementation detail. The rule is: push `new` to the
 *    edges of your application (composition root), not into the domain."
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q4: "Can you over-apply SOLID?" (Judgment)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * CORRECT answer:
 *   "Absolutely. Over-applying SRP creates class explosion — dozens of tiny
 *    classes for trivial tasks. Over-applying OCP adds unnecessary abstractions
 *    before you know what varies. Over-applying DIP creates 'interface soup' —
 *    a 1:1 interface for every class, adding no real value.
 *
 *    The rule of thumb: Apply SOLID at stable architectural boundaries.
 *    Use YAGNI (You Ain't Gonna Need It) for internal implementation details.
 *    The goal is MAINTAINABLE code, not SOLID-compliant code for its own sake."
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q5: "ISP says split interfaces. Doesn't that lead to too many
 *            tiny interfaces?" (ISP)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * CORRECT answer:
 *   "Yes, that's the other extreme. The guide is CLIENT-DRIVEN segregation:
 *    group methods that are ALWAYS used together by the same caller into one
 *    interface. One method per interface is almost always overkill.
 *    PHP 8.1 intersection types (Readable & Writable) let you combine small
 *    interfaces when needed, giving you both cohesion and flexibility."
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * TRICK Q6: "DIP vs DI vs IoC — are they the same thing?" (DIP)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * CORRECT answer (interviewers love when you nail this):
 *
 *   TERM                  TYPE         ONE LINE
 *   ─────────────────     ─────────    ────────────────────────────────
 *   Dependency Inversion  PRINCIPLE    Depend on abstractions, not concretions
 *   Dependency Injection  PATTERN      Pass dependencies in from outside
 *   Inversion of Control  CONCEPT      Framework calls your code (not vice versa)
 *   IoC Container         TOOL/FRAMEWORK Automates DI wiring (Laravel, Symfony)
 *
 *   "DIP is the goal. DI is the technique to achieve it. IoC is the broader
 *    concept (the framework is in control). A DI container is the tool."
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3: HOW TO SPOT VIOLATIONS — Red flags per principle
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ─────────────────────────────────────────────────────────────────────────
 * SRP RED FLAGS:
 * ─────────────────────────────────────────────────────────────────────────
 *  ✗ Class name has "And" in its purpose ("UserManagerAndEmailSender")
 *  ✗ Class has methods from 2+ different abstraction levels
 *    (validateInput() AND sendToDatabase() AND generateReport() in one class)
 *  ✗ File > ~200 lines usually means too many responsibilities
 *  ✗ When you change email logic, unrelated DB code could break
 *  ✗ Multiple unrelated private helper methods
 *  ✗ Class needs multiple unrelated imports/dependencies
 *
 * ─────────────────────────────────────────────────────────────────────────
 * OCP RED FLAGS:
 * ─────────────────────────────────────────────────────────────────────────
 *  ✗ Long if/else or switch/case blocks checking a 'type' string or enum
 *    and you need to add a new case regularly
 *  ✗ Code comment: "// Add new discount type here" inside a method
 *  ✗ A method has 5+ if blocks checking the same condition ($type === 'X')
 *  ✗ A ticket says "add new payment method" but requires editing 3 existing files
 *  ✗ instanceof checks to dispatch behavior: if ($x instanceof Pdf) {...}
 *
 * ─────────────────────────────────────────────────────────────────────────
 * LSP RED FLAGS:
 * ─────────────────────────────────────────────────────────────────────────
 *  ✗ Subclass method body contains: throw new \Exception("Not supported")
 *  ✗ Subclass method is empty / no-op when parent method does real work
 *  ✗ Client code: if ($employee instanceof Contractor) { skip bonus }
 *  ✗ Subclass returns null where base class guaranteed a non-null value
 *  ✗ Subclass requires MORE strict input (precondition strengthened)
 *  ✗ You override a method just to disable/ignore inherited behavior
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ISP RED FLAGS:
 * ─────────────────────────────────────────────────────────────────────────
 *  ✗ Interface has 10+ methods — most implementers only use 3–4
 *  ✗ Empty method implementations: public function cookFood() {} (not my job)
 *  ✗ "God interface" — one interface that covers all operations on an entity
 *  ✗ Test mock needs to stub 8 methods but the test only exercises 2
 *  ✗ Adding a method to the interface forces unrelated classes to add stubs
 *
 * ─────────────────────────────────────────────────────────────────────────
 * DIP RED FLAGS:
 * ─────────────────────────────────────────────────────────────────────────
 *  ✗ `new ConcreteClass()` called INSIDE a business method
 *    (not in a factory, not in a container, not in bootstrap)
 *  ✗ Type-hint in constructor is a CLASS name, not an INTERFACE name:
 *    public function __construct(MySQLDatabase $db)  ← bad
 *    public function __construct(DatabaseInterface $db)  ← good
 *  ✗ Swapping MySQL → PostgreSQL requires editing the service class
 *  ✗ Unit tests require a real DB connection because MySQL is hardcoded
 *  ✗ Static method calls to concrete classes: StripeSDK::charge(...)
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4: HOW PRINCIPLES RELATE TO EACH OTHER
// ═══════════════════════════════════════════════════════════════════════════
/**
 * Understanding RELATIONSHIPS between principles impresses senior interviewers.
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ SRP + OCP work together:                                             │
 * │   Classes with one responsibility are easier to extend without       │
 * │   modification. A God class almost always violates both SRP and OCP. │
 * │                                                                       │
 * │ LSP enables OCP:                                                      │
 * │   OCP says "add new classes to extend behavior." This only works if  │
 * │   the new class (subtype) is substitutable (LSP). If your new        │
 * │   Strategy subclass breaks the contract, OCP falls apart.            │
 * │                                                                       │
 * │ ISP prevents LSP violations:                                         │
 * │   LSP violations often happen because a subclass was FORCED to        │
 * │   implement a method it can't support (→ throws exception).          │
 * │   ISP fixes this: split the interface so the class only implements   │
 * │   what it genuinely supports. No forced method = no LSP violation.   │
 * │                                                                       │
 * │ ISP + DIP work together:                                             │
 * │   Small, focused interfaces (ISP) are EASIER to inject (DIP).        │
 * │   A fat interface is hard to mock and makes DI less useful.          │
 * │                                                                       │
 * │ DIP enables testing of ALL other principles:                         │
 * │   Once you depend on interfaces (DIP), you can inject stubs/mocks    │
 * │   to test SRP, OCP, LSP, ISP compliance in isolation.               │
 * │                                                                       │
 * │ SRP supports DIP:                                                    │
 * │   A class with one responsibility naturally has fewer dependencies.  │
 * │   Fewer deps → simpler interface → easier to inject.                 │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * SUMMARY CHAIN:
 *   SRP → small classes
 *   ISP → small interfaces
 *   DIP → inject those small interfaces
 *   OCP → add new implementations without editing injected code
 *   LSP → new implementations are safe drop-in replacements
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5: SOLID ↔ DESIGN PATTERNS MAPPING
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ┌──────────────┬─────────────────────────────────────────────────────────┐
 * │ PRINCIPLE    │ DESIGN PATTERNS THAT IMPLEMENT IT                       │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ SRP          │ Facade (splits complex system into focused classes)      │
 * │              │ Command (separates request from execution)               │
 * │              │ Repository (separates data access from business logic)   │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ OCP          │ Strategy (vary algorithm without changing context)       │
 * │              │ Decorator (add behavior without modifying class)         │
 * │              │ Template Method (vary steps without changing skeleton)   │
 * │              │ Observer (add new observers without changing subject)    │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ LSP          │ All properly implemented inheritance hierarchies         │
 * │              │ Template Method (subclasses fill in steps safely)        │
 * │              │ Strategy (all strategies are safe substitutes)           │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ ISP          │ Role interfaces (Readable, Writable, etc.)               │
 * │              │ Adapter (exposes only the interface the client needs)    │
 * │              │ Proxy (exposes same interface as real subject)           │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ DIP          │ Factory / Abstract Factory (creates concretions)         │
 * │              │ Dependency Injection / IoC Container                     │
 * │              │ Service Locator (less preferred — hides dependencies)    │
 * │              │ Proxy (client depends on Subject interface, not Proxy)   │
 * └──────────────┴─────────────────────────────────────────────────────────┘
 *
 * INTERVIEW TIP: When asked about a design pattern, ALWAYS mention which
 * SOLID principles it embodies. Shows depth of knowledge.
 * Example: "The Strategy pattern embodies OCP — the context class is closed
 *  for modification but open for extension via new strategy implementations.
 *  It also uses DIP — the context depends on the Strategy interface, not
 *  a concrete algorithm."
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6: SOLID VIOLATIONS IN REAL PHP CODE — Spot + Fix
// ═══════════════════════════════════════════════════════════════════════════

// ─────────────────────────────────────────────────────────────────────────
// CODE SMELL 1: SRP — Service class doing too much (very common in Laravel)
// ─────────────────────────────────────────────────────────────────────────

// ❌ Common Laravel anti-pattern: Fat controller / fat service
class BadProductService
{
    public function createProduct(array $data): array
    {
        // Responsibility 1: Validate (should be a FormRequest / Validator)
        if (empty($data['name'])) {
            throw new \InvalidArgumentException("Name required");
        }
        if ($data['price'] <= 0) {
            throw new \InvalidArgumentException("Price must be positive");
        }

        // Responsibility 2: Business logic (OK here)
        $slug = strtolower(str_replace(' ', '-', $data['name']));

        // Responsibility 3: Persistence (should be a Repository)
        // $product = DB::table('products')->insertGetId([...]);
        $productId = rand(1, 1000); // Simulated

        // Responsibility 4: Cache invalidation (should be an event listener)
        // Cache::forget('products.list');

        // Responsibility 5: Send email (should be a Mailable/Job)
        // Mail::to($data['admin_email'])->send(new ProductCreated(...));

        // Responsibility 6: Log audit trail (should be a logger/observer)
        // AuditLog::record('product.created', $productId);

        return ['id' => $productId, 'slug' => $slug];
    }
}

// ✅ Correct: Service handles only orchestration; real work delegated
interface ProductValidator    { public function validate(array $data): void; }
interface ProductRepository   { public function create(array $data): int; }
interface ProductCacheService { public function invalidateList(): void; }
interface ProductEventBus     { public function dispatch(string $event, array $payload): void; }

class GoodProductService
{
    public function __construct(
        private ProductValidator    $validator,
        private ProductRepository   $repository,
        private ProductCacheService $cache,
        private ProductEventBus     $events
    ) {}

    public function createProduct(array $data): array
    {
        $this->validator->validate($data);                           // SRP: validation
        $slug      = strtolower(str_replace(' ', '-', $data['name']));
        $productId = $this->repository->create($data + ['slug' => $slug]); // SRP: persistence
        $this->cache->invalidateList();                              // SRP: cache
        $this->events->dispatch('product.created', ['id' => $productId]);  // SRP: events
        return ['id' => $productId, 'slug' => $slug];
    }
}

// ─────────────────────────────────────────────────────────────────────────
// CODE SMELL 2: OCP — Notification type switch statement
// ─────────────────────────────────────────────────────────────────────────

// ❌ Adding 'WhatsApp' requires editing this method
class BadNotificationService
{
    public function send(string $type, string $message, string $to): void
    {
        if ($type === 'email') {
            echo "  [Email] Sending '$message' to $to\n";
        } elseif ($type === 'sms') {
            echo "  [SMS] Sending '$message' to $to\n";
        } elseif ($type === 'push') {
            echo "  [Push] Sending '$message' to $to\n";
        }
        // ← Must edit here to add 'whatsapp' → OCP violated
    }
}

// ✅ New channel = new class; NotificationDispatcher never changes
interface NotificationChannel
{
    public function send(string $message, string $recipient): void;
    public function supports(string $type): bool;
}

class EmailChannel implements NotificationChannel
{
    public function send(string $msg, string $to): void { echo "  [Email] $msg → $to\n"; }
    public function supports(string $type): bool        { return $type === 'email'; }
}

class SmsChannel implements NotificationChannel
{
    public function send(string $msg, string $to): void { echo "  [SMS] $msg → $to\n"; }
    public function supports(string $type): bool        { return $type === 'sms'; }
}

// ✅ New: zero modification to existing classes
class WhatsAppChannel implements NotificationChannel
{
    public function send(string $msg, string $to): void { echo "  [WhatsApp] $msg → $to\n"; }
    public function supports(string $type): bool        { return $type === 'whatsapp'; }
}

class NotificationDispatcher
{
    private array $channels = [];

    public function register(NotificationChannel $channel): void
    {
        $this->channels[] = $channel;
    }

    public function send(string $type, string $message, string $to): void
    {
        foreach ($this->channels as $channel) {
            if ($channel->supports($type)) {
                $channel->send($message, $to);
                return;
            }
        }
        echo "  [Dispatcher] No channel registered for type '$type'\n";
    }
}

// ─────────────────────────────────────────────────────────────────────────
// CODE SMELL 3: DIP — Service creates its own dependencies
// ─────────────────────────────────────────────────────────────────────────

// ❌ Hard to test; swapping DB/mailer requires code changes
class BadInvoiceService
{
    private object $db;
    private object $mailer;

    public function __construct()
    {
        // $this->db     = new MySQLConnection(...); // Hardcoded concrete class
        // $this->mailer = new SmtpMailer(...);      // Hardcoded concrete class
        // Cannot unit test without real DB + mail server
    }

    public function generate(int $orderId): void
    {
        // Uses $this->db and $this->mailer
    }
}

// ✅ Dependencies injected — fully testable with stubs
interface InvoiceRepository { public function save(array $invoice): int; }
interface InvoiceMailer     { public function sendInvoice(int $invoiceId, string $email): void; }

class InvoiceService
{
    public function __construct(
        private InvoiceRepository $repository,  // Could be MySQL, Mongo, etc.
        private InvoiceMailer     $mailer        // Could be SMTP, SendGrid, etc.
    ) {}

    public function generate(int $orderId, string $email): int
    {
        $invoiceId = $this->repository->save(['order_id' => $orderId, 'status' => 'issued']);
        $this->mailer->sendInvoice($invoiceId, $email);
        return $invoiceId;
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7: INTERVIEW ANSWER FRAMEWORKS
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ─────────────────────────────────────────────────────────────────────────
 * THE D.E.F. FRAMEWORK — Use for every SOLID principle question:
 * ─────────────────────────────────────────────────────────────────────────
 *  D — DEFINE    : State the principle in one clear sentence
 *  E — EXAMPLE   : Give a concrete violation + fix (real-world scenario)
 *  F — FRAMEWORK : Name the Laravel / PHP tool that embodies it
 *
 * Example using SRP:
 *  D: "A class should have only one reason to change."
 *  E: "A common violation is a fat service class that validates input, saves
 *      to DB, sends emails, and generates PDFs — four separate reasons to
 *      change. I'd split it into Validator, Repository, Mailer, and
 *      InvoiceGenerator."
 *  F: "In Laravel, FormRequests handle validation (SRP), Repositories handle
 *      DB, Mailables handle email. Each is its own class with one job."
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE C.A.R. FRAMEWORK — For behavioral / situational questions:
 * ─────────────────────────────────────────────────────────────────────────
 *  C — CONTEXT  : Describe the codebase situation
 *  A — ACTION   : What you did (which principle you applied + how)
 *  R — RESULT   : The outcome (easier testing, fewer bugs, team velocity)
 *
 * Example:
 *  C: "We had a PaymentService that directly instantiated StripeSDK."
 *  A: "I extracted a PaymentGateway interface (DIP), moved Stripe into
 *      StripeGateway class. Added RazorpayGateway for Indian market."
 *  R: "We could unit test PaymentService with FakeGateway — no real Stripe
 *      needed. Adding Razorpay took 2 hours, zero existing code changed."
 *
 * ─────────────────────────────────────────────────────────────────────────
 * POWER PHRASES — Drop these into answers to sound senior:
 * ─────────────────────────────────────────────────────────────────────────
 *  → "...which gives us the ability to swap implementations without
 *     touching existing code — that's OCP in action."
 *
 *  → "...the key insight is that IS-A in OOP is about behavioral
 *     substitutability, not just classification — that's LSP."
 *
 *  → "...by pushing the `new` keyword to the composition root and
 *     injecting interfaces, we satisfy DIP and make the class unit-testable."
 *
 *  → "...I prefer constructor injection over setter injection because it
 *     ensures the object is always in a valid, fully initialized state."
 *
 *  → "...this is why ISP and LSP often fix each other: splitting the fat
 *     interface (ISP) prevents the subclass from being forced to throw
 *     UnsupportedException (LSP)."
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 8: SENIOR-LEVEL DISCUSSION TOPICS
// ═══════════════════════════════════════════════════════════════════════════
/**
 * If the interviewer is senior (Staff+/Principal), expect these deeper topics:
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TOPIC 1: SOLID vs GRASP vs Clean Architecture
 * ─────────────────────────────────────────────────────────────────────────
 *  SOLID: Class-level OOP design guidelines.
 *  GRASP: General Responsibility Assignment Software Patterns — higher-level
 *         (Information Expert, Creator, Controller, Low Coupling, High Cohesion).
 *  Clean Architecture: Architecture-level (Entities → Use Cases → Interfaces
 *         → Frameworks/DB). SOLID is the building block inside each layer.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TOPIC 2: SOLID in Microservices
 * ─────────────────────────────────────────────────────────────────────────
 *  SRP → Each microservice owns ONE bounded context (Domain-Driven Design)
 *  OCP → Services communicate via versioned APIs; old clients unaffected
 *  LSP → v2 API is backward-compatible with v1 clients
 *  ISP → API contracts are lean; don't expose irrelevant endpoints to callers
 *  DIP → Services depend on contracts (OpenAPI/Protobuf), not each other
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TOPIC 3: When to BREAK SOLID rules (shows real-world wisdom)
 * ─────────────────────────────────────────────────────────────────────────
 *  "SOLID is a guide, not a law."
 *
 *  Break SRP: A tiny utility script — extracting into 5 classes adds no value.
 *  Break OCP: First iteration — YAGNI. Add abstraction when the second variant
 *             appears (Rule of Three). Premature abstraction is technical debt.
 *  Break LSP: Rarely justified. If you must, document the exception explicitly.
 *  Break ISP: Very small interfaces that are ALWAYS used together — merging
 *             two 1-method interfaces into a 2-method interface is fine.
 *  Break DIP: Value objects, DTOs, and data structures (no behavior) don't
 *             need interfaces. `new Address('123 Main St')` is perfectly fine.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TOPIC 4: SOLID and Test-Driven Development (TDD)
 * ─────────────────────────────────────────────────────────────────────────
 *  TDD naturally drives you toward SOLID:
 *   → You can't unit test a God class easily → SRP
 *   → You can't add a test for new behavior without changing class → OCP
 *   → Your test breaks when using a subclass → LSP
 *   → Your mock is huge because the interface is fat → ISP
 *   → Your test needs real MySQL → DIP (inject a fake instead)
 *
 *  "If your class is hard to test, it probably violates at least one
 *   SOLID principle. SOLID and TDD are mutually reinforcing."
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 9: QUICK COMPARISON TABLES
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ┌────────────────┬──────────────────────────────┬────────────────────────┐
 * │ PRINCIPLE      │ PRIMARY BENEFIT              │ COST OF VIOLATION      │
 * ├────────────────┼──────────────────────────────┼────────────────────────┤
 * │ SRP            │ Easy to find & fix bugs       │ Class explosion on     │
 * │                │ Parallel team work             │ change; regression risk│
 * ├────────────────┼──────────────────────────────┼────────────────────────┤
 * │ OCP            │ Add features without risk      │ Every new type breaks  │
 * │                │ (existing code untouched)      │ existing if-else logic │
 * ├────────────────┼──────────────────────────────┼────────────────────────┤
 * │ LSP            │ Safe polymorphism              │ Silent wrong results;  │
 * │                │ instanceof checks eliminated   │ runtime exceptions     │
 * ├────────────────┼──────────────────────────────┼────────────────────────┤
 * │ ISP            │ Small, focused mocks           │ Empty stubs; forced    │
 * │                │ Fewer dependencies per class   │ no-op implementations  │
 * ├────────────────┼──────────────────────────────┼────────────────────────┤
 * │ DIP            │ Testability; swap impls        │ No unit tests possible │
 * │                │ freely; loose coupling         │ without real infra     │
 * └────────────────┴──────────────────────────────┴────────────────────────┘
 *
 * ┌────────────────┬────────────────────────────────────────────────────────┐
 * │ PRINCIPLE      │ LARAVEL / PHP ECOSYSTEM EMBODIMENTS                   │
 * ├────────────────┼────────────────────────────────────────────────────────┤
 * │ SRP            │ FormRequest (validation), Mailable (email),            │
 * │                │ Job (background task), Repository (DB), Policy (auth)  │
 * ├────────────────┼────────────────────────────────────────────────────────┤
 * │ OCP            │ Laravel Gates (add new abilities without editing Auth), │
 * │                │ Event Listeners (add new listeners to existing events)  │
 * │                │ Laravel Macros (extend core without modifying it)       │
 * ├────────────────┼────────────────────────────────────────────────────────┤
 * │ LSP            │ Laravel Collection methods (any Arrayable substitutable)│
 * │                │ Eloquent casts (any CastsAttributes is interchangeable) │
 * ├────────────────┼────────────────────────────────────────────────────────┤
 * │ ISP            │ Laravel Contracts package (thin interfaces per feature) │
 * │                │ Illuminate\Contracts\Cache\Repository (only cache ops)  │
 * ├────────────────┼────────────────────────────────────────────────────────┤
 * │ DIP            │ Laravel IoC Container, service providers, constructor   │
 * │                │ injection, Facade (proxy over bound interface)          │
 * └────────────────┴────────────────────────────────────────────────────────┘
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 10: LIVE CODING DEMO — Refactor step-by-step (very common in rounds)
// ═══════════════════════════════════════════════════════════════════════════
/**
 * Interviewer gives you this class and says:
 * "Tell me what's wrong with this class and improve it."
 *
 * This happens frequently in mid/senior PHP interviews. Walk through it
 * out loud using the principles you know.
 */

// ─── STARTING CODE (what the interviewer shows you) ──────────────────────
class ReportManager
{
    private string $dbHost = 'localhost';
    private string $dbPass = 'secret';

    // ISSUE 1 (SRP): DB connection handled inside a report class
    // ISSUE 2 (DIP): Hardcoded connection details; concrete dependency
    private function connectToDb(): object
    {
        return new \stdClass(); // Simulated DB connection
    }

    // ISSUE 3 (SRP): Fetching data is a data-access responsibility
    private function fetchData(string $reportType): array
    {
        $conn = $this->connectToDb();
        return [['row' => 1], ['row' => 2]]; // Simulated
    }

    // ISSUE 4 (OCP): New format → must edit this method
    // ISSUE 5 (SRP): Formatting is a separate responsibility
    public function generateReport(string $type, string $format): string
    {
        $data = $this->fetchData($type);

        if ($format === 'csv') {
            $output = "type,row\n";
            foreach ($data as $row) { $output .= "$type,{$row['row']}\n"; }
            return $output;
        } elseif ($format === 'json') {
            return json_encode(['type' => $type, 'data' => $data]);
        } elseif ($format === 'html') {
            $rows = implode('', array_map(fn($r) => "<tr><td>{$r['row']}</td></tr>", $data));
            return "<table>$rows</table>";
        }
        // Adding 'pdf' format requires modifying this method → OCP violation
        return '';
    }

    // ISSUE 6 (SRP): Emailing is NOT a report responsibility
    public function emailReport(string $to, string $report): void
    {
        echo "  [ReportManager] Email sent to $to\n";
    }
}

// ─── REFACTORED CODE (what you write in the interview) ───────────────────

// STEP 1: SRP + DIP — Separate data access
interface ReportDataSource
{
    public function fetch(string $reportType): array;
}

class DatabaseReportDataSource implements ReportDataSource
{
    public function __construct(private object $connection) {} // DIP: injected

    public function fetch(string $reportType): array
    {
        return [['row' => 1], ['row' => 2]]; // Real: run query on $this->connection
    }
}

// STEP 2: OCP + SRP — Each format = its own class (no more if/else)
interface ReportFormatter
{
    public function format(string $reportType, array $data): string;
    public function supports(string $format): bool;
}

class CsvReportFormatter implements ReportFormatter
{
    public function format(string $type, array $data): string
    {
        $csv = "type,row\n";
        foreach ($data as $row) { $csv .= "$type,{$row['row']}\n"; }
        return $csv;
    }
    public function supports(string $format): bool { return $format === 'csv'; }
}

class JsonReportFormatter implements ReportFormatter
{
    public function format(string $type, array $data): string
    {
        return json_encode(['type' => $type, 'data' => $data]);
    }
    public function supports(string $format): bool { return $format === 'json'; }
}

// ✅ New format = new class; ReportService untouched (OCP)
class HtmlReportFormatter implements ReportFormatter
{
    public function format(string $type, array $data): string
    {
        $rows = implode('', array_map(fn($r) => "<tr><td>{$r['row']}</td></tr>", $data));
        return "<table>$rows</table>";
    }
    public function supports(string $format): bool { return $format === 'html'; }
}

// STEP 3: SRP — Emailing is its own concern
interface ReportMailer
{
    public function send(string $to, string $content): void;
}

class SmtpReportMailer implements ReportMailer
{
    public function send(string $to, string $content): void
    {
        echo "  [SMTP] Report sent to $to\n";
    }
}

// STEP 4: Clean orchestrator — only one responsibility: orchestrate report generation
class ReportService
{
    /** @param ReportFormatter[] $formatters */
    public function __construct(
        private ReportDataSource $dataSource, // DIP ✓
        private array            $formatters, // OCP ✓ (open-ended array)
        private ReportMailer     $mailer      // DIP + SRP ✓
    ) {}

    public function generate(string $type, string $format): string
    {
        $data = $this->dataSource->fetch($type); // SRP: delegate data fetching

        foreach ($this->formatters as $formatter) { // OCP: no if/else
            if ($formatter->supports($format)) {
                return $formatter->format($type, $data);
            }
        }
        throw new \InvalidArgumentException("Unsupported format: $format");
    }

    public function generateAndEmail(string $type, string $format, string $to): void
    {
        $report = $this->generate($type, $format);
        $this->mailer->send($to, $report); // SRP: delegate emailing
    }
}

// STEP 5: Wire everything (composition root — the ONLY place with `new`)
$db         = new \stdClass(); // Real: PDO / Eloquent connection
$dataSource = new DatabaseReportDataSource($db);
$formatters = [
    new CsvReportFormatter(),
    new JsonReportFormatter(),
    new HtmlReportFormatter(),
];
$mailer      = new SmtpReportMailer();
$reportService = new ReportService($dataSource, $formatters, $mailer);

echo "\n=== LIVE REFACTOR DEMO ===\n";

$csv  = $reportService->generate('sales', 'csv');
echo "CSV:\n$csv";

$json = $reportService->generate('sales', 'json');
echo "JSON: $json\n";

$reportService->generateAndEmail('sales', 'html', 'boss@company.com');

/**
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ HOW TO WALK THROUGH A REFACTOR IN AN INTERVIEW                       │
 * ├──────────────────────────────────────────────────────────────────────┤
 * │ 1. DIAGNOSE before touching the code:                                │
 * │    "I see at least 3 SOLID violations here. Let me list them:       │
 * │     First, SRP — this class manages data, formats reports, AND sends │
 * │     emails — three separate reasons to change.                       │
 * │     Second, OCP — the if/else on format requires modification for    │
 * │     each new format.                                                  │
 * │     Third, DIP — it creates its own DB connection internally."        │
 * │                                                                       │
 * │ 2. PROPOSE your approach BEFORE writing:                             │
 * │    "I'll create a ReportDataSource interface (DIP + SRP), separate   │
 * │     formatters per format (OCP), and a ReportMailer interface (SRP). │
 * │     The ReportService becomes a thin orchestrator."                   │
 * │                                                                       │
 * │ 3. CODE incrementally and narrate:                                   │
 * │    "Here I'm extracting ReportFormatter as an interface. Each format │
 * │     is a new class. Now adding 'pdf' doesn't touch ReportService at  │
 * │     all — that's OCP."                                               │
 * │                                                                       │
 * │ 4. END with testability:                                             │
 * │    "The final class is now fully unit testable — I inject            │
 * │     InMemoryDataSource and NullMailer in tests. No real DB needed."  │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              FINAL INTERVIEW CONFIDENCE TIPS                         ║
 * ╠══════════════════════════════════════════════════════════════════════╣
 * ║  ✓ Start every SOLID answer with the one-liner from Section 1        ║
 * ║  ✓ Always give a concrete real-world example — not just definition   ║
 * ║  ✓ Name the RED FLAG that tells you a principle is violated          ║
 * ║  ✓ Connect principles to each other (LSP + ISP, OCP + Strategy)     ║
 * ║  ✓ Mention testability — seniors value this above all                ║
 * ║  ✓ Show you know WHEN NOT to apply a principle (YAGNI, pragmatism)  ║
 * ║  ✓ In live coding: diagnose first, propose plan, then code           ║
 * ║  ✓ Use Laravel/PHP-specific examples when interviewing for PHP roles ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */


// ═══════════════════════════════════════════════════════════════════════════
// SECTION 11: BEST PREPARATION RESOURCES
// ═══════════════════════════════════════════════════════════════════════════
/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║           CURATED RESOURCES — SOLID PRINCIPLES                          ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  Organised by: Videos → Articles → Books → PHP-Specific → Practice      ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 🎥 YOUTUBE VIDEOS — Watch in this order
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ BEGINNER — Understand all 5 principles with visuals                     │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ 1. "SOLID Principles - The Simple Way to Understand"                    │
 * │    Channel : Fireship                                                    │
 * │    URL     : https://www.youtube.com/watch?v=pTB30aXS77U                │
 * │    Duration: ~10 min | Fast, visual, beginner-friendly                  │
 * │    Why     : Best quick intro — uses real analogies, easy to retain     │
 * │                                                                          │
 * │ 2. "SOLID Design Principles Explained" (Full playlist)                  │
 * │    Channel : Traversy Media                                              │
 * │    URL     : https://www.youtube.com/watch?v=HZwhNYRFMFo                │
 * │    Duration: ~25 min | Covers all 5 with code examples                  │
 * │    Why     : Good pacing, clear code demos in multiple languages        │
 * │                                                                          │
 * │ 3. "SOLID Principles with Real Examples" (PHP focused)                  │
 * │    Channel : Gary Clarke                                                 │
 * │    URL     : https://www.youtube.com/watch?v=rtmFCcjEgEw                │
 * │    Duration: ~30 min | PHP code, Laravel context                        │
 * │    Why     : Most relevant if you're interviewing for PHP/Laravel roles │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ INTERMEDIATE — Go deeper, understand the WHY                            │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ 4. "Uncle Bob — The SOLID Principles" (Original author)                 │
 * │    Channel : Clean Coders (Robert C. Martin)                            │
 * │    URL     : https://www.youtube.com/watch?v=zHiWqnTWsn4                │
 * │    Duration: ~1 hr | Authoritative; straight from the creator of SOLID  │
 * │    Why     : Understand the INTENT behind each principle, not just rules│
 * │    Note    : Watch at 1.5x speed — dense content but worth it          │
 * │                                                                          │
 * │ 5. "SOLID Principles | How to apply in real project"                    │
 * │    Channel : Web Dev Simplified                                          │
 * │    URL     : https://www.youtube.com/watch?v=UQqY3_6Epbg                │
 * │    Duration: ~20 min | Shows refactoring a real codebase step-by-step  │
 * │    Why     : Practice narrating refactors — exactly what interviews test│
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ ADVANCED — Design Patterns + SOLID combined                             │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ 6. "Design Patterns in Object Oriented Programming" (full series)       │
 * │    Channel : Christopher Okhravi                                         │
 * │    URL     : https://www.youtube.com/playlist?list=PLrhzvIcii6GNjpARdnO4ueTUAVR9eMBpc │
 * │    Duration: Full playlist ~8 hrs total | Watch 2–3 per day             │
 * │    Why     : BEST deep-dive on how patterns + SOLID interconnect.       │
 * │              Covers Strategy, Observer, Decorator, Factory etc.         │
 * │    Recommended episodes: Strategy (#1), Decorator (#3), Observer (#5)  │
 * │                                                                          │
 * │ 7. "Clean Code - Uncle Bob" (Conference talks)                          │
 * │    Channel : UnityCoin / various uploads                                 │
 * │    URL     : https://www.youtube.com/watch?v=7EmboKQH8lM                │
 * │    Duration: ~1 hr per episode | 6-part series                          │
 * │    Why     : Context for WHY SOLID matters in large codebases           │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 🌐 WEBSITES & ARTICLES
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ REFERENCE SITES                                                          │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ 1. Refactoring.Guru — SOLID & Design Patterns                           │
 * │    URL  : https://refactoring.guru/design-patterns                       │
 * │    Why  : Best visual explanations of all design patterns + SOLID.      │
 * │           Interactive diagrams, before/after code, real-world examples. │
 * │    USE  : Read the pattern page BEFORE each interview → refreshes memory│
 * │                                                                          │
 * │ 2. DigitalOcean — SOLID Principles in PHP (Article series)              │
 * │    URL  : https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design │
 * │    Why  : Concise, PHP-focused explanations with code snippets.         │
 * │           Good for last-minute revision the morning of an interview.    │
 * │                                                                          │
 * │ 3. Medium — "SOLID Principles in PHP" by Raza Bangi                     │
 * │    URL  : https://razabangi.medium.com/solid-principles-in-php-2a4f2e632a5a │
 * │    Why  : Practical PHP code for each principle. Short reads per topic. │
 * │                                                                          │
 * │ 4. PHP The Right Way                                                     │
 * │    URL  : https://phptherightway.com/                                    │
 * │    Why  : Industry-consensus PHP best practices — covers OOP, DI,       │
 * │           design patterns, and coding standards in one place.           │
 * │                                                                          │
 * │ 5. Clean Code PHP (GitHub)                                               │
 * │    URL  : https://github.com/jupeter/clean-code-php                      │
 * │    Why  : Extensive PHP examples of SOLID + Clean Code principles.      │
 * │           Great for learning idiomatic PHP alongside the principles.    │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ BLOGS & SERIES                                                           │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ 6. "SOLID Principles by Using PHP" — Dev Genius (Medium)                │
 * │    SRP : https://blog.devgenius.io/single-responsibility-principle-srp-by-using-php │
 * │    OCP : https://blog.devgenius.io/open-closed-principle-ocp-by-using-php-solid-principle │
 * │    LSP : https://blog.devgenius.io/liskov-substitution-principle-lsp-by-using-php-solid-principle │
 * │    ISP : https://blog.devgenius.io/interface-segregation-principle-isp-by-using-php │
 * │    DIP : https://blog.devgenius.io/dependency-inversion-principle-dip-by-using-php-solid-principle │
 * │    Why : Each article = one principle, concise, PHP-specific.           │
 * │                                                                          │
 * │ 7. Martin Fowler's Blog — Refactoring & OOP Design                      │
 * │    URL  : https://martinfowler.com/tags/object%20orientation.html        │
 * │    Why  : Deeper architectural thinking; useful for Staff/Principal      │
 * │           level interviews where design tradeoffs are discussed.        │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 📚 BOOKS (Essential — read in order of priority)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ PRIORITY 1 — Must Read                                                  │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ "Clean Code: A Handbook of Agile Software Craftsmanship"                │
 * │  Author : Robert C. Martin (Uncle Bob)                                  │
 * │  Why    : The definitive guide to writing maintainable code.            │
 * │           Chapters on classes, functions, and OOP design directly       │
 * │           cover SOLID in practice.                                       │
 * │  Read   : Chapters 1–10 minimum; Chapter 10 (Classes) is essential.    │
 * │                                                                          │
 * │ "Agile Software Development: Principles, Patterns, and Practices"       │
 * │  Author : Robert C. Martin                                               │
 * │  Why    : WHERE SOLID WAS ORIGINALLY DEFINED. Deep dive on each        │
 * │           principle with real case studies. More academic than          │
 * │           Clean Code but authoritative.                                  │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ PRIORITY 2 — Highly Recommended                                         │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ "Design Patterns: Elements of Reusable Object-Oriented Software"        │
 * │  Author : Gang of Four (Gamma, Helm, Johnson, Vlissides)                │
 * │  Why    : The original 23 GoF design patterns — Strategy, Observer,     │
 * │           Decorator etc. are how you IMPLEMENT SOLID principles.        │
 * │  Read   : Introduction + patterns you've coded: Strategy, Observer,     │
 * │           Decorator, Factory, Composite (all in your Design-Patterns/)  │
 * │                                                                          │
 * │ "Refactoring: Improving the Design of Existing Code" (2nd Ed)           │
 * │  Author : Martin Fowler                                                  │
 * │  Why    : Teaches you HOW to move from violated to compliant code.      │
 * │           Every refactoring technique has a SOLID principle behind it.  │
 * │           Directly useful for the live coding / refactor interview task.│
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ PRIORITY 3 — For PHP-Specific Depth                                     │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ "PHP Objects, Patterns, and Practice" (5th Ed)                          │
 * │  Author : Matt Zandstra                                                  │
 * │  Why    : PHP-specific treatment of OOP + design patterns + SOLID.      │
 * │           Covers dependency injection, interfaces, and patterns in PHP. │
 * │  Best   : Chapters on design patterns map directly to your code files.  │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 🛠️  INTERACTIVE PRACTICE PLATFORMS
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ PLATFORM                     WHAT TO DO                                 │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                          │
 * │ Exercism (exercism.org)                                                  │
 * │   URL  : https://exercism.org/tracks/php                                 │
 * │   Do   : PHP track → OOP exercises → practice interfaces, inheritance,  │
 * │          composition. Community mentors give SOLID-aware feedback.      │
 * │                                                                          │
 * │ CodeKata (codekata.com)                                                  │
 * │   URL  : http://codekata.com/                                            │
 * │   Do   : Kata #2 "Karate Chop", Kata #9 "Back to the Checkout".        │
 * │          These katas are designed to practise refactoring + OOP design. │
 * │                                                                          │
 * │ GitHub — Real-world open source PHP projects                            │
 * │   Laravel Framework : https://github.com/laravel/framework               │
 * │   Symfony           : https://github.com/symfony/symfony                 │
 * │   Do   : Browse src/ folder, pick a service class, identify which SOLID │
 * │          principles are applied and why. This is the best advanced       │
 * │          practice — reading professional SOLID code in the wild.        │
 * │                                                                          │
 * │ LeetCode / HackerRank (for OOP design rounds)                           │
 * │   URL  : https://leetcode.com/problemset/all/?topicSlugs=design         │
 * │   Do   : Filter by "Design" tag. Problems like "Design a Parking Lot",  │
 * │          "Design a Library System" test your SOLID + OOP design skills. │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 📅 SUGGESTED STUDY PLAN (1 week before interview)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * ┌──────────┬───────────────────────────────────────────────────────────────┐
 * │ DAY      │ ACTIVITY                                                       │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 1    │ Watch Fireship (10 min) + read Sections 1–3 of THIS file      │
 * │          │ → Can you recall each principle in one sentence from memory?  │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 2    │ Read all 5 principle files (1–5) in this folder               │
 * │          │ → Understand the ❌ violation AND ✅ fix for each             │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 3    │ Watch Gary Clarke PHP video + read Section 6 (Code Smells)    │
 * │          │ → Can you spot each violation in 30 seconds?                 │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 4    │ Re-read Section 7 (Interview frameworks) + practice           │
 * │          │ saying answers OUT LOUD using D.E.F. for each principle       │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 5    │ Watch Christopher Okhravi: Strategy + Decorator + Observer    │
 * │          │ → Connect each pattern to the SOLID principles it embodies   │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 6    │ Do Section 10 live refactor: close this file, open a blank    │
 * │          │ editor, write the BadReportManager → refactor from memory    │
 * │          │ → If you can do this, you're interview-ready                 │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ Day 7    │ Quick-read Refactoring.Guru for any patterns you're weak on  │
 * │          │ → Review Sections 2 (trick Qs) + 8 (senior topics)           │
 * │          │ → Rest. Confidence comes from preparation, not cramming.     │
 * └──────────┴───────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ QUICK-REFERENCE LINKS (copy-paste into browser)                         │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │ Refactoring Guru      → https://refactoring.guru/design-patterns        │
 * │ PHP The Right Way     → https://phptherightway.com/                     │
 * │ Clean Code PHP GitHub → https://github.com/jupeter/clean-code-php       │
 * │ SOLID PHP - DigitalOcean → https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design │
 * │ Exercism PHP Track    → https://exercism.org/tracks/php                 │
 * │ LeetCode Design Qs    → https://leetcode.com/problemset/all/?topicSlugs=design │
 * │ Laravel Source Code   → https://github.com/laravel/framework            │
 * └─────────────────────────────────────────────────────────────────────────┘
 */
