<?php
/**
 * B3. NOTIFICATION SYSTEM
 * ============================================================
 * PROBLEM: Multi-channel notification delivery (Email, SMS, Push)
 * with retry logic and user preferences.
 *
 * PATTERNS:
 *  - Strategy : NotificationChannel (Email, SMS, Push)
 *  - Observer : EventBus dispatches events to subscribers
 *  - Decorator: RetryNotification wraps a channel with retry logic
 * ============================================================
 */

enum NotificationType: string { case EMAIL='email'; case SMS='sms'; case PUSH='push'; }
enum NotificationPriority: int { case LOW=1; case NORMAL=2; case HIGH=3; }

// ─── Notification ──────────────────────────────────────────────
class Notification {
    public readonly string $id;
    public function __construct(
        public readonly string               $userId,
        public readonly string               $title,
        public readonly string               $body,
        public readonly NotificationType     $type,
        public readonly NotificationPriority $priority = NotificationPriority::NORMAL
    ) {
        $this->id = uniqid('N-');
    }
}

// ─── Channel Strategy ──────────────────────────────────────────
interface NotificationChannel {
    public function send(Notification $n): bool;
    public function getType(): NotificationType;
}

class EmailChannel implements NotificationChannel {
    public function send(Notification $n): bool {
        echo "  📧 Email → User:{$n->userId} | Subject:{$n->title}\n";
        return true;
    }
    public function getType(): NotificationType { return NotificationType::EMAIL; }
}

class SMSChannel implements NotificationChannel {
    public function send(Notification $n): bool {
        echo "  📱 SMS → User:{$n->userId} | Msg:{$n->body}\n";
        return true;
    }
    public function getType(): NotificationType { return NotificationType::SMS; }
}

class PushChannel implements NotificationChannel {
    public function send(Notification $n): bool {
        echo "  🔔 Push → User:{$n->userId} | {$n->title}: {$n->body}\n";
        return true;
    }
    public function getType(): NotificationType { return NotificationType::PUSH; }
}

// ─── Retry Decorator ──────────────────────────────────────────
class RetryNotificationChannel implements NotificationChannel {
    public function __construct(
        private NotificationChannel $inner,
        private int                 $maxRetries = 3
    ) {}

    public function send(Notification $n): bool {
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            if ($this->inner->send($n)) return true;
            echo "  ↺ Retry {$attempt}/{$this->maxRetries}...\n";
        }
        echo "  ✗ Failed after {$this->maxRetries} retries\n";
        return false;
    }
    public function getType(): NotificationType { return $this->inner->getType(); }
}

// ─── User Preferences ──────────────────────────────────────────
class UserPreferences {
    /** @var NotificationType[] */
    private array $enabledChannels = [];

    public function __construct(NotificationType ...$channels) {
        $this->enabledChannels = $channels;
    }
    public function isEnabled(NotificationType $type): bool {
        return in_array($type, $this->enabledChannels, true);
    }
}

// ─── Notification Service ──────────────────────────────────────
class NotificationService {
    /** @var array<string,NotificationChannel> type → channel */
    private array $channels = [];
    /** @var array<string,UserPreferences> userId → prefs */
    private array $userPrefs = [];

    public function registerChannel(NotificationChannel $channel): void {
        $this->channels[$channel->getType()->value] = $channel;
    }
    public function setUserPrefs(string $userId, UserPreferences $prefs): void {
        $this->userPrefs[$userId] = $prefs;
    }

    public function send(Notification $n): void {
        $prefs = $this->userPrefs[$n->userId] ?? null;
        $ch    = $this->channels[$n->type->value] ?? null;
        if (!$ch) { echo "  ✗ No channel registered for {$n->type->value}\n"; return; }
        if ($prefs && !$prefs->isEnabled($n->type)) {
            echo "  ✗ User {$n->userId} has disabled {$n->type->value}\n"; return;
        }
        $ch->send($n);
    }

    public function broadcast(Notification ...$notifications): void {
        foreach ($notifications as $n) $this->send($n);
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B3. Notification System ===\n\n";

$service = new NotificationService();
$service->registerChannel(new RetryNotificationChannel(new EmailChannel(), 3));
$service->registerChannel(new SMSChannel());
$service->registerChannel(new PushChannel());

$service->setUserPrefs('U001', new UserPreferences(NotificationType::EMAIL, NotificationType::PUSH));
$service->setUserPrefs('U002', new UserPreferences(NotificationType::SMS));

echo "--- U001 (Email + Push enabled) ---\n";
$service->send(new Notification('U001', 'Order Shipped!', 'Your order #123 shipped.', NotificationType::EMAIL));
$service->send(new Notification('U001', 'Order Shipped!', 'Your order #123 shipped.', NotificationType::SMS)); // Blocked

echo "\n--- U002 (SMS only) ---\n";
$service->send(new Notification('U002', 'OTP', '123456', NotificationType::SMS));
$service->send(new Notification('U002', 'OTP', '123456', NotificationType::EMAIL)); // Blocked
