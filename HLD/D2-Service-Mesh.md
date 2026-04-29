# D2 — Service Mesh

> **Section:** Architecture Patterns | **Level:** Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** A service mesh is like a network of smart highways between microservices. Instead of each service handling its own traffic management, security, and monitoring, the mesh handles it automatically.

**Technical:** A service mesh is a dedicated infrastructure layer for service-to-service communication. It uses sidecar proxies (e.g., Envoy) deployed alongside each service to intercept all network traffic, providing: mutual TLS (mTLS), circuit breaking, retries, load balancing, distributed tracing, and traffic policies — without changing application code.

---

## 2. Real-World Analogy

**Air Traffic Control vs. Pilot Navigation:**
Without service mesh: each pilot navigates independently, handles weather, talks to other planes.
With service mesh: air traffic control handles routing, weather alerts, collision avoidance — pilots just fly their route.

The "sidecar" is like a co-pilot who handles all radio communications while the main pilot focuses on flying the plane.

---

## 3. Visual Diagram

```
WITHOUT SERVICE MESH — each service handles its own cross-cutting concerns:
ServiceA code: {business logic + auth + retry + circuit breaker + metrics + tracing}
ServiceB code: {business logic + auth + retry + circuit breaker + metrics + tracing}
→ Duplicated boilerplate, different implementations

WITH SERVICE MESH (sidecar pattern):
┌────────────────────────────────────┐
│  Pod: ServiceA                     │
│  ┌──────────────┐ ┌─────────────┐ │
│  │ ServiceA app │←│ Envoy Proxy │ │  ← sidecar
│  │ (just biz    │→│ (handles:   │ │
│  │  logic)      │ │  mTLS, LB,  │ │
│  └──────────────┘ │  tracing,   │ │
│                   │  CB, retry) │ │
│                   └─────────────┘ │
└────────────────────────────────────┘
                ↕ encrypted mTLS
┌────────────────────────────────────┐
│  Pod: ServiceB                     │
│  ┌──────────────┐ ┌─────────────┐ │
│  │ ServiceB app │←│ Envoy Proxy │ │
│  └──────────────┘ └─────────────┘ │
└────────────────────────────────────┘

CONTROL PLANE (Istio):
Istiod ──→ push config to all Envoy sidecars
       ← receive metrics from all Envoy sidecars
```

---

## 4. Deep Technical Explanation

### Components
- **Data plane:** Sidecar proxies (Envoy) — intercept and manage all service traffic
- **Control plane:** Centralized configuration (Istio's Istiod) — pushes routing rules, policies to all sidecars

### Features Provided
1. **mTLS:** All service-to-service traffic encrypted and authenticated automatically
2. **Load balancing:** L7 (HTTP/gRPC) aware — round-robin, least request, consistent hash
3. **Circuit breaking:** Per-upstream configuration, no code changes needed
4. **Retries:** Automatic retry with backoff for 5xx responses
5. **Traffic splitting:** Route 10% to new version, 90% to old (A/B testing, canary deploy)
6. **Distributed tracing:** Envoy adds trace headers → Jaeger/Zipkin collects spans
7. **Observability:** Per-service metrics (request rate, error rate, latency) → Prometheus/Grafana

### Tools
| Tool | Notes |
|------|-------|
| **Istio** | Most features, most complex. Control plane = Istiod. Uses Envoy sidecar |
| **Linkerd** | Lightweight, Rust-based proxy. Simpler than Istio |
| **Consul Connect** | HashiCorp Consul's service mesh |
| **AWS App Mesh** | Managed AWS service mesh (Envoy-based) |
| **Cilium** | eBPF-based, no sidecar needed (kernel-level) |

---

## 5. Code Example

```yaml
# Istio VirtualService — traffic splitting (canary deploy)
apiVersion: networking.istio.io/v1beta1
kind: VirtualService
metadata:
  name: user-service
spec:
  http:
  - route:
    - destination:
        host: user-service
        subset: v1
      weight: 90
    - destination:
        host: user-service
        subset: v2
      weight: 10  # 10% traffic to new version
---
# Circuit breaking via DestinationRule
apiVersion: networking.istio.io/v1beta1
kind: DestinationRule
metadata:
  name: user-service
spec:
  host: user-service
  trafficPolicy:
    connectionPool:
      tcp:
        maxConnections: 100
    outlierDetection:
      consecutive5xxErrors: 5
      interval: 10s
      baseEjectionTime: 30s  # Circuit breaker
```

---

## 7. Interview Q&A

**Q1: What problem does a service mesh solve that wasn't solved before?**
> In microservices, each service needs retries, circuit breaking, mTLS, observability. Without a mesh, every service implements these in its own SDK — inconsistently, in different languages, with different behavior. A service mesh moves these concerns to the sidecar proxy — infrastructure layer rather than application layer. Services just make plain HTTP/gRPC calls; the sidecar handles encryption, retries, circuit breaking. Uniform policy enforcement across all services regardless of language.

**Q2: What are the downsides of a service mesh?**
> (1) Latency: every request goes through two sidecar proxies (caller + callee) — adds 1-5ms per hop. (2) Complexity: Istio is notoriously complex to configure and debug. (3) Resource overhead: each sidecar proxy consumes ~50MB RAM — significant in large clusters. (4) Debugging difficulty: when something breaks, was it the app or the proxy? (5) Learning curve: steep for teams new to service mesh. Consider whether you actually need it — many problems can be solved with simpler approaches.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Sidecar proxy (Envoy) handles: mTLS, LB, CB, tracing, retries  │
│ ✓ App code focuses on business logic only                         │
│ ✓ Control plane (Istio) pushes config to all sidecars             │
│ ✓ Traffic splitting = canary deploys without code changes         │
│ ✓ Downside: latency per hop, operational complexity, resource use │
│ ✓ Linkerd is simpler; Cilium uses eBPF (no sidecar overhead)     │
└────────────────────────────────────────────────────────────────────┘
```
