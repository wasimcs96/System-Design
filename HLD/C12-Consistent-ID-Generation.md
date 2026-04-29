# C12 — Consistent ID Generation

> **Section:** Distributed Systems Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** In a distributed system with many servers, how do you generate unique IDs without a central counter? If two servers each try to assign ID "12345" to different records, you have a collision. Distributed ID generation solves this.

**Technical:** Distributed ID generation creates globally unique identifiers across multiple nodes without coordination, while optionally preserving properties like k-sortability (time-ordered), compactness, and avoiding collisions.

---

## 2. Real-World Analogy

**Passport numbers:**
- Each country issues passports with country code + sequential number
- India: IN-1234567, US: US-9876543
- No two passports globally share the same number because the country code is unique
- Snowflake IDs work similarly: node ID + timestamp + sequence

---

## 3. Visual Diagram

```
SNOWFLAKE ID STRUCTURE (Twitter's algorithm, 64-bit integer):
┌──────────────────────────────────────────────────────────────────┐
│  1 bit    │  41 bits     │  10 bits    │  12 bits               │
│  (unused) │  timestamp   │  machine ID │  sequence              │
│           │  (ms since   │  (0-1023)   │  (0-4095 per ms)       │
│           │  epoch)      │             │                        │
└──────────────────────────────────────────────────────────────────┘

41-bit timestamp: ~69 years from custom epoch (e.g., Jan 1, 2020)
10-bit machine ID: up to 1024 machines
12-bit sequence: 4096 IDs per millisecond per machine

Total capacity: 1024 machines × 4096 IDs/ms = 4.19M IDs/ms globally

IDs are roughly time-sorted:
  1700000000001-01-0001 < 1700000000001-01-0002 < 1700000000002-01-0001
  ← same ms, same machine →                ← next ms →

UUID v4 (random):
  550e8400-e29b-41d4-a716-446655440000 (128 bits, not time-ordered)
```

---

## 4. Deep Technical Explanation

### Options Comparison

| Method | Uniqueness | Sortable | Coordination | Size | Use Case |
|--------|-----------|---------|-------------|------|---------|
| Auto-increment | Guaranteed | Yes | Needs DB | 8 bytes | Single DB |
| UUID v4 | Probabilistic | No | None | 16 bytes | General purpose |
| UUID v7 | Probabilistic | Yes (time-ordered) | None | 16 bytes | Modern systems |
| Snowflake | Guaranteed | Yes (k-sortable) | Node ID allocation | 8 bytes | High-throughput |
| ULID | Probabilistic | Yes | None | 16 bytes | Sortable UUID |
| NanoID | Probabilistic | No | None | Variable | URLs, short IDs |

### Snowflake Details (Twitter, 2010)
- Custom epoch: Twitter used Nov 4, 2010 midnight UTC
- Machine ID: 5-bit datacenter + 5-bit machine (10 bits total = 1024 machines)
- Sequence: 12-bit counter per millisecond, resets to 0 each new millisecond
- If sequence overflows within 1ms: wait until next millisecond
- Time ordering: IDs generated later always have higher value (sortable)

### UUID v7 (RFC 9562, 2024)
- 48-bit Unix millisecond timestamp
- 12-bit sub-millisecond precision
- 62 bits random
- K-sortable like Snowflake but no coordination needed
- Increasingly adopted as the modern UUID standard

### Database Auto-Increment Problems
- Single DB: no issue
- Multi-DB shards: each shard generates 1,2,3... independently → collision
- Fix: use separate increments (Shard 1: 1,3,5...; Shard 2: 2,4,6...) — limited
- Better: use Snowflake or UUID in sharded systems

---

## 5. Code Example

```php
class SnowflakeIdGenerator {
    private int    $machineId;
    private int    $sequence     = 0;
    private int    $lastMs       = -1;
    
    // Custom epoch: 2020-01-01 00:00:00 UTC
    private const EPOCH          = 1577836800000;
    private const MACHINE_BITS   = 10;
    private const SEQUENCE_BITS  = 12;
    private const MAX_SEQUENCE   = (1 << self::SEQUENCE_BITS) - 1;  // 4095
    private const MACHINE_SHIFT  = self::SEQUENCE_BITS;             // 12
    private const TIMESTAMP_SHIFT = self::SEQUENCE_BITS + self::MACHINE_BITS;  // 22
    
    public function __construct(int $machineId) {
        if ($machineId < 0 || $machineId >= (1 << self::MACHINE_BITS)) {
            throw new \InvalidArgumentException("Machine ID must be 0-1023");
        }
        $this->machineId = $machineId;
    }
    
    public function nextId(): int {
        $currentMs = $this->currentTimeMs();
        
        if ($currentMs < $this->lastMs) {
            // Clock went backwards — wait for time to catch up
            throw new \RuntimeException("Clock moved backwards");
        }
        
        if ($currentMs === $this->lastMs) {
            // Same millisecond — increment sequence
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;
            
            if ($this->sequence === 0) {
                // Sequence overflow — wait for next millisecond
                while ($currentMs <= $this->lastMs) {
                    $currentMs = $this->currentTimeMs();
                }
            }
        } else {
            // New millisecond — reset sequence
            $this->sequence = 0;
        }
        
        $this->lastMs = $currentMs;
        
        $id = (($currentMs - self::EPOCH) << self::TIMESTAMP_SHIFT)
            | ($this->machineId << self::MACHINE_SHIFT)
            | $this->sequence;
        
        return $id;
    }
    
    private function currentTimeMs(): int {
        return (int) round(microtime(true) * 1000);
    }
    
    public function extractTimestamp(int $id): \DateTime {
        $ts = ($id >> self::TIMESTAMP_SHIFT) + self::EPOCH;
        return \DateTime::createFromFormat('U', intdiv($ts, 1000));
    }
}

// Usage
$generator = new SnowflakeIdGenerator(machineId: 1);
$id        = $generator->nextId();
// $id = 1700000000001001001 (example 64-bit int)
```

---

## 7. Interview Q&A

**Q1: How would you design a distributed ID generator for a URL shortener like bit.ly?**
> For bit.ly: use Snowflake IDs or UUIDs. Generate 64-bit Snowflake ID, encode in base62 (a-z, A-Z, 0-9) = ~11 characters. For shorter codes: use random base62 of length 7 (62^7 = 3.5T unique codes — enough). Coordination: run multiple Snowflake generator instances, each with unique machine ID (allocate machine IDs via Redis `INCR` at startup or via Zookeeper). Time-ordered IDs: useful for analytics (get all URLs created this month).

**Q2: Why is time-sortability of IDs important for databases?**
> Database B-Tree indexes are kept sorted. When you INSERT random UUIDs, each insert is a random position in the B-Tree — causing many page splits and high fragmentation. With time-ordered IDs (Snowflake, UUID v7, ULID), inserts are mostly sequential (new IDs > all existing IDs) — writes go to the end of the B-Tree, minimal fragmentation. This improves insert performance by 3-5x and reduces index size by ~30%.

**Q3: What happens if two Snowflake generators on the same machine start simultaneously?**
> The machine ID uniquely identifies each generator instance. If both claim the same machine ID, their generated IDs may collide (same timestamp + same machine ID + same sequence). Prevention: Machine IDs must be allocated uniquely. Methods: (1) Zookeeper/etcd sequential node ID allocation; (2) Redis INCR at startup; (3) read from a configuration file/environment variable; (4) derive from IP address + port combination.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Snowflake: timestamp + machine ID + sequence = k-sortable 64-bit │
│ ✓ UUID v4: random, no coordination, not sortable                  │
│ ✓ UUID v7: time-ordered UUID, no coordination (modern choice)     │
│ ✓ Time-sorted IDs = sequential B-Tree inserts = better DB perf    │
│ ✓ Machine IDs must be allocated uniquely (etcd, Redis, env var)   │
│ ✓ Base62 encode Snowflake ID for short, human-readable codes      │
└────────────────────────────────────────────────────────────────────┘
```
