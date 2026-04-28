<?php
/**
 * C8. MESSAGE QUEUE (like SQS simplified)
 * ============================================================
 * PROBLEM: Async message passing between producers and consumers
 * with acknowledgement, dead-letter queue, and message visibility.
 *
 * PATTERNS:
 *  - Producer-Consumer : Decoupled via queue
 *  - Observer          : Dead-letter notification
 * ============================================================
 */

enum MessageStatus: string { case PENDING='Pending'; case IN_FLIGHT='InFlight'; case ACK='Acknowledged'; case DLQ='DeadLetter'; }

// ─── Message ──────────────────────────────────────────────────
class Message {
    public readonly string    $messageId;
    public readonly \DateTime $sentAt;
    public MessageStatus      $status     = MessageStatus::PENDING;
    public float              $visibleAt  = 0;   // Timestamp when visible again
    public int                $receiveCount = 0;

    public function __construct(
        public readonly string $body,
        public readonly array  $attributes = []
    ) {
        $this->messageId = uniqid('MSG-');
        $this->sentAt    = new \DateTime();
        $this->visibleAt = microtime(true);
    }
}

// ─── Dead Letter Queue Notifier ───────────────────────────────
interface DLQHandler {
    public function handle(Message $msg, string $queueName): void;
}

class LogDLQHandler implements DLQHandler {
    public function handle(Message $msg, string $queueName): void {
        echo "  ⚠️  DLQ [{$queueName}]: Msg {$msg->messageId} moved (body={$msg->body})\n";
    }
}

// ─── Queue ────────────────────────────────────────────────────
class MessageQueue {
    /** @var Message[] */
    private array      $messages         = [];
    private ?DLQHandler $dlqHandler       = null;
    private MessageQueue|null $dlq        = null;

    public function __construct(
        public readonly string $queueName,
        private int            $maxReceives        = 3,     // Max delivery attempts
        private float          $visibilityTimeout  = 30.0   // Seconds
    ) {}

    public function setDLQ(MessageQueue $dlq, DLQHandler $handler): void {
        $this->dlq        = $dlq;
        $this->dlqHandler = $handler;
    }

    // ─── Producer: Send ───────────────────────────────────────
    public function send(string $body, array $attributes = []): Message {
        $msg = new Message($body, $attributes);
        $this->messages[] = $msg;
        echo "  [Q:{$this->queueName}] Sent: {$msg->messageId} | {$body}\n";
        return $msg;
    }

    // ─── Consumer: Receive (makes invisible temporarily) ──────
    public function receive(int $maxMessages = 1): array {
        $now      = microtime(true);
        $received = [];
        foreach ($this->messages as $msg) {
            if (count($received) >= $maxMessages) break;
            if ($msg->status === MessageStatus::PENDING && $msg->visibleAt <= $now) {
                $msg->status      = MessageStatus::IN_FLIGHT;
                $msg->visibleAt   = $now + $this->visibilityTimeout;
                $msg->receiveCount++;
                $received[]       = $msg;
            }
        }
        return $received;
    }

    // ─── Consumer: Acknowledge (delete from queue) ────────────
    public function ack(string $messageId): void {
        foreach ($this->messages as $i => $msg) {
            if ($msg->messageId === $messageId) {
                $msg->status = MessageStatus::ACK;
                unset($this->messages[$i]);
                echo "  [Q:{$this->queueName}] ACK: {$messageId}\n";
                return;
            }
        }
    }

    // ─── Consumer: NACK (make visible again or send to DLQ) ───
    public function nack(string $messageId): void {
        foreach ($this->messages as $i => $msg) {
            if ($msg->messageId !== $messageId) continue;
            if ($msg->receiveCount >= $this->maxReceives) {
                // Move to DLQ
                $msg->status = MessageStatus::DLQ;
                unset($this->messages[$i]);
                if ($this->dlqHandler) $this->dlqHandler->handle($msg, $this->queueName);
                if ($this->dlq) $this->dlq->messages[] = $msg;
            } else {
                // Return to visible pool
                $msg->status  = MessageStatus::PENDING;
                $msg->visibleAt = microtime(true) + 1; // Brief delay before retry
                echo "  [Q:{$this->queueName}] NACK: {$messageId} (attempt {$msg->receiveCount}/{$this->maxReceives})\n";
            }
            return;
        }
    }

    public function size(): int {
        return count(array_filter($this->messages, fn($m) => $m->status !== MessageStatus::DLQ));
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C8. Message Queue (SQS-like) ===\n\n";

$dlq   = new MessageQueue('order-queue-dlq');
$queue = new MessageQueue('order-queue', 3, 5.0);
$queue->setDLQ($dlq, new LogDLQHandler());

// Producer sends messages
$m1 = $queue->send('{"orderId":"ORD-001","amount":500}');
$m2 = $queue->send('{"orderId":"ORD-002","amount":1200}');

echo "\n--- Consumer receives and acks ---\n";
$msgs = $queue->receive(2);
foreach ($msgs as $msg) {
    echo "  Processing: {$msg->body}\n";
    $queue->ack($msg->messageId);  // Successful processing
}

echo "\n--- Failing message → DLQ ---\n";
$m3 = $queue->send('{"orderId":"ORD-003","amount":bad}');
for ($i = 0; $i < 4; $i++) {
    $msgs = $queue->receive();
    foreach ($msgs as $msg) {
        echo "  Processing failed: {$msg->body}\n";
        $queue->nack($msg->messageId);  // Simulate failure
    }
}
echo "Queue size: {$queue->size()} | DLQ size: {$dlq->size()}\n";
