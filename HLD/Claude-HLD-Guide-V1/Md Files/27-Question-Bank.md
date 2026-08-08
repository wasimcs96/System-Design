# Chapter 27: Interview Question Bank (270+ Questions)

*← [Chapter 26: Cheat Sheet](26-Cheat-Sheet-Decision-Matrices.md) · [Index](00-Index-and-Assessment.md) · Next → [Chapter 28: Mock Interview Program](28-Mock-Interviews.md)*

*Delivered as reference tables rather than long-form write-ups for each — this keeps the bank genuinely usable as a practice-planning tool. Pick a row, set a timer matching the "Duration" column, and run the [10-step framework](14-Interview-Framework-Communication.md) solo before checking whether your answer covers what's in "Interviewer Expects." Questions marked with a chapter reference have a fully worked answer there — the rest are deliberately unworked, for genuine independent practice.*

---

## 27.1 Beginner (50)

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design a URL shortener *([Ch.16](16-Problems-Beginner.md))* | ID generation, cache-aside, read-heavy scaling | 30 min | Base62 encoding reasoning, cache justification |
| 2 | Design Pastebin *([Ch.16](16-Problems-Beginner.md))* | Metadata/blob split, expiry | 30 min | Object storage vs. inline storage threshold reasoning |
| 3 | Design a rate limiter *([Ch.16](16-Problems-Beginner.md))* | Token bucket, atomic Redis ops | 30 min | Algorithm choice + fail-open/closed trade-off |
| 4 | Design a file upload service *([Ch.16](16-Problems-Beginner.md))* | Pre-signed URLs, async processing | 30 min | Direct-to-storage upload pattern |
| 5 | Design a single-channel notification service | Queueing, retries | 30 min | Async decoupling from trigger |
| 6 | Design a log aggregation system *([Ch.16](16-Problems-Beginner.md))* | Buffering, indexing pipeline | 35 min | App never blocks on logging |
| 7 | Design a parking lot system (LLD) | OOP design, state management | 35 min | Class structure, spot-assignment logic |
| 8 | Design an elevator system (LLD) | State machine, scheduling algorithm | 35 min | Request-handling algorithm clarity |
| 9 | Design a vending machine (LLD) | State machine | 30 min | Clean state transitions |
| 10 | Design a library management system (LLD) | Entity relationships, concurrency on checkout | 35 min | Preventing double-checkout of one copy |
| 11 | Design an ATM (LLD) | State machine, concurrency | 35 min | Atomic balance operations |
| 12 | Design tic-tac-toe (LLD) | OOP, win-condition logic | 25 min | Clean extensibility (e.g., to N x N board) |
| 13 | Design an in-memory key-value store | Hash map, eviction | 25 min | Data structure choice reasoning |
| 14 | Design an LRU cache | Data structure (hashmap + doubly linked list) | 25 min | O(1) get/put reasoning |
| 15 | Design a to-do list backend | Basic CRUD, data modeling | 20 min | Clean REST resource design |
| 16 | Design a polling/voting system (small scale) | Data modeling, double-vote prevention | 30 min | Uniqueness constraint reasoning |
| 17 | Design a basic blogging platform | CRUD, pagination | 30 min | Cursor pagination justification |
| 18 | Design a weather app backend | Caching third-party API responses | 25 min | TTL-based caching of upstream data |
| 19 | Design a simple shopping cart (no checkout) | Session/state modeling | 25 min | Cart persistence approach |
| 20 | Design 1:1 chat with no scale requirement | Basic messaging, WebSocket intro | 30 min | Simple send/receive flow |
| 21 | Design a health-check endpoint strategy | Liveness vs. readiness | 15 min | Correct distinction (Ch.8.4) |
| 22 | Design a basic login/signup system | Password hashing, sessions | 30 min | bcrypt/argon2 reasoning |
| 23 | Design a simple leaderboard | Sorted data structure | 25 min | Redis sorted set reasoning |
| 24 | Design a coupon/discount code system | Uniqueness, expiry, usage limits | 30 min | Atomic redemption-count check |
| 25 | Design an event RSVP system | Capacity limits, concurrency | 30 min | Atomic capacity-check-and-reserve |
| 26 | Design a basic image resizing service | Async processing | 25 min | Queue-triggered worker pattern |
| 27 | Design a simple form/survey builder backend | Dynamic schema modeling | 30 min | Flexible schema decision (JSON column vs. doc DB) |
| 28 | Design a single-store inventory tracker | Basic CRUD, atomic decrement | 25 min | Atomic conditional update |
| 29 | Design a basic appointment scheduler | Time-slot conflict prevention | 30 min | Overlap-prevention logic |
| 30 | Design a basic blog comment system | Nested data, pagination | 25 min | Threaded-comment data modeling |
| 31 | Design a background job queue | Async processing, retries | 30 min | Queue + worker + DLQ pattern |
| 32 | Design a website uptime monitor | Polling, alerting | 30 min | Scheduled health checks + alert thresholds |
| 33 | Design a feature-flag toggle service (single-region) | Config propagation | 25 min | Fast read path, cache-backed |
| 34 | Design a basic audit log system | Append-only logging | 25 min | Immutability reasoning |
| 35 | Design pastebin with auto-expiry | TTL-based cleanup | 20 min | Lazy vs. scheduled cleanup trade-off |
| 36 | Design a session management system | Stateless vs. stateful tokens | 30 min | JWT vs. session trade-off (Ch.1.5) |
| 37 | Design a contact-form/lead-capture backend | Simple ingestion, spam prevention | 20 min | Rate limiting on submission |
| 38 | Design a content tagging system | Many-to-many modeling | 25 min | Join table / tag index design |
| 39 | Design a bookmarks/favorites service | Simple CRUD at user scale | 20 min | Efficient per-user listing query |
| 40 | Design a basic FAQ search | Simple text search | 25 min | When full-text search is/isn't overkill |
| 41 | Design a page-view counter service | High-write counters | 25 min | Redis INCR + async flush pattern |
| 42 | Design a password reset flow | Security, token expiry | 25 min | Time-limited, single-use token |
| 43 | Design an email verification flow | Async, token-based confirmation | 20 min | Token expiry + resend handling |
| 44 | Design an API key management system | Secrets handling | 25 min | Hashed storage, scoped permissions |
| 45 | Design a config management service | Central config, environment overrides | 25 min | Config-as-code vs. dynamic service |
| 46 | Design a multi-tenant feature flag system | Tenant isolation | 30 min | Per-tenant override resolution |
| 47 | Design a single-instance in-memory rate limiter | Basic algorithm implementation | 20 min | Correct token-bucket logic |
| 48 | Design a reusable retry library | Backoff, jitter | 20 min | Exponential backoff + jitter correctness |
| 49 | Design a reusable circuit breaker library | State machine (closed/open/half-open) | 25 min | Correct state transitions |
| 50 | Design a basic service health dashboard | Aggregating health checks | 25 min | Polling vs. push-based health aggregation |

---

## 27.2 Intermediate (50)

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design Twitter/X *([Ch.17](17-Problems-Intermediate.md))* | Fan-out, celebrity problem | 45 min | Hybrid fan-out resolution |
| 2 | Design Instagram *([Ch.17](17-Problems-Intermediate.md))* | Media pipeline + feed | 45 min | Upload/CDN + feed fan-out split |
| 3 | Design WhatsApp *([Ch.17](17-Problems-Intermediate.md))* | Connection routing, presence | 45 min | Gateway + routing-layer design |
| 4 | Design YouTube *([Ch.17](17-Problems-Intermediate.md))* | Transcoding, CDN | 45 min | Bandwidth-first framing |
| 5 | Design Dropbox *([Ch.17](17-Problems-Intermediate.md))* | Chunking, sync, dedup | 45 min | Block-level sync reasoning |
| 6 | Design Google Drive *([Ch.17](17-Problems-Intermediate.md))* | Permissions, collaboration | 45 min | Permission-check design + OT/CRDT awareness |
| 7 | Design an e-commerce system *([Ch.17](17-Problems-Intermediate.md))* | Catalog vs. checkout split | 45 min | Different consistency models per sub-path |
| 8 | Design a food delivery system *([Ch.17](17-Problems-Intermediate.md))* | Geospatial matching, order state | 45 min | Location pipeline separate from order data |
| 9 | Design hotel booking *([Ch.17](17-Problems-Intermediate.md))* | Date-ranged inventory | 45 min | No-double-booking atomicity |
| 10 | Design ticket booking *([Ch.17](17-Problems-Intermediate.md))* | Seat locks, admission control | 45 min | TTL-based hold + waiting room |
| 11 | Design a basic ride-sharing app | Matching, trip lifecycle | 45 min | Matching + state machine |
| 12 | Design a job board / job search platform | Search ranking, two-sided matching | 40 min | Recency-weighted ranking |
| 13 | Design a video conferencing app (small groups) | Real-time media, signaling | 45 min | Signaling server + peer connection basics |
| 14 | Design a collaborative code editor | Real-time sync, conflict resolution | 45 min | OT/CRDT awareness |
| 15 | Design a music streaming service | Catalog + streaming + offline | 40 min | CDN-first + licensing awareness |
| 16 | Design a podcast platform | Media hosting, RSS-like distribution | 35 min | Object storage + CDN reasoning |
| 17 | Design a "stories" feature (24h expiry content) | TTL content, view tracking | 35 min | Efficient expiry mechanism |
| 18 | Design a group chat/channels feature | Fan-out to many members | 40 min | Fan-out-on-write for bounded groups |
| 19 | Design a large-scale polling app | High-write counters, race conditions | 40 min | Atomic increment + eventual display consistency |
| 20 | Design an online auction system | Bidding concurrency, real-time updates | 45 min | Atomic highest-bid update + real-time push |
| 21 | Design a review/rating aggregation system | Aggregation at scale | 35 min | Precomputed aggregate, async update |
| 22 | Design a web crawler | Queue-based traversal, dedup | 45 min | URL frontier + dedup + politeness/rate limits |
| 23 | Design a distributed cron scheduler | Exactly-once-ish scheduling | 40 min | Leader election for single-trigger guarantee |
| 24 | Design an email service backend | Storage, search, delivery | 45 min | Metadata/content split, search indexing |
| 25 | Design a calendar system with invites | Conflict detection, notifications | 40 min | Overlap detection + async invite delivery |
| 26 | Design a survey platform at scale | High-volume writes, analytics | 35 min | Write-optimized ingestion, async aggregation |
| 27 | Design a content moderation pipeline | Async review, queueing | 40 min | Pre/post-publish moderation trade-off |
| 28 | Design an A/B testing platform | Bucketing, consistent assignment | 40 min | Deterministic hashing for bucket assignment |
| 29 | Design a feature-flag system at scale | Propagation latency, caching | 35 min | Push vs. pull propagation trade-off |
| 30 | Design URL shortener + analytics dashboard | Async analytics ingestion | 40 min | Analytics off the critical redirect path |
| 31 | Design a subscription/recurring billing system | Scheduled charges, retries | 40 min | Dunning/retry logic for failed charges |
| 32 | Design a multiplayer trivia game | Real-time state sync | 40 min | Room-based state + WebSocket fan-out |
| 33 | Design a live event Q&A app (Slido-like) | Real-time fan-out, moderation | 35 min | Broadcast to session attendees |
| 34 | Design a Google-Docs-lite editor | Concurrent editing | 45 min | OT/CRDT reasoning |
| 35 | Design a classifieds marketplace | Listings, search, messaging | 40 min | Search + messaging integration |
| 36 | Design a car rental system | Inventory across locations, date ranges | 40 min | Date-ranged inventory (echoes hotel booking) |
| 37 | Design a coworking space booking system | Resource + time-slot booking | 35 min | Same atomicity pattern as hotel booking |
| 38 | Design a fitness tracking app backend | High-volume sensor-like data ingestion | 35 min | Time-series storage reasoning |
| 39 | Design a recipe-sharing platform | Search, media, ratings | 35 min | Standard CRUD + search integration |
| 40 | Design a crowdfunding platform | Payment + goal tracking | 40 min | Atomic pledge tracking, payment integration |
| 41 | Design a Reddit-like forum | Voting, ranking, nested comments | 45 min | Hot/top ranking algorithm |
| 42 | Design a Stack-Overflow-like Q&A platform | Search, reputation, voting | 40 min | Search + ranking integration |
| 43 | Design video call recording/storage | Large media ingestion + storage | 35 min | Async post-call processing pipeline |
| 44 | Design a digital signage/content distribution system | Scheduled content push | 30 min | CDN-based content delivery to devices |
| 45 | Design a loyalty points/rewards system | Points ledger, redemption | 40 min | Ledger-style point tracking (echoes Ch.20) |
| 46 | Design a multi-vendor inventory sync system | Cross-system consistency | 40 min | Eventual consistency + reconciliation |
| 47 | Design a shipment tracking system | Status updates, notifications | 35 min | State machine + async notification |
| 48 | Design a clinic appointment booking system | Time-slot conflict, reminders | 35 min | Conflict prevention + scheduled reminders |
| 49 | Design a city-wide parking-space finder | Real-time availability, geospatial | 40 min | Geospatial index (echoes Ch.18) |
| 50 | Design a lost-and-found platform | Search/matching | 30 min | Simple matching/search design |

---

## 27.3 Advanced (50)

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design Uber *([Ch.18](18-Problems-Advanced.md))* | Matching, surge, trip state machine | 45–60 min | Geospatial matching + surge pricing design |
| 2 | Design Careem *([Ch.18](18-Problems-Advanced.md))* | Super-app, shared platform services | 45–60 min | Vertical vs. platform service boundary |
| 3 | Design Amazon *([Ch.18](18-Problems-Advanced.md))* | Marketplace, fulfillment, Buy Box | 60 min | Multi-tenant + fulfillment routing |
| 4 | Design Netflix *([Ch.18](18-Problems-Advanced.md))* | Personalization, licensing, CDN | 60 min | Precomputed ranking + regional licensing model |
| 5 | Design a Payment Gateway *([Ch.18](18-Problems-Advanced.md), [Ch.20](20-Fintech-System-Design.md))* | Idempotency, state machine, reconciliation | 60 min | Unknown-outcome handling |
| 6 | Design a Digital Wallet *([Ch.18](18-Problems-Advanced.md), [Ch.20](20-Fintech-System-Design.md))* | Double-entry ledger | 45–60 min | Derived balance reasoning |
| 7 | Design a Ride Matching Engine *([Ch.18](18-Problems-Advanced.md))* | Geospatial index, bipartite matching | 45 min | Filter-then-rank pattern |
| 8 | Design Food Delivery at scale *([Ch.18](18-Problems-Advanced.md))* | Order batching, fleet optimization | 45 min | Batching scoring function |
| 9 | Design a web-scale search engine *([Ch.22](22-Search-Systems.md))* | Inverted index, ranking, crawling | 60 min | Indexing pipeline + ranking depth |
| 10 | Design a Recommendation System *([Ch.18](18-Problems-Advanced.md))* | Candidate generation + ranking | 45–60 min | Two-stage architecture |
| 11 | Design an Ad Serving System *([Ch.18](18-Problems-Advanced.md))* | Auction, budget pacing | 45–60 min | Latency budget + pacing design |
| 12 | Design a Distributed Notification System *([Ch.18](18-Problems-Advanced.md))* | Multi-region, priority tiers | 45 min | Separate lanes for critical vs. bulk |
| 13 | Design Real-time Chat at scale (Slack/Discord) *([Ch.18](18-Problems-Advanced.md))* | Large-channel fan-out | 45–60 min | Sparse connection-state reasoning |
| 14 | Design Live Video Streaming (Twitch) *([Ch.18](18-Problems-Advanced.md))* | Live ingest, segmented delivery | 45–60 min | Latency/quality/scale trade-off |
| 15 | Design a global CDN | Edge caching, origin shielding | 45 min | Multi-tier caching to protect origin |
| 16 | Design a distributed cache (Redis Cluster from scratch) | Consistent hashing, replication | 45–60 min | Ring-based partitioning + failover |
| 17 | Design a multi-region distributed rate limiter | Coordination trade-offs | 45 min | Local approximation vs. global precision |
| 18 | Design a distributed lock service | Consensus, lease/TTL | 45 min | Correctness under node failure |
| 19 | Design a distributed ID generator (Snowflake-style) | Coordination-free uniqueness | 30 min | Reserved-range/timestamp-based design |
| 20 | Design a distributed job scheduler at scale | Exactly-once execution | 45 min | Leader election + idempotent execution |
| 21 | Design a global load balancer/traffic manager | GeoDNS, health-based routing | 40 min | Multi-layer routing decision |
| 22 | Design a stock exchange/trading system | Order matching, extreme low latency | 60 min | Order book + matching engine design |
| 23 | Design a fraud detection pipeline | Real-time + batch scoring | 45 min | Two-stage inline/async pattern |
| 24 | Design a global search autocomplete system | Prefix indexing at scale | 40 min | Edge n-gram / trie reasoning |
| 25 | Design a large-scale log analytics platform | Ingestion, indexing, retention | 45 min | Buffer-then-index pipeline |
| 26 | Design a real-time analytics/metrics platform | Streaming aggregation | 45 min | Windowed aggregation design |
| 27 | Design an ML feature store | Training-serving consistency | 45 min | Training-serving skew awareness |
| 28 | Design a billion-user push notification system | Extreme fan-out | 45–60 min | Priority-tiered, sharded delivery |
| 29 | Design a multi-region active-active database | Conflict resolution, consensus | 60 min | CRDT/last-write-wins/quorum trade-offs |
| 30 | Design a global rate-limited API gateway | Edge enforcement | 40 min | Local + global limit coordination |
| 31 | Design Google Maps / a routing service | Graph routing, live traffic | 60 min | Road-graph + live-traffic overlay |
| 32 | Design a distributed task queue at scale | Backpressure, worker scaling | 45 min | Queue-depth-driven autoscaling |
| 33 | Design a large-scale image/video CDN pipeline | Transcoding + delivery | 45 min | Async processing + tiered caching |
| 34 | Design a global experimentation platform | Consistent bucketing at scale | 45 min | Deterministic hashing, statistical validity awareness |
| 35 | Design a real-time payment fraud/anomaly system | Streaming ML scoring | 45–60 min | Inline rules + async model split |
| 36 | Design a distributed tracing system (Jaeger-like) | Span collection, sampling | 45 min | Sampling strategy at scale |
| 37 | Design a metrics monitoring system (Prometheus-like) | Time-series storage, scraping | 45 min | Pull vs. push model trade-off |
| 38 | Design a global CDN cache-invalidation system | Propagation consistency | 40 min | Versioned URLs vs. active purge trade-off |
| 39 | Design a large-scale transactional email system (SES-like) | Deliverability, reputation | 40 min | Queue + provider-rate-limit-aware sending |
| 40 | Design a global session store | Multi-region consistency | 40 min | Region-local + replicated fallback |
| 41 | Design a multi-tenant SaaS platform architecture | Tenant isolation, noisy-neighbor | 45–60 min | Isolation strategy (shared vs. siloed data) |
| 42 | Design a large-scale ETL/data warehouse pipeline | Batch processing at scale | 45 min | Batch vs. stream processing trade-off |
| 43 | Design a global URL shortener with abuse detection | Scale + security | 40 min | Abuse-pattern detection design |
| 44 | Design a large-scale voting/election system | Integrity, extreme peak load | 45–60 min | Correctness-under-load + auditability |
| 45 | Design a distributed leader-election service | Consensus (Raft/Paxos) | 40 min | Correct failure-handling reasoning |
| 46 | Design a global chat presence system | Sparse state at scale | 40 min | Lazy/on-demand presence design |
| 47 | Design a large-scale price-comparison engine | Cross-source aggregation | 40 min | Async ingestion + normalization pipeline |
| 48 | Design Amazon's warehouse/fulfillment routing | Multi-warehouse optimization | 45–60 min | Nearest-with-stock routing logic |
| 49 | Design a global content ranking pipeline | Precomputation at scale | 45 min | Offline ranking + fast-serving split |
| 50 | Design multi-region disaster recovery for a bank | RPO/RTO at extreme correctness bar | 60 min | Near-zero-RPO replication design |

---

## 27.4 FinTech (30)

*Companion reading: [Chapter 20](20-Fintech-System-Design.md), [Chapter 18 Problems 21–22](18-Problems-Advanced.md).*

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design a payment gateway | Idempotency, state machine | 60 min | Full flow incl. unknown-outcome handling |
| 2 | Design a digital wallet | Double-entry ledger | 45–60 min | Derived-balance reasoning |
| 3 | Design a P2P money transfer system | Atomic paired ledger entries | 45 min | Single-transaction atomicity |
| 4 | Design a double-entry ledger from scratch | Accounting invariants | 45 min | Debits-equal-credits invariant explained |
| 5 | Design transaction fraud detection | Two-stage scoring | 45 min | Inline rules + async ML split |
| 6 | Design a reconciliation system | Batch matching, discrepancy handling | 45–60 min | Settlement-file matching logic |
| 7 | Design a refund processing system | Linked reversing transaction | 35 min | Refund as new, linked transaction |
| 8 | Design chargeback handling | External-event ingestion, dispute workflow | 40 min | Chargeback vs. refund distinction |
| 9 | Design a subscription billing system | Scheduled charges, dunning | 40 min | Retry/dunning logic for failed renewals |
| 10 | Design a BNPL (buy-now-pay-later) system | Installment scheduling, risk | 45 min | Scheduled-charge state machine |
| 11 | Design a credit scoring system | Data aggregation, model serving | 45 min | Feature aggregation + serving latency |
| 12 | Design a loan origination system | Multi-step approval workflow | 45 min | State machine for approval stages |
| 13 | Design invoice generation and tracking | Scheduled generation, status tracking | 35 min | Status state machine |
| 14 | Design a multi-currency payment system | FX conversion, consistency | 45 min | Conversion-rate-locking reasoning |
| 15 | Design a UPI-like real-time payment system | Low-latency settlement | 45–60 min | Real-time rail integration reasoning |
| 16 | Design a stock order matching engine | Extreme low latency, order book | 60 min | In-memory order book design |
| 17 | Design a crypto wallet system | Key management, transaction signing | 45 min | Security-first key handling |
| 18 | Design a KYC verification pipeline | Async document processing | 40 min | Async review + status state machine |
| 19 | Design a payment webhook delivery system | Reliable async delivery | 40 min | Retry + idempotent receiver design |
| 20 | Design a marketplace payout/disbursement system | Batch payouts, ledger accuracy | 45 min | Batch payout reconciliation |
| 21 | Design a budgeting/expense tracker backend | Categorization, aggregation | 30 min | Efficient per-user aggregation |
| 22 | Design an insurance claims processing system | Multi-step workflow, documents | 45 min | State machine + async document review |
| 23 | Design a tax calculation engine for checkout | Rule-based computation, jurisdictions | 40 min | Rule-engine design for jurisdiction variance |
| 24 | Design a merchant settlement system | Batch aggregation, ledger | 45 min | Aggregation + reconciliation design |
| 25 | Design a bill-split app (Splitwise-like) | Multi-party ledger | 40 min | Simplified debt-graph/ledger design |
| 26 | Design a rewards/cashback system | Points ledger, expiry rules | 35 min | Ledger-style point tracking |
| 27 | Design a savings-goal/fixed-deposit feature | Scheduled accrual | 35 min | Interest-accrual scheduling |
| 28 | Design an ATM network transaction system | Distributed consistency, offline handling | 45 min | Network-partition handling for cash dispensing |
| 29 | Design a cross-border remittance system | Multi-hop settlement, compliance | 45–60 min | Multi-rail + compliance-check integration |
| 30 | Design a financial audit trail system | Immutable logging, queryability | 35 min | Append-only + efficient audit query design |

---

## 27.5 Real-Time Systems (30)

*Companion reading: [Chapter 21](21-Realtime-System-Design.md), [Chapter 17 Problem 9](17-Problems-Intermediate.md), [Chapter 18 Problems 17, 29, 30](18-Problems-Advanced.md).*

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design WhatsApp | Connection routing, ordering | 45 min | Gateway + routing-layer pattern |
| 2 | Design a live chat support widget | WebSocket, queue routing to agents | 35 min | Agent-assignment + real-time transport |
| 3 | Design real-time delivery location tracking | UDP-tolerant ingestion, targeted push | 40 min | Latest-value-wins + targeted routing |
| 4 | Design a live sports score system | One-to-many broadcast | 35 min | Compute-once, fan-out-many (SSE) |
| 5 | Design multiplayer game state sync | Low-latency state broadcast | 45 min | Tick-based state sync reasoning |
| 6 | Design a live auction bidding system | Real-time updates + atomicity | 40 min | Atomic highest-bid + broadcast |
| 7 | Design a real-time collaborative doc editor | OT/CRDT | 45 min | Operation-level merge reasoning |
| 8 | Design a live ops metrics dashboard | One-to-many broadcast | 35 min | Compute-once-fan-out-many pattern |
| 9 | Design a presence (online/offline) system | Sparse state, TTL heartbeats | 35 min | Lazy pull + TTL-based liveness |
| 10 | Design a 1:1 video call system | Signaling, peer connection | 40 min | Signaling server design |
| 11 | Design group video conferencing | Media routing (SFU/MCU concepts) | 45 min | Media-routing architecture awareness |
| 12 | Design a live-stream chat overlay | High fan-out chat | 40 min | Fan-out-to-thousands pattern |
| 13 | Design a real-time stock ticker | High-frequency broadcast | 35 min | Throttled/batched broadcast design |
| 14 | Design a real-time ride-tracking map | Targeted push, geospatial | 40 min | Single-recipient targeted routing |
| 15 | Design an in-app notification bell | Targeted push + persistence | 30 min | Durable-first, push-second design |
| 16 | Design a real-time voting/poll display | Aggregation + broadcast | 30 min | Async aggregation, broadcast result |
| 17 | Design a typing-indicator feature | Ephemeral, high-frequency, low-durability signal | 25 min | No-persistence-needed reasoning |
| 18 | Design a live ticket-sale waiting room | Admission control | 40 min | Queue-based admission control |
| 19 | Design a real-time fraud alert system | Streaming detection + push | 40 min | Streaming pipeline + alert delivery |
| 20 | Design a real-time IoT sensor data pipeline | High-throughput ingestion | 45 min | Kafka-class ingestion + downstream fan-out |
| 21 | Design a real-time game leaderboard | Sorted structure + push updates | 35 min | Redis sorted set + targeted broadcast |
| 22 | Design real-time flight tracking | High-frequency position updates | 40 min | Latest-value-wins ingestion |
| 23 | Design real-time inventory sync across warehouses | Eventual consistency at speed | 40 min | Event-driven sync + reconciliation |
| 24 | Design real-time support-queue routing | Dynamic assignment | 35 min | Real-time queue-depth-aware routing |
| 25 | Design a real-time bidding (RTB) ad system | Extreme low latency | 45–60 min | Sub-100ms auction pipeline |
| 26 | Design a real-time GPS fleet management system | High-volume ingestion, geospatial | 40 min | Ingestion + geospatial index pattern |
| 27 | Design live captioning/translation | Streaming processing + low latency | 40 min | Streaming pipeline with bounded latency |
| 28 | Design a real-time collaborative whiteboard | OT/CRDT for shapes | 40 min | Operation-based sync for non-text data |
| 29 | Design a scalable push notification system | Fan-out + provider rate limits | 40 min | Per-channel queue + throttling |
| 30 | Design a real-time multiplayer trivia buzzer | Race-condition-free "first buzz wins" | 30 min | Atomic first-write-wins logic |

---

## 27.6 E-commerce (30)

*Companion reading: [Chapter 17 Problem 13](17-Problems-Intermediate.md), [Chapter 18 Problem 19](18-Problems-Advanced.md).*

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design an e-commerce catalog and search | Read-heavy scaling, search integration | 45 min | Cache/CDN + Elasticsearch integration |
| 2 | Design a shopping cart service | Ephemeral state, fast reads/writes | 35 min | Redis/DynamoDB-backed cart reasoning |
| 3 | Design a checkout and order system | Strong consistency, Saga | 45–60 min | Checkout vs. catalog consistency split |
| 4 | Design an inventory management system | Atomic conditional updates | 40 min | No-oversell atomicity |
| 5 | Design a flash-sale system | Extreme peak load, admission control | 45–60 min | Pre-warming + waiting-room pattern |
| 6 | Design a product recommendation engine | Two-stage candidate/rank | 45 min | Candidate generation + ranking split |
| 7 | Design a wishlist/favorites service | Simple CRUD at scale | 20 min | Efficient per-user listing |
| 8 | Design a review and rating system | Aggregation, abuse prevention | 35 min | Precomputed aggregate + fraud reasoning |
| 9 | Design seller/marketplace onboarding | Multi-step verification workflow | 35 min | State machine for onboarding stages |
| 10 | Design a returns/refunds system | Workflow + ledger linkage | 40 min | Linked reversing transaction (echoes Ch.20) |
| 11 | Design a shipping/logistics tracking system | Status updates, notifications | 40 min | State machine + async notification |
| 12 | Design a price-drop alert system | Watch-list matching at scale | 35 min | Efficient match-on-price-change design |
| 13 | Design a coupon/discount engine | Rule evaluation, atomic redemption | 40 min | Atomic usage-limit enforcement |
| 14 | Design a loyalty/rewards program | Points ledger | 35 min | Ledger-style tracking |
| 15 | Design a product Q&A system | CRUD + moderation | 30 min | Moderation pipeline integration |
| 16 | Design an abandoned-cart recovery system | Scheduled/triggered notifications | 30 min | Event-triggered async workflow |
| 17 | Design multi-warehouse order fulfillment | Nearest-with-stock routing | 45 min | Warehouse-selection logic |
| 18 | Design a dynamic pricing engine | Real-time rule evaluation | 40 min | Streaming signal ingestion for pricing |
| 19 | Design a Buy-Box/offer-ranking system | Multi-factor ranking, caching | 40 min | Precomputed, cached ranking |
| 20 | Design an order-tracking notification system | Status-change-triggered push | 30 min | Event-driven notification design |
| 21 | Design a subscription box (recurring order) service | Scheduled recurring fulfillment | 40 min | Scheduled job + inventory reservation |
| 22 | Design a gift card system | Balance tracking, redemption | 35 min | Ledger-style balance (echoes Ch.20) |
| 23 | Design a size/fit recommendation system | ML-adjacent, feature-based | 40 min | Feature-based scoring reasoning |
| 24 | Design visual (image) search for a catalog | Embedding-based search | 45 min | Vector/embedding search awareness |
| 25 | Design a supplier inventory sync system | Cross-system eventual consistency | 40 min | Event-driven sync + reconciliation |
| 26 | Design fake-order/fraud detection | Two-stage scoring | 40 min | Inline + async detection split |
| 27 | Design a customer support ticketing system | Queueing, routing, SLAs | 35 min | Priority-queue routing |
| 28 | Design a live "stock left" counter at scale | High-read, approximate-OK counters | 35 min | Cached approximate count reasoning |
| 29 | Design a marketplace dispute resolution system | Multi-party workflow | 40 min | State machine across buyer/seller/platform |
| 30 | Design a bulk product-catalog import pipeline | Batch processing, validation | 40 min | Async batch validation + partial-failure handling |

---

## 27.7 Distributed Systems (30)

*Companion reading: [Chapter 6](06-Distributed-Systems.md), [Chapter 13](13-Architecture-Patterns.md).*

| # | Question | Concepts Tested | Duration | Interviewer Expects |
|---|---|---|---|---|
| 1 | Design a distributed lock service | Consensus, lease/TTL | 45 min | Correctness under node failure |
| 2 | Design a leader election service | Raft/Paxos-based consensus | 40 min | Correct failure-handling |
| 3 | Design a distributed consensus system | Raft mechanics | 45–60 min | Understanding of quorum-based agreement |
| 4 | Design a distributed unique ID generator | Coordination-free uniqueness | 30 min | Reserved-range/Snowflake-style design |
| 5 | Design a distributed rate limiter | Local vs. global coordination | 40 min | Precision/latency trade-off |
| 6 | Design a distributed cache with consistent hashing | Ring-based partitioning | 45 min | Minimal-remap reasoning |
| 7 | Design a distributed transaction coordinator (Saga) | Compensating transactions | 45 min | Orchestration vs. choreography trade-off |
| 8 | Design an outbox-pattern event publisher | Atomic DB write + publish | 35 min | Single-transaction outbox reasoning |
| 9 | Design a change-data-capture pipeline | Log-tailing, downstream sync | 40 min | CDC vs. dual-write reasoning |
| 10 | Design a distributed job scheduler (exactly-once) | Leader election + idempotency | 45 min | Idempotent execution under retries |
| 11 | Design a distributed configuration service | Propagation, consistency | 35 min | Push/pull propagation trade-off |
| 12 | Design a distributed tracing system | Span collection, sampling | 45 min | Sampling strategy at scale |
| 13 | Design a gossip-protocol membership service | Eventually-consistent membership | 40 min | Gossip convergence reasoning |
| 14 | Design a distributed queue with exactly-once processing | Delivery semantics | 40 min | At-least-once + idempotency = effective exactly-once |
| 15 | Design multi-region data replication | Conflict resolution | 45–60 min | CRDT/LWW/quorum trade-offs |
| 16 | Design a CRDT-based distributed counter | Conflict-free merge | 35 min | Merge-function correctness |
| 17 | Design a simplified distributed file system | Chunking, replication, metadata | 60 min | Metadata/chunk-server split (HDFS-style) |
| 18 | Design a quorum-based distributed key-value store | N/W/R tuning | 40 min | Correct quorum math |
| 19 | Design a distributed token-bucket rate-limiting service | Shared state coordination | 35 min | Redis-backed atomic bucket design |
| 20 | Design a service mesh control plane | Config distribution to sidecars | 45 min | Control plane/data plane separation |
| 21 | Design distributed circuit-breaker coordination | Shared failure-state signaling | 35 min | Per-instance vs. shared breaker state trade-off |
| 22 | Design a distributed deduplication service | Idempotency at scale | 35 min | Efficient seen-before check (e.g., bloom filter) |
| 23 | Design a distributed session store | Multi-region consistency | 35 min | Region-local + replication trade-off |
| 24 | Design distributed feature-flag propagation | Fast, consistent rollout | 35 min | Propagation-latency vs. consistency trade-off |
| 25 | Design a guaranteed-delivery distributed logging pipeline | Durability under failure | 40 min | Buffer-before-index reasoning |
| 26 | Design a distributed batch-processing framework | Partitioned parallel processing | 45 min | Map-reduce-style partitioning |
| 27 | Design leader-follower DB replication from scratch | Replication lag, failover | 45 min | Failover mechanics + lag handling |
| 28 | Design a distributed event bus with schema evolution | Compatibility management | 40 min | Backward-compatible schema versioning |
| 29 | Design a multi-region rate-limiting proxy | Global vs. local enforcement | 40 min | Budget-allocation-per-region pattern |
| 30 | Design a distributed clock synchronization approach | Clock skew, logical clocks | 35 min | Logical clocks / TrueTime-style awareness |

---

*Next → [Chapter 28: Mock Interview Program](28-Mock-Interviews.md) — 20 full mock interviews (interviewer version + candidate version) spanning beginner to Tier-1 difficulty.*
