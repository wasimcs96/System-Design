# C10 — Vector Clocks & Conflict Resolution

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** When two people edit the same document on different computers simultaneously (offline), how do we know whose edit happened "first" or if they conflict? Vector clocks track causality — they tell us "A happened before B" or "A and B happened concurrently (conflict!)".

**Technical:** A vector clock is a data structure (array of logical timestamps, one per node) that captures causality between events in distributed systems. If vector clock A ≤ vector clock B for all components, A happened before B (or is the same). If neither is ≤ the other, the events are concurrent — a potential conflict.

---

## 2. Real-World Analogy

**Google Docs vs local editing:**
- Alice edits paragraph 1 offline on her laptop (counter: Alice=1)
- Bob edits paragraph 1 offline on his phone (counter: Bob=1)
- They reconnect — both edited the same paragraph
- Vector clocks show: Alice=1,Bob=0 and Alice=0,Bob=1 → CONCURRENT → conflict detected!
- System can now prompt user or merge automatically

---

## 3. Visual Diagram

```
VECTOR CLOCK CAUSALITY:

Node A: [A=1,B=0,C=0] → [A=2,B=0,C=0] → [A=3,B=2,C=0]
                              ↓ send msg            ↑ receive msg from B
Node B:              [A=1,B=1,C=0] → [A=1,B=2,C=0] →sends to A

A's event [A=3,B=2]: A received B's message [A=1,B=2] → merged clocks

CONCURRENT EVENTS (conflict):
Node A: [A=1,B=0] → edit "color=red"
Node B: [A=0,B=1] → edit "color=blue"

Neither [A=1,B=0] ≤ [A=0,B=1] nor [A=0,B=1] ≤ [A=1,B=0]
→ CONCURRENT → CONFLICT!

CONFLICT RESOLUTION OPTIONS:
1. Last Write Wins (LWW): use physical timestamp → simpler, loses data
2. Multi-value (Siblings): keep both values, let application/user resolve
3. CRDT merge: mathematically merge both values
```

---

## 4. Deep Technical Explanation

### Rules
- When node A performs an event: A increments own counter (A[A] += 1)
- When A sends message to B: includes own vector clock
- When B receives message: B merges (take max of each position) + increment own counter

### Causality Detection
- A → B (A before B): ∀i, VC_A[i] ≤ VC_B[i] AND ∃i, VC_A[i] < VC_B[i]
- Concurrent (conflict): neither A → B nor B → A

### DynamoDB's Version Vectors
DynamoDB uses version vectors (simplified vector clocks) per replica:
- Each update adds a tag: `[node_id, counter]`
- On conflict: returns "siblings" — both versions shown to application
- Application code or Last Write Wins resolves

### Last Write Wins (LWW)
Simple conflict resolution: write with highest Lamport timestamp wins.
- ✓ Simple
- ✗ Concurrent writes with similar timestamps → data loss
- ✗ Requires synchronized clocks (NTP drift ≈ ±100ms)
- Used by: Cassandra (tunable), DynamoDB (optional)

### CRDTs (Conflict-free Replicated Data Types)
Mathematical data structures that merge automatically without conflict:
- **G-Counter:** Grow-only counter, merge = max per node
- **PN-Counter:** Increment/decrement, two G-counters
- **LWW-Register:** Last-write-wins using logical timestamp
- **OR-Set:** Add/remove set with tombstones
- Used by: Redis (some data types), Riak, collaborative editors

---

## 5. Code Example

```php
class VectorClock {
    private array  $clock;
    private string $nodeId;
    
    public function tick(): void {
        $this->clock[$this->nodeId] = ($this->clock[$this->nodeId] ?? 0) + 1;
    }
    
    public function merge(self $other): self {
        $merged = clone $this;
        foreach ($other->clock as $node => $time) {
            $merged->clock[$node] = max($merged->clock[$node] ?? 0, $time);
        }
        $merged->tick();
        return $merged;
    }
    
    public function happensBefore(self $other): bool {
        // This clock happened before $other if all of this's values <= other's
        $lessThan = false;
        foreach ($this->clock as $node => $time) {
            $otherTime = $other->clock[$node] ?? 0;
            if ($time > $otherTime) return false;
            if ($time < $otherTime) $lessThan = true;
        }
        return $lessThan;
    }
    
    public function isConcurrentWith(self $other): bool {
        return !$this->happensBefore($other) && !$other->happensBefore($this);
    }
}
```

---

## 7. Interview Q&A

**Q1: Why can't you use wall-clock timestamps for distributed event ordering?**
> Machine clocks drift — two servers may disagree on the time by 100ms or more. Events that feel "simultaneous" may get different timestamps. Under NTP sync, clocks can even go backward (drift correction). This means using wall-clock timestamps to determine which write happened "first" is unreliable. Vector clocks use logical time (counters), not wall-clock time, and track causality accurately regardless of clock drift.

**Q2: How does Amazon DynamoDB handle write conflicts?**
> DynamoDB uses "eventually consistent" writes. In case of concurrent writes to the same item from different regions (Global Tables), it uses Last Write Wins based on Dynamo's internal version vectors. The write with the higher version wins. For applications that can't tolerate data loss on conflicts, use conditional writes: `ConditionExpression: attribute_not_exists(id)` or version checking with `version = expected_version`. This implements optimistic concurrency control.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Vector clocks track causality: A → B means A happened before B  │
│ ✓ Concurrent events (neither before the other) = potential conflict│
│ ✓ LWW = simple but risks data loss on concurrent writes           │
│ ✓ CRDTs = mathematically merge concurrent updates without conflict │
│ ✓ DynamoDB uses version vectors + LWW for multi-region conflicts  │
│ ✓ Wall-clock timestamps unreliable due to NTP drift               │
└────────────────────────────────────────────────────────────────────┘
```
