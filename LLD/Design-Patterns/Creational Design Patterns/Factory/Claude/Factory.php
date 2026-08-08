<?php

/**
 * Factory.php
 * ---------------------------------------------------------------------------
 * Companion runnable code file for Factory-Design-Pattern-Guide.md.
 *
 * Progression (mirrors Part 19's Refactoring Journey):
 *   Stage 1 — Terrible: repeated inline type-branching conditionals
 *   Stage 2 — Simple Factory: one centralized method, still not GoF Factory Method
 *   Stage 3 — Broken Factory Method: uses `new self()` instead of `new static()`
 *             (Part 18's flagship bug — included deliberately, then fixed)
 *   Stage 4 — Correct Factory Method: NotificationCreator hierarchy, `new static()`,
 *             identity + behavioral-contract tests
 *   Stage 5 — Extending without touching existing code: adding WhatsApp
 *
 * Run with: php Factory.php
 * No framework dependency required.
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

echo "=== Factory Method Design Pattern — companion code ===\n\n";


/* =============================================================================
 * Shared Product interface used by every stage below.
 * =============================================================================
 */
interface Notification
{
    public function send(string $message): string; // returns a description of what happened, for demo purposes
}

final class EmailNotification implements Notification
{
    public function send(string $message): string
    {
        return "[Email] sent: {$message}";
    }
}

final class SmsNotification implements Notification
{
    public function send(string $message): string
    {
        return "[SMS] sent: {$message}";
    }
}

final class PushNotification implements Notification
{
    public function send(string $message): string
    {
        return "[Push] sent: {$message}";
    }
}


/* =============================================================================
 * STAGE 1 — Terrible: repeated inline conditionals.
 * =============================================================================
 * This exact block (or something close to it) tends to get copy-pasted into
 * every place a notification needs to be created. Shown here as a single
 * function standing in for "many near-identical copies across the codebase."
 */
function stage1_sendNotificationInline(string $channel, string $message): string
{
    if ($channel === 'email') {
        $n = new EmailNotification();
    } elseif ($channel === 'sms') {
        $n = new SmsNotification();
    } elseif ($channel === 'push') {
        $n = new PushNotification();
    } else {
        throw new \InvalidArgumentException("Unsupported channel: {$channel}");
    }
    return $n->send($message);
}


/* =============================================================================
 * STAGE 2 — Simple Factory (NOT the GoF Factory Method pattern).
 * =============================================================================
 * Genuinely better than Stage 1, and often sufficient on its own. Centralizes
 * the type decision in one place. Still just a static method with a match
 * expression — no subclassing, no polymorphic override.
 */
final class SimpleNotificationFactory
{
    public static function make(string $channel): Notification
    {
        return match ($channel) {
            'email' => new EmailNotification(),
            'sms' => new SmsNotification(),
            'push' => new PushNotification(),
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }
}


/* =============================================================================
 * STAGE 3 — Broken Factory Method: `self` vs `static` (Part 18's flagship bug).
 * =============================================================================
 * BrokenNotificationCreator uses `new self()` in a method that a subclass
 * inherits WITHOUT overriding. Every subclass that relies on inheriting this
 * method silently ends up constructing the BASE class instead of itself.
 *
 * This class is deliberately kept in the file, still runnable, so the bug
 * and its fix (Stage 4) can be demonstrated side by side rather than just
 * described.
 */
class BrokenNotificationCreator
{
    // BUG: `new self()` always resolves to BrokenNotificationCreator,
    // regardless of which subclass this method is actually called on.
    public static function create(): self
    {
        return new self();
    }
}

final class BrokenEmailNotificationCreator extends BrokenNotificationCreator
{
    // Deliberately NOT overriding create() — relying on inheritance,
    // which is exactly what triggers the bug.
}


/* =============================================================================
 * STAGE 4 — Correct Factory Method.
 * =============================================================================
 * A real Creator/ConcreteCreator hierarchy. The shared `notify()` method is
 * written entirely against the Notification interface; each ConcreteCreator
 * only needs to supply createNotification(). Uses `new static()` so that
 * inheriting (not overriding) still resolves to the correct subclass.
 */
abstract class NotificationCreator
{
    // The factory method — each ConcreteCreator overrides this.
    abstract public function createNotification(): Notification;

    // Ordinary method, written entirely against the Notification interface.
    // Never needs to know which concrete class createNotification() returned.
    public function notify(string $message): string
    {
        $notification = $this->createNotification();
        return $notification->send($message);
    }

    // FIXED version of Stage 3's bug: `new static()` resolves to whichever
    // class this method is actually called on, even if a subclass inherits
    // it without overriding it.
    public static function create(): static
    {
        return new static();
    }
}

final class EmailNotificationCreator extends NotificationCreator
{
    public function createNotification(): Notification
    {
        return new EmailNotification();
    }
}

final class SmsNotificationCreator extends NotificationCreator
{
    public function createNotification(): Notification
    {
        return new SmsNotification();
    }

    // Example of the "more than just creation varies" justification from
    // Part 12's ADR: SMS gets its own retry policy, which is exactly the
    // kind of per-type behavioral variation a Simple Factory can't express
    // as cleanly as a ConcreteCreator subclass can.
    public function sendWithRetry(string $message, int $maxAttempts = 3): string
    {
        // Simplified for demo purposes — a real implementation would retry
        // on actual delivery failure.
        return $this->notify($message) . " (retry policy: up to {$maxAttempts} attempts)";
    }
}

final class PushNotificationCreator extends NotificationCreator
{
    public function createNotification(): Notification
    {
        return new PushNotification();
    }
}


/* =============================================================================
 * STAGE 5 — Extending without touching existing code: adding WhatsApp.
 * =============================================================================
 * New product + new creator. Nothing above this point is modified.
 */
final class WhatsAppNotification implements Notification
{
    public function send(string $message): string
    {
        return "[WhatsApp] sent: {$message}";
    }
}

final class WhatsAppNotificationCreator extends NotificationCreator
{
    public function createNotification(): Notification
    {
        return new WhatsAppNotification();
    }
}


/* =============================================================================
 * Driver code — demonstrates and asserts every stage above.
 * =============================================================================
 */

function assertTrue(bool $condition, string $message): void
{
    echo ($condition ? "[PASS] " : "[FAIL] ") . $message . "\n";
}

echo "--- Stage 1: repeated inline conditionals ---\n";
echo stage1_sendNotificationInline('email', 'Hello via inline Stage 1') . "\n\n";

echo "--- Stage 2: Simple Factory (not GoF Factory Method) ---\n";
$n = SimpleNotificationFactory::make('sms');
echo $n->send('Hello via Simple Factory') . "\n\n";

echo "--- Stage 3: BROKEN Factory Method (self vs static) ---\n";
$broken = BrokenEmailNotificationCreator::create();
assertTrue(
    get_class($broken) === BrokenNotificationCreator::class,
    "BUG REPRODUCED: BrokenEmailNotificationCreator::create() returned a " . get_class($broken) . " instead of BrokenEmailNotificationCreator"
);
echo "\n";

echo "--- Stage 4: CORRECT Factory Method (new static()) ---\n";
$emailCreator = new EmailNotificationCreator();
$smsCreator = new SmsNotificationCreator();
$pushCreator = new PushNotificationCreator();

echo $emailCreator->notify('Your order shipped') . "\n";
echo $smsCreator->notify('Your OTP is 482913') . "\n";
echo $pushCreator->notify('You have a new message') . "\n";
echo $smsCreator->sendWithRetry('Retry-aware SMS') . "\n";

// The fix, proven: create() correctly resolves to the calling subclass now.
$fixed = SmsNotificationCreator::create();
assertTrue(
    get_class($fixed) === SmsNotificationCreator::class,
    "FIXED: SmsNotificationCreator::create() correctly returned " . get_class($fixed)
);

// Identity/behavioral-contract style tests from Part 18.
assertTrue($emailCreator->createNotification() instanceof EmailNotification, 'EmailNotificationCreator produces an EmailNotification');
assertTrue($smsCreator->createNotification() instanceof SmsNotification, 'SmsNotificationCreator produces an SmsNotification');
assertTrue($pushCreator->createNotification() instanceof PushNotification, 'PushNotificationCreator produces a PushNotification');
echo "\n";

echo "--- Stage 5: adding WhatsApp — zero existing files touched ---\n";
$whatsAppCreator = new WhatsAppNotificationCreator();
echo $whatsAppCreator->notify('Your appointment is tomorrow') . "\n";
assertTrue($whatsAppCreator->createNotification() instanceof WhatsAppNotification, 'WhatsAppNotificationCreator produces a WhatsAppNotification');
echo "\n";

echo "=== All stages demonstrated. ===\n";
