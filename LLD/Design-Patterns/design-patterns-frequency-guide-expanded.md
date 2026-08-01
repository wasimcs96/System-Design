---
title: "Top Design Patterns for Interviews — Expanded Country & Company Edition"
subtitle: "Frequency Ratings + Company-Level Data — Saudi Arabia | Dubai/UAE | Malaysia | India Tier-2 | India Tier-1/60LPA+"
author: "Interview Prep Reference"
date: "Updated July 2026"
---

# Top Design Patterns Asked in Interviews
### Expanded Edition — Country-Wise Company Data, Difficulty Levels & Pattern Demand

---

## How to Use This Document

This is the expanded version of your original frequency guide. It keeps the core pattern-frequency and pattern-recognition material, and adds a company-level research layer: for each of your five target markets (Saudi Arabia, Dubai/UAE, Malaysia, India Tier-2, and India Tier-1/60LPA+), you now have a table of roughly 25–30 employers with what's actually known about their LLD/design-pattern interview rounds — pulled from Glassdoor, LeetCode Discuss, GeeksforGeeks, Blind, and company engineering write-ups.

**India Tier-2 vs. Tier-1/60LPA+ — these are deliberately separate markets, not a relabeling.** Tier-2 covers well-funded product companies and fintechs (Razorpay, PhonePe, Swiggy, CRED-tier). Tier-1/60LPA+ covers the FAANG-adjacent and top-comp bracket (Amazon India, Google India, Flipkart, Atlassian Bengaluru, Rippling, Goldman Sachs India-tier) — a measurably higher bar, confirmed via a dedicated research pass rather than assumed.

**A note on data honesty before you use this:** public interview-report volume varies enormously by company. Big regional players (Careem, Talabat, Property Finder, Maybank, Razorpay, Swiggy) have dozens of dated, specific candidate reports. Many smaller or younger companies (especially in Saudi Arabia and Malaysia) have close to zero public interview write-ups. Every row below is labeled **Confirmed** (a real, sourced candidate report exists) or **Inferred** (no direct report found — pattern guess is based on the company's domain, e.g. a payments company probably tests Strategy/Adapter for gateway routing). Treat Inferred rows as reasonable priors, not facts. Nothing below is a fabricated quote or invented source.

**Rating scale:** Very High · High · Medium · Low

---

## 1. Master Frequency Table

| # | Pattern | Category | Global Frequency | Notably explicit in | Where It Shows Up |
|---|---------|----------|:---:|---|---|
| 1 | **Strategy** | Behavioral | Very High | All 5 markets — the single most universal pattern, incl. India Tier-1 (Microsoft cache eviction, VMware) | Pricing/discount engines, payment gateways, sorting/eviction policies |
| 2 | **Factory / Abstract Factory** | Creational | Very High | UAE (Property Finder), India | Parking Lot, Vehicle systems, Notification channels |
| 3 | **Singleton** | Creational | Very High | Malaysia (asked to be written live at Maybank & AirAsia) | Logger, Config Manager, DB Connection Pool |
| 4 | **Observer** | Behavioral | Very High | India (PhonePe pub-sub), UAE (Yellow.ai pub-sub) | Notification systems, stock tickers, event-driven/pub-sub systems |
| 5 | **Decorator** | Structural | High | General — no strong regional skew found | Pizza/Coffee ordering, middleware chains, UI customization |
| 6 | **Builder** | Creational | High | India (MakeMyTrip — thread-safe Builder w/ atomics) | Complex object construction, HTTP request builder, query builder |
| 7 | **State** | Behavioral | High | India (order-lifecycle heavy: Swiggy, Zomato, Meesho, Nykaa) | Elevator, Vending Machine, Order lifecycle |
| 8 | **Adapter** | Structural | Medium | UAE/Saudi (open banking, 3rd-party gateway integration) | Third-party API integration, legacy system wrapping |
| 9 | **Command** | Behavioral | Medium | Malaysia (Gojek — ride/order request objects) | Undo/Redo, Task Scheduler, Remote Control |
| 10 | **Chain of Responsibility** | Behavioral | Medium | Saudi/UAE fintech (fraud/approval pipelines) | Middleware/filters, approval workflows, logging levels |
| 11 | **Composite** | Structural | Medium | General | File System design, Org hierarchy, UI trees |
| 12 | **Facade** | Structural | Low–Medium | General | Simplifying subsystem access |
| 13 | **Proxy** | Structural | Low–Medium | India (Razorpay Rate Limiter) | Caching layer, Rate limiter, Lazy loading |
| 14 | **Template Method** | Behavioral | Low | General | Report generation, algorithm skeletons |
| 15 | **Prototype** | Creational | Low | General | Object cloning, invoice/template duplication |

> **Prep priority unchanged:** master rows 1–7 in depth. Rows 8–11 need working knowledge. Rows 12–15 need definition + one example.

---

## 2. Classic LLD Problems → Patterns Tested

| LLD Problem | Patterns Tested |
|---|---|
| Parking Lot System | Factory, Strategy, Singleton |
| Elevator System | State, Strategy, Observer |
| Splitwise / Expense Sharing | Strategy, Observer |
| Movie Ticket Booking (BookMyShow-style) | Factory, Singleton, State, Strategy |
| Food Delivery (Swiggy/Zomato/Talabat-style) | Observer, Strategy, State, Factory |
| Ride-Sharing (Uber/Careem-style) | Strategy, Observer, Factory, State |
| Rate Limiter | Strategy, Proxy, Singleton |
| Notification System (Email/SMS/Push) | Observer, Factory, Strategy |
| Vending Machine | State, Strategy |
| Chess / Tic-Tac-Toe | State, Strategy, Command |
| Logging Framework | Singleton, Chain of Responsibility, Strategy |
| LRU Cache Design | Strategy (eviction policy), Singleton |
| ATM Machine | State, Strategy, Command |
| Library Management System | Factory, Observer, Singleton |
| Hotel Booking System | Strategy, State, Factory, Observer |

### 2b. Company-Reported LLD Problems (real, dated candidate reports, 2024–2025)

These are problems specifically confirmed via candidate write-ups during this research pass — a good "surprise-proof" list to add to your practice set:

| Problem | Reported at |
|---|---|
| Class design for a Twitter-like object model | Careem (Dubai/Riyadh) |
| Yelp-like nearby-places system (HLD) | Careem |
| Parking Lot with role-based discounts (UML, extensibility) | Property Finder (Dubai) |
| SAGA pattern / distributed transaction orchestration | Property Finder |
| Pub-sub event-management service | Yellow.ai (Dubai) |
| Multi-tenant SaaS architecture | Emirates NBD (Dubai) |
| Message queue / pub-sub with regex-based subscribers | PhonePe (India) |
| Multilevel cache design | PhonePe |
| Splitwise-style expense manager (with partial splits) | Swiggy, Groww, ShareChat (India) |
| Restaurant/food-ordering order-lifecycle system | Zomato (India) |
| Thread-safe Builder pattern (atomics) | MakeMyTrip (India) |
| Rate Limiter (Token Bucket / Sliding Window) | Razorpay (India) |
| Payment Ledger, Loan EMI Calculator, Logger system | Razorpay |
| LRU Cache / Key-Value Store | PolicyBazaar, CRED (India) |
| Doctor-appointment / clinic booking system | Meesho (India) |
| Facebook/Twitter-like feed with follow + pagination | Meesho |
| Car rental system | Nykaa, CARS24 (India) |
| Snake & Ladder (modular, demoable) | CARS24 |
| Audit Log System / File-parsing service (Strategy) | Freshworks (India) |
| Reverse-proxy with round-robin load balancing | BrowserStack (India) |
| URL Shortener | iFlix (Malaysia) |
| Cache with custom eviction policy | Shopee (Malaysia/SEA hub) |
| "Design an online cart system" | Carsome (Malaysia) |
| Microservices vs. monolith trade-off discussion | HungerStation (Saudi) |
| Highly-available backend for millions of sensor events/day | Aramco Digital (Saudi) |

---

## 3. Recognition Triggers (Pattern-Spotting Cheat Sheet)

| If the problem says... | Think... |
|---|---|
| "Multiple ways to calculate/process something (payment, discount, route)" | **Strategy** |
| "Object creation should be centralized/hidden from client" | **Factory** |
| "Only one instance should ever exist (config, logger, connection pool)" | **Singleton** |
| "Notify multiple parts of the system when something changes" | **Observer** |
| "Add optional features/behavior without changing the base class" | **Decorator** |
| "Object has many optional fields / complex step-by-step construction" | **Builder** |
| "Behavior changes based on internal status/lifecycle" | **State** |
| "Integrate with a third-party API that has a different interface" | **Adapter** |
| "Need undo, redo, or queued/deferred actions" | **Command** |
| "Request passes through multiple handlers (validation, auth, logging)" | **Chain of Responsibility** |
| "Tree-like structure — folders/files, org chart, menus" | **Composite** |
| "Simplify a complex subsystem behind one clean interface" | **Facade** |
| "Control/restrict access to an object (caching, lazy-load, permissions)" | **Proxy** |
| "Object creation is expensive and you need many similar copies" | **Prototype** |
| "Same algorithm skeleton, different steps per subclass" | **Template Method** |

---

## 4. Country-Wise Deep Dive

Each section below: what dominates, difficulty, interview format, then a company table. Confidence key — **C** = Confirmed via a real sourced report, **I** = Inferred (no direct report found, domain-based guess).

---

### Saudi Arabia

**Most-asked pattern:** Strategy and Observer, tied — both largely *implied* by domain (payment/pricing logic, order/delivery notifications) rather than named outright. **SOLID principles and "justify your architectural choice" reasoning** are tested more explicitly than GoF pattern names at most companies.

**Difficulty:** Trails global big-tech LLD bars — most regional companies rate 2.7–3.1/5 on Glassdoor (Medium). Exceptions run Hard: Amazon, Uber, and Aramco Digital (rigorous system-design grilling on caching, Kafka, high-availability).

**Format:** Typically 3–4 rounds over 2–7 weeks. A single combined system-design/LLD round is more common than a dedicated LLD-only round, except at Careem (explicit architecture-and-patterns round) and HungerStation (explicit "architecture and design patterns" questions). Recurring candidate complaint across multiple companies: slow feedback / ghosting after technical rounds.

| Company | Sector | Conf. | Difficulty | Top patterns tested | Common LLD problems / topics | Source |
|---|---|:---:|---|---|---|---|
| Careem | Ride-hailing/super-app | C | Medium–Hard | Explicit design-pattern selection & justification | Twitter-like class design (LLD); Yelp-like nearby-places (HLD) | [InterviewQuery](https://www.interviewquery.com/interview-guides/careem-software-engineer), [Blind](https://www.teamblind.com/post/careem-technical-interview-ygh1zmyd) |
| Talabat (Delivery Hero KSA) | Food delivery | C | Medium (2.7/5) | Design patterns named in coding round; DDD | TDD kata + refactoring; domain-driven system design | [Glassdoor](https://www.glassdoor.com/Interview/Talabat-Software-Engineer-Interview-Questions-EI_IE1456233.0,7_KO8,25.htm) |
| HungerStation (stc-owned) | Food delivery | C | Medium (2.9/5) | Explicit "architecture and design patterns"; SOLID | Microservices vs. monolith; DB indexing/query efficiency | [Glassdoor](https://www.glassdoor.com/Interview/HungerStation-Software-Engineer-Interview-Questions-EI_IE2357983.0,13_KO14,31.htm) |
| Tamara | Fintech (BNPL) | C | Medium (3/5) | Strategy/State (inferred from domain) | Build "SplitPayment" from UI mock (1hr); integrating new project with legacy monolith | [Glassdoor](https://www.glassdoor.com/Interview/Tamara-Interview-Questions-E5303711.htm) |
| Tabby | Fintech (BNPL) | C (process) / I (patterns) | Easy–Medium | Strategy, State (inferred) | System design round; code review exercise; ACID/transaction isolation | [Glassdoor](https://www.glassdoor.com/Interview/tabby-Software-Engineer-Interview-Questions-EI_IE6075206.0,5_KO6,23.htm) |
| noon (KSA ops) | E-commerce | C (process) / I (patterns) | Medium (3.1/5) | Not itemized | DSA (LeetCode-Medium) + DB design | [Glassdoor](https://www.glassdoor.com/Interview/noon-Software-Engineer-Interview-Questions-EI_IE1669856.0,4_KO5,22.htm) |
| stc / STC Group | Telecom/digital | C | Medium (2.87/5) | **MVP, MVVM** explicitly named (Android roles) | OOP theory, coroutines/multithreading, mini-project | general synthesis |
| Aramco Digital | Energy-tech | C | Hard | No GoF names, but rigorous "why this choice" grilling | HA backend for millions of sensor events/day; cache-failure scenarios | [Medium](https://medium.com/career-drill/aramco-staff-backend-engineer-interview-experience-ea4451b0615c) |
| Mozn | AI/RegTech | C | Medium (2.9/5) | Not confirmed | BFS-based DSA + open-ended system design | [Glassdoor](https://www.glassdoor.com/Interview/Mozn-Senior-Software-Engineer-Interview-Questions-EI_IE4639668.0,4_KO5,29.htm) |
| Amazon (Riyadh) | Big Tech/Cloud | C (global loop) | Hard | Full GoF toolkit, SOLID | Design LRU Cache, Parking Lot, Unix "find" command | codezym.com/lld/amazon |
| Uber (regional) | Ride-hailing | C (global loop) | Hard | Concurrency-aware design | Key-value store handling millions of req + TTL | [Blind](https://www.teamblind.com/post/uber-recent-interview-software-engineer-2-ggmrd1ox) |
| Lean Technologies | Fintech infra (open banking) | C (process) / I (content) | Medium | Adapter/Strategy (inferred) | 4-stage process + case study to VP Finance | [Glassdoor](https://www.glassdoor.com/Interview/Lean-Technologies-Interview-Questions-E3920259.htm) |
| Unifonic | CPaaS/messaging | C (process) / I (content) | Medium (3.08/5) | Observer (inferred) | 1hr technical interview on past projects | [Glassdoor](https://www.glassdoor.com/Interview/Unifonic-Interview-Questions-E722866.htm) |
| Al Rajhi Bank / Neoleap | Digital banking | C (process) / I (content) | Medium | State (transaction lifecycle), Strategy (fee rules) — inferred | Panel case study, then individual interviews | [Glassdoor](https://www.glassdoor.com/Interview/Al-Rajhi-Banking-Interview-Questions-E40214.htm) |
| SNB / SAB digital | Banking | C (process) / I (content) | Unknown | Strategy/State (inferred) | 3-round process | [Glassdoor](https://www.glassdoor.com/Interview/SNB-Interview-Questions-E353475.htm) |
| Foodics | Retail-tech/POS SaaS | C (process) / I (content) | Unknown | Observer, State, Strategy (inferred) | HR → take-home → tech interview → 2nd take-home | [Glassdoor](https://www.glassdoor.com/Interview/Foodics-Interview-Questions-E2596015.htm) |
| Geidea | Fintech/payments | C (difficulty only) | Hard (8/10) | Strategy/Adapter/CoR (inferred) | Not surfaced | [Indeed](https://www.indeed.com/cmp/Geidea/interviews) |
| Elm Company | Gov-tech (PIF-owned) | C (process) / I (content) | Medium | Not confirmed | 2-phase, mostly behavioral | [Glassdoor](https://www.glassdoor.com/Interview/ELM-Interview-Questions-E1180316.htm) |
| Accenture (KSA centers) | IT consulting | C (global) | Medium | Factory, Strategy, Observer, Singleton flagged as "most important" | Scenario-based pattern implementation; HashMap/TreeMap internals | [InterviewQuery](https://www.interviewquery.com/interview-guides/accenture-software-engineer) |
| TCS (KSA delivery centers) | IT services | C (global) | Easy–Medium | OOP fundamentals; polymorphism a "favorite" | OOP + DSA + SQL rounds | [GeeksforGeeks](https://www.geeksforgeeks.org/tcs-interview-experience-for-software-developer/) |
| Cognizant (KSA) | IT services | C (global) | Easy–Medium | OOP fundamentals | Technical → Managerial → HR | general synthesis |
| Mrsool | On-demand delivery | C (process) / I (content) | Medium–Hard | Not confirmed | Phone screen → algo round → system design | [Glassdoor](https://www.glassdoor.com/Interview/MRSOOL-Software-Engineer-Interview-Questions-EI_IE2574567.0,6_KO7,24.htm) |
| Jahez | Food delivery | I | Unknown | State, Observer, Strategy (inferred) | No public data found | general regional inference |
| Salla | E-commerce SaaS | I | Unknown | Factory, Strategy, Adapter (inferred) | Indeed confirms no interview data exists | [Indeed](https://www.indeed.com/cmp/Salla/interviews) |
| Sary | B2B marketplace | I | Unknown | Strategy, Observer (inferred) | Indeed confirms no interview data exists | [Indeed](https://www.indeed.com/cmp/Sary/interviews) |
| Nana | Quick commerce | I | Unknown | Strategy, Observer, Decorator (inferred) | No public data found | general regional inference |
| Zid | E-commerce SaaS | I | Unknown | Factory, Strategy, Adapter (inferred) | No public data found | general regional inference |
| Rabbit | Quick commerce (new KSA entrant) | I | Unknown | Strategy, Observer (inferred) | Too new to market, no data | general regional inference |
| PayTabs | Fintech/payments | I | Unknown | Strategy, Adapter, CoR (inferred) | Stack confirmed as Java/Spring only | general regional inference |

---

### Dubai / UAE

**Most-asked pattern:** Strategy and Factory lead, but the region's real distinguishing feature is **SAGA / distributed-transaction patterns and pub-sub design** showing up explicitly (Property Finder, Yellow.ai) — more architecture-of-microservices flavored than pure GoF-pattern-naming.

**Difficulty:** Clusters Medium region-wide (2.7–3.0/5 on Glassdoor), below FAANG. Property Finder is the standout exception — candidates describe a genuinely hard, multi-round LLD+HLD+DSA day.

**Format:** Consistent pipeline across confirmed companies: recruiter screen → DSA round (often LeetCode-Medium) → dedicated component/class-design or LLD round → HLD round → hiring-manager/behavioral. Property Finder and Careem are the two companies that most explicitly label a round "Low Level Design."

| Company | Sector | Conf. | Difficulty | Top patterns tested | Common LLD problems / topics | Source |
|---|---|:---:|---|---|---|---|
| Careem (incl. NOW/Pay) | Ride-hailing/super-app | C | Medium–Hard | OOD class design, bar-raiser eval | Class design (social/Twitter-like); Yelp-like HLD | [Glassdoor](https://www.glassdoor.com/Interview/Careem-Interview-Questions-E1438731.htm) |
| Property Finder | Proptech | C — strongest UAE signal | Hard | Explicit "Low Level Design" round; SAGA; microservice orchestration | Parking Lot UML (role-based discounts, extensibility); ride-hailing HLD | [LeetCode Discuss](https://leetcode.com/discuss/interview-experience/6278332/PropertyFinder-or-Software-Engineer-or-Dubai-or-Jan-2025-Offer), [Glassdoor](https://www.glassdoor.com/Interview/Property-Finder-Software-Engineer-Interview-Questions-EI_IE1176641.0,15_KO16,33.htm) |
| Talabat (Delivery Hero) | Food delivery | C | Medium | TDD, DDD, queuing, observability | TDD kata/refactoring; scalability/caching/RPC system design | [Medium](https://vali-odedra.medium.com/what-its-like-to-interview-at-talabat-2c26ffd3d7cf), [Glassdoor](https://www.glassdoor.com/Interview/Talabat-Software-Engineer-Interview-Questions-EI_IE1456233.0,7_KO8,25.htm) |
| Dubizzle / Bayut | Classifieds/property | C | Easy–Medium (2.94/5) | OOP, caching strategies, queuing, security patterns (senior roles) | ER diagram of a social-media-like app + queries | [Glassdoor](https://www.glassdoor.com/Interview/Dubizzle-Interview-Questions-E670451.htm) |
| Amazon (Dubai/MENA) | E-commerce/cloud | C (global loop) | Hard | SOLID, OOD, bar-raiser | Amazon's standard LLD bank (Parking Lot, Library-style) | [Amazon interview prep](https://amazon.jobs/content/en/how-we-hire/interview-prep/software-development-topics) |
| Emirates NBD | Banking | C (partial) | Medium | Multitenancy architecture; MVVM (iOS roles) | Multi-tenant SaaS architecture question | [Medium](https://medium.com/@g.edwinlal/my-emirates-nbd-senior-software-engineer-interview-experience-56e2d8af4a78) |
| ADCB | Banking | C (generic) | Medium | OOD principles, distributed systems | Java, networking (TCP/SSL), no specific LLD problem documented | [Glassdoor](https://www.glassdoor.com/Interview/Abu-Dhabi-Commercial-Bank-Interview-Questions-E546874.htm) |
| Etisalat / e& | Telecom | C (partial) | Medium (2.7/5) | Java, Hibernate, Spring, DB design | No specific LLD case documented | [Glassdoor](https://www.glassdoor.com/Interview/Etisalat-Software-Developer-Interview-Questions-EI_IE15014.0,8_KO9,27.htm) |
| Emirates Group (airline IT) | Aviation/IT | C (partial) | Medium–Hard | Microservice architecture discussion | "Describe architecture of your project/microservice components" | [Glassdoor](https://www.glassdoor.com/Interview/The-Emirates-Group-Software-Engineer-Interview-Questions-EI_IE23433.0,18_KO19,36.htm) |
| Yellow.ai (has Dubai ops) | Conversational AI | C (company-wide) | Medium–Hard | Explicit **pub-sub** LLD round | Event-management service design, chatbot HLD, pub-sub LLD | [Glassdoor](https://www.glassdoor.com/Interview/yellow-ai-Interview-Questions-E2065855_P3.htm) |
| Deliveroo (owns InstaShop UAE) | Food delivery | C (global process) | Medium–Hard | Concurrency, scalability, locking | Architecture round on concurrency/high-scale/locking | [Deliveroo careers guide](https://careers.deliveroo.co.uk/interview-guide-for-engineers/) |
| Zoho (Dubai presence) | SaaS | C (global) / I (UAE-specific) | Medium–Hard | Hands-on OOP pattern implementation | Mini-app/CLI tool demonstrating OOP patterns | [InterviewQuery](https://www.interviewquery.com/interview-guides/zoho-software-engineer) |
| G42 | AI/tech holding | I | Medium (2.73/5) | Infra-heavy (Linux, Terraform, K8s, AWS) more than OOD | No LLD report found | [Glassdoor](https://www.glassdoor.com/Interview/G42-Interview-Questions-E3280022.htm) |
| Mashreq (Neo) | Digital banking | I | Medium | Not confirmed | Generic technical rounds only | [Glassdoor](https://www.glassdoor.com/Interview/Mashreq-Interview-Questions-E157674.htm) |
| Noon.com | E-commerce | I | Medium | Strategy/Factory/Observer (inferred) | Product catalog, cart, pricing (inferred) | general regional inference |
| Kitopi | Cloud-kitchen tech | I | Medium | Order/kitchen orchestration (inferred) | No SE-specific report found | [Glassdoor](https://www.glassdoor.com/Interview/Kitopi-Interview-Questions-E2860171.htm) |
| Network International | Fintech/payments | I | Medium–Hard | Strategy/Adapter for gateway integration (inferred) | Payment processing system (inferred) | general regional inference |
| du (EITC) | Telecom | I | Medium | Not confirmed | No LLD-specific report found | general regional inference |
| Presight AI (G42) | AI/data analytics | I | Medium | SQL/Python/data-modeling focus (found); no LLD | Not confirmed | general regional inference |
| Chalhoub Group (tech arm) | Luxury retail digital | I | Medium | Not confirmed | Generic technical questions only | [Glassdoor](https://www.glassdoor.com/Interview/Chalhoub-Group-Dubai-Interview-Questions-EI_IE499602.0,14_IL.15,20_IM954.htm) |
| InstaShop (Deliveroo-owned) | Grocery/q-commerce | I | Medium | Not confirmed | No SE-specific questions found | [Glassdoor](https://www.glassdoor.com/Interview/InstaShop-Interview-Questions-E1898923.htm) |
| Trukker | Logistics tech | I | Medium | Not confirmed | No data found | general regional inference |
| Fetchr | Last-mile logistics | I | Medium | Not confirmed | Company has scaled down significantly | general regional inference |
| YallaCompare | Fintech comparison | I | Medium | Not confirmed | No data found | general regional inference |
| ENOC / Petrolina | Energy/retail tech | I | Easy–Medium | Not confirmed | Generic HR-style questions only | [Glassdoor](https://www.glassdoor.com/Interview/ENOC-Interview-Questions-E379903.htm) |
| Dubai REST / Ejari | Gov proptech | I | Medium | Not confirmed | No candidate reports found | general regional inference |
| Bayzat | InsurTech/HRTech | I | Medium | Not confirmed | No data found | general regional inference |
| Anghami | Music streaming (Abu Dhabi HQ) | I | Medium | Not confirmed | No data found | general regional inference |
| Tabby / Tamara (UAE hiring) | Fintech/BNPL | I | Medium–Hard | Strategy/State (inferred) | Payment/checkout system design (inferred) | general regional inference |

*Note: Zomato exited the UAE market in 2019 (sold to Talabat/Delivery Hero) — not included as a current independent employer.*

---

### Malaysia

**Most-asked pattern:** **Singleton is uniquely explicit here** — candidates at both Maybank and AirAsia report being asked to write it out live (double-checked locking discussed at Maybank). Strategy is a close second, especially at e-commerce/superapp employers.

**Difficulty:** Medium at Malaysia-local companies (banks, StoreHub, Carsome). One notch harder — approaching Hard/near-FAANG — at Singapore-HQ'd regional offices hiring into KL (Shopee, Agoda, Grab, Gojek), so candidates targeting SEA-regional tech hubs should prep at the higher bar even if based in Malaysia.

**Format:** Combined HLD+LLD or "system design + code review" rounds are more common than a pure whiteboard LLD round — Agoda explicitly critiques pattern usage during a code review, Gojek asks candidates to discuss patterns in code they just wrote, Maybank runs two back-to-back system-design problems. Live coding (Spring Boot CRUD, Singleton implementation) is frequently expected, not just diagrams.

| Company | Sector | Conf. | Difficulty | Top patterns tested | Common LLD problems / topics | Source |
|---|---|:---:|---|---|---|---|
| Maybank | Digital banking | C | Medium–Hard | **Singleton** (double-checked locking, explicit) | Layered architecture, Hibernate N+1, 2 system-design problems onsite | [GeeksforGeeks](https://www.geeksforgeeks.org/geeksforgeeks/maybank-malaysia-interview-experience-for-senior-developer/), [Glassdoor](https://www.glassdoor.com/Interview/Maybank-Software-Developer-Interview-Questions-EI_IE7664.0,7_KO8,26.htm) |
| AirAsia / Capital A | Airline/Superapp (MOVE) | C | Medium–Hard | **Singleton** (asked to write live) | CAP theorem, SOLID, scaling, O(n²)→O(n) refinement, Spring Boot CRUD | [Glassdoor](https://www.glassdoor.com/Interview/AirAsia-Senior-Software-Engineer-Interview-Questions-EI_IE318753.0,7_KO8,32.htm) |
| Shopee (SEA hub incl. MY) | E-commerce | C (regional) | Hard | Strategy, Factory, Observer, Singleton | Cache with eviction policy; how trees are stored on disk; DB/network fundamentals | [Glassdoor](https://www.glassdoor.com/Interview/Shopee-Senior-Software-Engineer-Interview-Questions-EI_IE1263091.0,6_KO7,31.htm) |
| Agoda (large KL presence) | Travel-tech | C (regional) | Medium–Hard | **Strategy** (explicitly named in code review) | LLD Notification System; "Booking API" code review, spot bugs/propose patterns | [Prepfully](https://prepfully.com/interview-guides/agoda-software-engineer) |
| Carsome | Automotive e-commerce | C | Medium | Strategy/Factory implied | Design online cart system; composite-key DB management | [Glassdoor](https://www.glassdoor.com/Interview/Carsome-Software-Engineer-Interview-Questions-EI_IE1680849.0,7_KO8,25.htm) |
| GoTo / Gojek | Ride-hailing/Superapp | C (regional) | Hard | **Command** (ride/order requests), Strategy (matching) | Machine coding → project discussion → HLD+LLD on recent project | [GitHub](https://github.com/kamleshchandnani/awesome-interview-processes/blob/master/content/companies/06-gojek-tech.md), [Blind](https://www.teamblind.com/post/gojek-india-interview-process-sse-jsy76osg) |
| JPMorgan (KL Payment Tech hub) | Banking/Fintech | C (active KL hiring) | Hard | SOLID-driven design discussion | Core Java + data engineering + design patterns; fault-tolerant architecture | [educative](https://www.educative.io/blog/jp-morgan-system-design-interview-questions) |
| MoneyLion (KL eng. center) | Fintech | C | Medium | Not pattern-named | SQL vs NoSQL trade-off, algorithm round | [Glassdoor](https://www.glassdoor.com/Interview/MoneyLion-Interview-Questions-E1053194.htm) |
| CIMB / CIMB Octo | Digital banking | C (general CIMB) | Medium | Layered/service architecture (inferred) | HackerRank + easy DSA; backend stack justification | [Glassdoor](https://www.glassdoor.com/Interview/CIMB-Group-Software-Developer-Interview-Questions-EI_IE40512.0,10_KO11,29.htm) |
| iFlix | Streaming/OTT | C | Medium | Cloud/scalability focus over classic OOD | Design a URL Shortener (onsite); subscription mgmt take-home | [Glassdoor](https://www.glassdoor.com/Interview/iflix-Software-Engineer-Interview-Questions-EI_IE1147943.0,5_KO6,23.htm) |
| Grab (MY eng. hub) | Ride-hailing/Superapp | I | Hard | Strategy, Factory, Observer, State (industry-standard) | Parking-lot/ride-matching-style LLD (peer-superapp norm) | general regional inference |
| Curlec (Razorpay) | Fintech/Payments | I (parent-co data) | Medium–Hard | Strategy, Observer | In-memory pub-sub, photo-sharing app, severity-based logger (parent co.) | general inference from Razorpay reports |
| Boost (Axiata) | Fintech/eWallet | I | Medium | Strategy/Factory (wallet/txn norm) | Not confirmed | [Glassdoor listing](https://www.glassdoor.com/Interview/Boost-Malaysia-Interview-Questions-E2326926.htm) |
| PayNet / DuitNow | Payment infrastructure | I | Medium–Hard | Strategy, Chain of Responsibility (routing/validation) | Not found — domain-based reasoning (payment switch design) | general regional inference |
| Touch 'n Go eWallet | Fintech/eWallet | I | Medium | Strategy, Observer (wallet/txn norm) | Not confirmed | general regional inference |
| StoreHub | Retail/POS SaaS | I | Medium | Not confirmed | 80 MY reviews found, none SWE-technical | [Glassdoor](https://www.glassdoor.com/Interview/StoreHub-Malaysia-Interview-Questions-EI_IE1473638.0,8_IL.9,17_IN170.htm) |
| PropertyGuru | Proptech | I | Medium | Not confirmed | No data surfaced | general regional inference |
| Fave | Fintech/rewards | I | Unknown | Not confirmed | No data surfaced | general regional inference |
| ServisHero | Services marketplace | I | Unknown | Not confirmed | No data surfaced | general regional inference |
| Aeon Credit | Consumer finance | I | Medium | Not confirmed | No data surfaced | general regional inference |
| Public Bank (digital) | Banking | I | Medium | Not confirmed | No data surfaced | general regional inference |
| RHB | Banking | I | Medium | Not confirmed | No data surfaced | general regional inference |
| IBM Malaysia | Enterprise IT | I (global data) | Medium | Strategy, Factory, Observer, Singleton (global) | Vending Machine, Parking Lot System (global bank) | general inference |
| Dell Technologies (Cyberjaya/Penang) | Hardware/enterprise IT | I (global data) | Medium | OOP/pattern review cited generally | System design discussion (senior roles) | general inference |
| Micron Malaysia (Penang) | Semiconductor | I (global data) | Medium | OOP principles, not pattern-specific | DSA + embedded systems + some system design | general inference |
| Intel Malaysia (Penang) | Semiconductor | I (global data) | Hard (when LLD appears) | Not pattern-named; C++/memory-management skew | Parking Lot, Traffic Signal Controller, Elevator (global bank) | general inference |
| HSBC / Standard Chartered / PayPal / Visa / Mastercard (MY) | Banking/fintech caps | I | Medium–Hard | Not confirmed | Not confirmed — search budget exhausted before company-specific data pulled | general regional inference |
| Accenture Malaysia | IT consulting | I | Medium | Not confirmed | Varies heavily by client account | general regional inference |

---

### India Tier-2

**Most-asked pattern:** **Strategy** is the clear #1 — it demonstrates polymorphism/composition cleanly and shows up everywhere. **Observer, Factory, Singleton** round out the top 4, and **State** is unusually heavy here specifically because of the volume of order/booking-lifecycle problems (Swiggy, Zomato, Meesho, Nykaa, CARS24).

**Difficulty:** Easy–Medium at the classic IT-services majors (TCS, Infosys, Wipro, Cognizant, Capgemini, HCLTech, LTIMindtree — LLD is often folded into general OOP/project discussion rather than a dedicated timed round). Medium–Hard at well-funded product companies and fintechs (CRED, Razorpay, Freshworks, PhonePe, MakeMyTrip, Postman, BrowserStack) — comparable to India Tier-1 (Flipkart/Myntra-caliber) rigor, generally a notch below FAANG's design depth.

**Format:** A 90–150 minute standalone "Machine Coding" round producing working, compilable code plus a verbal design-defense, sometimes preceded by a separate DSA/LLD-theory round. **India-specific trend:** interviewers here more often ask candidates to directly *name and justify* a specific design pattern (Postman's process is a documented example) — a more definitional/theory-adjacent style than typical US interviews for the same companies.

| Company | Sector | Conf. | Difficulty | Top patterns tested | Common LLD problems / topics | Source |
|---|---|:---:|---|---|---|---|
| Razorpay | Fintech/Payments | C | Medium–Hard | Factory, Singleton, Strategy | Rate Limiter (Token Bucket/Sliding Window), Payment Ledger, Expense Splitter, Loan EMI Calculator, Logger | [LeetCode](https://leetcode.com/discuss/interview-experience/4824582/Razorpay-or-SDE2-or-Machine-Coding-Round-or-Reject/), [Dataford](https://dataford.io/interview-guides/razorpay/software-engineer) |
| PhonePe | Fintech | C | Medium–Hard | Observer (pub-sub), Strategy | Message-queueing/pub-sub system, multilevel cache design, support-ticketing system | [LeetCode](https://leetcode.com/discuss/interview-question/598134/phonepe-machine-coding-round-message-queuing-system/), [enginebogie](https://enginebogie.com/interview/experience/phonepe-software-engineer/160) |
| CRED | Fintech | C | Hard | SOLID-heavy, no single forced pattern | LRU Cache, Key-Value Store, Payment Recommendation system | [Medium](https://medium.com/@indraneel.ghosh1998/cred-sde-2-interview-experience-offer-16cf6d9fcf6c) |
| Swiggy | Food delivery/Q-commerce | C | Medium | Strategy, State, Observer | Splitwise-style expense manager; vaccine booking system | [Glassdoor](https://www.glassdoor.com/Interview/Swiggy-Software-Engineer-II-Interview-Questions-EI_IE952680.0,6_KO7,27.htm) |
| Zomato | Food delivery | C | Medium | Observer, State, Strategy | Restaurant/order-lifecycle system, delivery assignment | [Medium](https://medium.com/@prashant558908/design-of-a-food-ordering-system-like-zomato-swiggy-doordash-in-both-java-and-python-for-low-7d178a5f7b4c) |
| Meesho | E-commerce/Social commerce | C | Medium–Hard | Strategy, State | Doctor-appointment booking; Twitter-like feed w/ follow+pagination; inventory-blocking system | [LeetCode](https://leetcode.com/discuss/post/6702200/meesho-sde-1-backend-lld-machine-coding-uo43b/) |
| MakeMyTrip | Travel/OTA | C | Medium–Hard | Builder (thread-safe), Strategy | Vending Machine; thread-safe Builder w/ atomics; Uber-like driver allocation | [LeetCode](https://leetcode.com/discuss/interview-experience/5916765/MakeMyTrip-SDE-SSE-2-Interview-Experience/) |
| Freshworks | SaaS/Enterprise | C | Medium–Hard | Strategy | File-parsing service (Strategy), Audit Log System, LRU Cache; 4-round pipeline | [GeeksforGeeks](https://www.geeksforgeeks.org/interview-experiences/freshworks-interview-experience-for-senior-backend-developer/) |
| Postman | SaaS/DevTools | C | Medium–Hard | Strategy, Singleton, Factory, Observer | LLD for caching service; explicit note that patterns are named/justified directly | [Glassdoor](https://www.glassdoor.co.in/Interview/Postman-Software-Engineer-Interview-Questions-EI_IE1926052.0,7_KO8,25.htm) |
| BrowserStack | SaaS/DevTools | C | Medium–Hard | Strategy, State | 3-problem round (2 DSA + 1 LLD); reverse-proxy w/ round-robin load balancing | [Medium](https://medium.com/@ritinema23/browserstack-interview-experience-87eadadc7569) |
| Nykaa | E-commerce/Beauty | C | Medium–Hard | Strategy, State | Car-rental system; delivery-system design; multi-outlet w/ microservices | [LeetCode](https://leetcode.com/discuss/post/7773726/) |
| CARS24 | Used-vehicle marketplace | C | Medium | Strategy, State | Vehicle/car-rental LLD; Snake & Ladder (90-min modular round) | [GeeksforGeeks](https://www.geeksforgeeks.org/cars24-interview-experience-3-years-experienced/) |
| Groww | Fintech/Wealthtech | C | Medium | Strategy, State | Splitwise-style app; stock-exchange bid-matching; Spotify-like music app | [LeetCode](https://leetcode.com/discuss/interview-experience/6674506/) |
| ShareChat | Social media/Content | C | Medium | Singleton, Factory, Strategy | Expense-splitting system w/ partial splits | [Glassdoor](https://www.glassdoor.co.in/Interview/ShareChat-Interview-Questions-E1776417.htm) |
| PolicyBazaar | Insurtech/Fintech | C | Easy–Medium | Caching-related design | LRU Cache as first-round LLD problem | [LeetCode](https://leetcode.com/discuss/interview-experience/6325111/PolicyBazaar-oror-SDE-2-oror-DONT-WASTE-YOUR-TIME/) |
| Zoho | SaaS/Enterprise | C | Medium | OOP-first, less pattern-name-driven | Pattern-based pseudo-code problems, DB schema for feature extension | [GeeksforGeeks](https://www.geeksforgeeks.org/interview-experiences/zoho-interview-experience-on-campus-/) |
| Innovaccer | Healthtech SaaS | C | Medium | Strategy, layered DAO/service design | SD-III: DSA+LLD round 1, HLD+DB round 2 | [GeeksforGeeks](https://www.geeksforgeeks.org/innovaccer-interview-experience-for-junior-python-engineer-1-year-experienced/) |
| Delhivery | Logistics | C (generic) | Medium | Strategy, Factory | 3 rounds/4 problems spanning DSA, system design, LLD | [Naukri Code360](https://www.naukri.com/code360/interview-experiences/delhivery/delhivery-interview-experience-jul-2021-exp-0-2-years) |
| Sprinklr India | SaaS/Martech | C | Medium (3.22/5) | Component/composition design | Build a bar-chart component from scratch (machine coding) | [Glassdoor](https://www.glassdoor.co.in/Interview/Sprinklr-Interview-Questions-E427532.htm) |
| Dream11 | Fantasy sports/Gaming | C (generic) | Medium | Strategy, State, Factory, Observer | LLD round confirmed focused on OOD/patterns/testable code | [Glassdoor](https://www.glassdoor.com/Interview/Dream11-Interview-Questions-E493359.htm) |
| Rapido | Mobility (bike-taxi) | C (weak) | Medium | Strategy, Factory | Discussion of candidate's own past project design | [Naukri Code360](https://www.naukri.com/code360/interview-experiences/rapido/rapido-interview-experience-senior-product-engineer-apr-2022-exp-0-2-years) |
| Paytm | Fintech | C (generic) | Medium | Strategy, Factory | Listed among companies where LLD/machine coding is a mandatory round | [Medium](https://chakresh0108.medium.com/ultimate-list-of-lld-machine-coding-concurrency-design-questions-for-interviews-95efe1001bfe) |
| Ola | Mobility/Ride-hailing | C (generic) | Medium | Singleton, Factory, Strategy, Observer | Named as machine-coding-round employer; vehicle/dispatch modeling implied | general pattern-guide reference |
| Infosys | IT Services | C (generic) | Easy–Medium | Strategy, Observer, Factory, Singleton | Java/Spring Boot fundamentals; OOP applied to projects | [Glassdoor](https://www.glassdoor.com/Interview/Infosys-Interview-Questions-E7927.htm) |
| Cognizant | IT Services | C (generic) | Easy–Medium | Strategy, Observer, Factory, Singleton | OOPs tested in AI-Bot round + final technical round | [TechPrep](https://www.techprep.app/blog/cognizant-interview-process) |
| HCLTech | IT Services | C (generic, 2.7/5) | Easy | OOP principles applied to projects | Core Java/Spring Boot/React/SQL/REST; 1–2 live coding problems | [Glassdoor](https://www.glassdoor.com/Interview/HCLTech-Associate-Software-Engineer-Interview-Questions-EI_IE553909.0,7_KO8,35.htm) |
| LTIMindtree | IT Services | I | Easy–Medium | Strategy, Factory | String/DP problems; LLD/pair-programming round increasingly common | [GeeksforGeeks](https://www.geeksforgeeks.org/interview-experiences/ltimindtree-interview-experience-for-software-engineer-2/) |
| TCS Digital | IT Services | I | Easy–Medium | Strategy, Factory, Singleton | Basic OOP/SOLID discussion in digital-hire technical rounds | general regional inference |
| Wipro | IT Services | I | Easy–Medium | SOLID principles, OOP basics | Core OOP, DBMS normalization, OS; scalable API/caching for experienced hires | [TechPrep](https://www.techprep.app/blog/wipro-interview-process) |
| Capgemini | IT Services | I | Easy–Medium | Strategy, Factory | OOP concepts (encapsulation/inheritance/abstraction) | general regional inference |
| Urban Company | Home-services marketplace | I | Medium | Strategy, Observer (booking/dispatch) | Service-provider/booking assignment (sector norm) | general regional inference |
| InMobi | Adtech | I | Medium | Strategy, Observer | Ad-serving/bidding-engine style LLD plausible | general regional inference |
| Lenskart | E-commerce/Eyewear | I | Medium | Strategy, Factory | Inventory/order-management LLD plausible (omnichannel retail) | general regional inference |
| Darwinbox | HRTech SaaS | I | Medium | Strategy, State | Campus round observed = MCQ+DSA, not explicit LLD | [LeetCode](https://leetcode.com/discuss/post/5352628/darwinbox-coding-round-1-by-nithin093-2rvg/) |
| upGrad | Edtech | I | Easy–Medium | Strategy, Factory | Course/LMS entity-modeling LLD plausible | general regional inference |

---

### India Tier-1 / 60LPA+

**Most-asked pattern:** **Strategy** again leads (Microsoft's cache-eviction LLD, VMware, and the general prep-guide consensus all converge here), with **Singleton, Factory/Builder, and Observer** close behind — the same top group as Tier-2, but tested with meaningfully more rigor. **SOLID-principle articulation is a baseline expectation, not a bonus**, especially at Goldman Sachs, Atlassian, Rippling, and Walmart Global Tech.

**Difficulty:** Clearly exceeds India Tier-2. Where Tier-2 LLD rounds are generally Medium–Hard and pattern-naming-focused, this tier (Amazon, Google, Uber, Atlassian, Rippling, Microsoft, Goldman Sachs) more often demands working, compiling code with concurrency/thread-safety follow-ups (mutexes, thread pools, thread-safe eviction) layered on top of the base design. Multiple sources explicitly flag that candidates fail not on the core design but on unhandled edge cases or designs that require a large rewrite when a scale-up twist is introduced mid-interview (Atlassian, Rippling).

**Format:** A distinguishing structural trait at this tier is the **bar-raiser/veto mechanic** — Amazon's Bar Raiser and Microsoft's "As Appropriate" round both carry outsized, near-veto authority over the hire decision, and are frequently where the hardest LLD variant surfaces (Amazon's multi-lot Parking Lot twist was specifically reported in a Bar Raiser round). Banking/fintech players in this tier (Goldman Sachs, Morgan Stanley, Visa, PayPal, Mastercard) consistently run a **dedicated, separately-graded OOD/LLD round distinct from DSA** — a structural formality less commonly seen as a standalone gate at Tier-2 companies. Coverage confidence varies sharply by company: firms with large, English-heavy candidate-blogging communities (Amazon, Uber, Rippling, Atlassian, Walmart, Myntra) have rich, specific, recent (2024–2025) evidence; Meta India, Cisco, Grab, and Deutsche Bank yielded only generic or non-India-specific material and are marked Inferred.

| Company | Sector | Conf. | Difficulty | Level/Comp context | Top patterns tested | Common LLD problems / topics | Source |
|---|---|:---:|---|---|---|---|---|
| Amazon India | E-commerce/Cloud | C | Hard | SDE2/SDE3(L5)/L4, Bar Raiser has veto power | Strategy, general OOD/SOLID | Parking Lot (multi-lot variant), meeting/room reservation, Twitter API design, string+concurrency LLD | [LeetCode](https://leetcode.com/discuss/post/6044784/amazon-system-development-engineer-1-l4-sept-2024-chennai), [GeeksforGeeks](https://www.geeksforgeeks.org/interview-experience-of-amazon-sde2/) |
| Google India | Search/Cloud | C | Hard | L4/L5/L6 (L6 bar notably higher: multi-DC, partitioning) | Iterator, general OOD | Library/Iterator coding; LLD folded into system design in final ~10 min; tagging systems | [Blind (L6)](https://www.teamblind.com/post/Interview-experience-@-Google-L6-Bangalore-fBeBYXi4), [LeetCode (L4 India)](https://leetcode.com/discuss/post/1485434/google-india-onsite-l4/) |
| Flipkart | E-commerce | C | Medium–Hard | SDE2 | Strategy, State-machine patterns | Multi-level cache (L1/L2/L3), Wallet system, scalable Tic-Tac-Toe (nXn, multi-user), FSM, autocomplete | [GeeksforGeeks](https://www.geeksforgeeks.org/flipkart-interview-experience-for-sde-5/) |
| Walmart Global Tech (India) | Retail-tech | C | Medium–Hard | SDE3/Senior SE; Staff Engineer runs HLD round | Strategy, Dependency Injection, SOLID | Restaurant Reservation & Serving System; Aarogya-Setu-like app HLD | [GeeksforGeeks (SDE3)](https://www.geeksforgeeks.org/interview-experiences/walmart-global-tech-interview-experience-for-sde3/), [dev.to](https://dev.to/sukanyaa_rashmi_e1a8b8d4d/my-interview-experience-for-senior-software-engineer-role-at-walmart-global-tech-23o3) |
| Uber India | Ride-hailing | C | Hard | SDE2/L4 | Singleton, Strategy, multithreading | Train-platform scheduling w/ waiting queue; "Design Facebook" w/ 3 working features + concurrency; Linux file-system (mkdir/cd/ls) | [Medium](https://medium.com/@laxmankumarmegwal/uber-interview-experience-l4-sde2-backend-december-2024-da0f059c230e), [Blind](https://www.teamblind.com/post/uber-sde2-onsite-lldmachine-coding-round-ojyegdys) |
| Adobe India | Software/Creative-cloud | C | Medium | Senior SE (system design weighted more than DSA at senior levels) | General OOD principles | Booking app (order creation→execution flow); CoderPad 45–60 min format | [LinkedIn](https://www.linkedin.com/posts/ashishps1_my-adobe-india-interview-experience-and-activity-7071350497290448896-zvM8), [interviewing.io](https://interviewing.io/adobe-interview-questions) |
| Salesforce India | SaaS/CRM | C | Medium–Hard | SMTS (Senior Member Technical Staff) | General class/interface design | Bike Rental app (searchBikes w/ filters); Notification service (LLD+HLD combined) | [LeetCode](https://leetcode.com/discuss/interview-question/5794116), [Medium](https://medium.com/@padma.iitpatna/my-salesforce-smts-interview-experience-and-how-i-cracked-it-ea5f4e4796c6) |
| Microsoft India (IDC) | Cloud/Enterprise | C | Hard | Senior SDE / L63 (comp ~65L–1.1Cr per candidate report) | **Strategy** (eviction policy) | LRU Cache design (OOD+SOLID+Strategy); thread-safe transaction filter (mutex/locks) | [Medium](https://medium.com/@imran2018wahid/microsoft-sde-2-interview-experience-40fb0a4f41c0), [Rehearsal AI](https://www.tryrehearsal.ai/blog/microsoft-india-interview-complete-guide-2026) |
| Atlassian Bengaluru | DevTools/SaaS | C | Hard | SSE/Principal Engineer levels | SOLID-driven extensibility focus | Color Picker Tool, Tag Management System, Confluence-like feed, Web Crawler, Snake Game; graded on "scale-up requires minimal change" | [Medium](https://medium.com/@prashant558908/atlassian-low-level-design-questions-from-recent-interviews-d09c463c0889), [jointaro (Principal SE)](https://www.jointaro.com/interviews/companies/atlassian/experiences/principal-software-engineer-bengaluru-september-1-2024-no-offer-negative-7ee8b284/) |
| Goldman Sachs India | Investment Bank | C | Medium–Hard | SDE2/Java Developer, dedicated 60-min OOD round | SOLID-principles based OOD | Dedicated "Low-Level Design" round testing OOD/OOPs/clean code + thread pools/synchronization | [Medium](https://medium.com/techtrends-digest/my-goldman-sachs-sde2-interview-experience-a1d927860ab0) |
| Morgan Stanley India | Investment Bank | C | Medium | 2.5+ yrs experienced / Senior | General OOP/polymorphism | Online banking management system design w/ concurrency consideration | [GitHub gist](https://gist.github.com/asquare14/1847550449efff0ebdfc9e6ab3f4079e), [GeeksforGeeks](https://www.geeksforgeeks.org/morgan-stanley-interview-set-27-2-5-years-experienced/) |
| Directi / Media.net | Adtech | C | Medium–Hard | SDE/SRE | Singleton, Factory, Strategy, Adapter, Chain of Responsibility, Decorator, Template, Observer (explicitly named) | Socket-programming-based systems; LLD with deliberately unwritten/discussed requirements | [GeeksforGeeks](https://www.geeksforgeeks.org/interview-experiences/media-net-directi-interview-experience-on-campus/), [Medium](https://pravashjha21.medium.com/low-level-design-machine-coding-interview-saga-906837ca4730) |
| Meta India | Social/Ads | I (weak) | Hard | E5/E6 — India presence mostly non-core-SDE; loop generic | Java OOD encouraged given LLD-round possibility | ML system design dominates over classic LLD in found evidence | [Hellointerview (E5)](https://www.hellointerview.com/guides/meta/e5), general inference |
| ServiceNow India | Enterprise SaaS | C | Medium–Hard | Staff SE / IC3, SSE | General class-modeling | Loan Management System (EMI calc); employee-hierarchy DB design | [LeetCode (SSE IC3)](https://leetcode.com/discuss/interview-experience/4101399/Servicenow-or-SSE-or-IC3-or-Hyderabad-or-Offer) |
| Oracle India | Enterprise/Cloud | C | Medium | Software Developer (4.5 YOE), OCI roles | Strategy, Builder, Singleton, Observer (explicitly named) | Movie-booking system HLD w/ concurrent transactions & scalability | [GeeksforGeeks](https://www.geeksforgeeks.org/oracle-india-interview-experience-for-software-developer-4-5-years-experienced/), [LeetCode (OCI)](https://leetcode.com/discuss/post/912168/oracle-india-interview-experience/) |
| Visa India | Fintech/Payments | C | Hard | Senior/Staff SWE (10 YOE example) | Concurrency-aware OOD | BookMyShow-type LLD w/ concurrent booking handling + DB design | [Medium (Staff SE, 10 YOE)](https://anshurajlive.medium.com/my-visa-bengaluru-interview-experience-for-staff-software-engineer-10-yoe-97e5e773e552) |
| Mastercard India | Fintech/Payments | C (light) | Medium | SDE1 (evidence skews junior) | Singleton, Factory, Builder (explicit, e.g. Burger-Shop Builder) | Car system design w/ patterns | [LeetCode](https://leetcode.com/discuss/interview-experience/7299834/) |
| PayPal India | Fintech/Payments | C | Hard | SE3/T24, SDE2/L2 | Cloud-agnostic pattern selection (AWS/Azure abstraction) | File-based processing system w/ status tracking (HLD+LLD combined), class diagrams | [Substack (SE3)](https://roundz.substack.com/p/interview-experience-paypal-software-engineer-se3-t24), [Medium (Senior SE)](https://medium.com/@visahan.vnr/paypal-interview-experience-for-senior-software-engineer-java-role-66005ce12159) |
| Cisco India | Networking/Infra | I | Medium | General inference — no India-specific candidate account surfaced | Token bucket/sliding window style | Rate Limiter design (from general prep guides, not a verified transcript) | general inference |
| VMware India | Virtualization/Infra | C (light) | Medium | Senior Java Developer | **Strategy** (noted most common in India LLD rounds generally) | LRU Cache design; linked-list manipulation in coding round | [Medium (Senior Java Dev)](https://medium.com/javarevisited/my-interview-experience-with-vmware-for-the-senior-java-developer-role-3fc6efec4442) |
| Nutanix India | Cloud Infra/HCI | C | Medium–Hard | MTS-3/MTS-4 (senior IC track) | UML-driven class design | Cross-browser Bookmark Manager, Paint application (Windows Paint clone) | [LeetCode (MTS-3)](https://leetcode.com/discuss/interview-experience/4815315/Nutanix-or-MTS-3-or-Bengaluru-or-Feb-2024/), [LeetCode (MTS-4)](https://leetcode.com/discuss/interview-experience/6489221/) |
| Myntra | E-commerce/Fashion | C | Medium–Hard | SDE1/SDE2/SSE | Domain-driven class modeling | Shopping Cart w/ discount logic, Fashion Recommendation, Inventory atomic reservation, Flash Sale HLD (Redis DECR) | [Medium (SSE)](https://medium.com/@shbhggrwl/cracking-the-sse-role-at-myntra-my-complete-interview-experience-31858f6346c6), [GeeksforGeeks (SDE2)](https://www.geeksforgeeks.org/interview-experiences/myntra-interview-experience-for-sde-2-3-years-experienced/) |
| Cars24 | Auto/Marketplace | C | Medium | SDE2, Senior Java Developer | Extensible OOD | Vehicle rental system (APIs, DB schema, ER diagram + working code) | [LeetCode (Senior Java Dev SDE2)](https://leetcode.com/discuss/interview-experience/5011069/Cars24-or-Senior-Java-Developer-or-SDE2/) |
| Ather Energy | EV/Hardware-tech | C (light) | Medium | SDE/Backend Engineer | Not explicitly named | API Rate Limiter design in coding round (borderline LLD/coding hybrid) | [Medium](https://medium.com/career-drill/ather-sde-interview-experience-112c58d5542e) |
| Rippling India | HR-tech/SaaS (US-HQ) | C (strong) | Hard | Senior SE/SDE2 — "extremely selective, fails vast majority" | SOLID, extensibility-first OOD | Rule-Based System design; employee-record grouping/aggregation (extend to arbitrary key) | [jointaro](https://www.jointaro.com/interviews/companies/rippling/experiences/senior-software-engineer-india-october-5-2025-no-offer-negative-3f5a3d5b/), [enginebogie](https://enginebogie.com/interview/experience/rippling-senior-software-engineer/598) |
| Grab (India-facing eng) | Ride-hailing/Fintech (SG-HQ) | I | Medium–Hard | SDE2 (general APAC loop, no India-specific transcript) | Singleton, Factory, Strategy, Observer (general list, not verified) | Working module w/ compiling code, DS + design-pattern judged | general inference |
| Deutsche Bank India | Investment Bank | I | Medium | Inferred from peer banks (GS/MS pattern) | SOLID, general OOD (sector norm) | No direct evidence found; likely similar to GS/MS banking-domain LLD (loan/trade/portfolio systems) | general inference |

---

## 5. Cross-Country Comparison

| Country | #1 Pattern | #2 Pattern | Typical Difficulty | Distinctive trait |
|---|---|---|---|---|
| Saudi Arabia | Strategy (implied) | Observer (implied) | Medium, Hard at global cos | "Justify your architecture" reasoning valued over naming patterns |
| Dubai/UAE | Strategy / Factory | Observer / SAGA-pub-sub | Medium, Hard at Property Finder/Amazon | Explicit "Low Level Design"-labeled rounds; SAGA/distributed-transaction focus |
| Malaysia | **Singleton** (uniquely explicit) | Strategy | Medium, Hard at SG-linked hubs (Shopee/Grab/Agoda) | Live-code a pattern (not just diagram it); concurrency follow-ups common |
| India Tier-2 | **Strategy** | Observer / State | Easy–Medium at IT-services, Medium–Hard at product cos | Interviewers ask you to *name and justify* the pattern directly |
| India Tier-1/60LPA+ | **Strategy** | Singleton / Factory-Builder | Medium–Hard baseline, Hard at Amazon/Google/Atlassian/Rippling | Bar-raiser/veto rounds; concurrency + scale-up follow-ups layered on top of the base design |

**Bottom line for your prep:** Strategy is non-negotiable everywhere. If you're interviewing in Malaysia, drill Singleton (including thread-safety/double-checked locking) hard enough to write it from memory. If you're interviewing in UAE, add SAGA and pub-sub/event-driven system design to your HLD practice, not just class diagrams. India Tier-2 interviewers are the most likely to ask you to say the pattern name out loud and defend it — practice articulating "I used X instead of Y because Z" for your top 7 patterns. If you're targeting India Tier-1/60LPA+, the design itself is table stakes — what separates offers from rejections is handling a live scale-up or concurrency twist without a large rewrite, so rehearse extending your design under a follow-up, not just producing it once.

---

## 6. How Interviewers Actually Test This (Across All Regions)

```
Requirements → Identify entities → Class diagram → Code → Pattern justification
```

The pattern name is rarely the point — **the ability to justify why Strategy fits here instead of an if-else chain** is what's actually being scored. A strong answer always includes the trade-off: *"I used Strategy instead of a switch statement because new payment methods will be added frequently, and this keeps the Order class closed for modification."*

---

## 7. Quick Self-Check Before Any LLD Round

- [ ] Can I state Strategy, Factory, Singleton, Observer definitions in under 15 seconds each?
- [ ] Can I sketch Parking Lot + Elevator + Notification System class diagrams from memory?
- [ ] Can I write Singleton with thread-safety (double-checked locking) from memory — critical for Malaysia?
- [ ] Can I design a SAGA/distributed-transaction flow at a high level — relevant for UAE?
- [ ] For each pattern, do I have **one real production example**, not just a textbook one?
- [ ] Can I explain why I'd choose Strategy over a simple if-else, out loud, unprompted?
- [ ] Can I take my design and extend it live under a scale-up or concurrency twist without a large rewrite — critical for India Tier-1/60LPA+ bar-raiser rounds?

---

## 8. Methodology & Confidence Notes

This edition was built by researching ~25–30 companies per market (Saudi Arabia, UAE, Malaysia, India Tier-2, India Tier-1/60LPA+) across Glassdoor, LeetCode Discuss, GeeksforGeeks, Blind/TeamBlind, and Medium engineering write-ups. A few honest limits to keep in mind:

- **Coverage is uneven.** India Tier-1/60LPA+ and Tier-2, plus Malaysia's regional-hub companies (Shopee, Agoda, Grab), have the richest public data — large, English-heavy candidate-blogging communities (Amazon, Uber, Rippling, Atlassian, Walmart, Myntra) mean specific, recent (2024–2025) evidence. Saudi Arabia and several younger Malaysian/UAE startups have thin-to-nonexistent public interview reports — those rows are marked **Inferred** and should be treated as reasonable priors based on company domain, not fact.
- **"Confirmed" ≠ "verbatim current."** Sourced reports are dated candidate write-ups (mostly 2023–2025); interview formats change over time, especially at fast-growing startups.
- **No fabricated data.** Every Inferred row states plainly that no direct report was found — none of those rows cite a fake source or invented quote.
- **India Tier-2 vs. Tier-1/60LPA+ is a deliberate split, not a relabeling** — different company sets, researched separately, with a measurably different interview bar (see the note at the top of this document).
- Total companies profiled: **28 (Saudi) + 29 (UAE) + 28 (Malaysia) + 33 (India Tier-2) + 27 (India Tier-1/60LPA+)** ≈ **145 companies** across the five markets.

*Companion reference: see the dedicated Prototype Pattern deep-dive guide for full-depth treatment (production examples, shallow vs deep copy, Q&A bank) — apply the same depth to Strategy, Factory, Observer, and Singleton next, since those are your Very High priority patterns across every target market. The pattern-deep-dive template (`MASTER-PROMPT-TEMPLATE.md`) now cross-links its Market Calibration section directly to this document's data — check there before assuming a pattern's interview weight in a given market.*
