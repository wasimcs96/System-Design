# D7 — Serverless and FaaS

> **Section:** Architecture Patterns | **Level:** Intermediate | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Serverless means you write functions and cloud provider runs them for you — no servers to manage, no capacity planning, no idle cost. You pay only when the function actually runs.

**Technical:** Function as a Service (FaaS) is an execution model where functions are deployed as stateless units, automatically scaled per invocation by the cloud provider. AWS Lambda, Google Cloud Functions, Azure Functions. Billing per invocation + execution duration (GB-seconds). Provider handles: server provisioning, OS patches, scaling, availability.

---

## 2. Real-World Analogy

**Taxi vs. Owning a Car:**
- **Containers/VMs (own car):** Always available, always paying (insurance, parking, maintenance) even when parked.
- **Serverless (taxi):** Only pay when you need a ride. No maintenance. But: wait time (cold start) when ordering, not available for long trips (15-minute max).

---

## 3. Visual Diagram

```
LAMBDA EXECUTION MODEL:
                    COLD START                        WARM EXECUTION
                    ┌──────────────────────────────┐  ┌────────────┐
Event arrives  →    │ Init: download code          │  │ Execute    │
                    │ Init: start runtime (PHP/Node)│  │ function   │
                    │ Init: run global setup code   │  │ (fast)     │
                    │ Execute function              │  └────────────┘
                    └──────────────────────────────┘
                    ↑ 100-1000ms extra              ↑ <10ms overhead
                    Cold start                      Reused warm container

COLD START MITIGATION:
- Provisioned Concurrency: pre-warm N containers (costs money, eliminates cold start)
- Keep functions small (less code to load)
- Avoid large dependency trees
- Use lighter runtimes (Node > Java for cold start)

LAMBDA LIMITS (AWS, 2024):
┌─────────────────────────────────────────────┐
│ Max execution time:    15 minutes           │
│ Max memory:            10 GB                │
│ Max deployment size:   250 MB (unzipped)    │
│ Max ephemeral storage: 10 GB (/tmp)         │
│ Max concurrency:       1000 (default, soft) │
│ Max payload:           6 MB sync / 256 KB async│
└─────────────────────────────────────────────┘

EVENT SOURCES → Lambda:
API Gateway  ──→ Lambda (HTTP requests)
SQS          ──→ Lambda (message queue processing)
Kinesis      ──→ Lambda (stream processing)
DynamoDB Streams ──→ Lambda (change data capture)
S3 events    ──→ Lambda (file processing on upload)
EventBridge  ──→ Lambda (scheduled cron / event bus)
SNS          ──→ Lambda (pub/sub fan-out)
```

---

## 4. Deep Technical Explanation

### Execution Model
- Each Lambda invocation gets a fresh execution environment (or a warm reuse)
- State must be stored externally (DynamoDB, S3, ElastiCache)
- Global variables persist between warm invocations (reuse DB connections, SDK clients)

### Cold Start Anatomy
1. Container provisioning (AWS infrastructure layer)
2. Runtime initialization (JVM start, PHP-FPM init, Node.js module loading)
3. Function code loading
4. Handler init (constructors, DB connections in global scope)

**PHP cold starts are fast** because PHP is a lightweight scripting runtime.
**Java cold starts are slow** (JVM startup 500-2000ms) — use GraalVM native image or Provisioned Concurrency.

### Cost Model
- **Lambda:** $0.0000002/request + $0.0000166667/GB-second
- **Example:** 1M requests × 100ms × 128MB = $0.20 + $0.27 = ~$0.47/month
- **vs. t3.medium:** ~$30/month (always running)
- **Break-even:** ~100M requests/month for constant workloads

### PHP on Lambda (Bref Runtime)
- AWS Lambda natively supports: Node.js, Python, Ruby, Java, Go, .NET
- **Bref** (getbref.com): open-source custom runtime for PHP on Lambda
- Runs PHP using Lambda's custom runtime API
- Supports: function handlers, HTTP (via FPM layer), console commands

---

## 5. Code Example

```php
// PHP Lambda handler using Bref
// composer require bref/bref
use Bref\Context\Context;

// handler.php
return function (array $event, Context $context): array {
    // $event = payload from trigger (API Gateway event, SQS message, etc.)
    // Global scope executed once per cold start — reuse connections here:
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=app',
            getenv('DB_USER'),
            getenv('DB_PASSWORD'),
            [PDO::ATTR_PERSISTENT => true]  // Persistent = reused on warm invocations
        );
    }
    
    $userId = $event['pathParameters']['userId'] ?? null;
    
    if ($userId === null) {
        return [
            'statusCode' => 400,
            'body'       => json_encode(['error' => 'userId required']),
        ];
    }
    
    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'statusCode' => $user ? 200 : 404,
        'headers'    => ['Content-Type' => 'application/json'],
        'body'       => json_encode($user ?: ['error' => 'Not found']),
    ];
};
```

```yaml
# serverless.yml (Serverless Framework)
service: php-api
provider:
  name: aws
  region: ap-south-1
  memorySize: 512
  timeout: 10  # seconds

plugins:
  - ./vendor/bref/bref

functions:
  api:
    handler: handler.php
    layers:
      - ${bref:layer.php-82-fpm}  # PHP 8.2 FPM layer
    events:
      - httpApi:
          path: /users/{userId}
          method: GET
```

---

## 6. Trade-offs

| Aspect | Serverless | Containers (ECS/K8s) |
|--------|-----------|---------------------|
| Startup | Cold start (100-1000ms) | Always warm |
| Cost (low traffic) | Very cheap (pay per use) | Always-on cost |
| Cost (high traffic) | Expensive above 10M+ req/day | Cheaper at scale |
| Max duration | 15 minutes | Unlimited |
| State | External only | Local + external |
| Scaling | Automatic (0 → 1000) | Manual/HPA |
| Debugging | Harder (remote only) | Local debugging possible |

---

## 7. Interview Q&A

**Q1: When should you NOT use serverless?**
> (1) Long-running tasks > 15 minutes: use containers or EC2; (2) Very high, constant throughput: Provisioned Concurrency costs more than always-on containers; (3) Low latency requirements (< 10ms p99): cold starts are unacceptable; (4) Stateful workloads (in-memory state between calls); (5) Heavy compute (ML inference, video processing): better with GPU instances. Serverless is best for: event-driven workloads, unpredictable traffic spikes, scheduled tasks (cron), background processing, and prototypes.

**Q2: How do you handle database connections in Lambda?**
> Lambda scales to 1000+ concurrent instances — each trying to open a DB connection = connection pool exhaustion (most DBs max 100-200 connections). Solutions: (1) RDS Proxy: connection pooler between Lambda and RDS — Lambda connects to proxy, proxy maintains small pool to actual DB; (2) DynamoDB: designed for massive concurrent connections; (3) Reuse connections: initialize DB client in global scope (not handler function) — reused across warm invocations; (4) Use short-lived connections: always close when done, don't hold open across invocations.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Serverless: pay-per-invocation, auto-scale, no server management│
│ ✓ Cold start: 100-1000ms — mitigate with Provisioned Concurrency  │
│ ✓ Initialize DB/SDK clients globally (reused on warm invocations) │
│ ✓ Use RDS Proxy to manage connection pool from Lambda             │
│ ✓ PHP on Lambda via Bref custom runtime                           │
│ ✓ Best for: event-driven, unpredictable traffic, short tasks       │
│ ✓ Not for: long-running, high-throughput constant, low-latency    │
└────────────────────────────────────────────────────────────────────┘
```
