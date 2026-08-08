# E2 — Object Storage

> **Section:** Data Processing | **Level:** Intermediate | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Object storage is like a giant, unlimited hard drive in the cloud where you store files (images, videos, backups, logs). Amazon S3 is the most popular. Unlike a regular file system, you access files via HTTP URL, not file paths.

**Technical:** Object storage organizes data as flat key-value pairs (key = path-like string, value = binary blob + metadata). No directories (only simulated by key prefixes). Massively scalable, durable (S3: 11 nines = 99.999999999%), cheap per GB, accessible via REST API.

---

## 2. Real-World Analogy

**Post Office vs. File Cabinet:**
- File system = filing cabinet: hierarchical folders, fast local access, limited capacity
- Object storage = post office: each letter has a unique tracking number (key), stored in massive warehouse, retrieved by number. No folders — just keys. Infinite capacity. Access via mail (HTTP).

---

## 3. Visual Diagram

```
S3 ARCHITECTURE:
Bucket: "my-app-assets"
  ├── Key: "users/avatars/user-123.jpg"    Value: [binary]  Metadata: {content-type, size}
  ├── Key: "products/images/sku-456.png"   Value: [binary]
  ├── Key: "backups/2024/01/15/db.sql.gz"  Value: [binary]
  └── Key: "logs/2024/01/15/access.log"    Value: [binary]

(keys look like paths but are actually just strings — no real hierarchy)

S3 MULTIPART UPLOAD (for large files > 5MB):
File: 1GB video
Part 1 (5MB) ──┐
Part 2 (5MB) ──┤ Upload in parallel → Assemble on S3
Part N (5MB) ──┘
Benefits: resume on failure, parallel = faster, required for > 5GB

S3 STORAGE CLASSES (cost vs. retrieval speed):
Standard          → $0.023/GB    → milliseconds  → frequent access
Infrequent Access → $0.0125/GB   → milliseconds  → monthly access
Glacier Instant   → $0.004/GB    → milliseconds  → quarterly access
Glacier Flexible  → $0.0036/GB   → minutes/hours → archival
Deep Archive      → $0.00099/GB  → 12 hours      → 7+ year retention
```

---

## 4. Deep Technical Explanation

### S3 Internals
- **Consistency:** S3 provides strong read-after-write consistency (since Dec 2020)
- **Durability:** Data replicated across 3+ AZs. 99.999999999% (11 nines) durability
- **Partitioning:** S3 auto-partitions by key prefix. Avoid sequential prefixes (timestamps) as keys — they hash to same partition → hot spot. Recommendation: prefix with hash or random characters.

### Pre-signed URLs
- Grant temporary access to private S3 objects without exposing AWS credentials
- Use case: allow users to upload/download files directly to S3 (bypasses your server)
- Server generates signed URL (valid for 15 min to 7 days) → client uses URL directly

### S3 Select / Athena
- **S3 Select:** Query inside a single file (CSV, JSON, Parquet) with SQL — retrieve subset
- **Amazon Athena:** Query across S3 files with SQL (serverless, pay per query)
- Both avoid downloading entire files for analytics

### Lifecycle Policies
- Auto-transition objects between storage classes
- Example: Standard → IA after 30 days → Glacier after 90 days → Delete after 365 days

### Block vs. File vs. Object Storage
| Type | Example | Access | Use Case |
|------|---------|--------|---------|
| Block | EBS, SAN | Block I/O | OS volumes, databases |
| File | NFS, EFS | File path | Shared filesystems |
| Object | S3, GCS | HTTP/REST | Media, backups, data lake |

---

## 5. Code Example

```php
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class ObjectStorageService {
    private S3Client $s3;
    private string   $bucket;
    
    public function upload(string $key, string $filePath, string $contentType = 'application/octet-stream'): string {
        $this->s3->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'SourceFile'  => $filePath,
            'ContentType' => $contentType,
        ]);
        
        return "https://{$this->bucket}.s3.amazonaws.com/{$key}";
    }
    
    // Pre-signed URL for direct upload (avoid passing file through your server)
    public function getPresignedUploadUrl(string $key, int $expiresInSeconds = 900): string {
        $command = $this->s3->getCommand('PutObject', [
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'ContentType' => 'image/jpeg',
        ]);
        
        $request = $this->s3->createPresignedRequest($command, "+{$expiresInSeconds} seconds");
        return (string) $request->getUri();
    }
    
    // Pre-signed URL for temporary download
    public function getPresignedDownloadUrl(string $key, int $expiresInSeconds = 3600): string {
        $command = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ]);
        
        $request = $this->s3->createPresignedRequest($command, "+{$expiresInSeconds} seconds");
        return (string) $request->getUri();
    }
    
    // Multipart upload for large files
    public function uploadLargeFile(string $key, string $filePath): void {
        $this->s3->upload(
            $this->bucket,
            $key,
            fopen($filePath, 'r'),
            'private',
            ['mup_threshold' => 5 * 1024 * 1024]  // multipart if > 5MB
        );
    }
    
    // S3 Select — query CSV file without downloading entire file
    public function queryFile(string $key, string $sql): string {
        $result = $this->s3->selectObjectContent([
            'Bucket'         => $this->bucket,
            'Key'            => $key,
            'ExpressionType' => 'SQL',
            'Expression'     => $sql,  // e.g., "SELECT * FROM S3Object WHERE age > 30"
            'InputSerialization'  => ['CSV' => ['FileHeaderInfo' => 'USE']],
            'OutputSerialization' => ['JSON' => []],
        ]);
        
        $output = '';
        foreach ($result['Payload'] as $event) {
            if (isset($event['Records']['Payload'])) {
                $output .= $event['Records']['Payload'];
            }
        }
        return $output;
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you design a system for users to upload profile pictures?**
> (1) Client requests pre-signed S3 upload URL from API. (2) API generates pre-signed URL (PUT, 15-min expiry, max 5MB, image/jpeg only). (3) Client uploads directly to S3 (bypasses your server — saves bandwidth). (4) S3 event notification → Lambda → generate thumbnail, validate content type. (5) Lambda updates DB with S3 key. (6) Serve via CloudFront CDN (not direct S3 URLs) for performance + access control. Security: validate content type in Lambda (client-declared type is untrusted); scan with antivirus lambda.

**Q2: Why should you avoid using timestamps as S3 key prefixes?**
> S3 partitions objects by key prefix for performance. Keys like `2024/01/15/12:00:00-image.jpg` have the same prefix `2024/01/15/12:00:` during upload bursts → all writes go to one S3 partition → throttling. Fix: add random hash prefix: `a3f2/2024/01/15/12:00:00-image.jpg` distributes across S3 partitions. Alternatively: UUID-based keys (naturally random). The "random prefix" recommendation was important before 2018; S3 now handles sequential keys better but random is still best practice for high-throughput uploads.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Object storage: flat key-value, unlimited scale, HTTP access    │
│ ✓ Pre-signed URLs: grant temp access without exposing credentials │
│ ✓ Multipart upload: required > 5GB, better for > 5MB             │
│ ✓ Storage classes: tiered cost — use lifecycle policies           │
│ ✓ S3 Select: SQL queries on individual files (avoid full download)│
│ ✓ Serve via CDN (CloudFront), not direct S3 URLs                 │
│ ✓ Avoid sequential prefixes — use random/hash for high write load │
└────────────────────────────────────────────────────────────────────┘
```
