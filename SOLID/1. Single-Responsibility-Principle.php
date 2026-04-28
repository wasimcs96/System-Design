<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║         SOLID PRINCIPLE #1 — SINGLE RESPONSIBILITY (SRP)         ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  ACRONYM    : S in SOLID                                         ║
 * ║  DIFFICULTY : Easy                                               ║
 * ║  FREQUENCY  : ★★★★★ (Most commonly asked SOLID question)        ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DEFINITION                                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ "A class should have only ONE reason to change."                │
 * │                                                                  │
 * │ This means every class/module/function should do ONE thing and  │
 * │ do it well. If a class needs to be changed for two DIFFERENT     │
 * │ reasons, it has two responsibilities — split it.                 │
 * │                                                                  │
 * │ WHY IT MATTERS:                                                  │
 * │  • A change to one responsibility may unexpectedly break another │
 * │  • Large multi-responsibility classes become hard to test        │
 * │  • Teams can work on separate classes in parallel                │
 * │  • Easier to find where a bug lives when each class does one job │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM — BEFORE vs AFTER                                  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ❌ BEFORE (Violates SRP)                                        │
 * │  ┌─────────────────────────────────┐                            │
 * │  │           Order                 │                            │
 * │  │  + calculateTotal()             │ ← business logic           │
 * │  │  + validateOrder()              │ ← validation               │
 * │  │  + saveToDatabase()             │ ← persistence              │
 * │  │  + sendConfirmationEmail()      │ ← notification             │
 * │  │  + generateInvoicePdf()         │ ← reporting                │
 * │  └─────────────────────────────────┘                            │
 * │    5 reasons to change = 5 responsibilities                      │
 * │                                                                  │
 * │  ✅ AFTER (Follows SRP)                                          │
 * │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
 * │  │    Order     │  │OrderValidator│  │OrderRepository│          │
 * │  │+ getTotal()  │  │+ validate()  │  │+ save()       │          │
 * │  └──────────────┘  └──────────────┘  └──────────────┘          │
 * │  ┌──────────────┐  ┌──────────────┐                             │
 * │  │OrderNotifier │  │InvoiceService│                             │
 * │  │+ sendEmail() │  │+ generatePdf()│                            │
 * │  └──────────────┘  └──────────────┘                             │
 * │    Each class has ONE reason to change                           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ HOW TO IDENTIFY SRP VIOLATIONS                                   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: List ALL responsibilities of the class                   │
 * │ STEP 2: Ask "Why would this class change?" — multiple answers   │
 * │         = multiple responsibilities = SRP violation              │
 * │ STEP 3: Group responsibilities into cohesive clusters            │
 * │ STEP 4: Extract each cluster into its own class                  │
 * │ STEP 5: Original class becomes a thin orchestrator (optional)    │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// ❌ VIOLATION: God class doing everything — User manages itself,
//    persists itself, AND sends emails.
// ═══════════════════════════════════════════════════════════════
class BadUser
{
    private string $name;
    private string $email;
    private string $passwordHash;

    public function __construct(string $name, string $email, string $password)
    {
        $this->name         = $name;
        $this->email        = $email;
        // Responsibility 1: Business logic (password hashing)
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function getName(): string  { return $this->name; }
    public function getEmail(): string { return $this->email; }

    // Responsibility 2: Persistence — SHOULD NOT be here
    public function saveToDatabase(): void
    {
        // Directly talks to DB: now if DB changes, User must change
        echo "  [BadUser] INSERT INTO users (name, email, password_hash) VALUES...\n";
    }

    // Responsibility 3: Notification — SHOULD NOT be here
    public function sendWelcomeEmail(): void
    {
        echo "  [BadUser] Sending welcome email to {$this->email}\n";
    }

    // Responsibility 4: Report generation — SHOULD NOT be here
    public function generateProfilePdf(): string
    {
        return "  [BadUser] PDF profile for {$this->name}";
    }
}
// PROBLEM: If the DB schema changes, User changes.
//          If email template changes, User changes.
//          If PDF format changes, User changes.
//          = 4 separate reasons to change.

echo "=== SINGLE RESPONSIBILITY PRINCIPLE ===\n\n";
echo "--- ❌ SRP Violation ---\n";
$badUser = new BadUser("John", "john@example.com", "secret");
$badUser->saveToDatabase();
$badUser->sendWelcomeEmail();
echo $badUser->generateProfilePdf() . "\n";

// ═══════════════════════════════════════════════════════════════
// ✅ CORRECT: Each class has ONE responsibility
// ═══════════════════════════════════════════════════════════════

// Responsibility 1: Domain Model — represents a User + core logic ONLY
class User
{
    private string $passwordHash;

    public function __construct(
        private string $name,
        private string $email,
        string         $password
    ) {
        // Hashing password IS the user's responsibility (it's about its own data)
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function getName(): string         { return $this->name; }
    public function getEmail(): string        { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }

    // Changing reason: ONLY if User's core data/behavior changes
}

// Responsibility 2: Persistence — knows how to store/retrieve User from DB
class UserRepository
{
    // Reason to change: DB schema changes, ORM changes, query optimization
    public function save(User $user): void
    {
        echo "  [UserRepository] INSERT INTO users (name, email, password_hash)\n";
        echo "    Values: ({$user->getName()}, {$user->getEmail()}, ...hash...)\n";
    }

    public function findByEmail(string $email): ?User
    {
        echo "  [UserRepository] SELECT * FROM users WHERE email='$email'\n";
        return null; // Simulated
    }

    public function delete(int $userId): void
    {
        echo "  [UserRepository] DELETE FROM users WHERE id=$userId\n";
    }
}

// Responsibility 3: Notification — knows how to send emails
class UserNotificationService
{
    // Reason to change: email provider changes, template format changes
    public function sendWelcomeEmail(User $user): void
    {
        echo "  [EmailService] Sending welcome email to: {$user->getEmail()}\n";
        echo "    Subject: Welcome {$user->getName()}!\n";
    }

    public function sendPasswordResetEmail(User $user, string $token): void
    {
        echo "  [EmailService] Sending reset link to: {$user->getEmail()}\n";
        echo "    Token: $token\n";
    }
}

// Responsibility 4: Report — knows how to generate documents about Users
class UserReportService
{
    // Reason to change: PDF format changes, layout changes
    public function generateProfilePdf(User $user): string
    {
        return "  [ReportService] PDF generated for {$user->getName()} ({$user->getEmail()})";
    }
}

// Thin orchestrator (Application Service) — coordinates; does NOT add logic
class UserRegistrationService
{
    public function __construct(
        private UserRepository          $repo,
        private UserNotificationService $notifier,
        private UserReportService       $reporter
    ) {}

    public function register(string $name, string $email, string $password): User
    {
        $user = new User($name, $email, $password);
        $this->repo->save($user);
        $this->notifier->sendWelcomeEmail($user);
        return $user;
    }
}

echo "\n--- ✅ SRP Compliant ---\n";

$repo    = new UserRepository();
$mailer  = new UserNotificationService();
$reporter = new UserReportService();

$service = new UserRegistrationService($repo, $mailer, $reporter);
$user    = $service->register("Jane Doe", "jane@example.com", "p@ssw0rd");
echo $reporter->generateProfilePdf($user) . "\n";

// ─── REAL-WORLD EXAMPLE: E-Commerce Order Processing ─────────────────────────

echo "\n--- Real-World: Order Processing ---\n";

// Each class has exactly ONE reason to change:

class OrderItem
{
    public function __construct(
        public readonly string $productName,
        public readonly float  $price,
        public readonly int    $quantity
    ) {}

    public function subtotal(): float { return $this->price * $this->quantity; }
}

class Order
{
    private array $items = [];

    public function __construct(public readonly string $customerId) {}

    public function addItem(OrderItem $item): void    { $this->items[] = $item; }
    public function getItems(): array                  { return $this->items; }

    // Core business logic — ONLY reason to change: pricing rules
    public function getTotal(): float
    {
        return array_sum(array_map(fn($i) => $i->subtotal(), $this->items));
    }
}

// Reason to change: DB schema/ORM
class OrderRepository
{
    public function save(Order $order): int
    {
        echo "  [OrderRepo] Saving order for customer {$order->customerId}\n";
        return rand(1000, 9999); // Simulated order ID
    }
}

// Reason to change: validation rules change
class OrderValidator
{
    public function validate(Order $order): void
    {
        if (empty($order->getItems())) {
            throw new \InvalidArgumentException("Order must have at least one item.");
        }
        if ($order->getTotal() <= 0) {
            throw new \InvalidArgumentException("Order total must be positive.");
        }
        echo "  [OrderValidator] Order valid ✓\n";
    }
}

// Reason to change: email provider/template
class OrderNotificationService
{
    public function sendConfirmation(Order $order, int $orderId): void
    {
        echo "  [Notifier] Confirmation sent for Order #$orderId\n";
        echo "    Total: ₹" . number_format($order->getTotal(), 2) . "\n";
    }
}

// Orchestrator
$order = new Order("CUST-001");
$order->addItem(new OrderItem("Laptop",    75000.00, 1));
$order->addItem(new OrderItem("USB Mouse",  1200.00, 2));

$validator = new OrderValidator();
$validator->validate($order);

$orderRepo = new OrderRepository();
$orderId   = $orderRepo->save($order);

$orderNotifier = new OrderNotificationService();
$orderNotifier->sendConfirmation($order, $orderId);

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: Define Single Responsibility Principle in one sentence.      │
 * │ A: A class should have only one reason to change — it should    │
 * │    encapsulate exactly one responsibility.                       │
 * │                                                                  │
 * │ Q2: What happens if you violate SRP?                             │
 * │ A: • Changes to one feature risk breaking unrelated features.   │
 * │    • Class grows large, hard to understand and test.             │
 * │    • Multiple teams modifying same class → merge conflicts.      │
 * │    • Unit testing is harder (must mock unrelated dependencies).  │
 * │                                                                  │
 * │ Q3: SRP applies only to classes?                                 │
 * │ A: No. SRP applies at every level: functions (do one thing),    │
 * │    modules, microservices (each service owns one domain).        │
 * │                                                                  │
 * │ Q4: How do you identify when a class violates SRP?               │
 * │ A: Ask "for how many different reasons could this class change?" │
 * │    If the answer is more than one → SRP violation.               │
 * │    Also: classes with "And" in their purpose description often   │
 * │    violate SRP. ("manages users AND sends emails")               │
 * │                                                                  │
 * │ Q5: Can SRP lead to too many tiny classes?                       │
 * │ A: Yes — over-applying SRP leads to class explosion. The goal is │
 * │    cohesion: group things that change for the same reason, split │
 * │    things that change for different reasons.                     │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Thin Orchestrators: Having a UserRegistrationService that      │
 * │   calls save+email is fine — it's coordinating, not DOING the   │
 * │   work. Its one responsibility is "register a user".             │
 * │ ✓ Helper methods in same class: private formatting/conversion   │
 * │   helpers that serve the class's ONE responsibility are OK.      │
 * └─────────────────────────────────────────────────────────────────┘
 */
