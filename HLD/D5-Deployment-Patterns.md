# D5 — Deployment Patterns

> **Section:** Architecture Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Deployment patterns are strategies for releasing new code to production safely — minimizing downtime, reducing risk, and enabling rollback if something goes wrong.

**Technical:** Deployment patterns govern how new application versions are released: which traffic receives the new version, how much, and in what order. Each balances risk, cost, and release speed differently.

---

## 2. Real-World Analogy

**Blue-Green:** A hotel renovating one floor at a time. Keep floor A (old) running. Renovate floor B (new). Switch all guests to floor B. If problems: switch back instantly.

**Canary:** New drug trial — test on 5% of patients first. If safe, gradually expand. Named after canary in coal mines (early warning system).

**Rolling:** Theatre show with understudies — replace actors one at a time during the run. Show never stops.

---

## 3. Visual Diagram

```
BLUE-GREEN DEPLOYMENT:
Production traffic → [Load Balancer] → Blue (v1) [LIVE]
                                    ↗
                     Green (v2) [IDLE — pre-warmed]
Deploy v2 to Green, test it, then:
Production traffic → [Load Balancer] → Green (v2) [LIVE]
Blue now idle — instant rollback available for 30 minutes

CANARY DEPLOYMENT:
All users                → v1 (95% traffic)
                         → v2 (5% traffic — canary)
Monitor v2 error rate...
Ramp up: 5% → 20% → 50% → 100% over 1 hour
If error rate spikes at any %; route all back to v1

ROLLING DEPLOYMENT:
Server 1: v1 → v2 ✓
Server 2: v1 → v2 ✓
Server 3: v1 → v2 ✓
Server 4: v1 → v2 ✓
Only 1 server updated at a time — always some v1 servers up

FEATURE FLAG:
Same code deployed everywhere, feature controlled by config:
if (FeatureFlag::enabled('new_checkout', $userId)) {
    // new checkout flow
} else {
    // old checkout flow
}
Turn on for 1% → 10% → 100% without new deployment
```

---

## 4. Deep Technical Explanation

### Blue-Green Deployment
**Process:**
1. Two identical environments: Blue (current production) and Green (new version)
2. Deploy v2 to Green, run smoke tests
3. Switch load balancer from Blue → Green (< 1 second downtime typically)
4. Keep Blue for 30 minutes → instant rollback if issues
5. Decommission Blue

**Pros:** Zero-downtime, instant rollback, pre-production testing on production infrastructure
**Cons:** 2x infrastructure cost, DB schema changes must be backward-compatible

### Canary Deployment
**Process:**
1. Deploy v2 to a small % of instances (1-5%)
2. Route 1-5% of traffic to v2 (by header, user segment, or random)
3. Monitor error rates, latency, business metrics vs v1 baseline
4. Gradually increase %: 5% → 25% → 50% → 100%
5. If any anomaly: instant rollback (route 100% back to v1)

**Pros:** Early real-world validation, quick rollback, gradual blast radius
**Cons:** Must run two versions simultaneously (backward compat), harder to debug (which version?)

### Rolling Deployment
**Process:**
1. Update instances one at a time (or in batches)
2. Health check each new instance before moving to next
3. Old and new versions run simultaneously during rollout

**Pros:** No extra infrastructure needed
**Cons:** Slow rollback, simultaneous v1/v2 requires API compatibility

### Feature Flags
- Toggle features without deployment
- A/B testing: route users to feature A or B and measure conversion
- Kill switch: disable broken feature in production instantly
- Progressive rollout: enable for 1% → 10% → 50% users
- Tools: LaunchDarkly, Unleash, AWS AppConfig, custom Redis-based flags

---

## 5. Code Example

```php
// Feature flag implementation (Redis-backed)
class FeatureFlag {
    private static \Redis $redis;
    
    public static function enabled(string $flag, int $userId = 0): bool {
        $config = json_decode(self::$redis->get("feature_flag:{$flag}") ?? '{}', true);
        
        if (empty($config)) return false;
        
        return match($config['rollout_type']) {
            'global'  => (bool) $config['enabled'],
            'percent' => ($userId % 100) < $config['rollout_percent'],
            'users'   => in_array($userId, $config['user_ids'] ?? []),
            default   => false,
        };
    }
}

// Usage in application
class CheckoutController {
    public function checkout(Request $request): Response {
        if (FeatureFlag::enabled('new_payment_flow', $request->user()->id)) {
            return $this->newCheckout($request);
        }
        return $this->legacyCheckout($request);
    }
}

// Kubernetes rolling update
// kubectl set image deployment/app app=myapp:v2 --record
// Rollout strategy in deployment.yaml:
```
```yaml
spec:
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1        # one extra pod during rollout
      maxUnavailable: 0  # zero downtime — always full capacity
  template:
    spec:
      containers:
      - name: app
        image: myapp:v2
        readinessProbe:    # only route traffic once healthy
          httpGet:
            path: /health
          initialDelaySeconds: 10
          periodSeconds: 5
```

---

## 6. Trade-offs

| Pattern | Rollback Speed | Cost | Complexity | Zero Downtime |
|---------|---------------|------|-----------|--------------|
| Blue-Green | Instant | 2x infra | Medium | Yes |
| Canary | Fast | ~10% extra | High | Yes |
| Rolling | Slow (re-roll) | No extra | Low | Yes (with config) |
| Feature Flag | Instant | Software cost | Medium | N/A (logic only) |

---

## 7. Interview Q&A

**Q1: What deployment strategy would you use for a high-risk DB schema migration?**
> Blue-green with expand-contract pattern. (1) Expand: add new column as nullable in migration (backward compatible with old code). (2) Deploy old code reading/writing both old and new column. (3) Backfill: populate new column from old column. (4) Deploy new code using new column, falling back to old column if null. (5) Contract: remove old column once all traffic is on new code. Never do a big-bang migration where old code breaks with new schema.

**Q2: How does a canary deployment work in Kubernetes?**
> Deploy v2 as a separate Deployment with fewer replicas: v1 has 9 replicas, v2 has 1 replica → 90/10% traffic split. Both use same Service selector label (e.g., `app: my-service`). As confidence grows, scale up v2 replicas and scale down v1. Alternatively, use Istio VirtualService to route exactly 5% traffic to v2 without changing replica counts.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Blue-Green: instant rollback, 2x cost, zero downtime           │
│ ✓ Canary: gradual rollout, real-world validation, quick rollback  │
│ ✓ Rolling: no extra infra, slow rollback, requires API compat     │
│ ✓ Feature flags: decouple deployment from release                 │
│ ✓ DB migrations: expand-contract pattern (never breaking changes) │
│ ✓ Combine: canary + feature flags for safest releases             │
└────────────────────────────────────────────────────────────────────┘
```
