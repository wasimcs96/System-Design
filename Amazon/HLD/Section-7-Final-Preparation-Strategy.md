# Section 7: Final 30-Day Preparation Strategy

---

## 30-Day Study Plan

### Week 1 (Days 1–7): Foundations

| Day | Topic | Activity | Time |
|-----|-------|----------|------|
| 1 | Networking (HTTP, TCP/IP, DNS) | Read notes, watch 1 video | 2.5h |
| 2 | Database fundamentals (SQL vs NoSQL, ACID, indexes) | Read + write 5 flashcards | 2.5h |
| 3 | CAP theorem, consistency models | Read + diagram | 2h |
| 4 | Load balancing, caching fundamentals | Read notes | 2h |
| 5 | Design URL Shortener (end-to-end) | Practice alone, timed (45 min) | 2.5h |
| 6 | Review URL Shortener solution (Section 5) | Compare + fill gaps | 2h |
| 7 | Week 1 review + flashcard review | Light review | 1.5h |

**Week 1 Goals:**
- [ ] Can explain CAP theorem and give 3 real-world examples
- [ ] Can do QPS/storage estimation in < 3 minutes
- [ ] Can design URL Shortener end-to-end without notes

---

### Week 2 (Days 8–14): Core Concepts

| Day | Topic | Activity | Time |
|-----|-------|----------|------|
| 8 | Message queues (Kafka, SQS) deep dive | Read Section 2.2D | 2.5h |
| 9 | Database scaling (replication, sharding, consistent hashing) | Read + diagram | 2.5h |
| 10 | Rate limiting (all 5 algorithms) | Implement Token Bucket in code | 2h |
| 11 | Design Rate Limiter (end-to-end, timed 45 min) | Practice alone | 2.5h |
| 12 | Design Chat System (1-to-1) | Practice alone | 2.5h |
| 13 | Review both solutions with Section 5 | Compare + improve | 2h |
| 14 | Mock interview with peer (any 1 intermediate question) | Simulate real interview | 2h |

**Week 2 Goals:**
- [ ] Can explain Kafka internals (topic/partition/offset/consumer group)
- [ ] Can explain consistent hashing with virtual nodes
- [ ] Completed first mock interview

---

### Week 3 (Days 15–21): Distributed Systems + Advanced

| Day | Topic | Activity | Time |
|-----|-------|----------|------|
| 15 | Distributed systems: leader election, locking, vector clocks | Read Section 3 | 2.5h |
| 16 | Event-driven architecture + CQRS + Event Sourcing | Read + real examples | 2h |
| 17 | Microservices patterns (Saga, Circuit Breaker, API Gateway) | Read + diagram | 2h |
| 18 | Design Notification System (timed 45 min) | Practice alone | 2.5h |
| 19 | Design Amazon E-commerce Order System | Practice alone | 2.5h |
| 20 | Review Section 5 solutions in detail | Deep review | 2h |
| 21 | Mock interview (advanced question from Category C) | Peer or self-timed | 2h |

**Week 3 Goals:**
- [ ] Can explain Saga pattern (choreography vs orchestration) with example
- [ ] Can design a notification system with fan-out and preference routing
- [ ] Can handle "what if X fails?" questions for any component

---

### Week 4 (Days 22–30): Amazon-Specific + Polishing

| Day | Topic | Activity | Time |
|-----|-------|----------|------|
| 22 | Amazon Leadership Principles review (all 16 with LP-in-design examples) | Section 3.2 | 2h |
| 23 | Bar Raiser expectations + SDE-2 vs SDE-3 differentiation | Section 1.3, 3.4 | 2h |
| 24 | Design S3 (object storage) | Practice alone | 2.5h |
| 25 | Design Distributed Cache / Key-Value Store | Practice alone | 2.5h |
| 26 | Capacity estimation drills (5 examples without notes) | Section 6 | 2h |
| 27 | Mock interview #3 (full 60-min simulation) | Peer or record yourself | 2.5h |
| 28 | Weakness areas review (topics you struggled with) | Targeted study | 2h |
| 29 | Mock interview #4 (Amazon-style follow-up questions) | Practice handling follow-ups | 2h |
| 30 | Final review + mental preparation | Light review, sleep well | 1h |

**Week 4 Goals:**
- [ ] Can naturally integrate Leadership Principles into design answers
- [ ] Have done 4+ full mock interviews
- [ ] Can handle any follow-up question without panic
- [ ] Capacity estimation feels automatic

---

## Daily Study Schedule (2–3 Hours/Day)

### Weekday Schedule (2.5 hours)

```
[0:00 – 0:20] Review yesterday's notes / flashcards
[0:20 – 1:20] Study new topic (read, diagram, understand)
[1:20 – 1:30] Break
[1:30 – 2:20] Practice design question (timed 45 min)
[2:20 – 2:30] Write 3 key takeaways in your notes
```

### Weekend Schedule (3–4 hours)

```
[0:00 – 0:30] Week review (what did you learn? what's unclear?)
[0:30 – 1:30] Mock interview (with peer or self-recorded)
[1:30 – 2:00] Deep dive: read one engineering blog post (Netflix, Uber, AWS)
[2:00 – 3:00] Design a new system end-to-end
[3:00 – 3:30] Update flashcards / notes
```

---

## Must-Do vs Optional Topics

### MUST-DO (Non-negotiable for SDE-2/SDE-3)

**Concepts:**
- [ ] CAP theorem and consistency models
- [ ] Horizontal vs vertical scaling
- [ ] Consistent hashing
- [ ] Database replication + sharding
- [ ] Caching strategies (cache-aside, write-through, eviction policies)
- [ ] Message queues (Kafka fundamentals)
- [ ] Rate limiting algorithms
- [ ] API design (REST, idempotency, versioning, pagination)
- [ ] SQL vs NoSQL decision criteria
- [ ] Microservices patterns (Saga, Circuit Breaker, API Gateway)

**Design Questions (must be able to design end-to-end):**
- [ ] URL Shortener
- [ ] Chat System (1-to-1 + group)
- [ ] News Feed / Timeline
- [ ] Notification System
- [ ] Rate Limiter
- [ ] E-commerce order placement
- [ ] Distributed Cache
- [ ] Search Autocomplete
- [ ] File Storage System (S3)
- [ ] Ride-sharing (Uber)

**Capacity Estimation:**
- [ ] Can estimate QPS, storage, bandwidth for any of the above in < 3 min

---

### OPTIONAL (Good-to-have for SDE-3+)

- Raft consensus algorithm internals
- Paxos protocol
- CRDTs (Conflict-free Replicated Data Types)
- Bloom filter internals
- LSM tree compaction strategies
- Erasure coding math
- Kafka exactly-once semantics internals
- Distributed tracing implementation details
- Service mesh (Istio) internals
- Kubernetes scheduler internals

---

## Recommended Resources

### Books (Priority Order)

| Book | Why | When to Read |
|------|-----|-------------|
| **Designing Data-Intensive Applications** (Martin Kleppmann) | Best book ever written on distributed systems. Chapters 5-9 are gold. | Week 3–4 (selected chapters) |
| **System Design Interview Vol 1** (Alex Xu) | Interview-focused, practical designs | Week 1–2 |
| **System Design Interview Vol 2** (Alex Xu) | More advanced questions | Week 3–4 |
| **Database Internals** (Alex Petrov) | Deep dive on storage engines | Optional, after interview |

---

### YouTube Channels (Best Quality)

| Channel | Best For |
|---------|----------|
| **Gaurav Sen** | Distributed systems concepts with animations |
| **System Design Fight Club** | Detailed walkthroughs of real systems |
| **ByteByteGo** (Alex Xu) | Quick visual explanations |
| **Exponent** | Mock interviews + Amazon-specific |
| **Tech Dummies** | HLD interviews |
| **codeKarle** | Indian audience, Amazon/Flipkart focused |

**Must-watch playlists:**
- Gaurav Sen: "System Design" playlist (all 30+ videos)
- ByteByteGo: "System Design Basics" series

---

### GitHub Repositories

| Repo | Content |
|------|---------|
| `donnemartin/system-design-primer` | Most comprehensive free resource |
| `checkcheckzz/system-design-interview` | Interview questions + solutions |
| `InterviewReady/system-design-resources` | Curated resources |
| `binhnguyennus/awesome-scalability` | Real-world architecture articles |

---

### Engineering Blogs (Real-world Case Studies)

| Company | Blog URL | Key Articles |
|---------|----------|-------------|
| **AWS Architecture Blog** | aws.amazon.com/blogs/architecture | DynamoDB design, S3 internals |
| **Netflix Tech Blog** | netflixtechblog.com | Resilience engineering, Chaos Monkey |
| **Uber Engineering** | eng.uber.com | Geospatial systems, trip architecture |
| **Discord Engineering** | discord.com/blog | Real-time messaging at scale |
| **Shopify Engineering** | shopify.engineering | Flash sale architecture, Kafka at Shopify |
| **LinkedIn Engineering** | engineering.linkedin.com | Kafka origin story, feed architecture |

**Top articles to read:**
- "Amazon DynamoDB – A Scalable, Predictably Performant, and Fully Managed NoSQL Database Service" (USENIX 2022)
- "Uber's Real-time Push Platform" (Uber Engineering)
- "Netflix's Viewing History" (Netflix Tech Blog)
- "How Discord Stores Billions of Messages" (Discord Engineering)

---

### Courses

| Course | Platform | Cost | Best For |
|--------|----------|------|----------|
| **Grokking the System Design Interview** | Educative.io | $59/mo | Interview patterns, structured |
| **System Design by Gaurav Sen** | Udemy | ~$15 | Concepts + interviews |
| **Distributed Systems** (MIT 6.824) | YouTube (free) | Free | Deep distributed systems theory |
| **AWS Solutions Architect** | A Cloud Guru | Subscription | AWS-specific architecture |

---

## Mock Interview Sources

### Peer Mock Interviews
- **Pramp.com** — Free peer-to-peer mock interviews
- **Interviewing.io** — Anonymous practice, real interviewers
- **Meetapro** — Paid mock with FAANG engineers

### Self-Practice Setup
```
1. Open whiteboard app (Excalidraw, Miro, draw.io)
2. Set timer for 45 minutes
3. Pick question from Section 4 that you haven't practiced
4. Record yourself (video + screen)
5. After 45 min: review recording, compare with ideal solution
6. Note 3 weaknesses to fix
```

### Mock Interview Checklist
Before each mock:
- [ ] Set timer
- [ ] Have whiteboard/drawing tool ready
- [ ] Note down framework: Requirements → Capacity → Architecture → Deep Dive → Trade-offs

During mock:
- [ ] Ask clarifying questions (don't assume)
- [ ] State assumptions explicitly
- [ ] Think out loud
- [ ] Draw as you talk
- [ ] Mention trade-offs proactively

After mock:
- [ ] Did you finish in time?
- [ ] Did you cover all components?
- [ ] Did you handle failure scenarios?
- [ ] Did you mention monitoring/observability?

---

## Interview Day Strategy

### Night Before
- [ ] Review your framework one more time (not new concepts)
- [ ] Prepare 2–3 examples of systems you've personally built/designed
- [ ] Sleep 8 hours
- [ ] No new studying

### Day Of
- [ ] Arrive / log in 10 minutes early
- [ ] Have water and scratch paper ready (in-person)
- [ ] Open whiteboard tool (virtual) beforehand

### During the Interview

**First 60 seconds:**
> "Before I start designing, I'd like to clarify requirements to make sure we're solving the right problem. I have about 5 questions..."

**While designing:**
> "I'm going to start with the high-level architecture first, then we can deep dive into specific components. Does that sound good?"

**On every major decision:**
> "I'm choosing [X] here because [reason]. The trade-off is [Y]. Acceptable for this use case because [Z]."

**When you don't know something:**
> "I'm not certain of the exact internal implementation of [X], but I understand it provides [guarantee], and I'd design around that guarantee."

**On interviewer challenge:**
> "That's a great point. Let me reconsider... [think for 5 seconds]... You're right that [acknowledgment]. I'd address that by [solution]."

---

## Weak Areas Diagnostic

After each mock interview, rate yourself 1–5:

| Area | Self-Rating | Action if < 3 |
|------|-------------|--------------|
| Requirement clarification | | Re-read Section 2, Phase 5 |
| Capacity estimation | | Do all 5 examples in Section 6 |
| Architecture drawing | | Practice drawing systems daily |
| Database schema design | | Study more SQL/NoSQL patterns |
| API design | | Review REST best practices |
| Bottleneck identification | | Study scaling patterns |
| Trade-off discussion | | Study CAP, consistency models |
| Failure scenario handling | | Study fault tolerance patterns |
| Communication clarity | | Record + review your explanations |
| Time management | | Do more timed practices |

---

## Final Mental Model

Every system design question maps to one of these core patterns:

```
STORAGE: How do we store data at scale?
  → Sharding, replication, LSM trees, object storage

RETRIEVAL: How do we read data fast?
  → Caching, indexes, CDN, read replicas

COMMUNICATION: How do components talk?
  → REST, gRPC, Message queues, WebSockets

COORDINATION: How do distributed nodes agree?
  → Leader election, consensus, distributed locking

RELIABILITY: What happens when things fail?
  → Circuit breaker, retry, fallback, multi-region

SCALE: How do we handle 10× more load?
  → Horizontal scaling, partitioning, async processing
```

**Internalize this:** Every component in your design should answer one of these 6 questions. If you can't explain why a component is there, remove it.

---

## Your Pre-Interview Checklist (Final 48 Hours)

**48 hours before:**
- [ ] Review key concepts (CAP, consistent hashing, Kafka)
- [ ] Review your 3 most practiced design solutions
- [ ] Review Leadership Principles (Section 3.2)

**24 hours before:**
- [ ] Light review only — no heavy studying
- [ ] Review Section 3.5 (common traps to avoid)
- [ ] Rest

**Morning of interview:**
- [ ] Review the 8-step framework (Section 2, Phase 5)
- [ ] Warm up with one quick estimation exercise
- [ ] Confidence: you are ready

---

> "The goal is not to design a perfect system. The goal is to demonstrate that you can think through a complex problem systematically, make reasonable decisions, and communicate your reasoning clearly. That is what Amazon is hiring for."

---

*This concludes the Amazon HLD Interview Preparation Guide.*  
*Good luck — you have everything you need.*

---

## Full Guide Index

| Section | File |
|---------|------|
| Overview | [README.md](./README.md) |
| Section 1: Amazon HLD Expectations | [Section-1-HLD-Expectations.md](./Section-1-HLD-Expectations.md) |
| Section 2: Preparation Roadmap | [Section-2-Preparation-Roadmap.md](./Section-2-Preparation-Roadmap.md) |
| Section 3: Amazon-Specific Strategy | [Section-3-Amazon-Specific-Strategy.md](./Section-3-Amazon-Specific-Strategy.md) |
| Section 4: Top 50 Questions | [Section-4-Top-50-Questions.md](./Section-4-Top-50-Questions.md) |
| Section 5: Detailed Design Solutions | [Section-5-Detailed-Design-Solutions.md](./Section-5-Detailed-Design-Solutions.md) |
| Section 6: Capacity Estimation | [Section-6-Capacity-Estimation.md](./Section-6-Capacity-Estimation.md) |
| Section 7: Final Preparation Strategy | [Section-7-Final-Preparation-Strategy.md](./Section-7-Final-Preparation-Strategy.md) |
