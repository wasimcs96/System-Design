# D1 — Microservices vs Monolith

> **Section:** Architecture Patterns | **Level:** Beginner → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** A monolith is one big application with everything in it. Microservices is splitting that application into many small, independent services that communicate over a network.

**Technical:** A monolithic architecture packages all components (UI, business logic, data access) into a single deployable unit. A microservices architecture decomposes an application into small, autonomous services, each owning its domain, data, and deployment lifecycle. Services communicate via APIs or events.

---

## 2. Real-World Analogy

**Monolith = Swiss Army Knife.** One tool with everything — convenient, simple, always at hand. But if the blade breaks, the whole tool is unusable. Hard to upgrade one part without the whole.

**Microservices = a kitchen with specialized tools.** Separate knife, blender, oven, dishwasher — each does its job independently. If the dishwasher breaks, you can still cook. You can upgrade each appliance independently. But coordinating a meal requires more effort.

---

## 3. Visual Diagram

```
MONOLITH:
┌─────────────────────────────────────────────────────────┐
│                   Single Process                        │
│  ┌──────────┐  ┌──────────┐  ┌───────────┐  ┌───────┐ │
│  │   User   │  │  Order   │  │  Payment  │  │ Email │ │
│  │ Module   │  │ Module   │  │  Module   │  │ Module│ │
│  └──────────┘  └──────────┘  └───────────┘  └───────┘ │
│                                                         │
│         Single Database         Single Deploy           │
└─────────────────────────────────────────────────────────┘

MICROSERVICES:
User Service ──→ owns Users DB
     ↕ REST/gRPC/Events
Order Service ──→ owns Orders DB
     ↕
Payment Service ──→ owns Payments DB
     ↕
Email Service (async, subscribes to events)

Each: separate codebase, separate deploy, separate scale
```

---

## 4. Deep Technical Explanation

### Monolith Strengths and Weaknesses

**Strengths:**
- Simple development (one codebase, one IDE, one deploy)
- Low latency (function calls, not network calls)
- Easy testing (run the whole app)
- No distributed system complexity (no network partitions, no eventual consistency)
- Great for early-stage products (under-engineering is valuable)

**Weaknesses:**
- Deployment: changing 1 line requires deploying the entire application
- Scale: must scale entire app even if only one module is bottlenecked
- Team: all developers work in same codebase → merge conflicts, coordination
- Technology: stuck with one language/framework
- Reliability: one bug can crash everything

### Microservices Strengths and Weaknesses

**Strengths:**
- Independent deployment: deploy UserService without touching OrderService
- Independent scaling: scale PaymentService 10x without scaling UserService
- Technology heterogeneity: use Python for ML, Go for high-throughput, PHP for web
- Team autonomy: each team owns a service end-to-end
- Fault isolation: PaymentService crash doesn't take down UserService

**Weaknesses:**
- Distributed system complexity: network latency, timeouts, partial failures
- Data consistency: no cross-service ACID transactions (use Sagas)
- Operational overhead: many services to deploy, monitor, version
- Service discovery, load balancing, circuit breaking needed
- Testing harder: need service stubs/contracts for integration tests

### When to Choose Which

| Factor | Choose Monolith | Choose Microservices |
|--------|----------------|---------------------|
| Team size | < 5 engineers | > 20 engineers (Conway's Law) |
| Stage | Early startup | Scaled product |
| Complexity | Simple domain | Complex domain with clear boundaries |
| Scale needs | Uniform | Heterogeneous per component |
| Deployment | Low frequency | High frequency (CD per service) |

### Strangler Fig Pattern — Migrating Monolith to Microservices
1. Keep monolith running
2. Extract one bounded context (e.g., Payment) as a new service
3. Route payment traffic to new service (via facade/proxy)
4. Remove payment code from monolith
5. Repeat for next bounded context

---

## 5. Code Example

```php
// MONOLITH — OrderService calls UserService directly (in-process)
class OrderService {
    public function createOrder(int $userId, array $items): Order {
        // Direct function call — microseconds, no network
        $user     = UserRepository::find($userId);
        $order    = new Order($user, $items);
        
        // Direct in-process calls
        PaymentProcessor::charge($user->card, $order->total);
        EmailService::sendConfirmation($user->email, $order);
        InventoryManager::reserve($items);
        
        return $order;
    }
}
```

```php
// MICROSERVICES — OrderService calls UserService over HTTP
class OrderService {
    public function createOrder(int $userId, array $items): array {
        // HTTP call — 5-50ms, can fail, needs timeout, retry, circuit breaker
        $user = $this->userServiceClient->getUser($userId);  // gRPC/REST call
        
        // Saga pattern — each step is a separate service call
        $paymentRef = $this->paymentService->charge($user['payment_method_id'], $this->total($items));
        
        try {
            $this->inventoryService->reserve($items);
        } catch (InventoryException $e) {
            // Compensate: refund payment
            $this->paymentService->refund($paymentRef);
            throw $e;
        }
        
        // Async event — email service subscribes to Kafka
        $this->eventBus->publish('order.created', ['order_id' => $orderId, 'user_id' => $userId]);
        
        return ['order_id' => $orderId, 'status' => 'created'];
    }
}
```

---

## 6. Trade-offs

| Aspect | Monolith | Microservices |
|--------|---------|--------------|
| Complexity | Low | High |
| Deployment | Simple | Complex (CI/CD per service) |
| Scaling | Coarse-grained | Fine-grained |
| Latency | Low (in-process) | Higher (network) |
| Consistency | ACID transactions | Eventual (Saga) |
| Team autonomy | Low | High |
| Time to market | Faster initially | Faster at scale |

---

## 7. Interview Q&A

**Q1: When should you start with microservices vs monolith?**
> Start with a monolith. The complexity of microservices (distributed transactions, service discovery, observability) is very expensive to manage with small teams. As the product grows and you have 20+ engineers, clear domain boundaries, and specific scaling needs (e.g., checkout vs browsing scale differently), migrate specific domains to microservices incrementally using the Strangler Fig pattern. Starting with microservices for a 5-person startup is premature optimization.

**Q2: What is Conway's Law and how does it relate to microservices?**
> Conway's Law: "Organizations design systems that mirror their own communication structure." If one team owns an e-commerce system, they'll build a monolith (one system). If three teams (user team, order team, payment team) build it, they'll build three services communicating via APIs. Microservices work best when team structure matches service boundaries — each team owns one service end-to-end. This is the "Inverse Conway Maneuver" — reorganize teams to drive the desired architecture.

**Q3: What is the Strangler Fig pattern?**
> A safe incremental migration from monolith to microservices. Named after the strangler fig plant that grows around a tree, eventually replacing it. Process: (1) identify a bounded context (e.g., payments); (2) build new payment microservice with same interface; (3) route traffic to new service via a facade (reverse proxy); (4) remove payment code from monolith; (5) repeat. The monolith gradually shrinks as microservices grow. Never do a "big bang" rewrite.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Start with monolith — add microservices as you scale            │
│ ✓ Microservices: independent deploy, scale, fault isolation       │
│ ✓ Microservices cost: network latency, distributed transactions   │
│ ✓ Conway's Law: team structure determines architecture            │
│ ✓ Strangler Fig: incremental migration, never big-bang rewrite    │
│ ✓ Each microservice owns its data — no shared databases           │
└────────────────────────────────────────────────────────────────────┘
```
