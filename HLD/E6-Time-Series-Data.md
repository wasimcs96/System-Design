# E6 — Time-Series Data

> **Section:** Data Processing | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Time-series data is sequences of values indexed by time: CPU usage every 10 seconds, stock prices every millisecond, website requests per minute. Regular databases are inefficient for this because they're optimized for individual row lookups, not appending timestamped data and querying time ranges.

**Technical:** Time-series databases (TSDB) optimize for: high write throughput (append-only, sequential), efficient time-range queries, automatic compression (time-adjacent values are similar), and data retention policies (auto-delete old data). Key tools: InfluxDB, TimescaleDB (PostgreSQL extension), Prometheus, Apache Druid.

---

## 2. Real-World Analogy

**Weather station logs:** Every 5 minutes, record temperature, humidity, pressure. Over a year: 105,120 data points per sensor. 1000 sensors = 105 million rows. You need to answer: "What was the average temperature last week at 3pm?" Regular RDBMS would struggle. TSDB is purpose-built for this.

---

## 3. Visual Diagram

```
TIME-SERIES DATA STRUCTURE:
timestamp    | metric          | value  | labels
-------------|-----------------|--------|---------------------------
1700000000   | cpu_usage       | 72.5   | host=web-01, dc=ap-south
1700000000   | memory_used     | 4096   | host=web-01, dc=ap-south
1700000010   | cpu_usage       | 74.1   | host=web-01, dc=ap-south
1700000010   | cpu_usage       | 38.2   | host=web-02, dc=ap-south

PROMETHEUS DATA MODEL:
Metric name + labels = unique time series
cpu_usage{host="web-01", dc="ap-south"} -> [values over time]
cpu_usage{host="web-02", dc="ap-south"} -> [values over time]

DOWNSAMPLING (reduce storage for old data):
Raw: every 10s    (keep for 7 days)
     -> aggregate: 1-min averages  (keep for 30 days)
     -> aggregate: 1-hour averages (keep for 1 year)
     -> aggregate: 1-day averages  (keep forever)

Space: raw data 1TB -> hourly averages 50MB (same insight for year-over-year)
```

---

## 4. Deep Technical Explanation

### Why Regular RDBMS Struggles
- Time-series writes are purely sequential (always INSERT, never UPDATE)
- PostgreSQL B-Tree has write overhead for random inserts; TSDB uses LSM-tree or similar
- Compression: adjacent timestamps/values are similar -> delta encoding, XOR encoding (Gorilla algorithm)
- Retention: TSDBs automatically expire old data by time

### InfluxDB Data Model
- **Measurement** = table-like (e.g., "cpu")
- **Tags** = indexed string dimensions (e.g., host, region) — used for filtering
- **Fields** = unindexed numeric values (e.g., usage_idle, usage_user)
- **Timestamp** = nanosecond precision

### TimescaleDB (PostgreSQL Extension)
- Extends PostgreSQL with time-series optimizations
- **Hypertable:** automatically partitions by time (e.g., one chunk per day)
- Query any time range: planner only scans relevant chunks
- Native SQL + JOINs to regular PostgreSQL tables
- Best for: time-series + relational data in one place

### Prometheus + Grafana (Monitoring Stack)
- **Prometheus:** pull-based metrics collection (scrapes /metrics endpoints)
- **PromQL:** powerful query language for time-series math
  - `rate(http_requests_total[5m])` — request rate per second over last 5 min
  - `histogram_quantile(0.99, rate(http_request_duration_seconds_bucket[5m]))` — p99 latency
- **Grafana:** visualization dashboards connected to Prometheus
- **Alertmanager:** route alerts based on PromQL conditions

---

## 5. Code Example

```php
// Write metrics to InfluxDB via PHP SDK
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;

class MetricsService {
    private $writeApi;
    
    public function recordRequest(string $endpoint, int $statusCode, float $durationMs): void {
        $point = Point::measurement('http_requests')
            ->addTag('endpoint', $endpoint)
            ->addTag('status',   (string) $statusCode)
            ->addTag('host',     gethostname())
            ->addField('duration_ms', $durationMs)
            ->addField('count', 1)
            ->time(microtime(true) * 1000000000);  // nanosecond precision
        
        $this->writeApi->write($point);
    }
    
    public function recordOrderValue(float $amount, string $region): void {
        $point = Point::measurement('orders')
            ->addTag('region', $region)
            ->addField('amount', $amount)
            ->addField('count', 1);
        
        $this->writeApi->write($point);
    }
}

// TimescaleDB (PostgreSQL + TimescaleDB extension)
// Schema:
// CREATE TABLE metrics (
//   time   TIMESTAMPTZ NOT NULL,
//   host   TEXT,
//   metric TEXT,
//   value  DOUBLE PRECISION
// );
// SELECT create_hypertable('metrics', 'time');  -- partition by time

// Query: average CPU per host over last hour, in 5-minute buckets
// SELECT time_bucket('5 minutes', time) AS bucket,
//        host,
//        AVG(value) as avg_cpu
// FROM metrics
// WHERE time > NOW() - INTERVAL '1 hour'
//   AND metric = 'cpu_usage'
// GROUP BY bucket, host
// ORDER BY bucket;

// Prometheus metrics endpoint (for Prometheus scraping)
// composer require promphp/prometheus_client_php
class PrometheusMetrics {
    private CollectorRegistry $registry;
    
    public function increment(string $name, array $labels = []): void {
        $counter = $this->registry->getOrRegisterCounter('app', $name, 'Counter', array_keys($labels));
        $counter->inc(array_values($labels));
    }
    
    public function observe(string $name, float $value, array $labels = []): void {
        $histogram = $this->registry->getOrRegisterHistogram(
            'app', $name, 'Histogram', array_keys($labels),
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]  // buckets in seconds
        );
        $histogram->observe($value, array_values($labels));
    }
    
    public function renderMetrics(): string {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you design a metrics pipeline for 1000 microservices?**
> (1) Each service exposes /metrics endpoint (Prometheus format). (2) Prometheus federation or Victoria Metrics (horizontal Prometheus) scrapes all services every 15s. (3) Short-term storage (15 days) in Prometheus/Victoria. (4) Long-term storage: remote_write to Thanos or Cortex (Prometheus-compatible, object storage backend). (5) Grafana connects to Thanos for dashboards. (6) Alertmanager handles alert routing (PagerDuty, Slack). (7) Downsampling: keep raw for 7 days, 5-min rollups for 30 days, hourly for 1 year.

**Q2: What is the difference between monitoring and business metrics?**
> Monitoring metrics: technical health -- CPU, memory, error rate, latency, request count. Sourced from infrastructure + APM tools (Prometheus, Datadog). Business metrics: revenue, conversions, user signups, churn. Sourced from application events -> data warehouse (Redshift, BigQuery) -> BI tools (Looker, Metabase). They have different retention, latency, and accuracy requirements. Mixing them in the same system is possible (InfluxDB) but often kept separate: TSDB for ops metrics, data warehouse for business metrics.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| TSDB: optimized for append-only timestamped data                  |
| Downsampling: reduce old data size (raw->minute->hour aggregates) |
| InfluxDB: tags (indexed, filtering) vs fields (values, unindexed) |
| TimescaleDB: hypertables = auto-partitioned PostgreSQL by time     |
| Prometheus: pull-based scraping, PromQL for queries               |
| rate() in PromQL: correct way to compute request rate from counter|
+--------------------------------------------------------------------+
```
