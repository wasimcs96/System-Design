# B9 — CDN & Edge Computing

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A CDN (Content Delivery Network) is a network of servers around the world that store copies of your website's files (images, CSS, videos). When a user in Tokyo visits your site hosted in Virginia, they download files from a nearby Tokyo server instead of the distant Virginia one — much faster.

**Technical:** A CDN is a geographically distributed network of PoPs (Points of Presence / edge nodes) that cache static assets and serve them from locations physically close to end users, reducing latency and origin server load. Edge computing extends this by running code (not just cache) at edge nodes.

---

## 2. Real-World Analogy

**Bookstore Distribution:**
- Without CDN: All books are sold from one warehouse in New York. A customer in Mumbai orders a book — it ships from New York (7-10 days).
- With CDN: Books are pre-stocked in regional warehouses in Mumbai, London, Tokyo, São Paulo. Mumbai customer gets delivery in 1 day.
- Cache miss: If the Mumbai warehouse doesn't have a specific title, they order it from New York (origin), then stock it locally for future orders (cache fill).
- CDN eviction: Slow-moving books are removed from regional stock after 30 days (TTL expiry).

---

## 3. Visual Diagram

```
WITHOUT CDN:
User (Mumbai) ──────────────────────────────→ Origin (Virginia)
              ~300ms round-trip

WITH CDN:
User (Mumbai) ──→ Edge (Mumbai PoP) ──→ HIT: return cached asset (~5ms)
                                    └──→ MISS ──→ Origin (Virginia) ──→ cache fill

CDN NETWORK TOPOLOGY:
                    ┌──────────────────┐
                    │  Origin Server   │
                    │  (your backend)  │
                    └────────┬─────────┘
           ┌────────────────────────────────────┐
           ▼                 ▼                  ▼
   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
   │ Edge - US    │  │ Edge - EU    │  │ Edge - Asia  │
   │ (10 PoPs)    │  │ (8 PoPs)     │  │ (12 PoPs)    │
   └──────────────┘  └──────────────┘  └──────────────┘
         │                  │                  │
      US Users           EU Users          Asia Users
      ~5-20ms             ~5-20ms            ~5-20ms

PULL CDN vs PUSH CDN:
Pull: CDN fetches from origin on first cache miss (lazy, default)
Push: You upload assets directly to CDN ahead of time (eager, for large files)
```

---

## 4. Deep Technical Explanation

### Pull CDN (Lazy Loading)
1. User requests `cdn.example.com/image.jpg`
2. Edge node checks local cache → miss → fetches from origin `origin.example.com/image.jpg`
3. Edge caches the file, serves to user
4. Subsequent requests: served from cache until TTL expires

**Best for:** Dynamic content with unpredictable access patterns; smaller files; low-frequency large files.

### Push CDN (Eager Loading)
1. You upload assets directly to CDN storage (e.g., S3 + CloudFront, or Cloudflare R2)
2. CDN distributes to all edge nodes proactively
3. No cache miss ever — assets always available

**Best for:** Large video files; launch events; files that will be requested by many users immediately.

### Cache Control Headers
```http
# Static assets — cache aggressively (immutable content hash in filename)
Cache-Control: public, max-age=31536000, immutable
# e.g., /static/app.a1b2c3d4.js (filename contains hash)

# HTML — short TTL or no cache (content changes frequently)
Cache-Control: no-cache, no-store, must-revalidate

# API responses — edge cache with short TTL
Cache-Control: public, s-maxage=60, max-age=0
# s-maxage = CDN TTL; max-age = browser TTL

# User-specific — never CDN cache
Cache-Control: private, no-store
```

### Cache Busting
Problem: After a deploy, CDN still serves old `app.js` until TTL expires.
Solutions:
1. **Content-hash filename:** `app.a1b2c3d4.js` → new deploy = new hash = new URL = always fresh
2. **Query string versioning:** `app.js?v=2.1.0` → CDN treats as different URL (less reliable, some CDNs ignore query strings)
3. **Manual CDN purge:** AWS CloudFront invalidation API → costs $0.005 per path, take ~60s

### Origin Shield
An intermediate caching layer between edge nodes and origin:
```
Edge (Tokyo) ──→ Origin Shield (Singapore) ──→ Origin (Virginia)
Edge (Seoul) ──┘                    (only 1 request to origin per unique asset)
```
Reduces origin traffic on cache miss spikes.

### Edge Computing — Running Code at the Edge
Instead of just caching static files, run serverless functions at edge nodes:
- **Cloudflare Workers:** V8 JavaScript engine at 300+ edge locations. Sub-millisecond cold start. 30ms CPU time limit.
- **AWS Lambda@Edge:** Run Node.js/Python at CloudFront edges. Used for: A/B testing, auth token validation, image resizing on-the-fly, geolocation-based redirects.
- **Fastly Compute@Edge:** WebAssembly-based edge functions.

**Use cases:**
- A/B testing: edge function splits traffic without round-trip to origin
- Authentication: validate JWT at edge before forwarding to origin (reject early)
- Dynamic image resizing: resize images at edge based on device `Accept` header
- Geolocation redirects: `/` → redirect to `/en/` or `/fr/` based on IP geo

### When NOT to Use CDN
- Content is personalized per user (CDN would serve one user's data to another — privacy violation)
- Content changes every request (real-time stock prices, live chat)
- Files are very large and accessed rarely (backup files, logs)
- Very small, infrequently accessed sites (CDN cost > origin cost)

---

## 5. Code Example

```php
// Setting CDN-friendly cache headers in Laravel
class ImageController extends Controller {
    public function show(string $filename): Response {
        $path    = Storage::path("images/{$filename}");
        $content = file_get_contents($path);
        $hash    = md5_file($path);
        
        return response($content, 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('ETag', $hash)
            ->header('Cache-Control', 'public, max-age=31536000, immutable')
            ->header('Vary', 'Accept-Encoding');
    }
}

// Dynamic content — short CDN cache, no browser cache
class ProductController extends Controller {
    public function show(int $id): JsonResponse {
        $product = Product::find($id);
        
        return response()->json($product)
            ->header('Cache-Control', 'public, s-maxage=60, max-age=0, must-revalidate')
            // CDN caches for 60 seconds; browser always revalidates
            ->header('Surrogate-Key', "product:{$id}");  // Cloudflare tag-based purge
    }
}
```

```php
// Invalidate CloudFront cache after content update (AWS SDK)
use Aws\CloudFront\CloudFrontClient;

class CdnInvalidationService {
    private CloudFrontClient $cf;
    private string           $distributionId;
    
    public function invalidatePaths(array $paths): void {
        $this->cf->createInvalidation([
            'DistributionId' => $this->distributionId,
            'InvalidationBatch' => [
                'CallerReference' => uniqid(),
                'Paths' => [
                    'Quantity' => count($paths),
                    'Items'    => $paths,  // e.g., ['/images/product-1.jpg', '/api/products/1']
                ],
            ],
        ]);
    }
}
```

---

## 6. Trade-offs

| Approach | Latency | Origin Load | Freshness | Cost |
|----------|---------|------------|----------|------|
| No CDN | High (geo-dependent) | Max | Always fresh | Low |
| Pull CDN | Low (post-warm) | Low | Eventual (TTL) | Medium |
| Push CDN | Low (always) | Minimal | Manual control | Higher |
| Edge functions | Very low | None | Real-time | Medium-High |

---

## 7. Interview Q&A

**Q1: How does a CDN reduce latency?**
> CDN stores copies of assets at edge nodes geographically close to users. Instead of a request traveling from Mumbai to Virginia (300ms RTT), it goes from Mumbai to a Mumbai edge node (5ms RTT). The asset is served from the edge's local cache — no round-trip to origin. For a 1MB image: 6x faster load on first CDN hit; subsequent users in same region get instant response.

**Q2: How do you ensure users get fresh content after a deployment?**
> Three approaches: (1) Content-hash filenames — webpack/Vite generates `app.a1b2c3.js`; new build = new filename = new CDN URL = no stale cache issue; (2) CDN invalidation API — explicitly purge paths after deploy (has cost and latency of ~60s); (3) Short TTL on HTML (no-cache) + long TTL on hashed assets. Best practice: use content hashes for all static assets, purge only HTML on deploy.

**Q3: What is an edge function and when would you use it?**
> Edge functions are serverless code running at CDN edge nodes, not at a central origin. Sub-millisecond cold starts (Cloudflare Workers uses V8 isolates, not containers). Use cases: (1) A/B testing without origin roundtrip; (2) JWT validation at edge — reject unauthenticated requests before they reach origin; (3) Geolocation-based content/redirects; (4) Dynamic image resizing based on viewport. Not for: complex business logic, DB writes, long-running operations (CPU limited to 30-50ms).

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Pull CDN = lazy cache fill on miss; Push CDN = pre-populate      │
│ ✓ Content-hash filenames = best cache busting strategy             │
│ ✓ s-maxage = CDN TTL; max-age = browser TTL; private = no CDN     │
│ ✓ Origin shield reduces origin load on cache miss storms           │
│ ✓ Edge functions: A/B testing, auth, image resize, geo-redirects   │
│ ✓ Never CDN-cache: personalized data, real-time prices, live feeds │
└────────────────────────────────────────────────────────────────────┘
```
