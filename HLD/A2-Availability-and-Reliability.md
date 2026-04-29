# A2 — Availability & Reliability

> **Section:** Foundational Concepts | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Availability means your system is up and working when users need it. Reliability means it works correctly every time.

**Technical:**
- **Availability** = `(Total Time - Downtime) / Total Time` — percentage of time the system is operational
- **Reliability** = probability that the system functions correctly for a given time period without failure
- **Durability** = data is not lost even if hardware fails (distinct from availability)

---

## 2. Real-World Analogy

**ATM Machine:**
- **Available** = the ATM is turned on and has network connectivity (even if it runs slowly)
- **Reliable** = the ATM always dispenses the correct amount and records the transaction accurately
- **Durable** = your transaction record is never lost even if the ATM reboots mid-transaction
- **Fault Tolerant** = if one ATM breaks, the bank has 3 others in the same branch

---

## 3. Visual Diagrams

### Nines of Availability
```
┌─────────────┬──────────────┬─────────────┬──────────────────┐
│ Availability│ Downtime/year│ Downtime/mo │ Downtime/week    │
├─────────────┼──────────────┼─────────────┼──────────────────┤
│   99%       │   87.6 hours │  7.3 hours  │  1.68 hours      │
│   99.9%     │   8.76 hours │  43.8 min   │  10.1 min        │
│   99.99%    │   52.6 min   │  4.4 min    │  1.01 min        │
│   99.999%   │   5.26 min   │  26.3 sec   │  6.05 sec        │
│   99.9999%  │   31.5 sec   │  2.63 sec   │  0.6 sec         │
└─────────────┴──────────────┴─────────────┴──────────────────┘
```

### Active-Active vs Active-Passive
```
ACTIVE-ACTIVE (both serve traffic):
Client ──→ Load Balancer ──→ [Region A - Primary]  ←──┐
                          └→ [Region B - Primary]  ───┘ both write
    ✓ Better utilization
    ✓ Zero-downtime failover
    ✗ Write conflicts possible

ACTIVE-PASSIVE (one standby):
Client ──→ Load Balancer ──→ [Region A - Active]   ── replicates to ──→ [Region B - Passive]
                              (serves traffic)                           (on standby)
    ✓ Simpler, no conflicts
    ✗ Failover delay (30s–5min)
    ✗ Passive region wastes resources
```

### SLA, SLO, SLI Relationship
```
SLA (Contract with Customer)
  └── SLO (Internal Target)         ← stricter than SLA
        └── SLI (Measurement)       ← the actual metric you track
              └── Alert             ← fires when SLI breaches SLO
```

---

## 4. Deep Technical Explanation

### SLA vs SLO vs SLI

| Term | Full Name | Who it's for | Example |
|------|-----------|-------------|---------|
| **SLI** | Service Level Indicator | Engineers | "99.2% of requests returned 200 in last hour" |
| **SLO** | Service Level Objective | Engineering teams | "99.9% success rate target" |
| **SLA** | Service Level Agreement | Customers/legal | "We guarantee 99.5% or credit refund" |

**Rule:** SLA ≤ SLO ≤ actual SLI
If SLO = 99.9%, set SLA = 99.5% to have buffer for incidents.

**Error Budget:**
- If SLO = 99.9%, error budget = 0.1% of time = 8.76 hours/year
- When error budget is exhausted: freeze new deployments, focus on reliability
- Google SRE practice: split error budget between reliability work and new features

### MTTR and MTBF
- **MTBF (Mean Time Between Failures):** Average time between failures. Higher = more reliable.
  - MTBF = Total uptime / Number of failures
- **MTTR (Mean Time To Recover):** Average time to restore after a failure. Lower = better.
  - MTTR = Total downtime / Number of failures
- **Availability formula:** `Availability = MTBF / (MTBF + MTTR)`

Example: MTBF = 100h, MTTR = 1h → Availability = 100/101 = 99.0%

### Improving Availability
1. **Reduce MTTR:** Better monitoring, runbooks, on-call alerting, automated recovery
2. **Increase MTBF:** Better testing, canary deploys, circuit breakers, chaos engineering
3. **Add redundancy:** Multiple instances, regions, availability zones

### Chaos Engineering
**What:** Intentionally inject failures to discover weaknesses before they cause real outages.
**Why:** "Hope is not a strategy" — you only know your system handles failures when you've tested it.

Process:
1. Define steady state (baseline metrics: latency, error rate)
2. Hypothesize: "Killing one DB replica won't affect p99 latency"
3. Inject failure: kill the replica
4. Observe: did metrics degrade?
5. Fix weaknesses found

**Tools:** Netflix Chaos Monkey, AWS Fault Injection Simulator, Gremlin

---

## 5. Code Example

```php
// Health check endpoint — used by load balancer + monitoring
Route::get('/health/live', function() {
    // Liveness: is the app process alive?
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/health/ready', function() {
    // Readiness: can app serve traffic? (check dependencies)
    $checks = [];
    
    // Check DB connection
    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (Exception $e) {
        $checks['database'] = 'error';
    }
    
    // Check Redis
    try {
        Cache::store('redis')->get('health_check');
        $checks['cache'] = 'ok';
    } catch (Exception $e) {
        $checks['cache'] = 'error';
    }
    
    $allOk = !in_array('error', $checks);
    return response()->json([
        'status' => $allOk ? 'ready' : 'not_ready',
        'checks' => $checks,
    ], $allOk ? 200 : 503);
});
```

```php
// Error budget tracking (simplified)
class ErrorBudgetTracker {
    // SLO: 99.9% success rate
    const SLO_TARGET = 0.999;
    
    public function getRemainingBudget(Carbon $windowStart): array {
        $totalRequests = $this->getTotalRequests($windowStart);
        $errors        = $this->getErrorCount($windowStart);
        $successRate   = ($totalRequests - $errors) / $totalRequests;
        
        $budgetTotal   = (1 - self::SLO_TARGET) * $totalRequests;
        $budgetUsed    = $errors;
        $budgetLeft    = $budgetTotal - $budgetUsed;
        
        return [
            'slo_target'    => self::SLO_TARGET * 100 . '%',
            'current_rate'  => round($successRate * 100, 3) . '%',
            'budget_used'   => round(($budgetUsed / $budgetTotal) * 100, 1) . '%',
            'can_deploy'    => $budgetLeft > 0,
        ];
    }
}
```

---

## 6. Trade-offs

| Strategy | Pro | Con |
|----------|-----|-----|
| **Active-Active** | Zero failover time, full resource utilization | Write conflict complexity |
| **Active-Passive** | Simpler, no conflicts | Failover delay, wasted passive resources |
| **Multi-AZ** | Protects against datacenter failure | 2x cost |
| **Multi-Region** | Protects against regional outages | 10x complexity, replication lag |
| **Higher SLO** | More reliability | Exponentially more expensive engineering effort |

---

## 7. Interview Q&A

**Q1: What's the difference between availability and reliability?**
> Availability is about uptime percentage — is the system accessible? Reliability is about correctness — does it do the right thing when it is accessible? A system can be available (responding to requests) but unreliable (returning wrong data). You need both: high availability + high reliability.

**Q2: How do you design for 99.99% availability?**
> Eliminate all single points of failure: redundant load balancers, multiple app server instances across AZs, database primary/replica with automatic failover (Aurora), Redis Sentinel or Cluster, and S3 for object storage (11 nines durability). Automate failure detection and recovery. Monitor with SLI dashboards and alert before SLO breach.

**Q3: What is an error budget and how do teams use it?**
> Error budget = 1 - SLO target. If SLO = 99.9%, error budget = 0.1% (8.76 hours/year). Teams track how much of this budget has been consumed. When budget is healthy, teams deploy freely. When budget is near exhaustion (>80% used), freeze new deployments and focus purely on reliability. It creates a data-driven conversation between product and engineering.

**Q4: What is the difference between SLA, SLO, and SLI?**
> SLI is the metric you measure (e.g., success rate). SLO is your internal target for that metric (99.9%). SLA is the contractual promise to customers, set slightly looser than SLO to have buffer. If you breach SLA, you owe customers a credit or refund. The gap between SLO and SLA is your "firefighting buffer."

---

## 8. Key Takeaways

```
┌───────────────────────────────────────────────────────────────────┐
│ ✓ 99.9% = 8.76h/year downtime; 99.99% = 52 min/year             │
│ ✓ SLI (metric) → SLO (target) → SLA (contract)                  │
│ ✓ Availability = MTBF / (MTBF + MTTR)                            │
│ ✓ Error budget = 1 - SLO; governs deployment velocity            │
│ ✓ Chaos Engineering: test failures before they test you          │
│ ✓ Active-Active = better utilization; Active-Passive = simpler   │
└───────────────────────────────────────────────────────────────────┘
```
