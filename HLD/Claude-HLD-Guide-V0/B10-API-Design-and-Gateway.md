# B10 — API Design & API Gateway

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** An API (Application Programming Interface) is a set of rules for how different software programs talk to each other. An API Gateway is a single entry point for all client requests — like a receptionist who directs visitors to the right department.

**Technical:** API design encompasses the conventions, protocols, and patterns for exposing service capabilities. An API Gateway is an infrastructure component that sits between clients and backend services, handling cross-cutting concerns: authentication, rate limiting, routing, SSL termination, observability, and protocol translation.

---

## 2. Real-World Analogy

**REST** = A menu at a restaurant. You order items by name. You get back exactly what the menu describes. Predictable, standard, cacheable.

**GraphQL** = A buffet. You tell the chef exactly what combination you want. You get exactly that — no more, no less. Flexible but requires more coordination.

**gRPC** = A direct hotline to the kitchen. Faster, more efficient, uses a strict protocol (binary), best for internal kitchen-to-kitchen communication.

**API Gateway** = The front desk of a hotel. Every guest goes through reception (authentication, check-in). Reception routes guests to rooms/restaurant/gym. It logs every entry, limits access for suspicious guests.

---

## 3. Visual Diagram

```
API PROTOCOLS COMPARISON:
┌────────────────────────────────────────────────────────────────┐
│  REST                                                          │
│  GET  /users/123          → { "id": 123, "name": "Alice" }    │
│  POST /users              → create user                       │
│  PUT  /users/123          → replace user                      │
│  DELETE /users/123        → delete user                       │
│  Text-based (JSON), stateless, cacheable, ubiquitous          │
├────────────────────────────────────────────────────────────────┤
│  GraphQL                                                       │
│  query { user(id:123) { name, orders { total } } }            │
│  → exact fields + nested data in 1 request (no over-fetching) │
│  Single endpoint: POST /graphql                               │
├────────────────────────────────────────────────────────────────┤
│  gRPC                                                          │
│  Protobuf-defined: rpc GetUser (UserRequest) returns (User)   │
│  Binary protocol, HTTP/2 multiplexing, streaming support      │
│  2-10x faster than REST, but harder to debug                  │
└────────────────────────────────────────────────────────────────┘

API GATEWAY ARCHITECTURE:
Client (Mobile/Web/Third-party)
        │
        ▼
┌────────────────────────────────────────────────────────────────┐
│                    API GATEWAY                                  │
│  ① Auth (JWT validation / API key check)                      │
│  ② Rate Limiting (100 req/min per user)                       │
│  ③ SSL Termination                                             │
│  ④ Request routing (/users → user-service, /orders → order)  │
│  ⑤ Request/Response transformation (REST → gRPC)             │
│  ⑥ Circuit breaking (stop routing to unhealthy service)      │
│  ⑦ Observability (logging, metrics, distributed tracing)     │
└────────────────────────────────────────────────────────────────┘
        │               │               │
  User Service     Order Service    Product Service
```

---

## 4. Deep Technical Explanation

### REST vs GraphQL vs gRPC

| Feature | REST | GraphQL | gRPC |
|---------|------|---------|------|
| Protocol | HTTP 1.1/2 | HTTP 1.1/2 | HTTP/2 |
| Format | JSON/XML | JSON | Protobuf (binary) |
| Endpoint count | Many (/users, /orders) | One (/graphql) | Many RPC methods |
| Caching | Easy (GET = cacheable) | Hard (all POST) | Hard |
| Versioning | URL (/v1/users) | Field deprecation | Service versioning |
| Type safety | Loose (JSON) | Schema-typed | Strict (proto file) |
| Browser support | Native | Native | Limited (grpc-web) |
| Streaming | No (SSE hack) | Subscriptions | Native |
| Speed | Medium | Medium | Fastest |
| **Use for** | Public APIs | BFF, mobile | Internal services |

### REST API Design Best Practices

**Versioning strategies:**
1. URL path: `/v1/users`, `/v2/users` — most common, explicit
2. Header: `Accept: application/vnd.myapi.v2+json` — cleaner URLs, harder to test
3. Query param: `/users?version=2` — simplest, not RESTful purist choice

**Pagination:**
```
# Offset-based (simple, but drift and performance issues)
GET /users?limit=20&offset=40
Problem: If a record is inserted before offset, you skip/duplicate records

# Cursor-based (stable, recommended)
GET /users?limit=20&cursor=eyJpZCI6NDJ9
→ cursor is base64({"id": 42}) — next page starts after id=42
No drift; consistent even if records inserted/deleted

# Keyset-based (most performant for large datasets)
GET /orders?after_id=12345&limit=20
Uses indexed column directly — O(log N) query, not O(offset) full scan
```

**Idempotency keys:**
- For non-idempotent POST operations (payments, order creation)
- Client sends: `Idempotency-Key: uuid-v4`
- Server checks if key seen before → return same response without re-executing
- Prevents duplicate charges from network retries

**Error responses:**
```json
{
  "error": {
    "code":    "VALIDATION_ERROR",
    "message": "Email address is invalid",
    "field":   "email",
    "status":  422
  }
}
```

### API Gateway Responsibilities
1. **Authentication:** Validate JWT, API keys, OAuth2 tokens before reaching microservices
2. **Rate limiting:** Token bucket/sliding window per client/endpoint
3. **SSL termination:** Decrypt HTTPS at gateway; internal services use HTTP
4. **Request routing:** Based on URL, headers, body content
5. **Protocol translation:** REST → gRPC, REST → message queue
6. **Circuit breaking:** If a downstream service fails repeatedly, stop forwarding requests (fail fast)
7. **Request/Response transformation:** Add headers, rewrite URLs, filter sensitive fields
8. **Observability:** Log all requests with trace IDs, emit metrics, Prometheus scraping

### API Gateway Tools

| Tool | Type | Best For |
|------|------|----------|
| AWS API Gateway | Managed (AWS) | Serverless, Lambda, AWS-native |
| Kong | Open-source / Enterprise | Plugin ecosystem, Kubernetes |
| NGINX | Reverse proxy | High performance, custom logic |
| Traefik | Open-source | Kubernetes, auto-discovery |
| Cloudflare Workers | Edge | Global, low-latency auth/routing |

---

## 5. Code Example

```php
// REST API — Cursor-based pagination
class UserController extends Controller {
    public function index(Request $request): JsonResponse {
        $limit  = min($request->integer('limit', 20), 100);
        $cursor = $request->string('cursor');
        
        $query = User::orderBy('id');
        
        if ($cursor) {
            $cursorData = json_decode(base64_decode($cursor), true);
            $query->where('id', '>', $cursorData['last_id']);
        }
        
        $users = $query->limit($limit + 1)->get();  // Fetch +1 to detect next page
        
        $hasMore   = $users->count() > $limit;
        $items     = $hasMore ? $users->slice(0, $limit) : $users;
        $nextCursor = $hasMore
            ? base64_encode(json_encode(['last_id' => $items->last()->id]))
            : null;
        
        return response()->json([
            'data'        => UserResource::collection($items),
            'meta'        => ['limit' => $limit, 'has_more' => $hasMore],
            'next_cursor' => $nextCursor,
        ]);
    }
}
```

```php
// Idempotent payment endpoint
class PaymentController extends Controller {
    public function charge(Request $request): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');
        
        if (!$idempotencyKey) {
            return response()->json(['error' => 'Idempotency-Key header required'], 422);
        }
        
        // Check if this key was already processed
        $cached = Cache::get("idempotency:{$idempotencyKey}");
        if ($cached) {
            return response()->json(json_decode($cached), 200);
        }
        
        // Process payment
        $result = $this->paymentService->charge([
            'amount'   => $request->integer('amount'),
            'currency' => $request->string('currency'),
            'user_id'  => auth()->id(),
        ]);
        
        // Cache result for 24 hours
        $response = ['transaction_id' => $result->id, 'status' => 'charged'];
        Cache::put("idempotency:{$idempotencyKey}", json_encode($response), 86400);
        
        return response()->json($response, 201);
    }
}
```

---

## 6. Trade-offs

| Concern | REST | GraphQL | gRPC |
|---------|------|---------|------|
| Learning curve | Low | Medium | High |
| Tooling | Excellent | Good | Limited (browser) |
| Performance | Good | Good | Excellent |
| Caching | Easy | Hard | Hard |
| Public API suitability | Best | Good | Poor |
| Mobile BFF | Over-fetching risk | Excellent | Good |
| Microservice comms | Common | Uncommon | Excellent |

---

## 7. Interview Q&A

**Q1: When would you use GraphQL instead of REST?**
> Use GraphQL when: (1) Mobile clients need to minimize data transferred — GraphQL lets clients request only needed fields; (2) You have a Backend-for-Frontend (BFF) pattern where different clients need different shapes of the same data; (3) Your data is highly relational and requires multiple REST calls to assemble (N+1 roundtrips). Avoid GraphQL for public APIs (caching is difficult — all requests are POST), simple CRUD operations, or when HTTP caching is critical.

**Q2: What is cursor-based pagination and why is it better than offset?**
> Offset pagination (`LIMIT 20 OFFSET 40`) suffers from: (1) data drift — if a record is inserted on page 1, all pages shift and you see duplicates/gaps; (2) performance — `OFFSET 10000` requires scanning 10,000 rows before returning 20. Cursor pagination uses a stable pointer (last seen ID or timestamp). `WHERE id > last_id LIMIT 20` is an indexed range scan — O(log N) regardless of page depth. Consistent even if records are inserted. Instagram and Twitter feeds use cursor pagination.

**Q3: What does an API Gateway do that a reverse proxy doesn't?**
> A reverse proxy (NGINX) handles basic routing, load balancing, and SSL termination. An API Gateway adds: API-level authentication (JWT validation, API key management), rate limiting per client/endpoint, API versioning, request transformation, developer portal/documentation, analytics per API endpoint, circuit breaking per downstream service, and sometimes monetization/quota management. AWS API Gateway and Kong are purpose-built API gateways; NGINX is a general reverse proxy that can simulate some gateway functions with plugins.

**Q4: What is an idempotency key and when do you need one?**
> An idempotency key is a unique token the client includes in requests for non-idempotent operations (payments, order creation). If the client retries due to a network timeout, the server detects the same key and returns the same response without re-executing — preventing duplicate charges. Best practice: client generates a UUID v4 per logical operation; server stores key + response for 24-48 hours. Required for any operation where "did it succeed?" is ambiguous after a network error.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ REST: public APIs, caching; gRPC: internal microservices        │
│ ✓ GraphQL: mobile BFF, complex relational data                    │
│ ✓ Cursor pagination > offset pagination for large datasets        │
│ ✓ Idempotency keys prevent duplicate charges on retries           │
│ ✓ API Gateway: auth + rate limiting + routing + observability     │
│ ✓ URL versioning (/v1/) is the most practical REST version strategy│
└────────────────────────────────────────────────────────────────────┘
```
