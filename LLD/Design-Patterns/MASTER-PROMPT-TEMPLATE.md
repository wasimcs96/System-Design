# Design Pattern Prompt Library

A Document Generator plus three prompts that operate *on* an already-generated doc — Reviewer, Enhancer, Question Generator — instead of one prompt regenerating everything from scratch every time. Chain them; don't just run the Generator forever and call the result finished.

**The governing principle:** interview prep ROI comes from retrieval practice under time pressure, not re-reading prose. **Depth is earned by evidence of a gap, not applied uniformly** — that's why the Reviewer/Enhancer split exists instead of one ever-larger Generator prompt.

---

## 1. Document Generator

Act as a **Senior Staff Engineer, Engineering Manager, and Interview Coach** — someone who has both interviewed hundreds of Senior/Staff/Principal candidates at companies like Amazon, Google, Microsoft, Uber, Atlassian, Rippling, Careem, Talabat, Property Finder, Razorpay, and Goldman Sachs, *and* personally coached candidates into offers at that bar. Both lenses matter: the interviewer's judgment about what actually gets probed, and the coach's judgment about what's worth an engineer's limited prep time.

I am preparing for Senior/Staff/Tech Lead-level interviews at high-bar product companies across five target markets: **Saudi Arabia, Dubai/UAE, Malaysia, India Tier-2 (Paytm/Swiggy/Razorpay/PhonePe-tier), and India Tier-1/60LPA+ (Amazon India, Google, Flipkart, Atlassian, Rippling, Goldman Sachs-tier)**. My practice language is **PHP 8.3/Laravel**, but the interviews themselves are frequently language-agnostic or run in Java/Python/Go — the handbook must make clear which parts of the reasoning are pattern-level (portable) versus PHP-specific (practice-only).

Produce the most useful possible interview-prep handbook for the **{{PATTERN_NAME}}** design pattern — not the most exhaustive one. If a section would cost more prep-time than it returns in interview signal, cut it, shrink it, or fold it into something else, and say so. **Do not pad for the sake of matching a previous pattern's page count.** A pattern with genuinely thin real-world applicability (e.g., Prototype, Flyweight) should produce a shorter, honest document; a pattern that's asked everywhere (Strategy, Factory, Observer, Singleton) earns the length.

### Rules
- Optimize for **retrieval practice**, not completeness for its own sake. Every part earns its place by being something a candidate would actually be asked, tested on, or need to recall under time pressure — not by rounding out a taxonomy.
- **Depth is earned by evidence of a gap, not applied uniformly.** Don't give every section five difficulty-tier explanations "just in case" — stratify by level only where the content itself genuinely differs by level (the Interview Bank; nowhere else by default).
- Explain WHY, not just WHAT. Every claim needs an engineering reason.
- **State explicitly which parts are pattern-level (portable across languages) versus PHP-specific.** Use PHP 8.3 (PSR-12) as the practice language; include a short mapping table to Java/Python/Go/TypeScript equivalents for the pattern's core mechanism (Part 9) rather than assuming the interview will be in PHP.
- Ground examples in FinTech, SaaS, and e-commerce/marketplace domains (not Shape/Animal/Car toy examples).
- Use tables for every comparison. Use diagrams (ASCII/Mermaid) only where a candidate could plausibly be asked to draw it on a whiteboard — not one of every diagram type by default.
- Name the trade-off in every recommendation — nothing is free.
- **Cross-link every market claim to the frequency-guide data already researched** (`design-patterns-frequency-guide-expanded.md`) rather than making a fresh, disconnected claim about "how this pattern is asked."
- **If you claim a real framework's source code does something specific (Laravel, Symfony, PHP internals, Spring Boot), verify it against the actual current source before stating it as fact** — fetch it. Framework internals change across versions and don't always work the way common wisdom assumes (Laravel's `replicate()`, for example, does not use `clone`/`__clone()` at all — it rebuilds via `new static()` and manually copies filtered attributes; this is exactly the kind of detail that's wrong if recalled from memory instead of checked). If verification genuinely isn't possible in a given pass, say "plausible mechanism, not verified against current source" rather than asserting it.
- Write all runnable code in a **separate standalone `.php` file** (basic → good → real-world progression, heavily commented, runnable with `php file.php`, no framework dependency required). The Markdown/PDF stays theory-only and references the code file by name instead of inlining full listings.
- End every Deep Dive part with a **2-question "before you move on" recall box** — two quick retrieval prompts answered from memory before continuing, not a full quiz. The comprehensive self-assessment lives in the Appendix.
- If a section is genuinely not applicable or low-value for this specific pattern, say so in one line and move on — don't silently skip it, and don't force content into it either.

### Document Structure

#### ⚡ FAST TRACK (Parts 1–6) — revisited every time before an interview

**Part 1 — 60-Second Recall Card:** the entire pattern on one page — one-liner, core mechanism, trigger phrase, anti-trigger, closest-confused patterns, one memory hook. First thing in the document, full stop.

**Part 2 — Market Calibration:** pulled directly from `design-patterns-frequency-guide-expanded.md` — this pattern's measured frequency and role across all five target markets, named companies where it's confirmed, and an honest statement of whether this pattern is a headline-round topic or a follow-up-inside-another-topic per market.

**Part 3 — Recognition, Decision Tree & When NOT to Use:** requirement phrases and code smells that signal this pattern, a compact yes/no decision tree, explicit anti-triggers.

**Part 4 — Cheat Sheet & Multi-Length Pitch:** one-page cheat sheet table, plus the pattern explained at 30 seconds / 1 minute / 3 minutes / 10 minutes.

**Part 5 — Timed Mock Drill:** one realistic 45–60 minute simulated LLD round (prompt, time-boxed sub-steps, what a bar-raiser-caliber interviewer actually scores against) plus a self-grading rubric.

**Part 6 — Pattern Recognition Drill:** short, realistic scenarios (scale the count to this pattern's actual footprint per the frequency guide — a handful for a Low-frequency pattern, more for a Very High one; never pad to a fixed number) where the candidate must name the pattern, justify it, and explicitly say why the 2–3 next-most-plausible patterns *don't* fit as well. This is discriminative practice — the actual skill LLD interviews test — not recall of a definition.

#### 📘 DEEP DIVE (Parts 7–21) — read once, then reference

*Open this section with a one-line path map showing how these parts build on each other, e.g. `Fundamentals → Problem → Internals → Design → Implementation → Production → Trade-offs → Bugs → Interview Bank`.*

**Part 7 — Fundamentals:** definition, GoF category, the problem it solves, beginner framing, then senior/staff framing of the same thing. Two altitudes here, not five.

**Part 8 — The Engineering Problem & Refactoring Trigger:** what code looks like before this pattern, why it breaks down at scale, the code smell that should make an engineer reach for it. Frame this with production-mindset questions: what production problem actually forced engineers toward this pattern, how would a senior engineer discover the requirement before it became a crisis, what metric would have shown it coming, what alternatives would a competent engineer consider and reject first. (Full refactoring code sequence lives in Part 19, not duplicated here.)

**Part 9 — Internal Working (trimmed, language-agnostic first):** object lifecycle and memory behavior explained at the concept level first — PHP-specific mechanics included only to the depth needed to explain THIS pattern's core gotcha. If this pattern has no meaningful internals story, say so in two sentences and move to Part 10.

**Part 10 — Components, UML & Language Mapping:** class/role responsibilities, one class diagram and (only if it adds real information) one sequence diagram, plus a short table mapping the pattern's core mechanism across PHP, Java, Python, Go, and TypeScript/Node.

**Part 11 — Implementation Overview (PHP/Laravel/Node):** walks through the companion `.php` file's design decisions, plus where this pattern genuinely does or doesn't show up in real framework internals — verified against actual source per the rule above, not recalled from memory. Include the closest Java/Spring Boot equivalent where useful for readers interviewing in Java.

**Part 12 — Where This Shows Up in Production:** merged enterprise-scenario narratives (2–3, Amazon/Uber/Careem/Razorpay-style) with a compact microservices-usage table, plus one worked **Architecture Decision Record (ADR)** for a realistic decision to adopt this pattern — Context, Decision, Consequences, Alternatives Considered, Trade-offs — as both a teaching device and a reusable template for the reader's actual job.

**Part 13 — Field Notes (Simulated Production Experience):** a first-person staff-engineer account for rehearsal purposes. **Explicit caveat required in the generated text itself:** this is a rehearsal scaffold, not a script — personalize it with real project details before using it as an interview answer, or frame it plainly as illustrative rather than claim it as personal history.

**Part 14 — Analogies & Architecture Fit:** 3–5 real-world analogies that actually clarify this pattern, plus architecture-style fit (Clean/Hexagonal/DDD/event-driven/CQRS/cloud-native) scoped to only the styles genuinely relevant — explicitly skip the rest with one line.

**Part 15 — SOLID, Performance & Concurrency:** how the pattern supports/strains applicable SOLID principles (skip irrelevant ones, say so); time/space reasoning (labeled as reasoned estimates, never fabricated benchmarks); concurrency framed correctly for PHP-FPM's per-request model *and* long-running Node.js/Swoole/queue-worker contexts where the distinction matters.

**Part 16 — Advantages, Disadvantages & Trade-offs:** one merged, unpadded section organized by dimension (scalability, maintainability, readability, complexity, security, performance, testing).

**Part 17 — Pattern Comparisons:** comparison tables against the 3–5 patterns most commonly confused with this one, plus one decision table.

**Part 18 — Production Bugs, AI-Generated Code Review & Testing:** realistic bug categories this pattern causes when misused and how to debug each; how AI coding assistants typically misapply this pattern and what a reviewer should specifically check before merging AI-generated code using it; the testing strategy that would catch both; the code-review checklist that prevents recurrence. One continuous story, not four disconnected sections.

**Part 19 — Refactoring Journey:** terrible → bad → average → correctly-applied → production-ready, narrated with reasoning at each stage (full code lives in the `.php` file). Note in passing, without forcing a full separate example per level, roughly which career stage each transition typically corresponds to.

**Part 20 — Practices, Mistakes & Traps:** one section, organized by experience level (junior/mid/senior mistakes), closing with the specific interview follow-up questions that catch memorized-but-shallow understanding.

**Part 21 — Interview Question Bank & Coding Problems:** curated, high-signal questions across Beginner → Principal (roughly 6–10 per level, quality over a fixed quota, each with why-asked/wrong/good/excellent/follow-up — this is the one place five-tier stratification is warranted, because the question content genuinely changes by level), plus 2–3 original coding problems solvable with this pattern (solutions in the `.php` file). State the total question count actually delivered.

#### 📎 APPENDIX (Part 22)

**Part 22 — Learning Roadmap & Self-Assessment:** ranked beginner→advanced resources (only ones that plausibly exist), plus the comprehensive MCQs, scenario questions, one refactoring exercise, and one architecture/debugging scenario with an answer key — the full-length checkpoint; the per-part boxes in the Deep Dive are the lightweight version.

### Final Instruction
Generate a professional, interview-day-usable handbook — not a beginner blog post, and not a reference manual optimized for completeness over usefulness. Deliver as: one Markdown + PDF (this structure, Fast Track before Deep Dive) and one standalone `.php` file (all code, heavily commented, runnable). Save both into `<Category> Design Patterns/{{PATTERN_NAME}}/` (e.g. `Creational Design Patterns/Prototype/`, `Behavioral Design Patterns/Strategy/`) — match the pattern to its GoF category folder. If this pattern's real-world/interview footprint is thin, the resulting document should honestly be shorter than a high-frequency pattern's — do not pad to match.

---

## 2. Document Reviewer

Use this **after** the Generator has produced a pattern doc, as a genuinely separate pass — ideally with no memory of writing the original (a fresh subagent/session is better than the same context reviewing its own work, for the same reason a second engineer's code review catches more than self-review).

Act as a **skeptical Staff-level technical editor** reviewing a design-pattern interview-prep handbook you did not write. Your job is to find real gaps and real bloat — not to praise it, and not to rewrite it yourself.

Read `{{PATTERN_NAME}}/{{PATTERN_NAME}}-Design-Pattern-Guide.md` in full, then:

1. **Score it 1–10 on each of:** interview-day usability (can a candidate actually cram from this the night before), accuracy (any claim that looks fabricated, unverified-but-stated-as-fact, or inconsistent with `design-patterns-frequency-guide-expanded.md`), and signal density (how much of the content would actually change a real interview outcome vs. exists to look thorough).
2. **List concrete gaps** — missing scenarios in the Pattern Recognition Drill, thin follow-up coverage in the Interview Bank, a Field Notes section too generic to survive a real follow-up question, a framework-source claim that reads as unverified, a market-calibration claim not traceable to the frequency guide.
3. **List concrete bloat** — any part that restates another part, any section whose removal would cost nothing in interview value, any place the "don't pad" rule was violated.
4. **Do not rewrite anything.** Output a structured gap report only: `Section → Gap or bloat → Why it matters → Suggested fix (one line)`. This report is the Enhancer's input.

---

## 3. Document Enhancer

Use this **after** the Reviewer has produced a gap report. Do not regenerate the whole document — that defeats the purpose of splitting these roles.

You are given the existing `{{PATTERN_NAME}}` handbook and a Reviewer gap report. Your job is **surgical, not uniform**: fix exactly what the report identifies, at the depth the report justifies, and leave everything else untouched.

Rules:
- For every gap the report lists, make the specific fix — don't use it as license to also deepen unrelated sections "while you're in there."
- For every bloat item the report lists, cut or merge it — confirm the cut doesn't remove information that exists nowhere else in the doc first.
- If the report flags an unverified framework-source claim, actually fetch and verify the real source before restating the claim, or explicitly soften it to "plausible, not verified."
- Do not increase total document length unless a specific, named gap requires it. Net length should track net information added, not grow by default.
- Output a change log at the end: `Section → What changed → Which report item it addresses.`

---

## 4. Question Generator

Use this standalone, independent of a full Generator/Reviewer/Enhancer pass — for refreshing just the practice material (useful for spaced-repetition-style revisits months later, without touching theory that's already solid).

Given an existing `{{PATTERN_NAME}}` handbook's Part 7 (Fundamentals) through Part 20 (Practices & Mistakes) as source material, generate a **fresh, non-duplicate** set of:

- 6–10 new Interview Question Bank entries per level (Beginner → Principal), each with why-asked/wrong/good/excellent/follow-up, that do not repeat questions already in Part 21 of the existing doc.
- 3–5 new Pattern Recognition Drill scenarios not already in Part 6, keeping the same "name it, justify it, explain why not the alternatives" structure.
- One new timed coding problem, with solution and reasoning walkthrough, distinct from the ones already in the companion `.php` file.
- 5 MCQs and 2 scenario questions for a fresh Self-Assessment pass, with an answer key.

Output only the new material, clearly marked as an addendum (`{{PATTERN_NAME}}-Question-Refresh-{{DATE}}.md`) rather than merged into the main handbook — this keeps the core doc stable while giving you new retrieval-practice material on a cadence.

---

## 5. Bilingual Source-Study Document Generator

Use this for a **different job than the Generator above**: not an original interview-prep handbook, but a faithful bilingual (English + Hindi) study companion built from one specific external source URL about **{{PATTERN_NAME}}** — e.g. refactoring.guru, algomaster.io, or any other pattern-reference site the reader wants translated and internalized section-by-section.

Act as a **bilingual technical translator and editor** who preserves a source's structure and reasoning exactly while making it usable for a Hindi-English reader studying for interviews.

### Rules
- **Fetch and read the full source page(s) first.** Follow the source's own section order start to finish — do not reorder, summarize away, or skip explanations, examples, or technical details.
- **Every section: English first, Hindi immediately below it.** Paragraph-by-paragraph or block-by-block, not English-then-one-big-Hindi-summary at the end. Maintain this pairing for the entire document, including captions, quotes, and step lists.
- **Write code in the requested language, inline in the same file, AND also deliver it as a standalone runnable companion file.** Inline: the document is meant to be read start to finish as one bilingual artifact, so every code example appears in place, in the source's own order, immediately after the explanation it belongs to. Standalone: additionally collect every one of those same code blocks, in the same order, into one runnable companion file (see naming convention below) — this mirrors the main Generator's handbook-plus-`.php`-file convention, just scoped to one source-study instead of the whole pattern. If two code blocks from the source-study would collide when combined into one file (e.g. two successive versions of a class with the same name, shown one at a time in the document), wrap sections in namespaces or rename the earlier version with a suffix (`...ShallowDemo`, `...V1`, etc.) rather than dropping either version — the standalone file should still let a reader run and compare every stage the document walks through.
- **Never reproduce a source's text or code verbatim or near-verbatim, regardless of whether it sits behind a paywall.** Every website's exact wording is copyrighted, "free to read" or not — a "Get Premium" gate makes a source's *material* status more obviously restricted, but the absence of one is not a license to quote closely. In all cases: follow the source's section order and topic structure, but write every English explanation fresh in your own words, and write your own original code examples that teach the same underlying concepts and worked scenarios. State this openly in a note at the top of the document, naming the source and confirming the content below is original wording following its structure — not quoted from it.
- **Validate every code block before delivering** — parse-check it (e.g. via a language-appropriate parser/linter) so nothing shipped is syntactically broken.
- **End the document with two separate glossaries, not one:**
  1. **Technical Words Glossary** — domain/CS terms used in the document (e.g. *encapsulation*, *shallow copy*, *registry*, *polymorphism*) — English term, Hindi translation, one short example each.
  2. **General Words Glossary** — everyday English vocabulary used in the prose that a Hindi-primary reader may not know, separate from CS jargon (e.g. *rid*, *tweaked*, *consistent*, *cuts down*, *instinct*, *scattered*, *brittle*, *awkward*, *bloated*) — same format: English word, Hindi translation, one short example sentence showing natural usage (not necessarily a technical example). Pull these from words actually used in the document's own English prose, not a generic vocabulary list — the goal is that every unfamiliar word the reader hits while reading gets defined somewhere in the document.
- Both glossaries use the same table format: `| English Term | Hindi Translation | Example |`.

### Output & Folder Convention
Deliver as one Markdown + PDF pair (Hindi renders correctly only with a Devanagari-capable font — e.g. Noto Sans Devanagari loaded via `@font-face`/woff2 in the PDF build step, since standard PDF fonts like DejaVu Sans have no Devanagari glyphs).

Save under the pattern's existing folder, one subfolder per source — **every** source gets its own named subfolder, with no exception for the first/primary one:
- `<Category> Design Patterns/{{PATTERN_NAME}}/{{SOURCE_NAME}}/` (e.g. `Creational Design Patterns/Prototype/RefactoringGuru/` for the refactoring.guru version, `Creational Design Patterns/Prototype/AlgoMaster/` for the algomaster.io version — both siblings, neither one in the pattern root).
- `{{SOURCE_NAME}}` is the source site's name in PascalCase/no-spaces form (e.g. `RefactoringGuru`, `AlgoMaster`, `GeeksforGeeks`).
- Three files live inside that subfolder: `{{PATTERN_NAME}}-{{SOURCE_NAME}}-Bilingual-Study.md`, the matching `.pdf`, and a standalone companion code file.
- **Companion code file naming:** `{{source_key}}_{{pattern_key}}.php`, all lowercase, snake_case, no capitals — `{{source_key}}` is a short lowercase handle for the source (`refactor` for RefactoringGuru, `algomaster` for AlgoMaster, etc.) and `{{pattern_key}}` is the pattern name lowercased (`prototype`, `singleton`, `factory-method`, ...). Examples already in use: `refactor_prototype.php` / `algomaster_prototype.php` in `Prototype/RefactoringGuru/` and `Prototype/AlgoMaster/`; `refactor_singleton.php` / `algomaster_singleton.php` in `Singleton/RefactoringGuru/` and `Singleton/AlgoMaster/`. Apply the same `{{source_key}}_{{pattern_key}}.php` shape for every new source added later (e.g. a GeeksforGeeks study would use `gfg_{{pattern_key}}.php`).
- The pattern's root folder (`{{PATTERN_NAME}}/`) is reserved for the main interview-prep handbook (§1) and its companion code file only — never for a source-study, so the two document types stay visually separated in a folder listing.

This keeps multiple source-studies of the same pattern side by side without overwriting each other, and keeps them clearly separated from the main interview-prep handbook produced by the Document Generator (§1).

---

## Suggested Workflow

```
New pattern:            Generator → (optional) Reviewer → Enhancer   [full pipeline]
Doc feels stale:        Reviewer → Enhancer                            [audit + fix only]
Need more drill:        Question Generator                              [practice refresh, no theory touched]
New source to study:    Bilingual Source-Study Document Generator       [translate one external source, own subfolder]
```

Run the Reviewer as a fresh subagent/session whenever possible — the whole value of the role is independence from the Generator's blind spots, which a same-context self-review won't reliably catch.

---

## Folder Convention

Every pattern's deliverables (`.md`, `.pdf`, `.php`) live under its GoF category, one folder per pattern. Bilingual source-studies (§5) live inside that same pattern folder, one subfolder per external source:

```
Creational Design Patterns/
  Singleton/
  Factory/
  Prototype/
    Prototype-Design-Pattern-Guide.md/.pdf      <- main handbook (Generator, §1)
    Prototype.php                                <- companion code file
    RefactoringGuru/
      Prototype-RefactoringGuru-Bilingual-Study.md/.pdf   <- source-study, own subfolder (§5)
      refactor_prototype.php                              <- source-study's companion code file (§5)
    AlgoMaster/
      Prototype-AlgoMaster-Bilingual-Study.md/.pdf         <- source-study, own subfolder (§5)
      algomaster_prototype.php                             <- source-study's companion code file (§5)
  Builder/
  Abstract-Factory/
Structural Design Patterns/
  Adapter/
  Decorator/
  ...
Behavioral Design Patterns/
  Strategy/
  Observer/
  ...
```

This file (`MASTER-PROMPT-TEMPLATE.md`) and `design-patterns-frequency-guide-expanded.md` stay at the repo root — shared infrastructure, not pattern-specific.
