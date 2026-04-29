# J8 — Design a Notification System

> **Section:** Case Studies | **Difficulty:** Medium | **Interview Frequency:** ★★★★☆

---

## 1. Problem Statement

Design a large-scale notification system that can send push notifications, emails, and SMS.

**Functional Requirements:**
- Send notifications via: Push (iOS APNS / Android FCM), Email, SMS
- Notification types: transactional (order confirmed), marketing (sale!), system alerts
- User preferences: users can opt-out of marketing, set quiet hours
- Notification templates with variable substitution

**Non-Functional Requirements:**
- 1B users, 10M notifications/day (transactional), 1B notifications/day (marketing campaigns)
- Transactional: deliver within 1 second
- Marketing: best-effort, deliver within 1 hour
- Deduplication: same notification not delivered twice
- Rate limiting: max 10 notifications/user/day (marketing)

---

## 2. Capacity Estimation

```
Transactional: 10M / 86400 = ~116/sec (peak: ~1,160/sec) -- small, fast
Marketing burst: 1B in 1 hour = ~278K/sec peak -- needs queuing + fan-out

Push notifications: 60% users = 600M push devices
Email: 80% users = 800M emails
SMS: 20% users = 200M SMS (most expensive)

Third-party APIs:
  APNS: Apple processes ~10B notifications/day (we're small to them)
  FCM: Google, same scale
  Email (SendGrid): rate limit ~1K emails/sec per account -> need multiple accounts
  SMS (Twilio): $0.0075 per SMS -> 200M SMS/day = $1.5M/day -- expensive!
```

---

## 3. High-Level Design

```
[Event Sources]
  - Order Service:   POST /notifications {user_id, type: "order_confirmed", data: {...}}
  - Marketing:       POST /campaigns {template_id, user_segment, schedule_time}
  - System:          POST /alerts {severity, message}

[Notification Service API]
         |
         v
  [Validation + User Preferences Check]
  (Is user opted in? Quiet hours? Rate limit reached?)
         |
         v
  [Kafka Topics]
  - notifications.push.high    (transactional push)
  - notifications.push.low     (marketing push)
  - notifications.email.high
  - notifications.email.low
  - notifications.sms
         |
         v
[Channel Workers] (separate consumer groups per channel)
         |
  +------+-------+------+
  |              |      |
[Push Worker] [Email Worker] [SMS Worker]
  |              |      |
[APNS/FCM]  [SendGrid]  [Twilio]
         |
[Delivery Tracker DB] (status: SENT, DELIVERED, FAILED, OPENED)
```

---

## 4. User Preference & Rate Limiting

```php
class NotificationFilter {
    public function shouldSend(int $userId, string $type, string $channel): bool {
        // 1. Check opt-out (user disabled this channel/type)
        $prefs = $this->getUserPreferences($userId);
        if (!($prefs[$channel][$type] ?? true)) {
            return false;  // User opted out
        }

        // 2. Check quiet hours
        $userTz   = $prefs['timezone'] ?? 'UTC';
        $localHour = (new \DateTimeImmutable('now', new \DateTimeZone($userTz)))->format('H');
        if ($type === 'marketing' && ($localHour >= 22 || $localHour < 8)) {
            // Delay marketing notifications, don't drop them
            $this->scheduleForMorning($userId, $type, $channel);
            return false;
        }

        // 3. Rate limit (marketing: max 10/day per user)
        if ($type === 'marketing') {
            $key   = "notif:rate:{$userId}:" . date('Y-m-d');
            $count = $this->redis->incr($key);
            $this->redis->expire($key, 86400);

            if ($count > 10) {
                return false;  // Over daily limit -- drop marketing, not transactional
            }
        }

        return true;
    }
}
```

---

## 5. Template Engine

```php
// Notification templates stored in DB:
// Template: "Hi {{first_name}}, your order #{{order_id}} has shipped!"
// Data:     { "first_name": "Wasim", "order_id": "98765" }

class TemplateRenderer {
    public function render(string $templateId, array $variables): string {
        $template = $this->cache->remember(
            "template:{$templateId}",
            3600,  // cache 1 hour
            fn() => $this->db->find('notification_templates', $templateId)
        );

        // Safe variable substitution (no eval, no user-supplied templates)
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            fn($match) => htmlspecialchars($variables[$match[1]] ?? '', ENT_QUOTES),
            $template['body']
        );
    }
}

// Multi-language support:
// Templates keyed by (template_id, locale): "order_shipped:en", "order_shipped:ar"
// Fallback to "en" if locale not found
```

---

## 6. Deduplication

```php
// Problem: Kafka consumer may process same event twice (at-least-once delivery)
// Solution: idempotency key per notification

// Before sending: check if already sent
$idempotencyKey = "notif:sent:{$notificationId}:{$channel}";
$alreadySent    = $this->redis->set($idempotencyKey, 1, ['NX', 'EX' => 86400]);

if (!$alreadySent) {
    $this->logger->info("Duplicate notification skipped: {$notificationId}");
    return;  // Already sent within 24 hours
}

// Proceed to send via APNS/FCM/SendGrid/Twilio
```

---

## 7. Delivery Tracking & Analytics

```sql
-- PostgreSQL
CREATE TABLE notification_deliveries (
    id              BIGINT PRIMARY KEY,        -- Snowflake
    notification_id BIGINT,
    user_id         BIGINT,
    channel         VARCHAR(10),               -- push, email, sms
    status          VARCHAR(20) DEFAULT 'PENDING', -- SENT, DELIVERED, FAILED, OPENED
    sent_at         TIMESTAMP,
    delivered_at    TIMESTAMP,
    opened_at       TIMESTAMP,
    provider_msg_id VARCHAR(200),              -- FCM message ID, SendGrid ID
    error_message   TEXT
);

-- Analytics queries:
-- Delivery rate per campaign: SELECT COUNT(*) WHERE status='DELIVERED' / total
-- Open rate: opened_at IS NOT NULL
-- Failure breakdown: GROUP BY error_message
```

---

## 8. Handling Third-Party API Failures

```
APNS/FCM/Twilio failures:
  HTTP 429 (rate limit): back off + retry via DLQ
  HTTP 400 (invalid token): remove device token from DB, don't retry
  HTTP 5xx (provider down): retry with exponential backoff (1s, 2s, 4s, max 3 retries)
  Timeout: retry

Device token management:
  FCM returns "NotRegistered" -> user uninstalled app -> delete device token
  If email bounces (SendGrid webhook): mark email as invalid, stop sending

Failed notification lifecycle:
  PENDING -> SENT -> (webhook from provider) -> DELIVERED
  PENDING -> FAILED (after 3 retries) -> DLQ -> alert + manual review
```

---

## 9. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Priority | Separate Kafka topics (high/low) | Transactional not delayed by marketing burst |
| Rate limit | Redis INCR with daily TTL key | Atomic, distributed, auto-expires |
| Dedup | Redis NX key per notification | Prevents double-send on Kafka retry |
| Marketing burst | Kafka + consumer groups | Smooth 278K/sec burst over time |
| Device tokens | DB + sync on FCM error | Remove invalid tokens to save API calls |

---

## 10. Interview Q&A

**Q: How do you handle a marketing campaign that sends 1B notifications at once?**
> Don't send all at once. The campaign scheduling service segments users into batches of 100K and enqueues them to the low-priority Kafka topic over time (e.g., spread over 4 hours). Consumer workers process at a controlled rate matching third-party API rate limits. This prevents overwhelming APNS/FCM/SendGrid and allows backpressure if a provider slows down. Priority topics ensure transactional notifications (password reset, order confirmation) always jump ahead of marketing batch.

**Q: How do you implement "do not disturb" / quiet hours across time zones?**
> Store each user's timezone preference in their profile. At notification dispatch time, check local time in their zone. If in quiet hours (e.g., 10pm-8am): for transactional = send anyway (important); for marketing = delay and add to a scheduled queue (Redis sorted set with score = next allowed send timestamp). A scheduler process polls this sorted set every minute and re-enqueues ready notifications to Kafka.

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| Separate Kafka topics for priority = transactional never blocked |
| Rate limit marketing with Redis INCR daily key = simple + fast   |
| Redis NX idempotency key = prevent duplicates on Kafka retry     |
| Quiet hours = delay (not drop) marketing notifications           |
| Remove invalid tokens on FCM/APNS error = save API quota         |
+--------------------------------------------------------------------+
```
