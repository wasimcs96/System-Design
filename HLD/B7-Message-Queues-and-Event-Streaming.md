# B7 — Message Queues & Event Streaming

> **Section:** Core Infrastructure | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A message queue is like a postal system — you put a message in a mailbox, and another system picks it up later and processes it. This lets systems work asynchronously — the sender doesn't wait for the receiver.

**Technical:** Message queues provide asynchronous, decoupled communication between services. Traditional message queues (RabbitMQ, SQS) deliver each message to one consumer and delete after processing. Event streams (Kafka) are persistent, ordered, replayable logs that multiple consumer groups can read independently.

---

## 2. Real-World Analogy

**Traditional Queue (RabbitMQ/SQS):**
Like a restaurant's order printer. The waiter places an order (message); the kitchen (consumer) processes it; the order slip is torn off and discarded after completion. Multiple cooks share the same printer — each order goes to one cook.

**Event Stream (Kafka):**
Like a newspaper. Every copy is permanent. Each subscriber (consumer group) gets their own copy and reads at their own pace. You can go back and re-read yesterday's news. The newspaper is never discarded; it's retained for 7 days (retention period).

---

## 3. Visual Diagram

```
TRADITIONAL MESSAGE QUEUE:
Producer → [Queue] → Consumer 1 (processes, deletes)
                  → Consumer 2 (gets different message)
Message deleted after consumption — no replay

EVENT STREAM (KAFKA):
Producer → [Partition 0] → Consumer Group A (offset: 1500)
                        → Consumer Group B (offset: 1480) ← replaying
           [Partition 1] → Consumer Group A (offset: 2100)
           [Partition 2] → Consumer Group A (offset: 900)
Messages retained (default 7 days) — replay anytime

KAFKA ARCHITECTURE:
┌─────────────────────────────────────────────────────────────┐
│  Topic: "order-events" (3 partitions, replication factor 2) │
│                                                             │
│  Partition 0: [msg1][msg3][msg5] ──→ Broker 1 (leader)     │
│                                  ──→ Broker 2 (replica)    │
│  Partition 1: [msg2][msg4][msg6] ──→ Broker 2 (leader)     │
│                                  ──→ Broker 3 (replica)    │
│  Partition 2: [msg7][msg8][msg9] ──→ Broker 3 (leader)     │
│                                  ──→ Broker 1 (replica)    │
│                                                             │
│  Consumer Group "notifications": 3 consumers, 1 per part.  │
│  Consumer Group "analytics": 1 consumer, reads all partitions│
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Deep Technical Explanation

### Message Queue vs Event Stream

| Feature | Message Queue | Event Stream |
|---------|--------------|-------------|
| Message retention | Deleted after consumption | Retained (days/weeks) |
| Consumers | One consumer per message | Many consumer groups |
| Ordering | Per-queue, limited | Per-partition, guaranteed |
| Replay | No | Yes (seek to any offset) |
| Throughput | Moderate | Very high (millions/sec) |
| Use case | Task distribution | Event log, CDC, streaming |
| Tools | RabbitMQ, SQS | Kafka, Kinesis |

### RabbitMQ (AMQP)

**Exchange types:**
- **Direct:** Message routing key exactly matches queue binding key
- **Fanout:** Broadcast to all queues bound to exchange (pub-sub)
- **Topic:** Pattern matching (`orders.*`, `orders.#`)
- **Headers:** Route by message header attributes

**Dead Letter Queue (DLQ):** When a message is rejected, expires, or exceeds max retries, it's sent to DLQ. Used for manual inspection and reprocessing.

### Apache Kafka

**Key concepts:**
- **Topic:** A named, ordered, persistent log of events
- **Partition:** A topic split into N partitions for parallel processing. Messages within a partition are ordered; across partitions, no ordering guarantee
- **Offset:** Each message's position in a partition (starts at 0). Consumer tracks its own offset
- **Consumer Group:** Group of consumers sharing partitions. Each partition assigned to one consumer in the group. Adding consumers (up to partition count) = horizontal scaling
- **Retention:** Messages kept for N days regardless of consumption (default 7 days)
- **Log compaction:** Alternative to time-based retention — keep only latest value per key (useful for state snapshots)

**Producer configuration:**
- `acks=0`: Fire and forget (at-most-once, fastest, data loss possible)
- `acks=1`: Leader confirmed (default, fast, small data loss window)
- `acks=all`: All ISR replicas confirmed (at-least-once, slowest, most durable)

**Consumer configuration:**
- `enable.auto.commit=true`: Offset committed automatically (at-most-once risk)
- `enable.auto.commit=false` + manual commit after processing: at-least-once
- Exactly-once: Kafka transactions + idempotent producers

### Delivery Guarantees

| Guarantee | How | Risk |
|-----------|-----|------|
| **At-most-once** | Commit offset before processing | Message may be lost if consumer crashes |
| **At-least-once** | Commit offset after processing | Message may be processed twice (if crash after process but before commit) |
| **Exactly-once** | Idempotent producer + transactions | Complex, performance overhead |

**Idempotency:** Design consumers so processing the same message twice has the same result as processing it once. Use idempotency keys, database unique constraints, or conditional updates.

### The Outbox Pattern
**Problem:** How do you atomically write to DB AND publish an event?
```
// Naive (broken):
Order::create($data);          // If this succeeds but
event(new OrderCreated($id));  // this fails → inconsistent state
```
**Solution:** Write event to an outbox table in the same DB transaction, then a separate process reads the outbox and publishes to Kafka.
```php
DB::transaction(function() use ($data) {
    $order = Order::create($data);
    // Same transaction — either both committed or neither
    OutboxEvent::create(['topic' => 'orders', 'payload' => $order->toJson()]);
});
// Background job: polls OutboxEvent table, publishes to Kafka, marks as sent
```

---

## 5. Code Example

```php
// Kafka producer in PHP (using php-rdkafka)
class OrderEventProducer {
    private RdKafka\Producer $producer;
    
    public function __construct() {
        $conf = new RdKafka\Conf();
        $conf->set('metadata.broker.list', 'kafka:9092');
        $conf->set('acks', 'all');  // Wait for all ISR replicas
        $conf->set('enable.idempotence', 'true');  // Idempotent producer
        
        $this->producer = new RdKafka\Producer($conf);
    }
    
    public function publishOrderCreated(Order $order): void {
        $topic = $this->producer->newTopic('order-events');
        
        $topic->produce(
            RD_KAFKA_PARTITION_UA,  // Auto-assign partition
            0,
            json_encode([
                'event_type' => 'order.created',
                'order_id'   => $order->id,
                'user_id'    => $order->user_id,
                'total'      => $order->total,
                'created_at' => $order->created_at->toIso8601String(),
            ]),
            (string) $order->id  // Message key — ensures same order → same partition
        );
        
        $this->producer->flush(5000);  // Wait up to 5s for delivery
    }
}
```

```php
// Idempotent Kafka consumer — exactly-once semantics
class OrderCreatedConsumer {
    public function handle(array $message): void {
        $orderId = $message['order_id'];
        
        // Idempotency check: only process if not already processed
        // Use database unique constraint or processed-events table
        $alreadyProcessed = ProcessedEvent::where('event_id', $message['event_id'])->exists();
        if ($alreadyProcessed) {
            Log::info("Skipping duplicate event: {$message['event_id']}");
            return;
        }
        
        DB::transaction(function() use ($message, $orderId) {
            // Business logic
            $this->notificationService->sendOrderConfirmation($orderId);
            $this->inventoryService->reserveStock($orderId);
            
            // Mark event as processed within same transaction
            ProcessedEvent::create(['event_id' => $message['event_id']]);
        });
    }
}
```

---

## 6. Trade-offs

| Tool | Throughput | Ordering | Replay | Managed? | Best For |
|------|-----------|---------|--------|---------|----------|
| RabbitMQ | Medium | Per-queue | No | Self-hosted | Task queues, routing |
| SQS | High | FIFO option | No (DLQ only) | AWS managed | AWS apps, decoupling |
| Kafka | Very high | Per-partition | Yes | Self / Confluent | Event sourcing, CDC, streaming |
| Kinesis | High | Per-shard | Yes | AWS managed | AWS streaming analytics |

---

## 7. Interview Q&A

**Q1: What is the difference between Kafka and RabbitMQ?**
> RabbitMQ is a traditional message broker — messages are consumed and deleted. Best for task distribution, work queues, and complex routing. Kafka is a distributed event log — messages are persisted and retained (default 7 days), multiple consumer groups can independently replay events. Best for event sourcing, CDC, and high-throughput streaming. For a payment processing job queue: RabbitMQ. For an event-driven architecture where order events feed multiple services (analytics, inventory, notifications): Kafka.

**Q2: How does Kafka guarantee ordering?**
> Kafka guarantees ordering within a partition, not across partitions. All messages with the same key (e.g., user_id or order_id) go to the same partition, guaranteeing order for that key. To ensure all events for an order are processed in order: use order_id as the message key. Tradeoff: if you have hot keys (one order with millions of events), that partition becomes a bottleneck.

**Q3: What is the Outbox Pattern and why is it needed?**
> When a service updates a database and publishes an event, these are two separate operations — atomicity is not guaranteed. If the service crashes between DB write and event publish, downstream services never see the event. The Outbox pattern solves this: write the event to an `outbox` table in the same DB transaction as the business data. A separate process (CDC or polling) reads the outbox and publishes to Kafka. This guarantees at-least-once delivery with database-level transaction safety.

**Q4: How do you handle poison messages (messages that always fail)?**
> A poison message causes a consumer to crash repeatedly. With RabbitMQ: set max retry count; after N failures, route to Dead Letter Queue (DLQ) for manual inspection. With Kafka: use a retry topic — consumer sends to `orders-retry-1`, `orders-retry-2` with increasing backoff; after all retries, send to `orders-dead-letter`. Monitor DLQ/DLT size as an alert — it signals processing issues.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Queue: message consumed & deleted; Stream: retained & replayable │
│ ✓ Kafka: ordered per-partition, use key to route to same partition │
│ ✓ At-least-once = commit after processing + idempotent consumers  │
│ ✓ Outbox Pattern = atomic DB write + event publish                │
│ ✓ DLQ/DLT: catch unprocessable messages for manual review         │
│ ✓ Consumer group = horizontal scaling up to partition count       │
└────────────────────────────────────────────────────────────────────┘
```
