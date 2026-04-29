<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          OOP CONCEPT #17 — COUPLING & COHESION                   ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★★  (MUST-KNOW for system design & LLD rounds)  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * THE GOLDEN RULE OF OOP DESIGN:
 *   "LOW COUPLING + HIGH COHESION = Clean, Maintainable Code"
 *
 * COUPLING  = how much one class DEPENDS on another
 *   Low  → easy to change one without affecting the other ✓
 *   High → changing one breaks many others ✗
 *
 * COHESION  = how strongly RELATED a class's responsibilities are
 *   High → class does one well-defined thing ✓
 *   Low  → class does unrelated things ✗
 *
 * ANALOGY:
 *   Low Cohesion  = a "utility drawer" with scissors, batteries, rubber bands — unrelated
 *   High Cohesion = a "medical kit" — all items serve one purpose: first aid
 *   Tight Coupling = when you change the drawer, the cabinet breaks
 *   Loose Coupling = drawers are independent; changing one doesn't affect others
 */

// ═══════════════════════════════════════════════════════════════
// SECTION 1: LOW COHESION ❌ → HIGH COHESION ✅
// ═══════════════════════════════════════════════════════════════

echo "=== COUPLING & COHESION DEMO ===\n\n";

// ──────────────────────────────────────────
// ❌ LOW COHESION — class does too many unrelated things
// ──────────────────────────────────────────
class UserManager  // "Manager" = red flag word — usually low cohesion
{
    public function registerUser(string $name, string $email): void
    {
        // Validates, saves to DB, sends email, logs — all unrelated concerns!
        echo "  [UserManager] Validating...\n";
        echo "  [UserManager] Saving to DB...\n";
        echo "  [UserManager] Sending welcome email...\n";
        echo "  [UserManager] Writing to log...\n";
        echo "  [UserManager] Updating analytics...\n";
        // 5 reasons to change = LOW cohesion
    }
}

// ──────────────────────────────────────────
// ✅ HIGH COHESION — each class does ONE thing
// ──────────────────────────────────────────
class UserValidator
{
    public function validate(string $name, string $email): array
    {
        $errors = [];
        if (strlen($name) < 2)                                $errors[] = "Name too short";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = "Invalid email";
        return $errors;
    }
}

class UserRepository
{
    private array $users = [];

    public function save(array $userData): int
    {
        $id = count($this->users) + 1;
        $this->users[$id] = $userData;
        echo "  [UserRepo] Saved user #{$id}\n";
        return $id;
    }
}

class WelcomeMailer
{
    public function send(string $email, string $name): void
    {
        echo "  [Mailer] Welcome email → {$email} (for {$name})\n";
    }
}

class UserActivityLogger
{
    public function logRegistration(int $userId): void
    {
        echo "  [Logger] User #{$userId} registered at " . date('H:i:s') . "\n";
    }
}

// Orchestrator — ties them together (SRP: its job is to coordinate)
class UserRegistrationService
{
    public function __construct(
        private UserValidator      $validator,
        private UserRepository     $repository,
        private WelcomeMailer      $mailer,
        private UserActivityLogger $logger
    ) {}

    public function register(string $name, string $email): array
    {
        $errors = $this->validator->validate($name, $email);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $id = $this->repository->save(compact('name', 'email'));
        $this->mailer->send($email, $name);
        $this->logger->logRegistration($id);

        return ['success' => true, 'userId' => $id];
    }
}

echo "--- HIGH COHESION (separate concerns) ---\n";
$service = new UserRegistrationService(
    new UserValidator(),
    new UserRepository(),
    new WelcomeMailer(),
    new UserActivityLogger()
);

$result = $service->register('Alice', 'alice@example.com');
echo "  Result: " . ($result['success'] ? "User #{$result['userId']}" : implode(', ', $result['errors'])) . "\n";

$bad = $service->register('A', 'not-email');
echo "  Errors: " . implode(', ', $bad['errors']) . "\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 2: TIGHT COUPLING ❌ → LOOSE COUPLING ✅
// ═══════════════════════════════════════════════════════════════

echo "\n--- TIGHT COUPLING ❌ ---\n";

// ❌ Tightly coupled: OrderService creates its own dependencies
class TightOrderService
{
    private MySQLOrderRepo $repo;   // Hardcoded concrete classes!
    private SmtpMailer     $mailer;

    public function __construct()
    {
        $this->repo   = new MySQLOrderRepo();   // Tight coupling ❌
        $this->mailer = new SmtpMailer();        // Can't swap without modifying this class ❌
    }

    public function place(array $data): void
    {
        $this->repo->save($data);
        $this->mailer->sendOrderConfirmation($data['email']);
    }
}

class MySQLOrderRepo  { public function save(array $d): void   { echo "  [MySQL] Order saved\n"; } }
class SmtpMailer      { public function sendOrderConfirmation(string $e): void { echo "  [SMTP] Confirmation → {$e}\n"; } }

$tight = new TightOrderService();  // Can't test with mocks, can't swap DB/mailer
$tight->place(['email' => 'alice@test.com', 'total' => 500]);

echo "\n--- LOOSE COUPLING ✅ ---\n";

// ✅ Loosely coupled: depends on INTERFACES, receives deps from outside
interface OrderRepository
{
    public function save(array $orderData): int;
    public function findById(int $id): ?array;
}

interface OrderMailer
{
    public function sendConfirmation(string $email, int $orderId): void;
}

class MySQLOrderRepository implements OrderRepository
{
    private array $orders = [];

    public function save(array $data): int
    {
        $id = count($this->orders) + 1;
        $this->orders[$id] = $data;
        echo "  [MySQL] Order #{$id} saved\n";
        return $id;
    }

    public function findById(int $id): ?array { return $this->orders[$id] ?? null; }
}

class InMemoryOrderRepository implements OrderRepository
{
    private array $orders = [];

    public function save(array $data): int
    {
        $id = count($this->orders) + 1;
        $this->orders[$id] = $data;
        echo "  [InMemory] Order #{$id} saved\n";
        return $id;
    }

    public function findById(int $id): ?array { return $this->orders[$id] ?? null; }
}

class EmailOrderMailer implements OrderMailer
{
    public function sendConfirmation(string $email, int $orderId): void
    {
        echo "  [Email] Order #{$orderId} confirmation → {$email}\n";
    }
}

class LooseOrderService
{
    public function __construct(
        private OrderRepository $repo,    // Interface — can be ANY implementation ✓
        private OrderMailer     $mailer   // Interface — can be ANY implementation ✓
    ) {}

    public function place(array $data): int
    {
        $id = $this->repo->save($data);
        $this->mailer->sendConfirmation($data['email'], $id);
        return $id;
    }
}

// Production: MySQL + Email
$prodService = new LooseOrderService(
    new MySQLOrderRepository(),
    new EmailOrderMailer()
);
$prodService->place(['email' => 'bob@test.com', 'total' => 750]);

// Testing/Dev: InMemory + silent (swap without touching LooseOrderService!)
$testService = new LooseOrderService(
    new InMemoryOrderRepository(),
    new class implements OrderMailer {
        public function sendConfirmation(string $e, int $id): void {} // silent
    }
);
$testService->place(['email' => 'test@test.com', 'total' => 0]);

// ═══════════════════════════════════════════════════════════════
// SECTION 3: TYPES OF COUPLING (awareness for design reviews)
// ═══════════════════════════════════════════════════════════════
/**
 * COUPLING SPECTRUM (worst → best):
 *
 * 1. Content Coupling (worst) — one class modifies internal data of another
 * 2. Common Coupling  — two classes share global state
 * 3. Control Coupling — one class passes a flag telling another what to do
 * 4. Stamp Coupling   — passes unneeded data (whole object for 1 field)
 * 5. Data Coupling    — passes only needed params (best acceptable)
 * 6. No Coupling      — classes don't know about each other (ideal with DI)
 *
 * COHESION SPECTRUM (worst → best):
 *
 * 1. Coincidental  — random unrelated methods
 * 2. Logical       — methods do similar things but are unrelated
 * 3. Temporal      — methods run at same time but aren't related
 * 4. Procedural    — methods follow execution order
 * 5. Communicational — methods work on same data
 * 6. Sequential    — output of one method feeds next
 * 7. Functional    — every method contributes to ONE specific task (IDEAL)
 */

echo "\n--- Coupling types demo ---\n";

// ❌ Control coupling — flag tells method what to do
class ReportBuilder
{
    public function generate(string $type): string  // 'pdf' or 'csv' — control flag ❌
    {
        if ($type === 'pdf') return "PDF Report";
        if ($type === 'csv') return "CSV Report";
        throw new \InvalidArgumentException("Unknown type: {$type}");
    }
}

// ✅ Polymorphism removes control coupling
interface ReportFormat { public function render(array $data): string; }
class PdfReport  implements ReportFormat { public function render(array $d): string { return "PDF: " . json_encode($d); } }
class CsvReport  implements ReportFormat { public function render(array $d): string { return "CSV: " . implode(',', $d); } }
class JsonReport implements ReportFormat { public function render(array $d): string { return "JSON: " . json_encode($d); } }

class ReportGenerator
{
    public function generate(ReportFormat $format, array $data): string
    {
        return $format->render($data);  // No flag, no if/switch — pure polymorphism ✓
    }
}

$gen = new ReportGenerator();
echo "  " . $gen->generate(new PdfReport(),  ['Sales', 'Q1']) . "\n";
echo "  " . $gen->generate(new CsvReport(),  ['Sales', 'Q1']) . "\n";
echo "  " . $gen->generate(new JsonReport(), ['Sales', 'Q1']) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the difference between coupling and cohesion?       │
 * │ A: Coupling measures how dependent classes are on each other.  │
 * │    Low coupling = easier to change/test one without affecting   │
 * │    others. Cohesion measures how focused a class's purpose is.  │
 * │    High cohesion = single, clear responsibility. Goal: LOW      │
 * │    coupling + HIGH cohesion.                                    │
 * │                                                                  │
 * │ Q2: How do you reduce coupling in PHP code?                     │
 * │ A: 1) Program to interfaces, not concrete classes.             │
 * │    2) Use Dependency Injection — receive deps, don't create them│
 * │    3) Apply DIP (Dependency Inversion Principle).              │
 * │    4) Use events/observers to decouple event source from handler│
 * │    5) Avoid global state (global vars, static everywhere).     │
 * │                                                                  │
 * │ Q3: How do you identify low cohesion in code review?           │
 * │ A: Red flags: "Manager", "Helper", "Utils" class names.        │
 * │    Methods that operate on unrelated data.                      │
 * │    Classes with 10+ public methods.                             │
 * │    Changes in one feature always require editing the same file. │
 * │                                                                  │
 * │ KEY TAKEAWAYS:                                                   │
 * │ ✓ Low coupling + High cohesion = maintainable design.         │
 * │ ✓ Interfaces are the primary tool for loose coupling.          │
 * │ ✓ "God classes" violate both coupling and cohesion.            │
 * │ ✓ These metrics are HOW you evaluate if SOLID is working.      │
 * └─────────────────────────────────────────────────────────────────┘
 */
