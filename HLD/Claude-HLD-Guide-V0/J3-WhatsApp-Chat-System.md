# J3 — Design WhatsApp / Chat System

> **Section:** Case Studies | **Difficulty:** Hard | **Interview Frequency:** ★★★★★

---

## 1. Problem Statement

Design a real-time messaging system like WhatsApp.

**Functional Requirements:**
- 1:1 messaging (text, images, video)
- Group messaging (up to 1,000 members)
- Message delivery receipts (sent / delivered / read)
- Online presence (last seen)
- Message history (persistent)

**Non-Functional Requirements:**
- 2B users, 100B messages/day
- Messages delivered in <100ms on good network
- Messages must never be lost (at-least-once delivery)
- End-to-end encryption
- 99.99% availability

---

## 2. Capacity Estimation

```
Messages: 100B / 86400 = ~1.16M messages/sec
Average message size: ~100 bytes (text) + metadata = ~200 bytes
Storage: 100B x 200 bytes = 20 TB/day
5-year storage: 20 TB x 365 x 5 = ~36 PB (use tiered storage + compression)

WebSocket connections: 2B users, 10% online at peak = 200M concurrent connections
  -> Need connection servers with WebSocket affinity
  -> Each connection server handles ~100K connections (10GB RAM / 50KB per WS)
  -> 200M / 100K = 2,000 connection servers
```

---

## 3. High-Level Design

```
[User A Phone]                              [User B Phone]
     |                                             |
     | WebSocket                                   | WebSocket
     |                                             |
[Connection Server A]                    [Connection Server B]
     |                                             |
     +---------> [Message Router / Kafka] <--------+
                          |
                 [Message Service]
                 /        |                [Message DB]  [Receipt DB]  [Notification Service]
        (Cassandra)   (Cassandra)    (APNS/FCM for offline users)

[Presence Service] -- tracks online/offline status
[User Service] -- stores user profiles, contacts
[Media Service] -- uploads to S3, CDN delivery
```

---

## 4. Message Delivery (Critical Path)

```
SCENARIO 1: Both users online
User A sends message to User B:

1. A -> Connection Server A (via WebSocket): { to: B, content: "hi", client_msg_id: "uuid" }
2. Connection Server A -> Message Service (sync HTTP or gRPC)
3. Message Service:
   a. Persist message to Cassandra (status: SENT)
   b. Return message_id to A: ack with SENT receipt
   c. Lookup: which Connection Server is B connected to? (via Presence Service / Redis)
   d. Route to Connection Server B
4. Connection Server B -> User B (via WebSocket)
5. B's client acks DELIVERED -> Message Service updates status
6. B reads message -> client sends READ receipt
```

```
SCENARIO 2: User B offline
1-3 same as above
4. Message Service: B is offline -> push notification via APNS/FCM
5. B comes online -> Connection Server pulls undelivered messages from Cassandra
6. Delivers messages in order, B sends DELIVERED receipts
```

---

## 5. Message Storage

```php
// Cassandra schema -- optimized for "fetch messages in a conversation"
// Partition by (sender, receiver) pair for 1:1 chats

// Conversation ID: deterministic from two user IDs (smaller_id:larger_id)
function conversationId(int $userA, int $userB): string {
    return min($userA, $userB) . ':' . max($userA, $userB);
}

// CREATE TABLE messages (
//   conversation_id TEXT,      -- partition key
//   message_id      BIGINT,    -- Snowflake ID (time-ordered), clustering key DESC
//   sender_id       BIGINT,
//   content         TEXT,
//   media_url       TEXT,
//   status          TINYINT,   -- 0=SENT, 1=DELIVERED, 2=READ
//   created_at      TIMESTAMP,
//   PRIMARY KEY (conversation_id, message_id)
// ) WITH CLUSTERING ORDER BY (message_id DESC);

// Fetch last 50 messages in a conversation:
// SELECT * FROM messages WHERE conversation_id = ? LIMIT 50

// Group chat: conversation_id = "group:{group_id}"
// Same schema works -- partition by group_id
```

---

## 6. Message Ordering & Exactly-Once Delivery

```
Problem: Network retries can cause duplicate messages

Solution:
  - Client generates idempotency key (UUID) per message
  - Message Service: INSERT ... IF NOT EXISTS (Cassandra LWT) on idempotency key
  - If duplicate: return existing message_id (idempotent)

Message ordering:
  - Snowflake IDs encode timestamp -> natural time order
  - Within same millisecond: use sequence number in Snowflake
  - Client-side: show messages sorted by message_id
  - Don't rely on created_at (clock skew between devices)

At-least-once delivery (WebSocket ack):
  - Connection Server: deliver message, start ack timer (5s)
  - If no ack from client: resend (client deduplicates by message_id)
```

---

## 7. Group Messaging

```
Group message fan-out:
  User A sends to group G (1,000 members)

  Option 1: Fan-out on write (write to each member's inbox)
    1,000 writes per message -- at 100K group msg/sec = 100M writes/sec
    Too heavy!

  Option 2: Fan-out on read (single write, members pull on open)
    Write once to messages table (conversation_id = "group:{group_id}")
    Each member fetches when they open the chat
    Used by: WhatsApp, Telegram (for large groups)

  Option 3: Hybrid
    Small groups (<= 100): fan-out on write (real-time delivery)
    Large groups (> 100): fan-out on read + push notification only

Unread count:
  Redis: INCR unread:{user_id}:{group_id} on new message
  Clear on open: DEL unread:{user_id}:{group_id}
```

---

## 8. Presence System

```php
// Online status: Redis with TTL
// Client sends heartbeat every 10s via WebSocket
// Server: SET presence:{user_id} 1 EX 15 (expires after 15s)
// Last seen: SET lastseen:{user_id} {timestamp} (no expiry)

// Query:
$isOnline  = (bool) $redis->get("presence:{$userId}");
$lastSeen  = $redis->get("lastseen:{$userId}");

// At scale: 2B users -- don't store all users in one Redis
// Shard by user_id % N across N Redis instances
// Or: use dedicated presence service with consistent hashing
```

---

## 9. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Protocol | WebSocket | Full-duplex, persistent, low overhead |
| Message storage | Cassandra | High write throughput, time-series |
| Fan-out (group) | Hybrid | Fan-out on read for large groups |
| Delivery guarantee | At-least-once + idempotency key | Never lose messages, handle retries |
| Offline delivery | APNS/FCM push notification | Battery-efficient on mobile |

---

## 10. Interview Q&A

**Q: How do you handle a user switching from WiFi to 4G mid-conversation?**
> The WebSocket connection drops. The client reconnects to a (possibly different) Connection Server. On reconnect, the client sends its last received message_id. The server fetches all messages after that ID from Cassandra and delivers them in order. The presence service is updated. This is why message storage in Cassandra (not just in memory) is critical -- messages must survive connection interruptions.

**Q: How do you ensure end-to-end encryption?**
> E2EE means the server never sees plaintext. Each device generates a public/private key pair. Public keys are stored on the server's key distribution service. Before first message, A fetches B's public key and encrypts with it. Only B's private key can decrypt. The server only stores ciphertext. For group chats: sender encrypts the message once per group member using their public key (or uses a shared group secret established via key agreement protocols like Signal's Sender Key protocol).

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| WebSocket = full-duplex persistent connection for real-time chat  |
| Cassandra partitioned by conversation_id = fast message history  |
| Snowflake IDs = time-ordered messages, deduplication key         |
| Hybrid fan-out: push for small groups, pull for large groups     |
| Offline: APNS/FCM; on reconnect: replay from last message_id     |
+--------------------------------------------------------------------+
```
