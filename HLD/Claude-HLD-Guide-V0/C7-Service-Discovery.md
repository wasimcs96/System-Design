# C7 — Service Discovery

> **Section:** Distributed Systems Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Service discovery automatically finds where a service is running (its IP and port). In modern systems where services start/stop frequently (containers, auto-scaling), hardcoding IPs is impossible — you need a phone directory that's always up to date.

**Technical:** Service discovery is the mechanism by which services in a distributed system locate each other. Services register themselves with a service registry on startup and deregister on shutdown. Clients query the registry to find available instances.

---

## 2. Real-World Analogy

**DNS for internal services:**
- Without service discovery: hardcoded IPs → breaks on restart
- DNS: domain → IP (but slow TTL, no health check integration)
- Service registry: `user-service` → `[10.0.1.15:8080, 10.0.1.16:8080]` (dynamic, health-aware)

Like a hotel switchboard — you ask for "Room Service" and the switchboard finds which line is available right now, not a hardcoded extension that might be busy.

---

## 3. Visual Diagram

```
CLIENT-SIDE DISCOVERY:
Client ──→ Service Registry (query: "where is user-service?")
        ←── [10.0.1.15:8080, 10.0.1.16:8080]
Client selects instance (load balance) ──→ 10.0.1.15:8080

SERVER-SIDE DISCOVERY:
Client ──→ Load Balancer / API Gateway
           └──→ Service Registry (query: "user-service instances?")
           └──→ Route to healthy instance
Client doesn't know about registry

SERVICE REGISTRATION LIFECYCLE:
Service starts ──→ Register: {name, IP, port, health endpoint}
Service runs   ──→ Send heartbeats every 10s (or registry checks health endpoint)
Service stops  ──→ Deregister (or TTL expires → auto-removed on crash)

KUBERNETES SERVICE DISCOVERY:
Pod A calls "http://user-service:8080"
    ↓
Kubernetes Service (ClusterIP) → kube-proxy → routes to healthy pods
Kubernetes DNS: user-service.default.svc.cluster.local → ClusterIP
```

---

## 4. Deep Technical Explanation

### Client-Side vs Server-Side Discovery

| Aspect | Client-Side | Server-Side |
|--------|-------------|-------------|
| Load balancing | Client chooses | LB chooses |
| Registry coupling | Client must know registry | LB handles it |
| Protocol support | Client-specific | Any protocol |
| Flexibility | High | Limited by LB |
| **Tools** | Eureka + Ribbon | AWS ALB, Kubernetes, Consul + Envoy |

### Service Registry Tools

| Tool | Type | Features |
|------|------|---------|
| **Consul** | Registry + health check + KV + service mesh | Full-featured |
| **Etcd** | KV store used for registry (Kubernetes uses it) | Simple, consistent |
| **Eureka** | Netflix OSS, Java-focused | Client-side, eventually consistent |
| **Zookeeper** | CP, strong consistency | Complex, heavyweight |
| **Kubernetes DNS** | Built-in | Automatic for K8s workloads |

### Health Checks
Services must be healthy to receive traffic:
1. **Active health check:** Registry periodically calls `/health` endpoint
2. **Passive health check:** Registry monitors heartbeats from service
3. **TCP health check:** Registry checks if port is open
4. Unhealthy instances removed from registry within seconds (fast TTL)

### Kubernetes Service Discovery
In Kubernetes, service discovery is built-in:
- **Services:** stable DNS name → load balanced to pods
- **ClusterIP:** service accessible within cluster
- **Headless service:** DNS returns pod IPs directly (for stateful sets)
- **Envoy/Istio:** service mesh provides discovery + circuit breaking + mTLS

---

## 5. Code Example

```php
// Consul-based service discovery client
class ServiceDiscovery {
    private string $consulUrl;
    
    public function getServiceInstances(string $serviceName): array {
        $response = Http::get("{$this->consulUrl}/v1/health/service/{$serviceName}", [
            'passing' => true,  // only healthy instances
        ]);
        
        return collect($response->json())
            ->map(fn($node) => [
                'address' => $node['Service']['Address'] ?: $node['Node']['Address'],
                'port'    => $node['Service']['Port'],
                'url'     => "http://{$node['Service']['Address']}:{$node['Service']['Port']}",
            ])
            ->toArray();
    }
    
    public function register(string $name, string $ip, int $port): void {
        Http::put("{$this->consulUrl}/v1/agent/service/register", [
            'ID'      => "{$name}-{$ip}-{$port}",
            'Name'    => $name,
            'Address' => $ip,
            'Port'    => $port,
            'Check'   => [
                'HTTP'     => "http://{$ip}:{$port}/health",
                'Interval' => '10s',
                'Timeout'  => '3s',
            ],
        ]);
    }
}

// Load-balanced client with service discovery
class UserServiceClient {
    private ServiceDiscovery $discovery;
    
    public function getUser(int $userId): array {
        $instances = $this->discovery->getServiceInstances('user-service');
        
        if (empty($instances)) {
            throw new ServiceUnavailableException('user-service has no healthy instances');
        }
        
        // Round-robin selection
        $instance = $instances[array_rand($instances)];
        
        return Http::get("{$instance['url']}/users/{$userId}")->json();
    }
}
```

---

## 6. Trade-offs

| Approach | Consistency | Availability | Complexity | Best For |
|----------|-------------|-------------|-----------|----------|
| Hardcoded IPs | N/A | Low | None | Dev only |
| DNS-based | Eventual | High | Low | Simple cloud |
| Consul | CP/Eventual | High | Medium | Multi-cloud |
| Kubernetes DNS | Eventual | High | Low | K8s workloads |
| Istio/Envoy | Strong | High | High | Service mesh |

---

## 7. Interview Q&A

**Q1: How does Kubernetes service discovery work?**
> Kubernetes creates a virtual IP (ClusterIP) for each Service resource. kube-proxy programs iptables/IPVS rules on every node to route traffic destined for ClusterIP to actual pod IPs. Kubernetes DNS (CoreDNS) resolves `user-service.default.svc.cluster.local` to the ClusterIP. When pods are added/removed, kube-proxy updates routing rules. Services abstract away individual pod IPs and provide stable endpoint.

**Q2: What is the difference between client-side and server-side service discovery?**
> Client-side: the client queries the registry directly and performs its own load balancing (Netflix Eureka + Ribbon). Client must be registry-aware. Server-side: the client sends requests to a load balancer, which queries the registry and routes to a healthy instance (AWS ALB + Route 53, Kubernetes Service). Client is decoupled from registry. Server-side is simpler for clients and supports any protocol; client-side gives more control.

**Q3: What happens when a service crashes without deregistering?**
> The registry relies on either: (1) active health checks — registry polls `/health` endpoint, marks service DOWN after 3 consecutive failures (~30s), then removes it; (2) heartbeat TTL — service sends heartbeat every N seconds; if no heartbeat for TTL seconds, registry removes it (e.g., Consul default TTL = 30s). Traffic may briefly route to crashed instances until health check removes them — implement retries in clients.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Service registry = dynamic DNS for microservices                │
│ ✓ Client-side: client queries registry; Server-side: LB handles it│
│ ✓ Health checks remove failed instances within seconds            │
│ ✓ Kubernetes: built-in discovery via Services + CoreDNS           │
│ ✓ Consul: full-featured registry + health check + KV + mesh       │
│ ✓ TTL-based deregistration handles crashes without graceful stop  │
└────────────────────────────────────────────────────────────────────┘
```
