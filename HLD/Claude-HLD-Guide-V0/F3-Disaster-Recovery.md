# F3 — Disaster Recovery

> **Section:** Observability and Security | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Disaster recovery is your plan for when things go catastrophically wrong — data center fire, accidental database deletion, ransomware. How do you restore service? How much data do you lose? How quickly can you recover?

**Technical:** Disaster Recovery (DR) defines: **RPO** (Recovery Point Objective — how much data loss is acceptable), **RTO** (Recovery Time Objective — how fast must service be restored), backup strategies, multi-region failover architecture, and chaos engineering (proactively testing failure scenarios).

---

## 2. Real-World Analogy

**Fire insurance vs. fire prevention:**
- Backup = having fire insurance — paid out when disaster strikes
- Chaos engineering = fire drills — test your disaster response before a real fire
- Multi-region = having a second office ready — business continues even if main office burns

---

## 3. Visual Diagram

```
RPO vs RTO:

Timeline:
[Last backup]----[Disaster]----[Recovery complete]
       |<-- RPO -->|              |<-- RTO -->|
    data loss window          downtime window

RPO = 1 hour: you can lose up to 1 hour of data
RTO = 4 hours: you must restore within 4 hours

STRATEGIES (cost vs. RTO/RPO):

BACKUP AND RESTORE (cheapest, slowest):
  RPO: hours, RTO: hours
  Backup nightly to S3 -> restore from backup when disaster strikes
  Use for: non-critical data, cold data, compliance archives

PILOT LIGHT (medium cost):
  RPO: minutes, RTO: minutes
  Minimal live DR environment (just core DB replication)
  Scale up DR region resources when needed
  Use for: moderate criticality systems

WARM STANDBY (expensive):
  RPO: seconds, RTO: seconds to minutes
  Full copy of system running at reduced capacity in DR region
  Scaled up on failover
  Use for: critical business systems

MULTI-SITE ACTIVE-ACTIVE (most expensive):
  RPO: near-zero, RTO: near-zero
  Full capacity in multiple regions, load balanced
  Instant failover (route traffic away from failed region)
  Use for: SLA-critical, financial, healthcare systems

COST:       Backup < Pilot Light < Warm Standby < Active-Active
PROTECTION: Backup < Pilot Light < Warm Standby < Active-Active
```

---

## 4. Deep Technical Explanation

### RTO and RPO Targets by Business Type
| Business | RPO | RTO | Strategy |
|---------|-----|-----|---------|
| E-commerce (orders) | 1 min | 5 min | Warm standby |
| Banking transactions | 0s | 0s | Active-active multi-region |
| Blog/CMS | 24 hours | 4 hours | Backup and restore |
| Healthcare records | 15 min | 1 hour | Pilot light |

### Backup Strategies
- **Full backup:** copy everything (large, slow, easy restore)
- **Incremental backup:** only changes since last backup (small, fast, complex restore — must apply all incrementals)
- **Differential backup:** changes since last FULL backup (medium, medium restore)
- **Continuous backup (WAL archiving):** PostgreSQL WAL shipped to S3 every few seconds → point-in-time recovery to any second
- **Test your backups!** A backup you've never tested is not a backup.

### Multi-Region Architecture
- **Active-Passive:** Primary region handles all traffic; DR region is idle standby with data replica
  - Failover: manual or automated DNS switch
  - Problem: read latency for remote users
- **Active-Active:** Both regions handle traffic; data replicated bidirectionally
  - Problem: write conflicts, higher cost, complex data consistency

### Chaos Engineering
- Deliberately inject failures in production to test resilience
- Netflix Chaos Monkey: randomly terminates EC2 instances in production
- **Game Days:** planned exercises where teams simulate major incidents
- Tools: AWS Fault Injection Simulator, Gremlin, Chaos Monkey

---

## 5. Code Example

```php
// Automated failover health check and DNS switching
class DisasterRecoveryManager {
    private const PRIMARY_REGION = 'ap-south-1';
    private const DR_REGION      = 'ap-southeast-1';
    
    public function checkAndFailover(): void {
        $primaryHealthy = $this->checkRegionHealth(self::PRIMARY_REGION);
        
        if (!$primaryHealthy) {
            $this->notifyOps("PRIMARY REGION UNHEALTHY -- initiating DR failover");
            $this->performFailover();
        }
    }
    
    private function checkRegionHealth(string $region): bool {
        // Check multiple health indicators
        $checks = [
            $this->checkDatabaseConnectivity($region),
            $this->checkApiEndpointHealth($region),
            $this->checkQueueDepth($region),
        ];
        
        $failedChecks = array_filter($checks, fn($c) => !$c);
        return count($failedChecks) === 0;
    }
    
    private function performFailover(): void {
        // 1. Promote DR replica to primary
        $this->promoteDatabaseReplica(self::DR_REGION);
        
        // 2. Update Route53 (DNS) to point to DR region
        $this->updateDNS('api.example.com', self::DR_REGION);
        
        // 3. Scale up DR region resources
        $this->scaleUpRegion(self::DR_REGION);
        
        // 4. Notify team
        $this->notifyOps("FAILOVER COMPLETE -- serving from " . self::DR_REGION);
    }
}

// Database backup with WAL archiving (PostgreSQL point-in-time recovery)
// postgresql.conf:
// wal_level = replica
// archive_mode = on
// archive_command = 'aws s3 cp %p s3://backups/wal/%f'
//
// Base backup (daily):
// pg_basebackup -h db-primary -D /tmp/backup -Ft -z -P
// aws s3 cp /tmp/backup/base.tar.gz s3://backups/basebackup/$(date +%Y%m%d)/
//
// Point-in-time recovery (restore to specific timestamp):
// recovery.conf:
// restore_command = 'aws s3 cp s3://backups/wal/%f %p'
// recovery_target_time = '2024-01-15 14:30:00 UTC'

// Chaos engineering: random failure injection
class ChaosMiddleware {
    private float $failureProbability;  // 0.001 = 0.1% of requests
    
    public function handle(Request $request, callable $next): Response {
        if ($this->isEnabled() && mt_rand(1, 1000) <= ($this->failureProbability * 1000)) {
            // Inject random failure type
            return match(mt_rand(1, 3)) {
                1 => new Response(503, [], 'Chaos: Service Unavailable'),
                2 => (function() { usleep(5000000); return $next($request); })(),  // 5s delay
                3 => throw new RuntimeException('Chaos: Injected exception'),
            };
        }
        return $next($request);
    }
    
    private function isEnabled(): bool {
        return getenv('CHAOS_ENABLED') === 'true'
            && getenv('APP_ENV') !== 'production';  // NEVER in prod without safeguards
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you design a DR strategy for a payment processing system?**
> Payment systems need RPO near-zero and RTO < 1 minute (financial loss otherwise). Design: (1) Active-active multi-region (ap-south-1 + ap-southeast-1). (2) Database: synchronous replication (PostgreSQL streaming replication with synchronous_commit=on) -- every write acknowledged by DR replica before confirming to client. (3) Application: stateless -- any region can serve any request. (4) DNS: Route53 with health checks, 30-second TTL -- automatic failover on health check failure. (5) Testing: monthly failover drill (Game Day) -- actually fail over to DR and run load tests.

**Q2: What is the "backup paradox" and how do you avoid it?**
> The backup paradox: most teams have backups but have never tested restoring from them. When disaster strikes, they discover the backup is corrupt, incomplete, or takes 12 hours to restore (exceeding their RTO). Avoid by: (1) Automated restore testing -- weekly automated restore to test environment, run smoke tests; (2) Document and time the restore procedure; (3) Game Days: simulate real disaster, time the recovery; (4) Monitor backup success/failure (alert on backup failures); (5) Test both backup AND restore -- they are a system, not separate steps.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| RPO: how much data loss is acceptable (backup frequency)          |
| RTO: how quickly must service be restored                         |
| Strategies: Backup < Pilot Light < Warm Standby < Active-Active   |
| Cost and protection increase together -- match to business need   |
| WAL archiving: point-in-time recovery to any second (PostgreSQL)  |
| Test backups regularly -- untested backup = no backup             |
| Chaos engineering: inject failures before disasters do            |
+--------------------------------------------------------------------+
```
