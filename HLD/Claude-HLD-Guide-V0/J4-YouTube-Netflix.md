# J4 — Design YouTube / Netflix

> **Section:** Case Studies | **Difficulty:** Hard | **Interview Frequency:** ★★★★★

---

## 1. Problem Statement

Design a video streaming platform like YouTube or Netflix.

**Functional Requirements:**
- Upload videos
- Stream videos at multiple quality levels (adaptive bitrate)
- Search videos
- View recommendations (out of scope for deep dive)
- Like, comment, subscribe

**Non-Functional Requirements:**
- 2B users, 500 hours of video uploaded per minute
- 1B hours of video watched per day
- 99.9% availability
- Smooth streaming even on poor networks (adaptive bitrate)
- Global reach (CDN required)

---

## 2. Capacity Estimation

```
Uploads: 500 hrs/min = 30,000 hrs/hr = 720,000 hrs/day
Storage per uploaded hour: 1GB raw -> transcoded to 5 resolutions = ~5 GB total
Storage per day: 720,000 hrs x 5 GB = 3.6 PB/day

Streaming:
  1B hours/day / 86400 sec = ~11,574 hours/sec
  Average bitrate: 2 Mbps (720p)
  Bandwidth: 11,574 x 2 Mbps = ~23 Tbps peak -> CDN must absorb this
  Origin servers: CDN cache hit rate ~99% -> origin serves ~230 Gbps

Read:Write = 1B hours watched / 720K hours uploaded = ~1,400:1 (extreme read dominance)
```

---

## 3. High-Level Design

```
UPLOAD PATH:
[User] -> [API Gateway] -> [Upload Service]
                                 |
                           [Raw Video S3]
                                 |
                         [Kafka: video_uploaded]
                                 |
                       [Transcoding Orchestrator]
                       /    |    |    |                       [1080p] [720p][480p][360p][144p]  (parallel workers)
                       \    |    |    |    /
                        [Processed S3]
                              |
                        [Video CDN] (CloudFront/Akamai)
                              |
                   [Update video metadata in PostgreSQL]
                   [Index in Elasticsearch for search]

STREAM PATH:
[User] -> [CDN Edge] -> (cache hit: stream directly)
               |miss
               v
         [Video Service] -> [Processed S3]
         [returns HLS manifest with CDN URLs]
```

---

## 4. Video Upload & Transcoding

```php
// Step 1: Client gets a pre-signed S3 upload URL (bypass our servers for large files)
function getUploadUrl(string $filename, string $contentType): array {
    $videoId = $this->snowflake->nextId();
    $s3Key   = "raw/{$videoId}/{$filename}";

    $presignedUrl = $this->s3->createPresignedRequest(
        $this->s3->getCommand('PutObject', [
            'Bucket'      => 'raw-videos',
            'Key'         => $s3Key,
            'ContentType' => $contentType,
        ]),
        '+15 minutes'
    )->getUri();

    // Store pending video metadata in DB
    $this->db->insert('videos', [
        'id'         => $videoId,
        'status'     => 'UPLOADING',
        'user_id'    => $this->userId,
        'created_at' => now(),
    ]);

    return ['video_id' => $videoId, 'upload_url' => (string) $presignedUrl];
}

// Step 2: S3 event triggers Lambda -> publishes to Kafka
// Step 3: Transcoding workers consume Kafka event

// TRANSCODING STRATEGY:
// 1. Split video into 1-minute segments (parallelism)
// 2. Transcode each segment at each resolution in parallel
// 3. Re-assemble HLS chunks (.ts files + .m3u8 manifest)
// Output structure:
//   processed/{video_id}/1080p/index.m3u8
//   processed/{video_id}/1080p/seg001.ts, seg002.ts, ...
//   processed/{video_id}/master.m3u8  (adaptive bitrate manifest)
```

---

## 5. Adaptive Bitrate Streaming (ABR)

```
HLS (HTTP Live Streaming) -- Apple, widely used
DASH (Dynamic Adaptive Streaming over HTTP) -- YouTube, Netflix

How ABR works:
1. Client downloads master.m3u8 (lists all available resolutions + bandwidth)
2. Client monitors current download speed
3. Client picks highest quality that can be downloaded faster than playback speed
4. If speed drops: switches to lower quality (seamless segment boundary)
5. Buffer: client maintains 30s buffer ahead of playback position

Master manifest (master.m3u8):
  #EXT-X-STREAM-INF:BANDWIDTH=5000000,RESOLUTION=1920x1080
  1080p/index.m3u8
  #EXT-X-STREAM-INF:BANDWIDTH=2500000,RESOLUTION=1280x720
  720p/index.m3u8
  #EXT-X-STREAM-INF:BANDWIDTH=800000,RESOLUTION=854x480
  480p/index.m3u8

CDN caches each .ts segment (typically 6-10 seconds of video)
CDN cache hit rate: ~99% for popular videos (long cache TTL, content-addressable URLs)
```

---

## 6. Database Schema

```sql
-- PostgreSQL (video metadata)
CREATE TABLE videos (
    id           BIGINT       PRIMARY KEY,   -- Snowflake ID
    user_id      BIGINT       NOT NULL,
    title        VARCHAR(500),
    description  TEXT,
    duration_sec INT,
    status       VARCHAR(20)  DEFAULT 'PROCESSING', -- PROCESSING, READY, FAILED
    view_count   BIGINT       DEFAULT 0,
    manifest_url TEXT,                        -- CDN URL to master.m3u8
    thumbnail_url TEXT,
    created_at   TIMESTAMP    DEFAULT NOW()
);

CREATE INDEX idx_user_videos ON videos(user_id, created_at DESC);

-- View counts (high-write, eventually consistent)
-- Redis: INCR view:{video_id} (in-memory counter)
-- Periodic flush to PostgreSQL every 60 seconds
-- Or: Kafka events -> ClickHouse for accurate analytics
```

---

## 7. Search

```
Video search pipeline:
1. On video ready: extract title, description, tags -> publish to Kafka
2. Search indexer consumer: index in Elasticsearch
3. User search -> Elasticsearch (BM25 relevance ranking)

Autocomplete (typeahead):
  Redis sorted set: ZADD searches {score} "term"
  Score = search frequency
  On keystroke: ZRANGEBYLEX searches "[{prefix}" "[{prefix}z" LIMIT 5
```

---

## 8. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Upload method | Pre-signed S3 URL | Bypass app servers for large files (GB range) |
| Transcoding | DAG parallel per segment | 50x speedup vs sequential per video |
| Streaming | HLS/DASH ABR | Adapts to network conditions |
| CDN | CloudFront/Akamai | 99% cache hit, global edge nodes |
| View counts | Redis + async flush | High-write counter; slight staleness OK |
| Search | Elasticsearch | Full-text, relevance ranking, faceted |

---

## 9. Interview Q&A

**Q: How does Netflix serve the same video to millions simultaneously without overloading origin?**
> Netflix uses Open Connect -- their own CDN with servers embedded directly inside ISP networks. Popular content (top 10K titles) is pre-positioned to all edge servers daily during off-peak hours. At peak, 99%+ of traffic is served from these edge servers with zero origin egress. The key insight: pre-warming CDN (push CDN model) with known-popular content is far more efficient than pull CDN for streaming where every byte of the same file is served millions of times.

**Q: What happens if transcoding fails halfway?**
> Transcoding uses a DAG (Directed Acyclic Graph) of jobs stored in a workflow engine (AWS Step Functions or Apache Airflow). Each segment transcoding job is independent and idempotent (S3 PUT is idempotent). On failure: retry up to 3 times with exponential backoff. If all retries fail: mark job as failed, publish to Dead Letter Queue (DLQ), alert on-call. The original raw video is always preserved in S3, so re-transcoding is always possible. Video status remains "PROCESSING" until all segments succeed; UI shows "processing" to uploader.

---

## 10. Key Takeaways

```
+--------------------------------------------------------------------+
| Pre-signed S3 URL = direct upload, bypass app servers            |
| Segment + parallelize transcoding = 50x faster than sequential   |
| HLS/DASH ABR = adaptive quality based on network speed           |
| CDN cache hit 99% = origin serves only 1% of traffic            |
| Redis counter + async flush = high-write view counts             |
+--------------------------------------------------------------------+
```
