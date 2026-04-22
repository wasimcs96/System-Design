# Section 3: Amazon-Specific Interview Strategy

---

## 3.1 How Amazon HLD Interviews Are Conducted

### Interview Format (India & Malaysia)

**Typical interview loop for SDE-2/SDE-3:**
- 4–5 rounds total
- 1–2 rounds are dedicated system design (HLD)
- 1–2 rounds are LLD + coding
- 1 round is behavioral (Leadership Principles)
- 1 round is with Bar Raiser (can be any round type)

**HLD Round Duration:** 60 minutes
- 5 min: Introductions
- 5 min: Requirement clarification
- 40 min: Design + deep dives
- 10 min: Interviewer questions, wrap-up

**Interview format specifics (India — Hyderabad, Bangalore, Chennai):**
- Typically conducted via Amazon Chime (video + virtual whiteboard)
- Sometimes in-person with physical whiteboard
- Interviewer often from the same tech domain (AWS, Amazon retail, advertising)
- Questions are role-specific: AWS SDE gets infrastructure questions; marketplace SDE gets e-commerce questions

**Interview format specifics (Malaysia — KL):**
- Same structure as India
- More likely to involve AWS services in the design (preferred over generic cloud)
- Amazon KL office often works on global services (prime video, Alexa, AWS)

---

## 3.2 Leadership Principles (LP) in System Design

Amazon's 16 Leadership Principles are not just for behavioral rounds — they directly influence how you should frame system design answers.

### LP Integration in System Design

**1. Customer Obsession**
> "Work backwards from the customer."

**How to use it:**
- Start your design by defining the customer experience first
- "The customer expects checkout to complete in under 2 seconds. Let's design backwards from that SLA."
- Mention SLOs and how your architecture guarantees them

**Design implication:** Always define latency and availability SLAs. Trace your decisions back to customer experience.

---

**2. Ownership**
> "Think long-term. Don't say 'that's not my job.'"

**How to use it:**
- Proactively mention operational concerns: monitoring, alerting, runbooks
- "I'd also set up CloudWatch alarms for queue depth and p99 latency. My team owns this end-to-end."

**Design implication:** Include observability in your design (metrics, logs, distributed tracing).

---

**3. Invent and Simplify**
> "Expect and require innovation. Always find ways to simplify."

**How to use it:**
- If the interviewer proposes a complex solution, suggest a simpler one
- "Instead of building a custom rate limiter, I'd use AWS API Gateway's built-in rate limiting — simpler to operate."

**Design implication:** Don't over-engineer. A simpler design you can defend > complex design you can't explain.

---

**4. Are Right, A Lot**
> "Seek diverse perspectives. Work to disconfirm your beliefs."

**How to use it:**
- Show you've considered alternatives: "I considered MongoDB but chose DynamoDB because..."
- When interviewer challenges you, don't immediately capitulate. If you're right, defend it.

---

**5. Learn and Be Curious**
> "Never satisfied with current knowledge."

**How to use it:**
- Mention recent learnings: "I read about Amazon's DynamoDB's adaptive capacity feature recently and that influenced this design."

---

**6. Hire and Develop the Best**
> "Set a high bar."

**How to use it (Bar Raiser context):**
- Demonstrate that you set high standards in your design
- Mention code review expectations, design doc reviews, runbook requirements

---

**7. Insist on the Highest Standards**
> "Raise the bar. Don't settle."

**How to use it:**
- Proactively mention data integrity checks, monitoring thresholds, CI/CD requirements
- "I'd insist on 100% test coverage for the payment module and automated canary deployments."

---

**8. Think Big**
> "Create and communicate bold direction."

**How to use it:**
- Design for 10x scale even if not asked
- "This design handles 10M DAU today, but with minor sharding changes can scale to 1B."

**Design implication:** Always mention a scaling path.

---

**9. Bias for Action**
> "Speed matters in business. Take calculated risks."

**How to use it:**
- Show you make decisions with incomplete information, not paralyzed by analysis
- "I'd start with a relational DB to move fast, with the understanding we migrate to Cassandra when write volume exceeds 50K/sec."

---

**10. Frugality**
> "Accomplish more with less."

**How to use it:**
- Mention cost trade-offs: "DynamoDB on-demand vs provisioned — at our write volume, reserved capacity saves 40%."
- Prefer managed services over custom (lower operational cost)

**Design implication:** Always mention at least one cost optimization.

---

**11. Earn Trust**
> "Listen, speak frankly, treat others respectfully."

**How to use it:**
- Acknowledge what you don't know: "I'm not certain about the exact Kafka partition replication protocol, but I understand the guarantees it provides."
- Don't bluff. Interviewers respect intellectual honesty.

---

**12. Dive Deep**
> "Stay connected to the details. No task is beneath you."

**How to use it:**
- Go deep when asked. Know your numbers. Know your data structures.
- "The hot partition issue arises because we're using timestamp as the partition key in DynamoDB. All writes go to one partition. Fix: use a composite key — userId + timestamp."

---

**13. Have Backbone; Disagree and Commit**
> "Respectfully challenge when you disagree."

**How to use it:**
- If the interviewer suggests something suboptimal, respectfully disagree:
  > "I see the appeal of a relational DB here, but at 500K writes/sec, I think we'd hit contention. Could we consider Cassandra? That said, if you think the write volume will stay lower, I'm happy to use Postgres."

---

**14. Deliver Results**
> "Deliver despite setbacks."

**How to use it:**
- Show how your design handles failures and still delivers:
  > "Even if the payment service is down, we queue the order and notify the customer. We deliver the order when service recovers."

---

## 3.3 How to Handle Follow-Up Questions

Amazon interviewers are trained to push deeper. Expect these types of follow-ups:

**Type 1: Scale It Up**
> "What if you have 10x more traffic?"

**Response pattern:**
1. Identify the current bottleneck
2. Propose specific solution
3. Estimate new capacity

> "At 10x traffic, the main bottleneck would be the database writes. I'd introduce a write buffer using Kafka — orders flow into Kafka first, then consumer workers batch-insert into DB. This decouples write spikes from DB capacity."

---

**Type 2: What If X Fails?**
> "What happens if your cache goes down?"

**Response pattern:**
1. Describe graceful degradation
2. Cache-aside fallback to DB
3. Mention thundering herd mitigation

> "If Redis goes down, requests fall through to DynamoDB. We'd see higher latency but no data loss. To prevent thundering herd, I'd add a mutex per cache key so only one thread fetches from DB per cache miss."

---

**Type 3: Change the Requirement**
> "What if we need strong consistency instead of eventual?"

**Response pattern:**
1. Acknowledge the trade-off
2. Explain what changes
3. New latency/cost implications

> "For strong consistency, I'd switch to synchronous replication in DynamoDB (ConsistentRead=true), or use Aurora with synchronous replicas. The trade-off is higher read latency (~10ms vs ~1ms) and higher cost. Worth it for financial transactions."

---

**Type 4: Challenge Your Choice**
> "Why not just use a relational database for everything?"

**Response pattern:**
1. Acknowledge the valid point
2. Explain your reasoning
3. Offer a compromise if appropriate

> "That's fair — a relational DB would work at lower scale and is simpler to operate. My concern is at 100K writes/sec, we'd hit PostgreSQL's limits. We could start with Postgres (faster to ship) and migrate to Cassandra when we hit that wall."

---

**Type 5: Operational Questions**
> "How would you deploy this? How do you monitor it?"

**Response pattern:**
- Blue/green or canary deployments
- CloudWatch metrics: p50/p99 latency, error rate, queue depth
- Distributed tracing: AWS X-Ray, Jaeger
- Runbook for common failure modes

---

## 3.4 What Distinguishes SDE-2 vs SDE-3 Answers

### Scenario: "Design a Notification System"

**SDE-2 Answer (Acceptable):**
> "I'd have a notification service that receives events, looks up user preferences, and sends push notifications via APNS/FCM. Use SQS for async processing. Store notifications in DynamoDB."

**SDE-3 Answer (Expected):**
> "Let me break this into a fan-out problem. We have 3 tiers: 1) Event ingestion via Kafka for durability, 2) Fan-out service that reads user subscriptions and creates per-user notification records in a worker queue — this scales independently from ingestion, 3) Delivery adapters (APNS, FCM, email, SMS) as separate consumers with per-channel rate limiting and retry logic.
>
> For delivery guarantees, I'll use at-least-once with deduplication at the receiver. User preference lookup gets cached in Redis with 5-min TTL — acceptable staleness since preferences don't change frequently.
>
> The fan-out service is the bottleneck for celebrities with millions of followers. I'd split celeb users (>100K followers) into a separate Kafka partition with higher-parallelism consumers. Regular users handled normally.
>
> Monitoring: delivery latency histogram by channel, failure rate by provider, queue depth per channel as leading indicator of backlog."

**Key differentiators:**
| Dimension | SDE-2 | SDE-3 |
|-----------|-------|-------|
| Problem scope | Feature-level | Platform-level with edge cases |
| Bottleneck analysis | Obvious ones | Subtle ones (celeb fan-out) |
| Trade-offs | Mentioned if asked | Proactively mentioned |
| Operational concern | Not mentioned | Monitoring, deployment, runbook |
| Cost awareness | Not mentioned | Cost vs performance analysis |
| Scale path | Single solution | Evolution of solution |

---

## 3.5 Common Traps in Amazon Interviews

### Trap 1: "Design YouTube/Netflix" — Boiling the Ocean
**Trap:** Trying to design everything (upload, encoding, streaming, recommendations, analytics)
**Fix:** "I'll scope this to the video streaming path. Specifically: how users discover and watch videos at scale. Uploads and encoding are out of scope unless you'd like me to cover them."

---

### Trap 2: Buzzword Bingo Without Substance
**Trap:** "I'd use Kafka, Redis, DynamoDB, Elasticsearch, CDN, GraphQL, Kubernetes..."
**Fix:** Use technologies only when you can justify them specifically.
> Wrong: "I'd use Kafka for messaging."
> Right: "I'd use Kafka here because we need message replay capability — if a downstream service fails, we can replay events without re-processing the entire job. SQS doesn't support replay."

---

### Trap 3: Ignoring the Non-Functional Requirements
**Trap:** Designing a functional system without considering scale, latency, availability.
**Fix:** Always ask non-functional requirements upfront. Reference them throughout design.
> "Given our 99.99% availability requirement, I need to eliminate single points of failure. That's why I'm using a multi-AZ RDS setup with automatic failover."

---

### Trap 4: Not Drawing
**Trap:** Talking through the design without a diagram.
**Fix:** Draw as you talk. Even a simple ASCII diagram on whiteboard shows structured thinking.
```
[Client] → [CDN] → [API GW] → [Auth] → [Service] → [DB]
                                               ↓
                                           [Cache]
```

---

### Trap 5: Saying "It Depends" Without Following Up
**Trap:** "It depends on the requirements" and stopping there.
**Fix:** Say "it depends" AND immediately make a decision.
> "It depends on the consistency requirement. For this use case — shopping cart — I'll choose eventual consistency because the gain in availability outweighs the risk of brief stale data. We validate at checkout."

---

### Trap 6: Under-communicating Assumptions
**Trap:** Making assumptions silently.
**Fix:** State every assumption explicitly.
> "I'm assuming 80% of traffic is reads. I'm assuming data doesn't need to be deleted (append-only). I'm assuming users are globally distributed."

---

### Trap 7: Weak Handling of Data Loss / Durability
**Trap:** Assuming writes always succeed, ignoring crashes.
**Fix:** Always address: "What happens if this node crashes mid-write?"
> "If the order service crashes after creating the order but before publishing to Kafka, we'd have an orphaned order. I'd use the Outbox pattern — write order + event to DB in one transaction, a separate process reliably publishes to Kafka."

---

### Trap 8: Forgetting Security
**Trap:** Not mentioning authentication, authorization, encryption.
**Fix:** Briefly mention:
- Auth: JWT/OAuth2 at API Gateway
- AuthZ: Row-level security or RBAC
- Encryption: TLS in transit, AES-256 at rest
- PII handling: Mask in logs, encrypt in storage

---

*Next: [Section 4 — Top 50 Amazon System Design Questions](./Section-4-Top-50-Questions.md)*
