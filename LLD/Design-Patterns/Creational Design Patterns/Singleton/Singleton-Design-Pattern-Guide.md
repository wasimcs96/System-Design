---
title: "Singleton Design Pattern"
subtitle: "Senior/Staff Interview Handbook — Saudi Arabia, Dubai/UAE, Malaysia, India Tier-2, India Tier-1/60LPA+"
author: "Interview Prep Handbook"
date: "Updated July 2026"
---

# Singleton Design Pattern

*Fast Track (Parts 1–6) → Deep Dive (Parts 7–21) → Appendix (Part 22). Companion code: `Singleton.php` — all runnable examples referenced by name live there, not inlined here.*

---

## ⚡ FAST TRACK

### Part 1 — 60-Second Recall Card

| | |
|---|---|
| **One-liner** | Singleton ensures a class has **exactly one instance** for the lifetime of the process, and provides **one global access point** to it. |
| **GoF category** | Creational |
| **Core mechanism** | Private (or protected) constructor + a private static instance property + a public static `getInstance()` method that lazily creates the instance on first call and returns the same cached object on every call after. Block `__clone()` and `__wakeup()`/`__unserialize()` so copying or deserializing can't produce a second instance. |
| **Trigger phrase** | "There must only ever be **one** X in the whole application" — one logger, one config manager, one connection pool, one cache manager, one ID generator. |
| **Anti-trigger** | You need more than one configured instance of the same class (→ Factory/Registry); you need to unit-test the consumer in isolation and mock this dependency (classic Singleton fights you here — prefer container-managed single-instance injection); "one per tenant/request/user" is not actually "one," it's scoped state wearing a Singleton costume. |
| **Closest confused patterns** | **Static class** (no instance exists at all — Singleton *is* an object, can implement interfaces, can be polymorphic, a static class cannot); **Registry** (many named instances behind string/enum keys — Singleton is exactly one, anonymous); **Multiton** (a keyed map of Singletons — "one per key," not "one, period"); **DI-container "singleton scope"** (what most engineers actually mean day-to-day — a container caches and reuses one instance per binding, but there's no private constructor and no `getInstance()`; it's the same *idea*, deliberately without the GoF pattern's testability costs). |
| **Memory hook** | A country has exactly one sitting president at a time — the *office* enforces "only one," not the person's own willpower. Singleton is the class enforcing "only one instance of me" on itself, the same way. |

---

### Part 2 — Market Calibration

*Sourced directly from `design-patterns-frequency-guide-expanded.md`. Singleton ranks **#3 overall** on the master frequency table, labeled **Very High**, with real-world anchors: Logger, Config Manager, DB Connection Pool.*

| Market | Singleton's standing | Evidence | What that means for prep |
|---|---|---|---|
| **Malaysia** | **Headline pattern — the strongest single-pattern signal in any market in this guide.** | Market summary states Singleton is "uniquely explicit" — candidates at both **Maybank** and **AirAsia/Capital A** report being asked to *write it out live*, with **double-checked locking explicitly discussed at Maybank**. Also named at **Shopee** and **IBM Malaysia**. | If you're interviewing in Malaysia, this is not optional depth — you need to write a thread-safe Singleton from memory, unprompted, and be ready to defend the thread-safety mechanism under follow-up. This is the one market/pattern combination in the whole guide with a named, repeated "write it live" signal. |
| **India Tier-1 / 60LPA+** | **Strong #2, close behind Strategy.** | Named at **Uber India** (alongside multithreading), **Directi/Media.net**, **Oracle India**, **Mastercard India**, and inferred at **Grab** (India-facing). | Expect it as one of 2–4 patterns a bar-raiser-caliber interviewer wants named and justified in a larger design, often alongside a concurrency or scale-up follow-up — not usually the sole focus of a round, but a near-certain supporting player. |
| **India Tier-2** | **Top-4 recurring pattern**, after Strategy. | Named at **Razorpay**, **Postman** (explicitly justified, not just implemented), **ShareChat**, **Ola**, **Infosys**, **Cognizant**, **TCS Digital**. | Very likely to come up in a machine-coding round for a Logger/Config/Connection-Pool-shaped component; interviewers here are more likely to ask you to say the pattern name out loud and justify the choice than to probe deep internals. |
| **Saudi Arabia** | **Thin, single data point.** | Only one company row names it: **Accenture (KSA centers)**, where Singleton is flagged "most important" alongside Factory/Strategy/Observer, at global (not country-specific) confidence. | Don't over-invest specifically for Saudi Arabia on this pattern beyond the baseline — the guide's honest position is that the country-specific evidence here is thin, not that the pattern is unimportant globally. |
| **Dubai/UAE** | **No company-level evidence in the guide.** | The UAE section's "most-asked pattern" line names Strategy, Factory, and SAGA/pub-sub — Singleton does not appear in any of the 29 UAE company rows checked. | Treat this as a genuine data gap, not a signal that Singleton is unused in UAE interviews — it almost certainly still appears as a supporting pattern (it's foundational enough that most LLD rubrics touch it), but there's no direct citation to calibrate depth against. Prepare it at baseline depth rather than Malaysia-level intensity for UAE specifically. |

**Bottom line:** Singleton is the guide's #3 pattern overall and the single strongest "must write it live, thread-safe, from memory" signal anywhere in the dataset — specifically for Malaysia. Everywhere else it's a near-certain supporting pattern, not usually the headline of a round.

---

### Part 3 — Recognition, Decision Tree & When NOT to Use

**Requirement phrases that signal Singleton:**
- "There should only ever be one instance of the logger/config/connection pool/cache manager in the whole application."
- "Multiple parts of the system need to share the exact same [state/connection/counter] without passing it around explicitly."
- "Avoid creating a new [expensive resource] every time it's needed."
- "Provide a single, well-known access point to [X] from anywhere in the codebase."

**Code smells that signal an existing Singleton opportunity (or an existing broken attempt at one):**
- A class is instantiated with `new` in dozens of places, always configured identically, and every instance behaves identically.
- Global mutable state implemented as loose global variables or static class properties scattered across files, with no single owner.
- A resource (DB connection, thread pool, file handle) is being opened and closed repeatedly where one long-lived instance would do.

**Decision tree:**

```
Does the requirement genuinely need exactly ONE instance for the
entire process lifetime (not per-request, not per-tenant, not per-user)?
│
├─ NO → it's scoped state, not Singleton. Use a container binding
│        scoped to request/session/tenant instead.
│
└─ YES → Will this class ever need to be unit-tested in isolation,
         with this dependency mocked?
         │
         ├─ YES (almost always, in production code) → Prefer a DI
         │        container "singleton" binding (Laravel's
         │        ->singleton(), Spring's default bean scope) —
         │        same "one instance, cached" behavior, but the
         │        class itself stays a plain, mockable class with
         │        no private constructor or static access point.
         │
         └─ NO / this is a small self-contained utility or a
                  learning/interview exercise → Classic GoF
                  Singleton (private constructor + static
                  getInstance()) is acceptable.
```

**Explicit anti-triggers — do NOT reach for Singleton when:**
- You need a different configured instance per environment, tenant, or request (that's Factory, Registry, or a container-scoped binding — "one per X" is not "one, period").
- The class needs to be unit-tested with this dependency swapped for a test double — classic Singleton's hard-coded `getInstance()` call sites make that materially harder; a DI-container singleton binding solves the same problem without this cost.
- You're using it purely to avoid passing a parameter through a few function calls — that's usually a sign the design needs restructuring (introduce a parameter object or a service layer), not a global.
- In a distributed system with multiple processes/pods/nodes — a Singleton only guarantees "one instance per process." If the requirement is really "exactly one across the whole fleet" (e.g., a distributed lock, a leader-election result), you need a distributed coordination mechanism (Redis lock, ZooKeeper, etcd), not this pattern — Singleton solves the in-process case only, and confusing the two is a common interview trap.

---

### Part 4 — Cheat Sheet & Multi-Length Pitch

**One-page cheat sheet:**

| Aspect | Summary |
|---|---|
| Problem solved | Uncontrolled creation of a resource that should logically exist exactly once (config, logger, connection pool). |
| Mechanism | Private constructor, private static instance, public static accessor, guarded clone/deserialize. |
| Cost | Global mutable state, hidden dependencies, hard to unit-test, breaks under multi-process/distributed deployment, can hide poor design. |
| Benefit | Guaranteed single instance, lazy initialization, controlled global access without true global variables. |
| Modern alternative | DI container singleton-scope binding — same behavior, testable, no `getInstance()` call sites baked into consumers. |
| PHP-specific gotcha | PHP-FPM's shared-nothing-per-request model means a naive Singleton is automatically "safe" within one request but resets every request — very different from a long-running Node.js/Swoole/RoadRunner process, where the same instance persists across many requests and concurrency actually matters. |

**30 seconds:** "Singleton makes sure a class has exactly one instance for the whole app and gives you one place to reach it from — a private constructor plus a static `getInstance()` that builds the object once and hands back the same one every time after."

**1 minute:** "Singleton makes sure a class has exactly one instance for the whole app's lifetime, with one global access point. You do it with a private constructor so nobody can `new` it directly, a private static property holding the one instance, and a public static `getInstance()` that lazily creates it on first call and returns the cached one after that. You also block cloning and deserialization so those can't sneak a second instance into existence. It's the natural fit for things like a logger, a config manager, or a connection pool — anything that's genuinely supposed to exist exactly once. The catch is it introduces global state and makes the class hard to mock in tests, which is why most modern codebases get the same 'one instance, shared everywhere' behavior from a DI container's singleton-scope binding instead of the textbook pattern."

**3 minutes:** adds — the thread-safety story (why naive lazy init races under concurrency, what double-checked locking does and why Java needs `volatile` for it to actually be correct post-JDK5, and why PHP's per-request model changes the calculus versus a long-running Swoole/Node process); the testability cost in concrete terms (a `getInstance()` call baked into a consumer can't be swapped for a mock without a static-state reset hack); the distributed-systems caveat (Singleton is process-scoped, not fleet-scoped — don't confuse it with a distributed lock); and the Registry/Multiton distinction for when "one" isn't really the requirement.

**10 minutes:** full pattern — everything above, plus walking the interviewer through a real example end-to-end (e.g., a `ConfigManager` in a Laravel-style app), naming the SOLID tension it creates (violates the spirit of Dependency Inversion by having consumers reach out to a concrete global rather than receiving an injected dependency), comparing it live against the DI-container alternative with a one-line trade-off for each, and closing with the specific interview follow-up an interviewer is likely to layer on: "how would you make this safe if we moved this to a multi-worker async server?"

---

### Part 5 — Timed Mock Drill

**Prompt (45–60 minutes, live-coding style — matches the Maybank/AirAsia format from Part 2):** *"Design a `ConfigManager` for a payments backend. Configuration is loaded once from environment/config files at startup and read frequently across the request lifecycle by many unrelated services (payment gateway client, logger, feature-flag checker). Loading config is moderately expensive (file I/O + parsing) and must never produce two different in-memory copies with different values. Implement it, then be ready to discuss how your implementation behaves if we move this service from PHP-FPM to a long-running Swoole worker pool."*

**Time-boxed sub-steps:**
1. **0–5 min** — Restate the requirement, confirm "exactly one instance, process lifetime" is genuinely the ask (not per-request config), name the pattern out loud, state the trade-off you're accepting (global state / testability cost) before writing code.
2. **5–20 min** — Implement the naive version: private constructor, private static instance, public static `getInstance()`, lazy load on first call, block `__clone()`/`__wakeup()`.
3. **20–30 min** — Interviewer follow-up: "what happens under concurrent access?" — explain PHP-FPM's per-request isolation (no real race there), then explain what changes under Swoole/RoadRunner (shared process, concurrent coroutines/requests *can* race on first initialization), and implement or narrate a locking guard for that context.
4. **30–40 min** — Interviewer follow-up: "how would you unit-test a service that depends on this?" — walk through why `ConfigManager::getInstance()` baked into a consumer resists mocking, and what changing to constructor-injected + container-managed singleton scope would look like instead.
5. **40–55 min** — Interviewer follow-up: "we're scaling to multiple pods behind a load balancer — does your Singleton still guarantee one instance system-wide?" — correctly say no, it's process-scoped only, and name what *would* be needed for a true fleet-wide single-instance guarantee (external coordination, not this pattern).
6. **55–60 min** — Wrap: state the final design's trade-offs unprompted, don't wait to be asked.

**Self-grading rubric (score yourself honestly):**
- [ ] Named the pattern and its trade-off before writing code, not after.
- [ ] Implemented lazy initialization correctly (not eager, unless you explicitly justified why eager is safer here).
- [ ] Blocked `__clone()` and `__wakeup()`/`__unserialize()`, not just the constructor.
- [ ] Correctly distinguished PHP-FPM's per-request safety from Swoole/RoadRunner's shared-process risk, without conflating them.
- [ ] Correctly explained the testability cost and named the DI-container alternative unprompted.
- [ ] Correctly said "process-scoped, not fleet-scoped" when the multi-pod follow-up came — did not claim the Singleton solves distributed uniqueness.

---

### Part 6 — Pattern Recognition Drill

For each scenario: name the pattern, justify it in one sentence, then explicitly say why the two next-most-plausible patterns don't fit as well.

1. **"Every microservice needs to read the same feature-flag values, and we want exactly one in-memory feature-flag cache per running process, refreshed every 60 seconds."** → Singleton (one cache instance per process, refreshed in place) — not Registry (there's only one flag-cache, not many named ones) and not a plain static class (the cache needs to hold mutable, refreshable state and be swappable in tests, which a static class can't do cleanly).
2. **"We need a factory that returns a `PaymentGateway` object, and which concrete gateway (Stripe, Razorpay, PayU) depends on the merchant's configured region."** → Factory Method, not Singleton — the requirement is "pick the right concrete type," not "ensure only one instance exists"; nothing here says a gateway object should be process-unique.
3. **"Our test suite keeps failing intermittently because two tests running in the same process both mutate a shared `ConfigManager::getInstance()` and interfere with each other's expected values."** → This is the *textbook cost* of Singleton showing up as a bug report, not a scenario to apply the pattern to — the fix is migrating `ConfigManager` to a DI-container-managed instance that tests can override or reset per-test, not doubling down on the static accessor.
4. **"We need a connection pool that hands out a fixed number of reusable database connections, tracking which are checked out."** → Singleton *for the pool object itself* (there should be exactly one pool managing the connections) combined with Object Pool for the connection-reuse mechanics inside it — not Prototype (connections aren't cloned copies of each other, they're interchangeable pool members) and not Factory alone (a Factory would hand out a *new* connection every time, which defeats the pooling requirement).
5. **"We need one logger instance per request, tagged with that request's correlation ID, not shared across requests."** → Not Singleton — "one per request" is scoped state, not "one, period"; this is a container binding scoped to request lifetime (Laravel's `scoped()` binding, for example), which is exactly the anti-trigger from Part 3.
## 📘 DEEP DIVE

*Path map: `Fundamentals → Problem → Internals → Design → Implementation → Production → Trade-offs → Bugs → Interview Bank`.*

### Part 7 — Fundamentals

**Definition:** Singleton is a creational pattern that restricts instantiation of a class to a single object and provides a global point of access to that object.

**Beginner framing:** you want exactly one `Logger`, so instead of trusting every developer to remember "don't create a second one," the class refuses to let anyone create it directly — you always ask the class itself for "the" instance, and it either builds one the first time or hands you back the one it already built.

**Senior/staff framing:** Singleton is really solving two separable problems that GoF bundled into one pattern — **(1) controlled, lazy creation of an expensive or stateful resource**, and **(2) global accessibility without passing the reference through every layer of the call stack**. Modern practice has largely un-bundled these: DI containers solve (1) cleanly via singleton-scoped bindings, and (2) is now widely treated as a smell to *avoid* rather than a feature to lean on, because global reachability is exactly what makes a class's dependencies implicit and its tests fragile. Knowing this bundling — and why senior engineers reach for the container instead of the textbook pattern — is itself a strong interview signal.

---

### Part 8 — The Engineering Problem & Refactoring Trigger

**What code looks like before this pattern:** a `ConfigManager` (or `Logger`, or `DbConnection`) gets instantiated with `new` at multiple call sites across the codebase — a controller here, a background job there, a CLI command somewhere else. Each `new ConfigManager()` re-reads and re-parses the same config files from disk. Worse, if `ConfigManager` holds any runtime-mutable state (a feature-flag override set at runtime, for instance), each instance now has its *own* copy, and different parts of the app silently disagree about the current config.

**Why it breaks down at scale:** the cost compounds two ways — wasted work (re-parsing the same files repeatedly) and correctness risk (multiple sources of truth for state that must be consistent). The failure mode is rarely a crash; it's a silent inconsistency bug that surfaces as "why did this request see the old feature-flag value" days later.

**The code smell that should make an engineer reach for it:** repeated `new SomeExpensiveOrStatefulThing()` calls, all configured identically, scattered across files that have no reason to know about each other's instantiation.

**Production-mindset questions:**
- *What production problem actually forced engineers toward this pattern?* — usually either a measurable cost (re-opening DB connections or re-parsing config on every use) or a correctness bug (two components silently disagreeing about shared state) rather than a purely theoretical concern.
- *How would a senior engineer discover the requirement before it became a crisis?* — a code review flagging repeated `new X()` for something that's conceptually "the app's one and only Y," or a profiler/APM trace showing redundant I/O from repeated construction.
- *What metric would have shown it coming?* — elevated request latency or DB connection-pool exhaustion correlated with connection object churn; config-parsing time showing up disproportionately in a flame graph.
- *What alternatives would a competent engineer consider and reject first?* — "just document that everyone should reuse one instance" (relies on discipline, doesn't scale with team size); a plain global variable (works but has none of Singleton's lazy-init or encapsulation benefits and is even harder to reason about); passing the instance explicitly through every constructor (the "correct" DI answer, often rejected early only because of the refactor size, then revisited later once the codebase matures).

*(Full before/after refactoring code sequence lives in Part 19, not duplicated here.)*

---

### Part 9 — Internal Working

**Concept level (language-agnostic):** the pattern relies on the language allowing you to (a) restrict who can call a constructor, and (b) attach state and behavior to the *class itself* rather than only to instances — i.e., static/class-level members. `getInstance()` performs a check-then-act sequence: "does the cached instance exist yet? If not, create and cache it; either way, return it." That check-then-act sequence is the entire source of every concurrency bug this pattern can produce — two threads/coroutines can both pass the "does it exist?" check before either finishes creating it, producing two instances.

**PHP-specific mechanics, only as deep as this pattern's core gotcha requires:**
- Under classic **PHP-FPM**, each incoming HTTP request gets a fresh PHP process/worker with its own memory space — nothing (including your Singleton's static property) survives between requests, and nothing is shared *during* a request across threads, because there's only one execution context handling that request. This means the naive, non-thread-safe `getInstance()` is completely safe under PHP-FPM — but also means your "singleton" is really "one instance per request," which is often *not* what a config manager conceptually wants (it just happens not to matter for correctness, since each request re-derives the same config).
- Under a **long-running process model — Swoole, RoadRunner, or a persistent worker in Node.js/Java-style deployments** — the process (and therefore the static instance) persists across many requests, and multiple requests/coroutines can be in flight concurrently within that same process. Now the check-then-act race in `getInstance()` is real: two coroutines can both observe "not yet created" before either finishes constructing the instance, and you can end up with two live instances silently coexisting — exactly the bug the pattern was supposed to prevent.
- **PHP has no native `volatile` keyword or memory-visibility primitive** the way Java does — this is a genuine language-level gap. On a long-running PHP server (Swoole), the safe approaches are: use Swoole's own coroutine-safe primitives (a `Swoole\Lock` or `Swoole\Coroutine\Channel`-based guard around initialization), or — more commonly in practice — sidestep the problem entirely by initializing the singleton once at worker-boot time (before any request-handling coroutines start), rather than lazily on first access. This is a real, current PHP-ecosystem gotcha, not a theoretical one, since Swoole/RoadRunner adoption for high-throughput PHP services has grown specifically because of this shared-process performance model.

---

### Part 10 — Components, UML & Language Mapping

**Roles:**
- **Singleton (the class itself):** owns a private static reference to its one instance, a private/protected constructor, and a public static accessor.
- **Client:** never calls `new`; always goes through the static accessor.

```
┌─────────────────────────────┐
│         Singleton           │
├─────────────────────────────┤
│ - instance: static Singleton│
├─────────────────────────────┤
│ - __construct()             │
│ - __clone()                 │
│ + getInstance(): Singleton  │
│ + someBusinessMethod()      │
└─────────────────────────────┘
```

**Sequence (first call vs. subsequent call) — worth drawing because it's the one diagram that actually explains the lazy-init behavior an interviewer will ask about:**

```
Client            Singleton
  │  getInstance()    │
  ├───────────────────>│  (instance == null)
  │                    │  create instance, cache it
  │<───────────────────┤  return instance
  │  getInstance()    │
  ├───────────────────>│  (instance != null)
  │<───────────────────┤  return cached instance  (no construction)
```

**Language mapping for the core mechanism:**

| Language | How "exactly one instance" is typically achieved |
|---|---|
| **PHP 8.3** | Private constructor + private static property + public static `getInstance()`; block `__clone()` (throw) and `__wakeup()`/`__unserialize()`. |
| **Java** | Same shape, but thread-safety is a first-class concern — either `enum Singleton { INSTANCE }` (JVM-guaranteed single instantiation, the community-preferred modern approach), or double-checked locking with a `volatile` instance field. |
| **Python** | Rarely done via `__new__` override in production code — more commonly a module-level instance (Python modules are already singletons, imported once and cached by the interpreter) or a metaclass-based singleton for a class hierarchy. |
| **Go** | `sync.Once` — `Do()` guarantees the initialization function runs exactly once even under concurrent callers, which is a cleaner primitive than manual double-checked locking. |
| **TypeScript/Node.js** | Node's module cache already behaves like a singleton for anything exported from a module (imported once, same object reference everywhere) — an explicit class-based Singleton is less common than in PHP/Java for this reason, similar to Python. |

---

### Part 11 — Implementation Overview (PHP/Laravel/Node)

The companion `Singleton.php` file walks through: a naive (not-concurrency-safe) version; a version with clone/wakeup guards; a Swoole-aware version using boot-time initialization instead of lazy check-then-act; and a `ConfigManager` real-world example.

**Where this pattern genuinely does — and doesn't — show up in real framework internals, verified against current source rather than assumed:**
- **Laravel's service container `->singleton()` binding** is the idiomatic modern replacement most PHP teams actually reach for instead of the textbook pattern. Per Laravel's own documentation (verified against the current Service Container docs): `singleton()` registers a binding that is "resolved only one time" — the first time the container resolves it, it builds the instance and caches it internally; every subsequent resolution of that binding returns the same cached object. Contrast with `bind()`, which constructs a fresh instance on every resolution. Critically, this achieves the "one instance" guarantee **without** a private constructor or a `getInstance()` call baked into consumers — the class stays a plain, constructor-injectable, mockable PHP class, and the container is the only thing that knows it's being treated as a singleton. This is the textbook illustration of Part 7's "un-bundling" point: Laravel gives you problem (1) — controlled single instantiation — without problem (2)'s global-accessibility cost.
- **Spring Boot's default bean scope is `singleton`** (one instance per Spring `ApplicationContext`, not per JVM) — the same idea, same un-bundling, for readers whose interviews may run in Java/Spring shops (Oracle India and Mastercard India per Part 2's data both evaluate Spring-adjacent backend roles).
- **What Singleton is *not*, in framework terms:** it's easy to conflate "the framework's container treats this as a singleton" with "this class implements the GoF Singleton pattern" — they produce the same runtime behavior (one shared instance) through opposite mechanisms (container-managed lifetime vs. self-enforced construction control). An interviewer asking "does Laravel use Singleton internally?" is testing whether you know this distinction, not just whether you can define the pattern.

---

### Part 12 — Where This Shows Up in Production

**Scenario 1 — Payments platform config manager (Razorpay/Postman-style, per Part 2's India Tier-2 data):** a `ConfigManager` loads API keys, feature flags, and rate-limit thresholds once at boot and serves them to dozens of unrelated services. Implemented as a Laravel container singleton binding rather than a classic static-accessor Singleton specifically so the payment-gateway integration tests could inject a fake config without touching global state.

**Scenario 2 — Ride-hailing dispatch service connection pool (Uber India/Grab-style, per Part 2's Tier-1 data):** a single `DbConnectionPool` instance manages a fixed set of reusable database connections shared across the dispatch service's request handlers running under a long-running worker process. This is the scenario where the Swoole/RoadRunner concurrency caveat from Part 9 is not academic — the pool's initialization genuinely needs a concurrency guard because the worker process is shared across many in-flight coroutines.

**Scenario 3 — Digital banking audit logger (Maybank-style, per Part 2's Malaysia data):** a single `AuditLogger` instance ensures every audit-trail write goes through one code path with consistent formatting and a single open file handle/log-shipping connection, avoiding interleaved or duplicated log entries that multiple independent logger instances could produce.

**Microservices-usage table:**

| Component | Typically Singleton-shaped? | Why |
|---|---|---|
| Config/feature-flag manager | Yes | Genuinely one source of truth per process. |
| Structured logger | Yes | One consistent output stream/format per process. |
| DB connection pool | Yes (the pool object; not each connection) | Pooling requires one coordinator. |
| Cache client wrapper (Redis/Memcached connection) | Usually | Avoids reconnect overhead per use. |
| Per-request correlation-ID context | **No** | Scoped to request, not process — common anti-trigger from Part 3. |
| Domain entities (User, Order, Payment) | **No** | Each is naturally many-instanced; forcing Singleton here is a design smell. |

**Architecture Decision Record — adopting a container-managed singleton for `ConfigManager`:**

- **Context:** `ConfigManager` was being instantiated with `new` in 14 call sites across the payments service, each re-parsing the same YAML config files, and two integration tests had started failing intermittently due to config drift between instances created at slightly different times during a deploy.
- **Decision:** Register `ConfigManager` as a Laravel container singleton binding (`app()->singleton(ConfigManager::class, ...)`) rather than implementing the GoF static-accessor pattern.
- **Consequences:** Config is now parsed exactly once per process; all 14 call sites were refactored to constructor-inject `ConfigManager` instead of instantiating it; integration tests can now bind a fake `ConfigManager` in the test container without touching production code.
- **Alternatives considered:** (a) classic GoF Singleton with `getInstance()` — rejected because it would keep the same testability cost the team was trying to eliminate; (b) a plain global variable — rejected as strictly worse than either option, with none of the lazy-init or type-safety benefits; (c) passing `ConfigManager` explicitly through every constructor without container support — technically the "purest" DI answer, rejected only due to the size of the refactor versus the container-based option delivering the same testability win with far less churn.
- **Trade-offs:** the team accepted that `ConfigManager` is still implicitly "special" (only one binding registered, by convention) rather than the container enforcing true single-instantiation the way a private constructor would — a deliberate trade of a small amount of enforcement rigor for a large gain in testability and reduced boilerplate.

---

### Part 13 — Field Notes (Simulated Production Experience)

*Rehearsal scaffold, not a script — personalize with real project details before using as an actual interview answer, or present it plainly as illustrative rather than personal history.*

"On a payments team I worked with, we had a classic-style `Logger::getInstance()` singleton that had been in the codebase for years. It worked fine until we started running integration tests in parallel inside the same PHP-FPM worker pool during CI — turned out two tests could, under specific timing, both trigger `Logger`'s first-call initialization at nearly the same moment, and depending on scheduling we'd occasionally get a log file handle opened twice, with one handle silently going stale. The fix wasn't a locking mechanism — it was recognizing that a classic Singleton was solving a problem we no longer had (we weren't sharing state across true concurrent threads within one process; PHP-FPM doesn't do that), while creating a testability problem we did have (that hard static call site made it hard to isolate tests cleanly). We migrated it to a container-managed singleton binding, injected everywhere it was needed, and the flaky test disappeared along with about 40 lines of defensive locking code that had never actually been the right fix in the first place."

---
### Part 14 — Analogies & Architecture Fit

**Analogies:**
- **A country's sitting president/PM** — the office structurally enforces "exactly one at a time"; nobody has to personally remember not to create a second one. Best single analogy for "the class enforces it, not the developer's discipline."
- **A building's single reception desk** — every visitor is routed through the same one desk regardless of which entrance they used; the desk itself doesn't multiply just because more people show up. Captures the "single access point" half of the definition well, less so the lazy-creation half.
- **A company's one official letterhead template** — every department must use the identical, centrally-controlled version rather than each keeping its own slightly-drifted copy. Useful for the config/consistency framing specifically.
- **Weak analogy, worth naming as weak:** "a bank has one vault" — sounds right but breaks down immediately (real banks have branches, each with its own vault), so it accidentally illustrates *Multiton* (one per branch/key) better than true Singleton — worth having ready specifically to redirect an interviewer who offers this comparison.

**Architecture fit:**
- **Clean/Hexagonal/Onion:** Singleton-as-global-access-point sits uncomfortably here — it's most defensible as an *infrastructure-layer* concern (a DB connection pool, a logger sink) accessed via an interface the domain layer depends on, never as something the domain layer reaches for directly. A domain entity calling `Logger::getInstance()` directly is a violation worth flagging in review.
- **DDD:** maps to nothing in the domain model itself — Aggregates, Entities, and Value Objects are all meant to be many-instanced. Singleton belongs, if anywhere, in the supporting infrastructure/application layers (a repository's shared connection pool), not the domain.
- **Event-driven architecture:** a single event-bus client connection per service instance is a legitimate Singleton-shaped need — but the guarantee is process-scoped, so a genuinely fleet-wide "exactly one consumer processed this event" guarantee needs idempotency keys or a distributed lock, not this pattern (ties directly to the anti-trigger in Part 3).
- **CQRS:** no strong connection — stated plainly rather than forced.
- **Cloud-native/Kubernetes:** worth one sentence, not more — a Singleton guarantees one instance *per pod/process*; it says nothing about the fleet, and conflating the two is the single most common architecture-level mistake candidates make with this pattern in system-design-adjacent interviews.

**✓ Before you move on:** (1) Which analogy actually illustrates Multiton better than Singleton, and why is that worth catching? (2) In a Kubernetes deployment with 20 replica pods, how many Singleton instances of a given class actually exist across the fleet?

---

### Part 15 — SOLID, Performance & Concurrency

**SOLID:** the honest picture here is mostly negative, and saying so directly is a stronger interview answer than forcing a positive spin. **Single Responsibility** is often violated in practice, not by the pattern itself but by what accretes onto Singletons over time — a `ConfigManager` that started focused tends to accumulate unrelated helper methods precisely because it's the one object everyone can reach from anywhere. **Open/Closed** has no strong connection either way. **Liskov Substitution** is not meaningfully engaged unless the Singleton implements an interface (recommended practice specifically so it CAN be substituted with a test double). **Interface Segregation** has no meaningful connection. **Dependency Inversion is the one Singleton actively fights** — DIP wants consumers to depend on abstractions provided to them, not reach out to a concrete global; a hard-coded `Logger::getInstance()` call site is a textbook DIP violation, which is exactly why the container-managed-singleton alternative (constructor-injected, bound to an interface) is preferred in mature codebases.

**Performance:** the win is real but narrow — avoiding repeated expensive construction (file I/O, network handshake, parsing) by paying that cost once. It is not a general performance pattern and shouldn't be reached for on that basis alone; a cheap-to-construct class gains nothing from Singleton and only inherits its costs.

**Concurrency:** this is the section with the most genuine technical depth in this pattern, and where Part 2's Malaysia/Maybank data makes it non-optional. The unguarded `getInstance()` check-then-act sequence races under true concurrent execution. In **Java**, the historically correct fix is double-checked locking with the instance field marked `volatile` — verified via search against current sources: pre-JDK5, double-checked locking was actually broken due to instruction reordering allowing another thread to observe a partially-constructed object; JDK5+'s revised memory model, combined with `volatile`, fixes this by preventing that reordering. Simpler and now more commonly recommended in Java: the enum-based singleton (`enum Singleton { INSTANCE }`), where the JVM itself guarantees single, thread-safe instantiation with no manual locking at all. In **PHP**, the calculus is different by deployment model, exactly as detailed in Part 9: PHP-FPM's per-request isolation means there's no real race to guard against; Swoole/RoadRunner's shared, long-running process means there is, and PHP's lack of a `volatile`-equivalent primitive means the practical fix is either a coroutine-safe lock (`Swoole\Lock`) or — more common in production — eager initialization at worker-boot time rather than lazy check-then-act at all. In **Go**, `sync.Once` is the idiomatic, already-correct primitive purpose-built for exactly this problem, and is generally preferred over hand-rolled double-checked locking in any language that offers an equivalent.

**✓ Before you move on:** (1) Which single SOLID principle does classic Singleton most directly fight, and why? (2) Why was double-checked locking actually broken in Java before JDK5, and what specific change fixed it?

---

### Part 16 — Advantages, Disadvantages & Trade-offs

| Dimension | Advantage | Disadvantage / trade-off |
|---|---|---|
| **Performance** | Avoids redundant expensive construction (I/O, parsing, connection setup) | Zero benefit for cheap-to-construct classes; pure overhead if misapplied |
| **Scalability** | Predictable, bounded resource usage (one pool, one connection) | Process-scoped only — provides no fleet-wide guarantee, and can become a per-process bottleneck under high concurrency if not internally thread-safe |
| **Maintainability** | One well-known place to find "the" instance of something | Accretes unrelated responsibilities over time precisely because it's globally reachable (classic scope creep) |
| **Readability** | Simple, widely recognized pattern once named | Hidden dependency — reading a method's signature doesn't reveal that it silently depends on global state |
| **Security** | Neutral | A shared mutable Singleton holding tenant- or user-specific state is an active data-leak risk under any shared-process deployment model — must never hold per-request-sensitive data |
| **Testing** | None inherent to the pattern itself | Actively adversarial to unit testing — hard-coded `getInstance()` call sites resist mocking without static-state reset hacks between tests; this is the single most-cited reason mature teams avoid the classic form |
| **Observability** | One instance is a natural place to attach metrics (cache hit rate, connection pool utilization) | Harder to attribute behavior to a specific caller/request when state is globally shared and mutated from many call sites |

**✓ Before you move on:** (1) Name the one dimension where Singleton is close to a pure, low-risk win. (2) Name the dimension most responsible for mature teams preferring the DI-container alternative.

---

### Part 17 — Pattern Comparisons

| | Singleton | Static Class | Registry | Multiton | DI-Container Singleton Scope |
|---|---|---|---|---|---|
| Instances that exist | Exactly one (an object) | Zero (no instance at all) | Many, named | Many, keyed | Exactly one per binding (an object) |
| Can implement an interface / be polymorphic | Yes | No | Yes (each entry) | Yes (each entry) | Yes |
| Testable / mockable | Hard — hard-coded access point | Hard — no instance to substitute | Easier — can register a fake under the same key | Easier, per-key | Easy — constructor injection, container swaps the binding in tests |
| Enforced by | The class itself (private constructor) | The language (no instantiation concept used) | Convention (nothing stops `new`-ing the class directly unless combined with Singleton) | Convention, per key | The container's configuration, not the class |
| Typical modern usage | Small, self-contained utilities; interview/learning contexts | Pure stateless helper functions (math, string utils) | Multiple named services/strategies (e.g., a strategy registry) | "One connection pool per database shard," "one client per tenant" | Production services in mature codebases (Laravel `singleton()`, Spring default scope) |

**Decision table:**

| Situation | Reach for |
|---|---|
| Exactly one instance, ever, self-contained utility, testability not a concern | Classic Singleton |
| Exactly one instance, but the class must stay unit-testable | DI-container singleton-scope binding |
| Several interchangeable stateless helper functions, no instance needed at all | Static class / free functions |
| Several named, independently-swappable instances (strategies, gateways) | Registry |
| "One per key" (one pool per shard, one client per tenant) | Multiton |
| Fleet-wide "exactly one," across multiple processes/pods | Neither — distributed lock / leader election |

**✓ Before you move on:** (1) What's the one-sentence difference between Registry and Multiton? (2) Why does a DI-container singleton binding solve the same problem as classic Singleton without the same testability cost?

---

### Part 18 — Production Bugs, AI-Generated Code Review & Testing

**The flagship bug — the race in lazy check-then-act.** Under any deployment model where multiple execution contexts can run concurrently within one process (Swoole, RoadRunner, a Java/Node service — not classic PHP-FPM), an unguarded `getInstance()` can construct two separate instances if two callers both observe "not yet created" before either finishes constructing. Symptom: intermittent, hard-to-reproduce bugs where state that "should" be shared silently isn't — e.g., a feature flag toggled through one instance doesn't appear to take effect, because a different caller is reading a second, stale instance. Debug by adding an instance-identity log (`spl_object_id($instance)`) at every call site during investigation — if you see more than one ID for what should be "the" singleton, you've confirmed the race.

**Stale-state-across-tests bug.** A classic Singleton's static state persists for the life of the PHP-FPM worker process (which is reused across many requests, just not indefinitely) or, worse, for the life of a test run if tests share a process — leading to test pollution where one test's mutation of the Singleton leaks into the next test's assumptions. Fix: either migrate to a container-managed instance the test harness can reset per test, or explicitly add a test-only reset hook — and flag that reset hook in code review as a sign the design should probably migrate away from classic Singleton.

**How AI coding assistants typically get this pattern wrong:**
- **Most common failure:** AI-generated Singleton implementations reliably include the private constructor and `getInstance()` but **frequently omit the `__clone()` and `__wakeup()`/`__unserialize()` guards** — the pattern "looks complete" (compiles, passes a basic smoke test) while still allowing a second instance via cloning or deserialization, which is a subtle, review-time-only catch, not something a quick manual test surfaces.
- **Second most common failure:** when asked to "make a thread-safe singleton," AI assistants frequently generate Java-style double-checked locking **translated literally into PHP**, including a nonexistent `volatile` keyword or a lock construct that doesn't map to PHP's actual concurrency model — this is a direct instance of pattern-matching on the *shape* of a known-correct Java solution without reasoning about whether PHP's execution model (per-request isolation under FPM; shared-process coroutines under Swoole) makes that specific mechanism meaningful at all.
- **What a reviewer should check before merging:** (1) are `__clone()` and `__wakeup()`/`__unserialize()` explicitly guarded, not just the constructor; (2) if "thread safety" was requested, does the chosen mechanism actually correspond to this codebase's real concurrency model (PHP-FPM vs. Swoole/RoadRunner), rather than a mechanically-translated Java idiom; (3) is this even the right call — could a DI-container singleton binding deliver the same guarantee with better testability, and was that alternative actually considered or just skipped.

**Testing strategy — the identity/uniqueness test is the one category that matters most for this pattern:**

```php
public function test_get_instance_always_returns_the_same_object(): void
{
    $a = ConfigManager::getInstance();
    $b = ConfigManager::getInstance();

    $this->assertSame($a, $b); // identity, not just equal values
}

public function test_cloning_is_blocked(): void
{
    $this->expectException(\Error::class);
    $clone = clone ConfigManager::getInstance();
}
```

The critical detail, mirroring the equivalent rule from the Prototype handbook but inverted: here you assert `assertSame` (identity) to prove uniqueness is being *preserved*, and you explicitly test that cloning/unserialization is *blocked* — a Singleton test suite that only checks "does `getInstance()` return a `ConfigManager`" without checking identity across calls hasn't actually tested the pattern's core guarantee at all.

**Code review checklist:** constructor is private (or protected, if subclassing is deliberately supported); `__clone()` throws or is private; `__wakeup()`/`__unserialize()` is guarded; an identity test (`assertSame`) exists, not just a type check; if "thread safety" is claimed, the mechanism is verified against this codebase's actual deployment/concurrency model, not copy-pasted from a different language's idiom; a DI-container alternative was genuinely considered for anything beyond a small self-contained utility.

**✓ Before you move on:** (1) What's the most common AI-generated-code gap specifically in Singleton implementations? (2) Why must the uniqueness test use `assertSame` rather than checking the returned type alone?

---

### Part 19 — Refactoring Journey

Full code for every stage lives in `Singleton.php`; this narrates the reasoning connecting each one.

**Stage 1 — Terrible** *(where most engineers start, no shame in it):* `new ConfigManager()` scattered across a dozen files, each call re-parsing the same config from disk, no single source of truth.

**Stage 2 — Bad, but a realistic first instinct** *(often written by a mid-level engineer under time pressure):* a plain global variable or a static class property holding "the" config, set once somewhere in a bootstrap file and read everywhere else. Fixes the redundant-parsing problem but has none of Singleton's encapsulation — anything can overwrite the global from anywhere, with no controlled access point and no lazy-initialization guarantee.

**Stage 3 — Average, and the most dangerous stage in the whole journey** *(a senior engineer moving fast, or code that later drifts as new call sites are added without matching review):* a correctly-implemented classic Singleton — private constructor, static `getInstance()`, lazy init — but **missing the `__clone()`/`__wakeup()` guards**. Passes every normal test, looks finished, and silently allows a second instance the moment anything clones or unserializes it — exactly the AI-generated-code gap flagged in Part 18.

**Stage 4 — Pattern correctly applied** *(what a rigorous senior/staff engineer ships):* adds the clone/wakeup guards, adds the identity-uniqueness test proving them, and — critically for Malaysia-market prep per Part 2 — adds the concurrency guard appropriate to the actual deployment model (a no-op justification under PHP-FPM; an explicit coroutine-safe lock or boot-time eager init under Swoole/RoadRunner).

**Stage 5 — Production-ready** *(staff-level judgment about the surrounding system, not just the class):* migrates from the classic static-accessor form to a DI-container singleton binding, preserving the "one instance" guarantee while restoring full testability — instrumented with metrics on initialization time and cache/connection-pool utilization, and documented with an ADR (Part 12) explaining why the team made that trade.

**✓ Before you move on:** (1) Which stage is the most dangerous to leave in production, and why specifically that one rather than Stage 1 or 2? (2) What's the concrete difference between Stage 4 and Stage 5 — is it a testability difference or a concurrency-safety difference?

---

### Part 20 — Practices, Mistakes & Traps

**Junior mistakes:** forgetting the clone/wakeup guards entirely; making the constructor `public` "by accident" while still calling it a singleton; assuming `getInstance()` is automatically thread-safe in every language/runtime without checking.

**Mid-level mistakes:** reaching for Singleton to solve "I don't want to pass this parameter through three function calls" — a design smell better solved by restructuring, not a global; conflating "the DI container treats this as a singleton" with "I need to implement the GoF pattern by hand," and hand-rolling one unnecessarily inside a codebase that already has a perfectly good container.

**Senior mistakes:** translating a Java-style double-checked-locking solution literally into PHP without reasoning about whether PHP's actual concurrency model (per-request under FPM vs. shared-process under Swoole) makes that mechanism meaningful — the exact AI-generated-code failure mode from Part 18, equally possible from a human who's pattern-matching on a remembered Java answer; assuming a Singleton provides a fleet-wide uniqueness guarantee in a distributed/multi-pod deployment, when it only guarantees uniqueness per process.

**Interview follow-up questions that catch memorized-but-shallow understanding:**
- "You said this is thread-safe — thread-safe under what specific deployment model, and why does that qualifier matter in PHP specifically?" (catches candidates who memorized "double-checked locking = thread-safe" without understanding PHP-FPM vs. Swoole.)
- "If I have 20 replica pods running this service, how many instances of your Singleton actually exist right now, across the whole fleet?" (catches the process-scoped-vs-fleet-scoped conflation from Part 14.)
- "How would you unit-test a class that depends on this Singleton, without touching global state between tests?" (catches candidates who can implement the pattern but haven't internalized its testability cost.)
- "Why did you choose a classic Singleton here instead of just registering this in the DI container as a singleton binding?" (tests whether the candidate made a deliberate trade-off or just defaulted to the textbook pattern out of habit.)

**✓ Before you move on:** (1) What's the difference between a mid-level and a senior mistake with this pattern, in one sentence? (2) Which interview follow-up specifically targets the process-scoped-vs-fleet-scoped misunderstanding?

---
### Part 21 — Interview Question Bank & Coding Problems

*Curated, high-signal, roughly 7 per level. Total questions delivered: 35.*

**Beginner (7)**

1. *What problem does Singleton solve?* — Why asked: baseline definition check. — Wrong: "it makes code run faster." — Good: "ensures only one instance of a class exists." — Excellent: adds "...and provides a single global access point to it — both halves of the definition, not just the instance count." — Follow-up: "give a real example from a web app."
2. *How do you prevent a class from being instantiated with `new` from outside?* — Wrong: doesn't know constructors can have visibility modifiers. — Good: "make the constructor private." — Excellent: names that `protected` is used instead when controlled subclassing is intentionally supported. — Follow-up: "what happens if you forget this and leave it public?"
3. *What does `getInstance()` typically do?* — Good: "returns the single instance, creating it the first time." — Excellent: explicitly separates the "does it exist yet" check from the "create and cache" step, foreshadowing the concurrency discussion. — Follow-up: "is that creation eager or lazy in your example, and why?"
4. *Name two real-world examples where Singleton fits well.* — Good: logger, config manager. — Excellent: adds *why* each fits (process-wide consistency requirement) rather than just naming them. — Follow-up: "name one place people wrongly reach for it."
5. *What's the difference between Singleton and a static class?* — Wrong: "they're the same thing." — Good: "static class has no instance at all." — Excellent: adds that Singleton can implement interfaces and be polymorphic, a static class cannot. — Follow-up: "when would that difference actually matter in real code?"
6. *Why might cloning a Singleton be a problem?* — Good: "it would create a second instance." — Excellent: names `__clone()` specifically and that it must be explicitly blocked. — Follow-up: "what about deserialization?"
7. *Is Singleton a creational or structural pattern?* — Good: "creational." — Excellent: explains why (it's about controlling *how* an object comes into existence, not about composing objects together). — Follow-up: "name the other four GoF creational patterns."

**Intermediate (7)**

1. *Walk through implementing a thread-safe Singleton in PHP.* — Wrong: translates Java double-checked locking literally without addressing whether PHP's model needs it. — Good: correctly notes PHP-FPM's per-request isolation means it's usually a non-issue. — Excellent: distinguishes PHP-FPM from Swoole/RoadRunner and gives a concrete guard for the latter. — Follow-up: "what would you do differently if this ran on Swoole?"
2. *Why is Singleton considered hard to unit test?* — Good: "the hard-coded `getInstance()` call resists mocking." — Excellent: proposes the concrete fix (constructor injection via a DI container singleton binding). — Follow-up: "show me how you'd refactor a consumer to make it testable."
3. *What's the difference between Singleton and the Registry pattern?* — Good: "Registry holds many named instances; Singleton is exactly one." — Excellent: gives a concrete example needing Registry instead (a strategy registry keyed by payment-provider name). — Follow-up: "when would you combine the two?"
4. *Does a Singleton guarantee uniqueness across multiple servers/pods?* — Wrong: "yes." — Good: "no, only within one process." — Excellent: names what would actually be needed for fleet-wide uniqueness (distributed lock/leader election) and gives an example. — Follow-up: "what's a concrete scenario where this distinction caused a real bug?"
5. *How would you make a Singleton class mockable in tests without abandoning the pattern entirely?* — Good: "extract an interface, inject it instead of calling `getInstance()` directly." — Excellent: describes a container-managed singleton binding as the natural landing point of that refactor. — Follow-up: "is that still 'really' a GoF Singleton at that point?"
6. *What happens if two threads/coroutines call `getInstance()` at the exact same moment on an unguarded implementation?* — Good: "you might get two different instances." — Excellent: explains the check-then-act race precisely (both observe "null" before either finishes assignment). — Follow-up: "how would you reproduce this in a test?"
7. *Why might eager initialization be preferable to lazy initialization in some Singleton implementations?* — Good: "avoids the concurrency race entirely, since it's created before any concurrent access is possible." — Excellent: names the Swoole worker-boot-time pattern from Part 9 as a concrete example. — Follow-up: "what's the downside of eager init if the resource is expensive and rarely used?"

**Senior (7)**

1. *Design a `ConfigManager` that must be safely shared across a Swoole worker pool. Walk through your concurrency strategy.* — Wrong: copies Java's `volatile` + double-checked locking verbatim. — Good: proposes a `Swoole\Lock`-guarded initialization or boot-time eager init. — Excellent: explains *why* PHP has no `volatile` equivalent and reasons about the actual memory model, not just pattern-matching a remembered solution. — Follow-up: "how would you test this specific race condition?"
2. *Your team has 14 classes each implementing their own classic Singleton. What's your refactoring plan, and how do you sequence it safely?* — Good: proposes migrating to a shared DI-container pattern. — Excellent: sequences it (start with the least-depended-on class, add tests before refactoring each, keep the old static accessor as a thin wrapper during transition to avoid a big-bang rewrite). — Follow-up: "how do you know when it's safe to delete the old static accessor?"
3. *A Singleton-managed connection pool is showing degraded performance under high load. Where do you look first?* — Good: instrumentation on pool utilization/wait times. — Excellent: also considers whether the Singleton itself (not just the pool inside it) has become a contention point — e.g., a lock inside `getInstance()` being hit far more often than expected due to a bug elsewhere causing repeated re-initialization attempts. — Follow-up: "how would you distinguish 'pool is too small' from 'singleton initialization is the bottleneck'?"
4. *When would you explicitly choose NOT to use a Singleton for something that seems to want 'only one instance'?* — Good: cites the per-tenant/per-request anti-trigger. — Excellent: connects it back to Multiton or scoped DI bindings with a concrete example. — Follow-up: "how would a multi-tenant SaaS product change your answer?"
5. *Explain the DIP tension Singleton creates and how you'd defend using it anyway in a specific case.* — Good: names that consumers depend on a concrete global rather than an injected abstraction. — Excellent: gives a genuinely defensible case (a tiny, self-contained, rarely-changing utility where the DI ceremony isn't worth it) rather than hand-waving. — Follow-up: "where's the line — how do you decide when it's not worth it anymore?"
6. *How would you detect, in production, that a Singleton's uniqueness guarantee has been silently broken?* — Good: proposes instance-identity logging. — Excellent: proposes a lightweight runtime assertion/health-check that periodically compares instance identity across code paths, plus alerting on divergence. — Follow-up: "what's the blast radius if this goes undetected for a week?"
7. *Compare classic Singleton to Go's `sync.Once` and Java's enum-based singleton as concurrency-safety strategies.* — Good: names both as safer, more idiomatic alternatives to manual double-checked locking. — Excellent: explains specifically *why* each is safer (`sync.Once` guarantees exactly-once execution via a language primitive; Java enums are guaranteed single-instantiated by the JVM's classloading semantics) rather than just naming them. — Follow-up: "is there a PHP equivalent to `sync.Once`?"

**Staff/Principal (7)**

1. *A Singleton-based feature-flag cache is causing a production incident: some pods are serving stale flags for up to 10 minutes after a flag change. Diagnose and fix.* — Excellent answer covers: confirming it's a per-process staleness issue (each pod's Singleton cached the flag at boot/last-refresh, not a shared-state bug); proposing a refresh mechanism (TTL-based re-fetch, or a pub/sub invalidation signal) rather than abandoning the pattern; and explicitly stating this is a cache-invalidation problem wearing a Singleton costume, not a Singleton-correctness bug. — Follow-up: "would you consider this a bug in the Singleton pattern's design, or in how it was applied here?"
2. *Your org is migrating from PHP-FPM to a Swoole-based deployment for a service with a dozen existing classic Singletons. What's your migration risk assessment?* — Excellent answer: identifies that every one of those Singletons silently changes behavior (from "safe by construction" under FPM to "potentially racy" under Swoole) without any code change being required to trigger the new risk — a genuinely dangerous "nothing changed but everything changed" migration trap — and proposes an audit-then-guard-or-refactor plan prioritized by which Singletons hold genuinely mutable state versus which are effectively read-only after boot.
3. *Design an ADR recommending for or against classic Singleton usage as a team-wide standard, for a payments platform.* — Excellent answer produces a real ADR shape (Context/Decision/Consequences/Alternatives/Trade-offs) and lands on a defensible, non-absolutist position — e.g., "prefer container-managed singleton bindings by default; classic Singleton permitted only for small, stateless, self-contained utilities with no test-isolation requirement" — rather than a blanket "always/never."
4. *How does Singleton interact with horizontal autoscaling, and what's the failure mode if an engineer assumes it doesn't?* — Excellent answer: each new pod gets its own fresh Singleton instance (cold cache, cold connection pool) — if the team assumed "there's only one instance" fleet-wide, autoscaling events can cause a thundering-herd re-initialization spike (many pods all cold-starting their pool/cache simultaneously) that the original single-process design never had to account for.
5. *A candidate on your team wants to implement a distributed cache using a Singleton. Coach them.* — Excellent answer: names precisely where the mental model breaks (Singleton is process-scoped, distributed cache needs cross-process coordination), and redirects toward the actually-correct tools (Redis/Memcached as the shared store, with each process's Singleton being merely a *client* to that shared store, not the store itself) — the coaching angle, not just the technical answer, is what's being evaluated at this level.
6. *When, if ever, is it defensible to make Singleton state genuinely mutable and written from many call sites, versus read-only after initialization?* — Excellent answer distinguishes: read-only-after-boot state (config, feature flags refreshed on a controlled cadence) is comparatively low-risk; freely-mutable state written from many unrelated call sites is where almost all the pattern's real production incidents originate, and a staff engineer should push back hard on the latter shape specifically, not on Singleton in general.
7. *Retrospectively, what would you tell a team that over-used classic Singleton for years, without shaming the original decisions?* — Excellent answer acknowledges the original decisions were often reasonable given the tooling/team maturity at the time (no mature DI container yet, small team, low test-isolation pain), frames the migration as a natural evolution rather than a correction of a mistake, and proposes a low-risk, incremental migration path (Part 19's Stage 4→5 transition) rather than a rewrite.

**Coding problems (solutions in `Singleton.php`):**
1. Implement a `ConfigManager` singleton that lazily loads config from a JSON file exactly once, with `__clone()`/`__wakeup()` guards and a unit test proving instance identity across multiple `getInstance()` calls.
2. Implement a Swoole-aware `DbConnectionPool` singleton that initializes eagerly at worker boot (not lazily on first request) and demonstrate, via a commented-out lazy version, exactly which race the eager approach avoids.

**Total questions delivered: 35 (7 per level × 5 levels), plus 2 coding problems.**

---

## 📎 APPENDIX

### Part 22 — Learning Roadmap & Self-Assessment

**Ranked resources:**
- *Beginner:* the GoF-pattern chapter on Singleton in any standard design-patterns reference; PHP manual pages on `__clone()`/`__wakeup()`/`__unserialize()` magic methods.
- *Intermediate:* Laravel's official Service Container documentation (specifically the `bind()` vs `singleton()` distinction) — directly verified and cited in Part 11.
- *Advanced:* Java Memory Model documentation/discussion on why pre-JDK5 double-checked locking was broken and what changed — directly verified and cited in Part 15; Go's `sync` package documentation on `sync.Once`.

**Self-Assessment — MCQs (answer key at the end):**

1. What does `getInstance()` return on the *second* call in a correctly-implemented Singleton?
   a) A new instance b) The same cached instance c) null d) An error
2. Why must `__clone()` be guarded in a PHP Singleton?
   a) Performance b) It would silently create a second instance c) PHP requires it by default d) It's not actually necessary
3. Under classic PHP-FPM, is an unguarded `getInstance()` vulnerable to a concurrency race?
   a) Yes, always b) No — each request has its own isolated process/memory c) Only on Linux d) Only with more than 2 CPU cores
4. What does Laravel's `->singleton()` container binding do differently from `->bind()`?
   a) Nothing, they're identical b) `singleton()` caches and reuses the same instance after first resolution; `bind()` builds fresh every time c) `bind()` is faster d) `singleton()` requires a private constructor
5. What was broken about double-checked locking in Java before JDK5?
   a) It didn't compile b) Instruction reordering could expose a partially-constructed object to another thread c) It was too slow d) It required a private constructor
6. What does a Singleton guarantee in a 20-pod Kubernetes deployment?
   a) Exactly one instance across all 20 pods b) Exactly one instance per pod/process — up to 20 total c) Exactly zero instances d) It depends on the ingress controller
7. What is Go's idiomatic primitive for exactly-once safe initialization?
   a) `volatile` b) `sync.Once` c) `getInstance()` d) A mutex is always required manually, no primitive exists

**Answer key:** 1-b, 2-b, 3-b, 4-b, 5-b, 6-b, 7-b.

**Scenario questions:**
1. *Your team's `Logger::getInstance()` singleton is implicated in an intermittent test-flakiness issue in CI, where tests run in parallel worker processes. Diagnose likely causes and propose a fix.* — Expected reasoning: check whether "parallel" here means separate OS processes (each gets its own Singleton instance — likely fine) or shared-process concurrency (Swoole-style test runner — race is plausible); propose either isolating test state per-process or migrating to a container-managed, test-resettable binding.
2. *A staff engineer proposes replacing every classic Singleton in a legacy codebase with DI-container bindings in one large PR. Evaluate this plan.* — Expected reasoning: flag the big-bang-rewrite risk from Part 21's senior question #2; propose an incremental, dependency-ordered migration with tests added before each refactor, rather than a single large PR.

**One refactoring exercise:** Take the Stage 3 (missing clone/wakeup guards) implementation from Part 19, add the guards, add the identity-uniqueness test from Part 18, and — if targeting Malaysia specifically — add the Swoole-aware concurrency guard from Part 9, documenting which deployment model your guard assumes.

**One architecture/debugging scenario:** A `FeatureFlagCache` Singleton is serving stale data for up to 10 minutes after flag changes across a fleet of autoscaled pods (same underlying scenario as Part 21's staff question #1). Produce a short design note: root cause, why it's a cache-invalidation problem rather than a Singleton-correctness bug, and your proposed fix (TTL refresh vs. pub/sub invalidation), with the trade-off of each option named explicitly.

---

*Companion file: `Singleton.php` — basic → clone/wakeup-guarded → Swoole-aware → real-world `ConfigManager`/`DbConnectionPool` progression, heavily commented, runnable with `php Singleton.php`.*
