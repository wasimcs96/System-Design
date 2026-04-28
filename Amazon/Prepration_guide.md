# Complete Amazon SDE L5/L6 Interview Preparation Roadmap

---

## 🗓️ Timeline Overview

| Phase | Duration | Focus |
|---|---|---|
| Phase 1 | Weeks 1–3 | DSA Foundation & Problem Patterns |
| Phase 2 | Weeks 4–6 | DSA Advanced + System Design Basics |
| Phase 3 | Weeks 7–9 | System Design Deep Dive |
| Phase 4 | Weeks 10–11 | Leadership Principles (Behavioral) |
| Phase 5 | Week 12 | Mock Interviews + Full Revision |

> **Total: 12 weeks (3 months)** — adjust based on your current strength.

---

## 📌 Amazon Interview Structure (L5/L6)

| Round | Type | Count |
|---|---|---|
| Online Assessment | DSA (2 problems) | 1 |
| Phone/Video Screen | DSA + LP | 1–2 |
| Virtual Onsite (Loop) | DSA + System Design + LP | 4–6 rounds |

> **L6 will have harder system design + deeper LP bar. Every round has LP questions (15–20 min).**

---

## 🔷 DOMAIN 1: Data Structures & Algorithms

### 🎯 Target for L5: Leetcode Medium (strong)
### 🎯 Target for L6: Leetcode Medium–Hard (consistent)

---

### 📚 Topics & Study Order

#### **Week 1 — Core Foundations**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **Arrays & Strings** | Two pointers, sliding window, prefix sum, kadane's | #1, #3, #53, #121, #238, #560 |
| **HashMap / HashSet** | Frequency count, anagram, duplicates | #49, #128, #146, #242, #347 |
| **Sorting** | Merge sort, quick sort, custom comparators | #56, #57, #179, #215, #252 |
| **Binary Search** | Classic, search in rotated, answer-space binary search | #33, #34, #153, #162, #378 |

---

#### **Week 2 — Linear Data Structures**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **Linked List** | Fast/slow pointer, reverse, merge, cycle | #21, #23, #141, #142, #206, #234 |
| **Stack** | Monotonic stack, next greater element | #20, #84, #85, #155, #739 |
| **Queue / Deque** | Sliding window max, BFS base | #239, #346, #387, #641 |
| **Prefix Sum / Difference Array** | Range queries, subarray problems | #304, #307, #560, #974 |

---

#### **Week 3 — Trees & Recursion**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **Binary Tree** | DFS (pre/in/post), BFS, height, diameter | #94, #102, #104, #124, #236, #543 |
| **Binary Search Tree** | Validate, search, insert, kth element | #98, #230, #450, #700, #701 |
| **Tries** | Insert, search, prefix | #208, #211, #212, #421 |
| **Recursion / Backtracking** | Permutations, combinations, subsets | #39, #40, #46, #78, #79, #131 |

---

#### **Week 4 — Graphs**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **BFS / DFS** | Connected components, flood fill, shortest path | #200, #286, #417, #542, #695 |
| **Topological Sort** | Kahn's algorithm, cycle detection | #207, #210, #310, #329 |
| **Union Find** | Disjoint sets, redundant connections | #261, #323, #547, #684, #721 |
| **Shortest Path** | Dijkstra, Bellman-Ford | #743, #787, #1514 |
| **Graph DP / Advanced** | Floyd-Warshall, clone graph | #133, #399, #floyd variants |

---

#### **Week 5 — Dynamic Programming**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **1D DP** | Fibonacci, house robber, climb stairs | #70, #139, #198, #322, #413 |
| **2D DP** | Grid paths, edit distance, LCS | #62, #64, #72, #97, #1143 |
| **Knapsack Pattern** | 0/1 knapsack, partition equal subset | #416, #494, #518, #474 |
| **Interval DP** | Matrix chain, burst balloons | #312, #516, #1039 |
| **State Machine DP** | Stock problems | #121, #122, #123, #188, #309 |

---

#### **Week 6 — Advanced Patterns**

| Topic | Key Concepts | LeetCode Problems |
|---|---|---|
| **Heap / Priority Queue** | Top-K, merge K sorted, median | #23, #215, #295, #347, #378, #632 |
| **Sliding Window (Advanced)** | Variable window, multi-condition | #76, #159, #340, #395, #424 |
| **Bit Manipulation** | XOR tricks, subsets, counting bits | #136, #191, #201, #268, #338 |
| **Math & Number Theory** | GCD, prime, modular arithmetic | #204, #263, #372, #509 |

---

### ✅ DSA Practice Strategy

```
Daily Target:
  - Weekdays: 2–3 problems (1 easy warm-up + 1–2 mediums)
  - Weekends: 1 Hard problem + Review weak areas

Weekly:
  - 1 Timed Mock (LeetCode Contest or 90-min session)
  - Review all wrong/slow solutions
  - Maintain error log
```

### 🛠️ Recommended Platforms
- **LeetCode** (primary — filter Amazon tagged)
- **NeetCode.io** (structured roadmap + video solutions)
- **AlgoExpert** (optional for video explanations)
- **Blind 75 + Grind 75** (must-do list)

---

## 🔷 DOMAIN 2: System Design

> **This is THE most critical differentiator between L5 and L6.**
> L5: Design medium-complexity systems clearly.
> L6: Design at massive scale, handle ambiguity, drive tradeoffs confidently.

---

### 📚 Phase 1 — Foundations (Week 4–5)

#### **Core Building Blocks (Must Master)**

| Topic | Key Concepts to Learn |
|---|---|
| **Scalability Basics** | Vertical vs horizontal scaling, stateless services |
| **Load Balancing** | Round robin, least connections, consistent hashing |
| **Caching** | Redis/Memcached, cache-aside, write-through, TTL, cache invalidation |
| **Databases** | SQL vs NoSQL, ACID vs BASE, sharding, replication, indexing |
| **CAP Theorem** | Consistency, Availability, Partition tolerance tradeoffs |
| **Message Queues** | Kafka, SQS, async processing, fan-out, pub-sub |
| **CDN** | Edge caching, static asset delivery, latency reduction |
| **API Design** | REST vs GraphQL, rate limiting, versioning, pagination |
| **Networking Basics** | DNS, TCP/UDP, HTTP/HTTPS, websockets, long polling |
| **Storage** | Object storage (S3), block storage, file storage |

---

### 📚 Phase 2 — System Design Framework (Week 6–7)

#### **Use this structure in EVERY design interview:**

```
Step 1 (5 min)  → Clarify Requirements
                  - Functional: What does it do?
                  - Non-functional: Scale, latency, availability, consistency?
                  - Constraints: How many users, QPS, data size?

Step 2 (3 min)  → Back-of-envelope Estimation
                  - DAU, QPS (read/write), storage, bandwidth

Step 3 (5 min)  → High-Level Design
                  - Core components: Client → LB → App Servers → DB → Cache

Step 4 (15 min) → Deep Dive
                  - Schema design, API design, core algorithms
                  - Scale bottlenecks and solutions

Step 5 (5 min)  → Address Non-functional Requirements
                  - Fault tolerance, replication, monitoring

Step 6 (2 min)  → Trade-offs & What you'd do differently at scale
```

---

### 📚 Phase 3 — Must-Know System Design Problems (Week 7–9)

#### **Tier 1 — Very Likely at Amazon**

| System | Key Components to Cover |
|---|---|
| **URL Shortener (TinyURL)** | Hash generation, collision handling, DB design, redirect latency |
| **Rate Limiter** | Token bucket, sliding window, distributed rate limiting |
| **News Feed / Timeline** | Fan-out on write vs read, ranking, pagination |
| **Notification System** | Push/Email/SMS, fanout, priority queues, delivery guarantees |
| **Distributed Cache** | Consistent hashing, eviction policies, cluster replication |
| **Search Autocomplete** | Trie, top-K, caching, prefix ranking |
| **Object Storage (S3-like)** | Chunking, metadata, replication, consistency |
| **Payment System** | Idempotency, double-entry ledger, distributed transactions |

---

#### **Tier 2 — Good to Know**

| System | Key Components |
|---|---|
| **Ride Sharing (Uber)** | Geo-indexing, matching algo, surge pricing |
| **Video Streaming (Netflix)** | CDN, adaptive bitrate, chunking, metadata |
| **Distributed Message Queue (Kafka)** | Partitioning, offset, consumer groups, durability |
| **Web Crawler** | BFS, politeness, dedup, distributed crawling |
| **E-Commerce (Amazon-style)** | Inventory, cart, orders, flash sale handling |
| **Distributed ID Generator** | Snowflake, UUID, clock skew |
| **Real-time Chat (WhatsApp)** | WebSockets, message delivery guarantees, presence |

---

#### **Amazon-Specific Design Considerations**

> Amazon loves these topics because they're core to their business:

- **Order management systems** — inventory consistency, eventual consistency
- **Recommendation engine** — collaborative filtering at scale
- **Distributed transactions** — SAGA pattern, 2PC
- **Event-driven architecture** — EventBridge, SQS/SNS patterns
- **Multi-region architecture** — Active-active vs active-passive

---

### 📚 Phase 4 — Advanced System Design (L6 focus)

| Topic | Key Concepts |
|---|---|
| **Consistency Models** | Strong, eventual, causal, linearizability |
| **Consensus Algorithms** | Raft, Paxos (conceptual understanding) |
| **Distributed Transactions** | 2PC, SAGA pattern, outbox pattern |
| **Data Pipelines** | Lambda architecture, Kappa architecture, stream processing |
| **Multi-region Deployment** | Data residency, conflict resolution, replication lag |
| **Observability** | Metrics, logs, traces (OpenTelemetry), alerting |
| **Chaos Engineering** | Failure injection, circuit breaker, bulkhead |

---

### 🛠️ System Design Resources

| Resource | Type |
|---|---|
| **Designing Data-Intensive Applications (DDIA)** — Martin Kleppmann | 📖 Must-Read Book |
| **System Design Interview Vol 1 & 2** — Alex Xu | 📖 Book |
| **ByteByteGo** (bytebytego.com) | 🎥 Newsletter + YouTube |
| **Gaurav Sen YouTube** | 🎥 YouTube |
| **GitHub: donnemartin/system-design-primer** | 📄 Free Resource |
| **High Scalability Blog** | 📄 Real-world case studies |

---

## 🔷 DOMAIN 3: Leadership Principles (Behavioral)

> **At Amazon, LP rounds are as important as technical rounds.**
> Every round will have 2–3 LP questions. Failing LP = no offer, regardless of technical performance.

---

### 📚 Amazon's 16 Leadership Principles

| # | Principle | What They're Really Testing |
|---|---|---|
| 1 | **Customer Obsession** | Did you start with customer impact? |
| 2 | **Ownership** | Did you go beyond your job description? |
| 3 | **Invent and Simplify** | Did you find a creative, simpler solution? |
| 4 | **Are Right, A Lot** | Did you make good judgment calls with incomplete data? |
| 5 | **Learn and Be Curious** | Do you proactively learn new things? |
| 6 | **Hire and Develop the Best** | Have you raised the bar on your team? |
| 7 | **Insist on the Highest Standards** | Did you push back on mediocrity? |
| 8 | **Think Big** | Did you challenge assumptions and think beyond the obvious? |
| 9 | **Bias for Action** | Did you act decisively with calculated risk? |
| 10 | **Frugality** | Did you do more with less? |
| 11 | **Earn Trust** | Did you build credibility through transparency? |
| 12 | **Dive Deep** | Did you get into details when it mattered? |
| 13 | **Have Backbone; Disagree & Commit** | Did you push back respectfully, then align? |
| 14 | **Deliver Results** | Did you actually ship something meaningful? |
| 15 | **Strive to be Earth's Best Employer** | People leadership and team care |
| 16 | **Success and Scale Bring Broad Responsibility** | Big-picture thinking, ethical choices |

---

### 📚 STAR Framework (Mandatory)

```
S — Situation:  Set the context (brief, 2–3 sentences)
T — Task:       What was YOUR responsibility?
A — Action:     What did YOU specifically do? (this is the longest part)
R — Result:     Quantified outcome (%, time saved, revenue, scale, users)
```

> ⚠️ **Common mistakes:**
> - Saying "we" instead of "I" — Amazon wants YOUR specific actions
> - No numbers in results — always quantify
> - Story too long — each answer should be 2–3 minutes max

---

### 📚 Story Bank (Build 8–10 stories from your 9 years)

**For each story, tag it to multiple LPs:**

| Story Theme | Maps To |
|---|---|
| Delivered a project under pressure | Deliver Results, Bias for Action, Ownership |
| Pushed back on a bad decision | Backbone, Earn Trust, Are Right A Lot |
| Simplified a complex system | Invent & Simplify, Frugality, Think Big |
| Mentored a junior engineer | Hire & Develop, Highest Standards |
| Fixed production incident | Dive Deep, Ownership, Deliver Results |
| Proposed a new product/feature | Customer Obsession, Think Big, Invent & Simplify |
| Dealt with a difficult stakeholder | Earn Trust, Backbone, Deliver Results |
| Learned a new technology to solve a problem | Learn & Be Curious, Bias for Action |

---

### 📚 Most Frequently Asked LP Questions at Amazon

```
1. Tell me about a time you failed. What did you learn?
   → (Ownership + Learn & Be Curious)

2. Tell me about a time you disagreed with your manager or peer.
   → (Backbone; Disagree & Commit)

3. Tell me about the most complex system you designed.
   → (Invent & Simplify + Dive Deep)

4. Tell me about a time you took ownership beyond your role.
   → (Ownership)

5. Tell me about a time you had to make a decision with incomplete data.
   → (Are Right A Lot + Bias for Action)

6. Tell me about a time you raised the bar for your team.
   → (Highest Standards + Hire & Develop)

7. Tell me about a time you had a significant positive customer impact.
   → (Customer Obsession + Deliver Results)

8. Tell me about your most impactful project.
   → (Think Big + Deliver Results + Ownership)
```

---

### 📚 L6-Specific LP Bar

> L6 expects **org-level scope**, not just team-level:

- Stories should involve **multiple teams or stakeholders**
- Impact should be **company or product-wide**, not just your sprint
- You should demonstrate **defining direction**, not just executing tasks
- Clear examples of **raising engineering bar** across teams

---

## 🔷 DOMAIN 4: Object Oriented Design (OOD)

> Less common at Amazon but can appear in screen rounds or as part of system design.

### Topics to Cover

| Topic | Examples |
|---|---|
| **SOLID Principles** | Single Responsibility, Open/Closed, Liskov, Interface Segregation, Dependency Inversion |
| **Design Patterns** | Factory, Singleton, Observer, Strategy, Decorator, Builder, Command |
| **OOD Problems** | Design Parking Lot, Design Chess Game, Design Elevator, Design Library System |

### Key Resources
- **Head First Design Patterns** (book)
- **Refactoring Guru** (refactoring.guru) — free, visual explanations

---

## 🔷 DOMAIN 5: Coding Best Practices (In-Interview)

> Amazon interviewers evaluate not just correctness but **how you think and communicate.**

```
✅ Always do this during coding rounds:

1. Clarify the problem before coding (2 min)
   - Ask about edge cases, input constraints, expected output format

2. Verbalize your approach before writing code
   - "I'm thinking of using a sliding window here because..."

3. Write clean, readable code
   - Use meaningful variable names
   - Add brief inline comments for complex logic

4. Walk through a test case manually
   - Use the given example first, then an edge case

5. Analyze time & space complexity
   - Always give Big-O at the end
   - Be ready to optimize if asked

6. Handle edge cases explicitly
   - Empty input, null, single element, overflow
```

---

## 🔷 DOMAIN 6: Amazon-Specific Technical Knowledge

> Bonus points if you know AWS services — Amazon teams use them daily.

| Service Category | Key Services to Know |
|---|---|
| **Compute** | EC2, Lambda, ECS/EKS, Fargate |
| **Storage** | S3, EBS, EFS, Glacier |
| **Database** | DynamoDB, RDS, Aurora, ElastiCache |
| **Messaging** | SQS, SNS, EventBridge, Kinesis |
| **Networking** | VPC, API Gateway, CloudFront, Route 53 |
| **Observability** | CloudWatch, X-Ray, AWS Config |

> You don't need to be an AWS expert, but knowing **when to use DynamoDB vs RDS** or **SQS vs Kinesis** shows real-world thinking.

---

## 📅 Week-by-Week Execution Plan

| Week | Focus | Daily Time |
|---|---|---|
| 1 | Arrays, Strings, HashMap, Sorting | 2–3 hrs |
| 2 | Linked List, Stack, Queue, Binary Search | 2–3 hrs |
| 3 | Trees, Tries, Recursion, Backtracking | 2–3 hrs |
| 4 | Graphs (BFS/DFS/Topo/Union Find) | 2–3 hrs |
| 5 | Dynamic Programming | 3 hrs |
| 6 | Heaps, Sliding Window (Advanced), Bit Manipulation | 2–3 hrs |
| 7 | System Design Foundations + Framework | 2 hrs SD + 1 hr LP |
| 8 | System Design: Tier 1 problems (4 designs) | 2 hrs SD + 1 hr LP |
| 9 | System Design: Tier 2 problems + Advanced | 2 hrs SD + 1 hr LP |
| 10 | LP Story Bank (write all 8–10 stories) | 2 hrs LP + 1 hr DSA revision |
| 11 | Mock LP interviews + OOD + AWS basics | Mixed |
| 12 | **Full Mock Loops** (DSA + SD + LP combined) | Full sessions |

---

## 🎯 Mock Interview Strategy (Week 12 + Ongoing)

| Platform | What For |
|---|---|
| **Interviewing.io** | Paid mocks with FAANG engineers (highly recommended) |
| **Pramp** | Free peer-to-peer mock interviews |
| **LeetCode Mock Assessment** | Timed OA simulation |
| **Meetapro** | Mock with ex-Amazon interviewers |
| **YouTube: Mock SD interviews** | Watch Clement Mihailescu, Exponent channel |

---

## 📊 L5 vs L6 Comparison — What to Optimize For

| Dimension | L5 Target | L6 Target |
|---|---|---|
| **DSA** | Consistent Mediums, some Hards | Mediums fast + Hards reliably |
| **System Design** | Single-system clarity, good tradeoffs | Multi-system scope, ambiguity handling, drive conversation |
| **LP Scope** | Team-level impact | Org/company-level impact |
| **Ownership** | Feature/service owner | Platform/product owner |
| **Communication** | Clear and structured | Shapes conversation, handles pushback |

---

## 🔑 Final Tips

> 1. **Don't grind blindly** — understand patterns, not just memorize solutions.
> 2. **System design > DSA at senior levels** — invest more time here.
> 3. **LP stories are non-negotiable** — write them down word-for-word, practice out loud.
> 4. **Quantify everything** — "improved latency by 40%", "served 10M users", "reduced cost by $200K/year".
> 5. **Amazon hires for the LP bar first** — a brilliant engineer who doesn't demonstrate LPs will get rejected.
> 6. **Negotiate the level** — if you perform strongly, ask to be considered for L6.

---

> 💡 **You have 9 years of strong industry experience from Malaysia — that's an asset. Focus your LP stories on large-scale ownership, cross-team impact, and architectural decisions. That's what separates a strong L5 from an L6 candidate.**