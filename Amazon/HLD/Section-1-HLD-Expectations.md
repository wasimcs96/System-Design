# Section 1: Amazon HLD Interview Expectations

---

## 1.1 What Amazon Evaluates in System Design Interviews

Amazon's system design panel assesses your ability to build systems that are **reliable, scalable, maintainable, and cost-effective** at Amazon's scale (billions of requests/day). Here is exactly what interviewers look for:

---

### 1. Scalability — Horizontal vs Vertical

| Aspect | What Amazon Checks |
|--------|-------------------|
| Horizontal Scaling | Can you add more machines to handle load? Stateless services, load balancers, consistent hashing |
| Vertical Scaling | Know its limits — single point of failure, hardware ceiling |
| Auto-scaling | Awareness of dynamic provisioning (like AWS Auto Scaling Groups) |

**Practical expectation:** For any write-heavy service, demonstrate horizontal scaling. For compute-heavy tasks (ML inference), understand when vertical helps temporarily.

**Example:** "I would deploy the order service behind an Application Load Balancer. Each instance is stateless — session state stored in ElastiCache. I can horizontally scale from 10 to 1000 instances without redesign."

---

### 2. Availability and Reliability

| Concept | Expectation |
|---------|-------------|
| Multi-AZ / Multi-Region | Know when to use each |
| Redundancy | No single point of failure |
| Fault tolerance | Graceful degradation — system still works partially when components fail |
| SLA targets | Express availability as "five 9s" = 99.999% = ~5 min downtime/year |

**Availability math:**
- 99% = 3.65 days downtime/year
- 99.9% = 8.76 hours/year
- 99.99% = 52.6 minutes/year
- 99.999% = 5.26 minutes/year

**Key insight for Amazon:** They operate across regions (us-east-1, ap-south-1, ap-southeast-1). Know how to design active-active vs active-passive multi-region setups.

---

### 3. Consistency Models

Amazon uses different consistency models for different services. You MUST know:

| Model | Definition | Amazon Use Case |
|-------|-----------|----------------|
| Strong Consistency | Every read sees the latest write | Banking, inventory deduction |
| Eventual Consistency | Reads will eventually reflect writes | Shopping cart, product reviews |
| Read-your-writes | User sees their own writes immediately | Profile updates |
| Monotonic reads | Same user won't see older data after newer | Notification feed |
| Causal consistency | Related writes are seen in order | Comment threads |

**CAP Theorem in interviews:**
- You can only choose 2 of 3: Consistency, Availability, Partition Tolerance
- In practice, P is always required (networks fail), so you choose between C and A
- Amazon DynamoDB: AP by default (eventual), but supports strong consistency per-request
- Amazon RDS: CP (strong consistency, may be unavailable during partition)

---

### 4. Latency and Performance Optimization

Interviewers expect you to know approximate latency numbers:

| Operation | Approx. Latency |
|-----------|----------------|
| L1 cache reference | 0.5 ns |
| L2 cache reference | 7 ns |
| RAM read | 100 ns |
| SSD random read | 150 μs |
| Network round trip (same DC) | 500 μs |
| HDD seek | 10 ms |
| Network round trip (cross-region) | 150 ms |

**Optimization strategies to mention:**
- Caching at every layer (L1/L2, application cache, CDN)
- Read replicas for read-heavy workloads
- Database connection pooling
- Async processing for non-critical paths
- Data locality (keep compute near data)

---

### 5. Trade-off Analysis

This is THE most important skill. Amazon interviewers don't want the "perfect" answer — they want to see you **reason through trade-offs**.

**Common trade-offs:**
- Consistency vs Availability
- Latency vs Throughput
- Storage cost vs Query speed (denormalization)
- Simplicity vs Flexibility
- Synchronous vs Asynchronous processing

**How to present trade-offs:**
> "Option A gives us strong consistency with ~50ms additional latency. Option B gives eventual consistency but responds in 5ms. For a payment system, I'd choose A. For a product view count, I'd choose B."

---

### 6. API Design

Amazon evaluates API quality as a measure of engineering maturity:

**REST API best practices:**
- Use resource-based URLs: `GET /orders/{orderId}` not `GET /getOrder?id=123`
- Proper HTTP verbs: GET (read), POST (create), PUT (replace), PATCH (partial update), DELETE
- Versioning: `/v1/orders`, `/v2/orders`
- Pagination: `?page=2&limit=50` or cursor-based `?after=cursor_xyz`
- Idempotency keys for POST requests (critical for payment APIs)
- Proper error codes: 400 (bad request), 404 (not found), 429 (rate limited), 503 (unavailable)

**GraphQL awareness:** Know when to prefer it (mobile clients needing flexible queries, multiple entity fetches in one round-trip).

---

### 7. Data Modeling

| Skill | What's Tested |
|-------|--------------|
| Schema design | Normalize vs denormalize decisions |
| Primary key selection | Avoid hot partitions in DynamoDB |
| Indexing | When to add, what to index, covering indexes |
| Data access patterns | Design schema around queries (NoSQL) |
| Sharding keys | Even distribution, query efficiency |

**Golden rule for NoSQL:** "Start with your access patterns, then design your schema."  
**Golden rule for SQL:** "Start with your entities, normalize first, optimize later."

---

### 8. Distributed Systems Fundamentals

Topics you must be fluent in:

| Topic | Key Points |
|-------|-----------|
| Consistent Hashing | How to add/remove nodes with minimal data redistribution |
| Leader Election | Raft, Paxos — why needed, how it works conceptually |
| Distributed Transactions | Two-phase commit (2PC), Saga pattern |
| Replication | Synchronous (strong consistency, higher latency) vs asynchronous |
| Vector Clocks | Detecting conflicts in distributed writes |
| Heartbeats | How nodes detect failures |

---

## 1.2 LLD vs HLD — Clear Distinction with Examples

| Aspect | HLD (High-Level Design) | LLD (Low-Level Design) |
|--------|------------------------|----------------------|
| Focus | Architecture, components, interactions | Classes, methods, interfaces, algorithms |
| Output | Block diagrams, API contracts, data flow | UML class diagrams, code-level design |
| Duration | 45–60 min interview | 45–60 min interview |
| Example Question | "Design Instagram" | "Design the Like button class hierarchy" |
| Deals with | Services, databases, queues, CDN | Object relationships, design patterns |
| Scale concern | Yes — capacity, throughput | No — focuses on code structure |

**Concrete Example — Design a URL Shortener:**

*HLD covers:*
- API Gateway → App Servers → Cache (Redis) → Database (DynamoDB)
- How to generate short codes (Base62 encoding)
- How to handle 100M URLs at 10k reads/sec
- CDN placement for redirect caching

*LLD covers:*
- `UrlShortener` class with `shorten(url)` and `expand(code)` methods
- `Base62Encoder` utility class
- Strategy pattern for different encoding algorithms
- Repository pattern for storage abstraction

---

## 1.3 How the Bar Raiser Evaluates System Design

The **Bar Raiser** is a specially trained Amazon interviewer whose sole job is to maintain hiring standards. Here's what they specifically assess:

### Bar Raiser's Mental Scorecard

**1. Structured Thinking**
- Did the candidate clarify requirements before jumping in?
- Did they follow a logical progression?
- Can they drive the conversation, or do they need hand-holding?

**2. Depth vs Breadth Balance**
- Did they cover the full system (breadth) AND dive deep into critical components (depth)?
- Red flag: covering everything at surface level with no depth anywhere

**3. Proactive Trade-off Discussion**
- Did they mention trade-offs WITHOUT being asked?
- Bar Raisers specifically look for: "I chose X because of A, B. The downside is C, which I'd mitigate by D."

**4. Failure Mode Awareness**
- Did they think about what happens when a component fails?
- Did they address single points of failure?

**5. Leadership Principles Integration**
- "Customer Obsession" → Did they start with user experience/SLAs?
- "Frugality" → Did they consider cost-effectiveness?
- "Think Big" → Did they design for 10x scale?
- "Dive Deep" → Did they know specifics, not just buzzwords?

**6. Communication Quality**
- Can they explain a complex system to a non-technical stakeholder?
- Do they draw as they speak?

### Bar Raiser Verdict Criteria
- **Strong Hire:** Candidate raised the bar. Better than 50% of current engineers at that level.
- **Hire:** Meets the bar. Solid candidate.
- **No Hire:** Doesn't meet the bar even with coaching.
- **Strong No Hire:** Significant red flags.

> The Bar Raiser has VETO power. A unanimous "Hire" from all other interviewers can be overturned by a single Bar Raiser "No Hire."

---

## 1.4 SDE-2 vs SDE-3 Expectations in HLD

| Dimension | SDE-2 | SDE-3 |
|-----------|-------|-------|
| Scope | Design a single service/component | Design an entire platform with multiple services |
| Bottleneck identification | Identify obvious bottlenecks | Identify subtle, non-obvious bottlenecks |
| Trade-off depth | Mention trade-offs when prompted | Proactively enumerate and justify trade-offs |
| Failure handling | Single-node failure | Cascading failures, network partitions, region outages |
| Leadership principles | Aware of them | Naturally weaves them into design decisions |
| Data modeling | Design for stated requirements | Anticipate future access patterns |
| Cost awareness | Not required | Expected — "this adds $X/month, justified because Y" |
| Ambiguity handling | Needs clarification prompts | Asks the right clarifying questions independently |

---

*Next: [Section 2 — Complete Preparation Roadmap](./Section-2-Preparation-Roadmap.md)*
