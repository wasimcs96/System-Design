# F1 — Observability

> **Section:** Observability and Security | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Observability is the ability to understand what your system is doing from the outside — like having gauges, cameras, and logs in your car. Without observability, when something breaks you have no idea why. With it, you can pinpoint issues in seconds.

**Technical:** Observability is built on three pillars: **Metrics** (aggregated numeric measurements over time), **Logs** (discrete event records), and **Traces** (request journey across multiple services). Together they enable: real-time alerting, root cause analysis, capacity planning, and SLO/error budget tracking.

---

## 2. Real-World Analogy

**Airplane cockpit:**
- **Metrics:** altimeter, speed indicator, fuel gauge — summarized current state
- **Logs:** black box flight recorder — every event, detailed, queryable after incident
- **Traces:** GPS flight path — exactly where the plane went, how long each segment took

---

## 3. Visual Diagram

```
THREE PILLARS OF OBSERVABILITY:

METRICS (Prometheus/Datadog):
http_requests_total{status="200"} 1234567  <- counter
http_request_duration_seconds p99 = 0.245  <- histogram
memory_used_bytes = 4294967296             <- gauge
Aggregated, cheap, fast query, but lose individual request detail

LOGS (Elasticsearch/CloudWatch/Loki):
{"timestamp":"2024-01-15T14:23:45Z", "level":"ERROR", "service":"payment",
 "trace_id":"abc123", "message":"DB timeout", "user_id":456, "duration_ms":5001}
Individual events, rich context, but expensive at high volume

TRACES (Jaeger/Zipkin/Datadog APM):
Request: GET /checkout
  [0ms]    → Checkout Service (2ms local)
  [2ms]    → Payment Service (150ms)
     [2ms] → Database query (140ms) <-- bottleneck!
  [152ms]  → Email Service (5ms, async)
  [157ms]  Total: 157ms

Shows exactly WHERE time is spent across microservices

CORRELATION: trace_id links all three pillars
Log entry: {"trace_id":"abc123", "message":"slow query"}
Metric: latency spike at 14:23:45
Trace:  abc123 shows DB was 140ms/157ms total (84% of request time)
```

---

## 4. Deep Technical Explanation

### Metrics Best Practices
- **RED Method** (for services): Rate, Error rate, Duration
- **USE Method** (for resources): Utilization, Saturation, Errors
- **4 Golden Signals** (Google SRE): Latency, Traffic, Errors, Saturation
- Counters (monotonically increasing) vs Gauges (current value) vs Histograms (distributions)
- Label cardinality: keep low (never use user_id as label — millions of unique values = TSDB explosion)

### Structured Logging
- JSON format with consistent fields: timestamp, level, service, trace_id, user_id
- Enables: searching by field, correlation with traces, automated parsing
- Log levels: ERROR (actionable, page someone), WARN (investigate soon), INFO (audit trail), DEBUG (development only)
- Log aggregation: Loki (Grafana Labs), Elasticsearch (ELK stack), CloudWatch Logs

### Distributed Tracing
- **Span:** single operation (HTTP call, DB query, function call) with start/end time
- **Trace:** tree of spans representing one request journey
- **Context propagation:** trace_id + span_id passed as HTTP headers (W3C TraceContext standard)
  - `traceparent: 00-abc123-def456-01`
- Sampling: trace 100% in dev, 1-10% in production (cost), or head-based/tail-based

### SLO/SLI/SLA and Error Budgets
- **SLI** (Service Level Indicator): actual measurement (e.g., % requests < 200ms)
- **SLO** (Service Level Objective): target (e.g., 99.9% of requests < 200ms)
- **SLA** (Service Level Agreement): contractual commitment with penalty
- **Error budget:** 1 - SLO availability. 99.9% SLO -> 0.1% budget = 43.8 min/month
  - If budget exhausted: freeze new features, focus on reliability
  - If budget is healthy: accelerate release velocity

---

## 5. Code Example

```php
// Structured logging with correlation
class Logger {
    private string $service;
    private ?string $traceId = null;
    
    public function withTrace(string $traceId): self {
        $clone = clone $this;
        $clone->traceId = $traceId;
        return $clone;
    }
    
    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }
    
    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }
    
    private function log(string $level, string $message, array $context): void {
        $entry = array_merge([
            'timestamp' => date('c'),
            'level'     => $level,
            'service'   => $this->service,
            'trace_id'  => $this->traceId,
            'host'      => gethostname(),
            'message'   => $message,
        ], $context);
        
        // Write as JSON (one line per log entry for aggregators)
        fwrite(STDOUT, json_encode($entry) . PHP_EOL);
    }
}

// Distributed tracing with OpenTelemetry PHP SDK
use OpenTelemetry\API\Trace\TracerProviderInterface;

class PaymentService {
    private $tracer;
    
    public function processPayment(string $orderId, float $amount): array {
        // Start a span for this operation
        $span = $this->tracer->spanBuilder('process_payment')
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
        
        $scope = $span->activate();
        
        try {
            $span->setAttribute('order.id', $orderId);
            $span->setAttribute('payment.amount', $amount);
            
            // Child span for DB call
            $dbSpan = $this->tracer->spanBuilder('db.insert_payment')->startSpan();
            $dbScope = $dbSpan->activate();
            $result = $this->db->insertPayment($orderId, $amount);
            $dbSpan->end();
            $dbScope->detach();
            
            $span->setStatus(StatusCode::STATUS_OK);
            return $result;
        } catch (\Exception $e) {
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}

// Prometheus metrics (PHP)
$counter = $registry->getOrRegisterCounter('app', 'http_requests_total', 'Total HTTP requests', ['method', 'endpoint', 'status']);
$counter->inc(['GET', '/api/orders', '200']);

$histogram = $registry->getOrRegisterHistogram('app', 'http_duration_seconds', 'HTTP duration', ['endpoint'],
    [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5]);
$histogram->observe(0.045, ['/api/orders']);
```

---

## 7. Interview Q&A

**Q1: How would you debug a latency spike in a microservices system?**
> (1) Check dashboards: look at RED metrics (rate, error rate, duration) for the affected service -- which endpoint spiked? (2) Zoom in with traces: find slow trace_ids in Jaeger/Datadog APM -- which span took the most time? (3) Database? Check slow query log, connection pool saturation. (4) Downstream dependency? Check circuit breaker state, dependency latency histograms. (5) Correlate logs with trace_id: what was happening in the slow traces? Infrastructure event? (6) Check resource saturation (USE method): CPU, memory, network I/O for the affected hosts.

**Q2: What is an error budget and how do you use it?**
> Error budget = 1 - SLO. If SLO is 99.9% availability, error budget is 0.1% = 43.8 minutes/month of allowed downtime/errors. Use: (1) When budget is healthy: teams can move fast, deploy frequently, take risks; (2) When budget is nearly exhausted: freeze non-critical deploys, focus on reliability improvements; (3) When budget is exhausted: trigger incident review, postmortem, reliability sprint. This creates a rational data-driven conversation between product (wants velocity) and engineering (wants stability). SRE teams at Google use error budgets to enforce SLO compliance.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| Three pillars: Metrics (aggregate), Logs (events), Traces (journey)|
| RED method: Rate, Error rate, Duration -- for every service       |
| Structured logging: JSON with trace_id for correlation            |
| trace_id links metrics + logs + traces for root cause analysis    |
| Error budget: SLO violation rate -- when gone, freeze deploys     |
| Never use high-cardinality values (user_id) as metric labels      |
+--------------------------------------------------------------------+
```
