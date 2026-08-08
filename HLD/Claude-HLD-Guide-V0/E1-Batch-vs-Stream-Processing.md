# E1 — Batch vs Stream Processing

> **Section:** Data Processing | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Batch processing is like washing clothes — you collect clothes for a week, then wash them all at once. Stream processing is like an automatic dishwasher — clean each dish immediately as it gets dirty.

**Technical:** Batch processing collects data over a period, then processes all records together (Hadoop MapReduce, Spark batch). Stream processing continuously processes data as it arrives, with low latency (Kafka Streams, Apache Flink, Spark Structured Streaming). The choice depends on latency requirements and processing complexity.

---

## 2. Real-World Analogy

**Payroll processing = batch:** Process salaries for all employees on the 1st of every month. Doesn't matter if it takes 2 hours — must be accurate.

**Fraud detection = streaming:** Credit card transaction arrives → detect fraud within 50ms → block transaction before it completes. Waiting until end-of-day is too late.

---

## 3. Visual Diagram

```
BATCH PROCESSING:
Data    → [      Accumulate data for N hours     ] → [Batch Job] → Results
Stream:                                                              Latency: hours

STREAM PROCESSING:
Event → [Process immediately] → Result                           Latency: ms-seconds

LAMBDA ARCHITECTURE (combines both):
                    ┌──────────────────────────────────────────────┐
                    │                Kafka Stream                  │
                    └──────┬───────────────────────────┬──────────┘
                           ↓                           ↓
              ┌────────────────────┐    ┌────────────────────────┐
              │  Speed Layer       │    │  Batch Layer           │
              │  (Flink/Spark      │    │  (Spark/Hadoop on S3) │
              │  streaming)        │    │  Recomputes exact      │
              │  Approximate/fast  │    │  results daily         │
              └────────────────────┘    └────────────────────────┘
                           ↓                           ↓
              ┌──────────────────────────────────────────────────┐
              │            Serving Layer (merge results)          │
              └──────────────────────────────────────────────────┘

KAPPA ARCHITECTURE (stream only — simpler):
All data → Kafka → Flink (streaming) → Results
Historical reprocessing: replay from Kafka beginning
No separate batch layer — simpler operational model
```

---

## 4. Deep Technical Explanation

### Batch Processing (Spark, Hadoop MapReduce)
- **When:** Accuracy > latency. ETL jobs, ML training, reports, billing
- **How:** Read from HDFS/S3, process in memory with large parallelism, write results
- **Spark vs MapReduce:** Spark processes in-memory (100x faster); MapReduce writes to disk between stages
- **Window:** Process all data since last run (hourly, daily, monthly)

### Stream Processing (Flink, Kafka Streams, Spark Structured Streaming)
- **When:** Low latency required (seconds or less). Fraud detection, real-time dashboards, alerting
- **How:** Continuously read from Kafka partitions, process each event, maintain state

### Windowing (critical for stream processing)
- **Tumbling window:** Fixed-size, non-overlapping. "Count orders per 5-minute interval."
- **Sliding window:** Fixed-size, overlapping. "Count orders in last 5 minutes, updated every 1 minute."
- **Session window:** Gap-based. "Group all events with < 30-second gap as one session."

### Watermarks and Late Data
- Events can arrive out of order (network latency, retries)
- Watermark: threshold for how long to wait for late events
- `watermark = max_event_time - allowed_lateness`
- Events arriving after watermark: either dropped or handled by late data strategy

### Delivery Semantics
- **At-most-once:** Fast, can lose data (fire and forget)
- **At-least-once:** Can process twice (retries) — handlers must be idempotent
- **Exactly-once:** Flink + Kafka checkpointing; expensive but no duplicates

---

## 5. Code Example

```php
// Stream processing concept in PHP with Kafka (using rdkafka)
// In production: use Flink/Kafka Streams for complex stream processing

class OrderStreamProcessor {
    private \RdKafka\KafkaConsumer $consumer;
    private \Redis                 $redis;
    
    public function process(): void {
        $this->consumer->subscribe(['orders']);
        
        while (true) {
            $message = $this->consumer->consume(timeout_ms: 100);
            
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $event = json_decode($message->payload, true);
                
                // Tumbling window: count orders per 5-minute bucket
                $bucket = floor(time() / 300) * 300;  // 5-min epoch bucket
                $key    = "order_count:{$bucket}";
                
                $count = $this->redis->incr($key);
                $this->redis->expire($key, 3600);  // keep 1 hour
                
                // Threshold alert
                if ($count > 1000) {
                    $this->alertHighVolume($bucket, $count);
                }
                
                $this->consumer->commitAsync($message);
            }
        }
    }
}

// Batch processing: daily revenue report with Spark (pseudocode)
// $spark = SparkSession::builder()->getOrCreate();
// $df = $spark->read()->parquet("s3://data/orders/date=2024-01-15/");
// $df->groupBy("product_id")
//    ->agg(sum("amount").as("revenue"), count("*").as("order_count"))
//    ->orderBy(col("revenue").desc())
//    ->write()->parquet("s3://reports/daily-revenue/2024-01-15/");
```

---

## 6. Trade-offs

| Aspect | Batch | Stream |
|--------|-------|--------|
| Latency | Hours/minutes | Milliseconds/seconds |
| Throughput | Very high | High |
| Complexity | Lower | Higher (state, watermarks) |
| Accuracy | Exact | Approximate or exact-with-effort |
| Use cases | ETL, ML training, reports | Fraud, dashboards, real-time alerts |
| Cost | Lower (periodic) | Higher (always running) |

---

## 7. Interview Q&A

**Q1: How would you design a real-time dashboard showing orders per minute?**
> Orders → Kafka topic. Flink job consumes Kafka, maintains a 1-minute tumbling window aggregation per region, writes results to Redis sorted set every 10 seconds. Dashboard polls Redis every 5 seconds (or uses WebSocket pushed from Redis pub/sub). For accuracy: Flink handles out-of-order events with 10-second watermark (waits up to 10s for late arrivals). For scale: Kafka partitioned by region → parallel Flink tasks per region.

**Q2: What is the difference between Lambda and Kappa architecture?**
> Lambda architecture: two parallel processing paths — speed layer (streaming, approximate, low latency) + batch layer (accurate, high latency). Serving layer merges both. Problem: maintain two codebases (one for batch, one for stream) that must produce the same results. Kappa architecture: stream processing only. Historical reprocessing: replay Kafka from the beginning (Kafka retains events for configurable period). Simpler operationally. Used by Uber, LinkedIn. Lambda is older and now mostly replaced by Kappa.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Batch: high accuracy, high latency — ETL, reports, ML training  │
│ ✓ Stream: low latency — fraud detection, real-time dashboards     │
│ ✓ Windowing: tumbling (fixed), sliding (overlapping), session     │
│ ✓ Watermarks: handle out-of-order events in stream processing     │
│ ✓ Kappa > Lambda: stream-only architecture is simpler             │
│ ✓ Flink: best for exactly-once stream processing                  │
└────────────────────────────────────────────────────────────────────┘
```
