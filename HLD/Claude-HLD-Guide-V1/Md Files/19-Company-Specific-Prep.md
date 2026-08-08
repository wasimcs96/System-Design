# Chapter 19: Company-Specific Preparation

*← [Chapter 18: Advanced Problems](18-Problems-Advanced.md) · [Index](00-Index-and-Assessment.md) · Next → [Chapter 20: FinTech System Design](20-Fintech-System-Design.md)*

**A note on sourcing:** every row below is labeled by confidence. **[Researched]** means it's drawn from current (2025–2026) publicly reported interview experiences (Glassdoor, Blind, candidate write-ups, and similar) gathered specifically for this roadmap. **[General]** means it reflects well-established, widely-corroborated industry reputation and this roadmap's own domain reasoning, not a specific fresh data pull for that company. Interview processes change — treat every "typical round" description as a strong prior, not a guarantee, and verify against the most recent Glassdoor/Blind reports for your specific role and location before an actual interview.

---

## 19.1 Tier 1 — Global Product Companies

### Amazon **[Researched]**

- **Expected level:** Bar-raiser-driven, calibrated across all levels — SDE II and above will see a dedicated system design round.
- **Typical round:** For SDE II+, part of a 4-round loop (each ~55 min), blending technical depth with Amazon's 16 **Leadership Principles** woven into follow-up questions — expect "why did you choose X" to sometimes pull toward "Ownership" or "Bias for Action" framing, not just technical justification.
- **HLD vs LLD:** Primarily HLD for mid-to-senior; a dedicated LLD/coding round is separate.
- **Common domains:** Active-active global systems, disaster recovery, extreme-scale traffic engineering, distributed data systems, event-driven/streaming architectures — Amazon's own 2026 prep materials explicitly call these out.
- **Difficulty:** High for senior roles — expect to justify designs at "planet-scale" numbers (100x traffic spikes, 1M+ events/sec) even if the base question sounds modest.
- **What's evaluated:** Correctness under scale, trade-off articulation, and — distinctively — whether your design decisions reflect genuine **ownership and cost-awareness**, not just technical correctness.
- **Strong-answer signal:** Explicitly connecting a technical trade-off to a Leadership Principle when natural (e.g., "I'd bias toward the simpler design and iterate — Bias for Action — rather than over-designing for a scale we haven't validated yet") — don't force this, but don't ignore it either.

### Google, Microsoft, Meta **[General]**

- **Expected level:** All three run calibrated, level-based loops; system design typically enters at mid-level and above (L4/L5+ equivalent).
- **Typical round:** One dedicated 45-minute HLD round, generally less rigidly scripted than Amazon's LP-driven format — more of an open technical conversation, with the interviewer actively probing whichever area they find most interesting.
- **HLD vs LLD:** Primarily HLD; Google in particular sometimes blends in "how would you test this" or coding-adjacent probes.
- **Common domains:** Search/ranking-adjacent systems (Google), collaboration/enterprise-scale systems (Microsoft), social graph and real-time feed systems (Meta) — each company's own product surface tends to bias the examples they reach for, even when the underlying question is generic.
- **What's evaluated:** Structured thinking under ambiguity, depth on follow-ups (all three are known for pushing hard on "why not X instead" repeatedly), and communication clarity — these companies place real weight on how you *think out loud*, not just your final answer.
- **Strong-answer signal:** Comfortably handling 3–4 rounds of "but what if..." follow-ups without becoming defensive — this is deliberately tested, not incidental (Chapter 14.2's "handling a challenge" excellent-answer pattern is your rehearsal for exactly this).

### Uber **[Researched]**

- **Expected level:** Strong system design bar across levels, with an emphasis on real-world infrastructure parallels to their own marketplace.
- **Common domains (2025–2026 reports):** Ride-matching engines, real-time location tracking, surge pricing, dispatch services — expect the interviewer to want specific throughput/latency numbers, not just qualitative descriptions ("get comfortable talking about throughput, concurrency, and latency numbers," per multiple 2026 prep sources).
- **What's evaluated:** Geospatial indexing knowledge, pub/sub streaming design, event-driven trip lifecycle modeling, caching/sharding decisions, and observability instincts — directly overlapping with [Chapter 18, Problems 17 and 23](18-Problems-Advanced.md).
- **Strong-answer signal:** Naming concrete numbers unprompted (Chapter 3's estimation habit) and knowing the two-stage filter-then-rank matching pattern cold — this maps almost exactly onto what Uber's own interviewers are reported to probe for.

### Atlassian, Airbnb **[General]**

- **Expected level:** Both run calibrated, collaborative-style loops; Atlassian is known for valuing clear written/verbal communication given its own product culture (documentation-heavy, collaboration tools).
- **Common domains:** Atlassian — collaborative/real-time editing systems, permission models (echoing [Chapter 18's Google Drive discussion](17-Problems-Intermediate.md)), workflow/ticketing-style systems. Airbnb — marketplace/booking systems (echoing [Chapter 17's Hotel Booking](17-Problems-Intermediate.md)), trust/safety and search-ranking systems.
- **Strong-answer signal:** For Atlassian specifically, given their own product is collaboration software, a candidate who reasons carefully about concurrent-edit conflict resolution (OT/CRDTs) or permission-model design tends to land well, since it mirrors problems Atlassian's own engineers solve daily.

---

## 19.2 India Product Companies

### Flipkart, Walmart (Walmart Global Tech / Walmart Labs) **[Researched]**

- **Typical process:** Flipkart is notable for a **dedicated machine-coding round** (build a small working application in ~90 minutes) in addition to HLD/LLD rounds — a real differentiator versus most Tier-1 companies. Reported 2025 questions include designing a Google-Photos-like shareable album system.
- **Walmart's stated focus:** high-throughput, globally synchronized systems supporting retail operations — inventory, search, order fulfillment, logistics, fraud detection, pricing, curbside pickup. Directly maps to [Chapter 18, Problem 19 (Amazon-style marketplace)](18-Problems-Advanced.md) and [Chapter 17, Problem 13 (E-commerce)](17-Problems-Intermediate.md).
- **What's evaluated:** Practical, buildable designs — Flipkart's machine-coding round in particular rewards candidates who can translate HLD into working code quickly, so don't neglect hands-on fluency even while focusing this roadmap on HLD.
- **Strong-answer signal:** For Walmart specifically, explicit inventory-consistency reasoning (the atomic-conditional-update pattern from Chapter 17's E-commerce problem) — this is close to literally their daily engineering problem at retail scale.

### Razorpay **[Researched]**

- **Typical process:** LLD, HLD, manager, and HR rounds — HLD and LLD are treated as genuinely distinct rounds, unlike some companies that blend them.
- **Reported 2025 questions:** A logger system routing different log levels to different destinations by configuration; a notification system across multiple channels; classic LLD staples (parking lot, ATM, library management) alongside fintech-flavored HLD (split-payment/wallet systems).
- **Domain focus:** B2B payment infrastructure — [Chapter 18, Problem 21 (Payment System)](18-Problems-Advanced.md) and [Chapter 20 (FinTech deep dive)](20-Fintech-System-Design.md) are your most directly relevant prep material.
- **Strong-answer signal:** Precise idempotency-key and reconciliation reasoning (Chapter 6.4, Chapter 18 Problem 21) — Razorpay's actual product is payment infrastructure, so vague "I'd add some retries" answers land noticeably worse here than at a generic product company.

### PhonePe **[Researched]**

- **Domain focus:** Consumer payments at massive scale — reported 2025 interview content specifically includes designing **reconciliation systems** (comparing internal ledgers against bank settlement files) and **ledger systems with double-entry bookkeeping** — this is a near-exact match to [Chapter 18, Problem 22 (Wallet System)](18-Problems-Advanced.md) and [Chapter 20](20-Fintech-System-Design.md).
- **Strong-answer signal:** Being able to explain *why* balance is derived from an append-only ledger rather than stored directly (Chapter 18, Problem 22) is close to a direct rehearsal of PhonePe's actual reported question content — this is one of the highest-ROI specific things to over-prepare for given your stated interest.

### CRED, Swiggy, Zomato, Meesho, Zepto **[Mixed — see per-company note]**

- **Zomato/Swiggy [Researched]:** Reported focus on DSA plus system design plus company-values fit; common HLD content centers on the food-delivery domain directly — restaurant search, menu/catalog management, cart/order/payment flow, delivery tracking — an almost exact match to [Chapter 17, Problem 14](17-Problems-Intermediate.md) and [Chapter 18, Problem 24](18-Problems-Advanced.md).
- **CRED [General]:** Specific recent interview reports were limited in this research pass; general reputation and CRED's own product (a credit-card bill payment and rewards app with a strong fintech/consumer-trust angle) suggest overlap with both the Wallet/Payment problems (Chapter 18, Problems 21–22) and consumer-app design sensibility (polish, UX-adjacent reasoning) — verify against fresh Glassdoor/Blind reports closer to your interview date.
- **Meesho [General]:** A social-commerce/reseller-driven marketplace — expect e-commerce fundamentals (Chapter 17, Problem 13) with a possible twist toward the reseller/social-sharing angle rather than a standard single-buyer marketplace.
- **Zepto [General]:** Quick-commerce (10–15 minute delivery) — this is a meaningfully different NFR profile than standard e-commerce or even standard food delivery: extremely tight delivery-time SLAs push hard toward hyper-local dark-store inventory (very small geographic radius per fulfillment point) and much more aggressive real-time inventory/routing optimization than Chapter 17's e-commerce problem assumes — worth explicitly naming "dark store" / micro-fulfillment-center architecture if asked to design something like this.

---

## 19.3 Dubai / UAE

### Careem **[Researched]**

- **Typical process:** 1 coding round + 2 system design rounds reported — one high-level design question, one deep-dive design question, with interviewers described as generally collaborative rather than adversarial. Broader process includes a prescreen, portfolio review, a whiteboard challenge, and a cultural interview.
- **Domain focus:** Reported questions skew heavily **geolocation-related** (one example: designing a grocery-store app) — directly maps to [Chapter 18, Problems 17–18 (Uber/Careem)](18-Problems-Advanced.md) and the geospatial matching content in [Problem 23](18-Problems-Advanced.md).
- **What's evaluated:** Follow-up questions specifically probe trade-offs and alternative tech-stack choices — expect to be asked "what else could you have used here, and why didn't you" repeatedly (echoing Chapter 14.2's challenge-response pattern).
- **Strong-answer signal:** Given this roadmap's [Problem 18](18-Problems-Advanced.md) covers Careem's actual multi-vertical super-app architecture explicitly, being able to speak to how rides/food/payments share platform services (identity, wallet) while keeping vertical-specific logic separate is a distinctive, well-targeted answer for this specific company.

### Noon **[Researched]**

- **Typical process:** A HackerRank technical assessment, then a technical interview blending DSA and system design, then a final culture/expectations call with the CTO.
- **Reported 2025–2026 content:** One reported example asked a candidate to design a **schema** for WhatsApp-style features — suggesting Noon's system design rounds can lean toward concrete data-modeling depth, not just high-level boxes-and-arrows.
- **Domain focus:** Noon is a large general e-commerce marketplace (UAE/Saudi-focused) — [Chapter 17, Problem 13](17-Problems-Intermediate.md) and [Chapter 18, Problem 19](18-Problems-Advanced.md) are the most relevant prep.
- **Glassdoor sentiment:** ~63% positive interview experience rating, difficulty rated moderate (~2.84/5) as of this research — suggestive that Noon's bar, while real, is less consistently extreme than Tier-1 companies.

### Talabat **[Researched]**

- **Typical process:** HR interview → coding interview → system design interview (if coding passes) → a further general technical interview if that passes too. Reported topics include TDD, DDD, prior work experience, observability, and queuing — a notably broader technical conversation than a pure whiteboard HLD session.
- **Domain focus:** Food delivery — [Chapter 17, Problem 14](17-Problems-Intermediate.md) and [Chapter 18, Problem 24 (batching/fleet optimization)](18-Problems-Advanced.md) are directly relevant, given Talabat's core product.
- **Candidate sentiment (mixed, worth noting honestly):** some reports describe the system design round as feeling scripted ("say whatever the interviewer wants to hear, no creativity"), while others describe it more positively, emphasizing that demonstrating broad technology knowledge and multiple ways to solve the same problem with clear trade-off awareness is what's rewarded — the second framing aligns with everything in [Chapter 14](14-Interview-Framework-Communication.md), so prepare that way regardless of which style you draw.
- **Given Talabat's explicit mention of observability and queuing as topics:** your existing Prometheus/Grafana/OTel and Kafka background is a genuine, direct advantage here — make sure it surfaces naturally rather than staying implicit.

### Deliveroo, Tabby, Tamara, PayBy, Ziina, Magnati **[General]**

Specific, fresh interview-experience data for these companies wasn't part of this research pass — treat the guidance below as informed general reasoning based on each company's public business model, and verify against current Glassdoor/Blind reports close to your actual interview.

- **Deliveroo:** UK-founded, MENA-present food delivery — expect strong overlap with Talabat/Swiggy-style food-delivery HLD content ([Chapter 17, Problem 14](17-Problems-Intermediate.md); [Chapter 18, Problem 24](18-Problems-Advanced.md)), likely with a European engineering-culture interview style (collaborative, trade-off-focused, similar to the "excellent answer" patterns throughout Chapter 14).
- **Tabby, Tamara:** Buy-Now-Pay-Later (BNPL) fintech platforms operating across the UAE/Saudi/wider MENA region — expect a strong overlap with [Chapter 18, Problems 21–22](18-Problems-Advanced.md) and [Chapter 20's FinTech deep dive](20-Fintech-System-Design.md), specifically around installment/repayment scheduling (a state-machine and ledger problem, structurally similar to the payment state machine but with a recurring, scheduled-charge dimension worth thinking through: what happens when a scheduled installment charge fails — retry policy, grace periods, and how that interacts with the merchant's already-completed sale), credit-risk-adjacent data handling, and reconciliation.
- **PayBy, Ziina, Magnati:** UAE-focused payments/fintech infrastructure companies — expect core payment-gateway and ledger system design ([Chapter 18, Problem 21](18-Problems-Advanced.md); [Chapter 20](20-Fintech-System-Design.md)) to be squarely on-topic, likely with genuine regional-regulatory awareness valued (UAE Central Bank payment regulations, data residency) given these companies operate directly within that regulatory perimeter — worth being able to at least name that this context exists and that architecture (e.g., data residency, audit trail requirements) is shaped by it, even without deep regulatory expertise.

---

## 19.4 Cross-Cutting Prep Priorities Given Your Target List

Looking across every company above, three things repeat constantly regardless of geography: **fintech-flavored problems** (Razorpay, PhonePe, CRED, Tabby, Tamara, PayBy, Ziina, Magnati all lean here — make [Chapter 20](20-Fintech-System-Design.md) and [Chapter 18, Problems 21–22](18-Problems-Advanced.md) close to memorized), **geospatial/marketplace problems** (Careem, Uber, Talabat, Deliveroo, Swiggy, Zomato — make [Chapter 17, Problem 14](17-Problems-Intermediate.md) and [Chapter 18, Problems 17, 18, 23, 24](18-Problems-Advanced.md) equally close to memorized), and **trade-off articulation under direct challenge** (explicitly reported at Amazon, Google-family companies, and Careem specifically) — which is exactly what [Chapter 14.2](14-Interview-Framework-Communication.md) is built to drill.

---

*Next → [Chapter 20: FinTech System Design](20-Fintech-System-Design.md) — payment gateways, wallets, double-entry ledgers, reconciliation, and fraud detection at interview depth.*
