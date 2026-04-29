# B1 — Load Balancing

> **Section:** Core Infrastructure | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A load balancer is like a traffic cop that stands in front of your servers and decides which server should handle each incoming request, so no single server gets overwhelmed.

**Technical:** A load balancer is a component that distributes incoming network traffic across multiple backend servers to maximize throughput, minimize response time, and prevent any single server from becoming a bottleneck. It operates at Layer 4 (Transport — TCP/UDP) or Layer 7 (Application — HTTP/HTTPS).

---

## 2. Real-World Analogy

**Supermarket Checkout Lanes:**
- Multiple checkout lanes = multiple servers
- The person directing customers to lanes = load balancer
- Round-robin: "Lane 1, Lane 2, Lane 3, repeat"
- Least connections: always direct to the lane with fewest people
- IP hash: always direct the same customer to the same lane (session affinity)
- Health check: if Lane 3's cashier calls in sick, stop sending customers there

---

## 3. Visual Diagrams

```
WITHOUT LOAD BALANCER:          WITH LOAD BALANCER:
                                                 ┌──→ Server 1 (30%)
Client → Server 1 (overloaded)  Client → LB ────┼──→ Server 2 (35%)
                                                 └──→ Server 3 (35%)

LOAD BALANCER LAYERS:
┌─────────────────────────────────────────────────────────┐
│  Layer 7 (Application) Load Balancer                    │
│  - Sees HTTP headers, URL, cookies, body                │
│  - Can route: /api → API servers, /static → CDN        │
│  - SSL termination here                                 │
│  - More intelligent, more overhead                      │
├─────────────────────────────────────────────────────────┤
│  Layer 4 (Transport) Load Balancer                      │
│  - Sees IP + TCP port only                              │
│  - Faster (no HTTP parsing)                             │
│  - Cannot inspect content                               │
│  - Good for: TCP/UDP, database connections              │
└─────────────────────────────────────────────────────────┘

GLOBAL LOAD BALANCING (GeoDNS):
User in Mumbai ──→ DNS ──→ Asia-Pacific Load Balancer ──→ India Servers
User in London ──→ DNS ──→ Europe Load Balancer ────────→ EU Servers
```

---

## 4. Deep Technical Explanation

### L4 vs L7 Load Balancing

| Feature | L4 (Network/Transport) | L7 (Application) |
|---------|----------------------|-----------------|
| Sees | IP, TCP/UDP port | HTTP URL, headers, cookies |
| Speed | Faster (less parsing) | Slower (full HTTP parsing) |
| Routing | IP/port-based only | Content-based routing |
| SSL termination | No (pass-through) | Yes |
| Sticky sessions | IP-hash only | Cookie-based |
| Tools | AWS NLB, HAProxy (TCP) | AWS ALB, NGINX, Traefik |

### Load Balancing Algorithms

**1. Round-Robin:** Requests go to servers in rotation (1→2→3→1→2→3)
- ✓ Simple, equal distribution
- ✗ Ignores server capacity and current load

**2. Weighted Round-Robin:** Server with more capacity gets proportionally more traffic
- Server A (8 cores) = weight 4, Server B (2 cores) = weight 1
- 4/5 requests go to A, 1/5 to B

**3. Least Connections:** New request goes to server with fewest active connections
- Best for variable-length requests (some slow, some fast)
- ✓ Adapts to real load

**4. IP Hash:** Client IP determines server (same IP always → same server)
- Useful for session affinity without shared session store
- ✗ Uneven if many users behind same IP (NAT/proxy)

**5. Consistent Hash:** Hash of (client IP + request key) → server
- Used when backend servers are stateful caches
- Minimal remapping when servers added/removed

**6. Least Response Time:** Combines least connections + lowest latency
- More sophisticated, requires latency tracking

### Health Checks
- **Active health check:** LB sends periodic HTTP GET `/health` to each server
  - If 3 consecutive failures → mark server DOWN, stop routing
  - If 2 consecutive successes → mark server UP, resume routing
- **Passive health check:** Monitor actual traffic; if error rate > threshold → mark down

### SSL Termination
- HTTPS decrypted at load balancer
- Internal traffic is plain HTTP (within trusted network)
- Saves CPU on every app server (SSL is expensive)
- Re-encryption: LB → app server can use HTTPS for compliance (mTLS)

### Session Affinity (Sticky Sessions)
- Same client always goes to same server
- Needed when session stored on server (stateful apps)
- LB sets cookie: `AWSALB=abc123` → always routes to server abc123
- ✗ Breaks if server goes down (session lost)
- ✓ Better solution: stateless servers + Redis for sessions

---

## 5. Code Example

```nginx
# NGINX L7 Load Balancer Configuration

upstream api_servers {
    # Round-robin by default
    server api1.example.com:8080 weight=3;  # 3x traffic
    server api2.example.com:8080 weight=1;
    server api3.example.com:8080 weight=1;
    
    # Least connections algorithm
    least_conn;
    
    # Health check (NGINX Plus)
    keepalive 32;
}

upstream static_servers {
    server static1.example.com:80;
    server static2.example.com:80;
}

server {
    listen 443 ssl;
    ssl_certificate /etc/ssl/cert.pem;
    ssl_certificate_key /etc/ssl/key.pem;
    
    # Content-based routing (L7)
    location /api/ {
        proxy_pass http://api_servers;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        
        # Timeout settings
        proxy_connect_timeout 5s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
    }
    
    location /static/ {
        proxy_pass http://static_servers;
    }
}
```

```php
// Application code — get real client IP behind load balancer
function getRealClientIp(Request $request): string {
    // LB adds X-Forwarded-For header
    $forwardedFor = $request->header('X-Forwarded-For');
    
    if ($forwardedFor) {
        // May be comma-separated if multiple proxies: "client, proxy1, proxy2"
        $ips = array_map('trim', explode(',', $forwardedFor));
        return $ips[0];  // First IP is the original client
    }
    
    return $request->ip();  // Direct connection
}

// Health check endpoint — LB pings this
Route::get('/health', function() {
    return response()->json(['status' => 'healthy', 'timestamp' => now()]);
})->middleware('throttle:60,1');  // Prevent abuse of health endpoint
```

---

## 6. Trade-offs

| Algorithm | Distribution | Complexity | Best For |
|-----------|-------------|-----------|----------|
| Round-Robin | Equal | Minimal | Uniform request size |
| Weighted | Proportional | Low | Mixed server capacities |
| Least Connections | Adaptive | Medium | Variable request duration |
| IP Hash | Client-bound | Low | Stateful servers (bad idea) |
| Consistent Hash | Near-equal | High | Caching layers |

---

## 7. Interview Q&A

**Q1: What is the difference between L4 and L7 load balancers?**
> L4 load balancers operate at TCP/UDP level — they see IP and port but not HTTP content. They're faster because they don't parse HTTP. L7 load balancers operate at HTTP level — they can route based on URL paths, headers, cookies, enabling features like content-based routing (/api → API servers, /images → CDN), SSL termination, and cookie-based sticky sessions. AWS ALB is L7; AWS NLB is L4.

**Q2: What happens when a server behind a load balancer goes down?**
> The LB sends periodic health check requests. If the server fails N consecutive checks (typically 3), the LB marks it unhealthy and stops routing new requests to it. In-flight requests to the failed server may fail. Once the server recovers and passes M consecutive checks (typically 2), it's re-added. The key is health check frequency (every 10–30s) and failure threshold. In AWS ALB, you configure HealthCheckInterval, HealthyThreshold, and UnhealthyThreshold.

**Q3: How does a load balancer handle session state?**
> Two approaches: (1) Sticky sessions — LB always routes same client to same server using a cookie. Problem: if server dies, session is lost. (2) Stateless sessions — session stored in shared store (Redis). Any server handles any request, reading session from Redis. Option 2 is preferred because it enables true horizontal scaling without session loss on server failure.

**Q4: What is a single point of failure in a load balancer, and how do you fix it?**
> A single load balancer is itself a SPOF. Fix: deploy LBs in Active-Passive pair (one handles traffic, failover to standby on failure — uses Virtual IP/floating IP that switches over). Or use Active-Active pair with DNS-level load balancing. Cloud managed LBs (AWS ALB, Google Cloud LB) are inherently highly available — AWS runs ALB across multiple AZs automatically.

---

## 8. Key Takeaways

```
┌──────────────────────────────────────────────────────────────────┐
│ ✓ L4 = fast, port-based; L7 = intelligent, content-based        │
│ ✓ Least connections is best for variable-duration requests       │
│ ✓ Health checks every 10–30s; fail fast, recover conservatively  │
│ ✓ SSL termination at LB saves CPU on app servers                 │
│ ✓ Sticky sessions are an anti-pattern; use Redis instead         │
│ ✓ LB itself can be SPOF — deploy Active-Passive or managed LB   │
└──────────────────────────────────────────────────────────────────┘
```
