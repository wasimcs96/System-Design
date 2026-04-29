# D6 — Containers and Orchestration

> **Section:** Architecture Patterns | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Docker packages your app and all its dependencies into a "container" — a portable box that runs identically everywhere. Kubernetes is the manager that runs hundreds of these containers, restarting them when they fail, scaling them up when traffic grows, and routing traffic between them.

**Technical:** Containers are lightweight, portable, isolated execution environments (sharing the host OS kernel, unlike VMs). Docker images are built from layered filesystems (Union FS). Kubernetes is a container orchestration platform providing automated deployment, scaling, self-healing, service discovery, and configuration management for containerized workloads.

---

## 2. Real-World Analogy

**Docker = Shipping container.** Before containers, shipping was chaotic — different packages needed different handling. Standard shipping containers revolutionized logistics: same container, any ship, any port, any crane. Docker does this for software: same container image, any cloud, any server.

**Kubernetes = Container ship captain.** Knows how many containers to carry, where to place them for balance, automatically replaces dropped containers, and routes trucks (traffic) to the right containers.

---

## 3. Visual Diagram

```
DOCKER IMAGE LAYERS (Union FS — each layer is read-only):
Layer 5: COPY app/ /app/                ← your code (changes often)
Layer 4: RUN composer install           ← dependencies
Layer 3: RUN apt-get install php8.2     ← PHP runtime
Layer 2: FROM ubuntu:22.04              ← OS base
Layer 1: Bootfs                         ← kernel interface

Layers 1-3 cached → rebuilt only when changed → fast builds
Container = image layers (read-only) + writable layer on top

KUBERNETES ARCHITECTURE:
Control Plane (Master node):
  ┌──────────┐ ┌──────────────┐ ┌──────────┐ ┌───────────┐
  │ API      │ │  Controller  │ │Scheduler │ │   etcd    │
  │ Server   │ │  Manager     │ │          │ │ (state DB)│
  └──────────┘ └──────────────┘ └──────────┘ └───────────┘

Worker Nodes:
  ┌─────────────────────────────────────────────────┐
  │  Node 1: kubelet + kube-proxy                   │
  │  ┌───────────────┐  ┌───────────────┐           │
  │  │ Pod: app v1   │  │ Pod: app v1   │           │
  │  │ [container]   │  │ [container]   │           │
  │  │ [sidecar]     │  │ [sidecar]     │           │
  │  └───────────────┘  └───────────────┘           │
  └─────────────────────────────────────────────────┘
```

---

## 4. Deep Technical Explanation

### Docker Core Concepts
- **Image:** Immutable snapshot of filesystem + metadata (built from Dockerfile)
- **Container:** Running instance of an image (ephemeral — write layer discarded on stop)
- **Registry:** Image storage (Docker Hub, AWS ECR, GCR)
- **Dockerfile:** Instructions to build an image
- **Layer caching:** Unchanged layers reuse cache → faster builds

### Kubernetes Core Objects
| Object | Purpose |
|--------|---------|
| **Pod** | Smallest deployable unit — 1+ containers with shared network/storage |
| **Deployment** | Manages replica count + rolling updates for stateless pods |
| **StatefulSet** | Like Deployment but with stable identity + persistent storage (for DBs) |
| **Service** | Stable DNS + load-balanced access to a group of pods |
| **Ingress** | L7 HTTP routing (path-based, host-based) into the cluster |
| **ConfigMap** | Non-secret configuration (env vars, config files) |
| **Secret** | Sensitive configuration (passwords, API keys) |
| **HPA** | Horizontal Pod Autoscaler — auto-scale based on CPU/custom metrics |
| **PersistentVolume** | Durable storage that outlives pods |

### Container vs VM
| Aspect | Container | VM |
|--------|-----------|-----|
| Startup time | Milliseconds | Minutes |
| Size | MBs | GBs |
| OS | Shared host kernel | Full OS per VM |
| Isolation | Process-level | Hardware-level |
| Performance | Near-native | 5-10% overhead |
| Density | 1000+ per host | 10-50 per host |

---

## 5. Code Example

```dockerfile
# Dockerfile — PHP 8.2 application
# Stage 1: Build
FROM composer:2 AS build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist  # layer cached until lock changes
COPY . .
RUN composer dump-autoload --optimize

# Stage 2: Runtime (smaller final image)
FROM php:8.2-fpm-alpine
WORKDIR /app

# System dependencies (cached until this line changes)
RUN apk add --no-cache libpq postgresql-dev &&     docker-php-ext-install pdo_pgsql opcache

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/

# Copy app from build stage
COPY --from=build /app .

# Non-root user (security)
RUN addgroup -g 1000 appuser && adduser -u 1000 -G appuser -D appuser
USER appuser

EXPOSE 9000
CMD ["php-fpm"]
```

```yaml
# Kubernetes Deployment + HPA
apiVersion: apps/v1
kind: Deployment
metadata:
  name: php-app
spec:
  replicas: 3
  selector:
    matchLabels:
      app: php-app
  template:
    metadata:
      labels:
        app: php-app
    spec:
      containers:
      - name: php-app
        image: myrepo/php-app:v1.2.3
        ports:
        - containerPort: 9000
        env:
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: db-secret
              key: password
        resources:
          requests:
            memory: "128Mi"
            cpu: "100m"
          limits:
            memory: "256Mi"
            cpu: "500m"
        readinessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 10
---
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: php-app-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: php-app
  minReplicas: 3
  maxReplicas: 20
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70  # scale when avg CPU > 70%
```

---

## 7. Interview Q&A

**Q1: How does Kubernetes ensure zero-downtime rolling updates?**
> Configure `maxUnavailable: 0` and `maxSurge: 1` in the Deployment strategy. Kubernetes: (1) creates one new pod with v2; (2) waits until it passes readinessProbe; (3) only then terminates one old v1 pod; (4) repeats. At every point, full capacity is maintained. The readinessProbe is critical — without it, Kubernetes might route traffic to a pod before the app is ready to serve requests.

**Q2: What is the difference between a Deployment and a StatefulSet?**
> Deployment: for stateless apps. Pods are interchangeable (any pod can serve any request). Names are random (app-7f9b-x8qv). Pods can be scheduled anywhere. StatefulSet: for stateful apps (databases, Kafka, ZooKeeper). Each pod has a stable identity (kafka-0, kafka-1, kafka-2). Pods start and stop in order. Each pod gets its own PersistentVolume. Pod names are stable (even if pod is rescheduled). Use StatefulSet for Kafka brokers, Redis Cluster, Cassandra — where node identity matters for cluster membership.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Docker: image layers (cached), container = lightweight + portable│
│ ✓ Use multi-stage Dockerfile: build image ≠ runtime image         │
│ ✓ Run containers as non-root user (security)                      │
│ ✓ Kubernetes: Pod→Deployment→Service→Ingress hierarchy            │
│ ✓ HPA: auto-scale on CPU/custom metrics                           │
│ ✓ StatefulSet for databases (stable identity + persistent storage) │
│ ✓ Readiness probe = prerequisite for traffic routing              │
└────────────────────────────────────────────────────────────────────┘
```
