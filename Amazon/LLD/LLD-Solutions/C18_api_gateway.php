<?php
/**
 * C18. API GATEWAY
 * ============================================================
 * PROBLEM: Single entry point for microservices. Handle auth,
 * rate limiting, routing, request logging, response transformation.
 *
 * PATTERNS:
 *  - Chain of Responsibility : Middleware pipeline
 *  - Strategy                : RouterStrategy
 *  - Command                 : Request/Response encapsulation
 * ============================================================
 */

// ─── HTTP Request/Response ────────────────────────────────────
class HttpRequest {
    public readonly string $requestId;
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public array           $headers = [],
        public array           $body    = [],
        public array           $query   = []
    ) {
        $this->requestId = uniqid('REQ-');
    }

    public function getHeader(string $key, string $default = ''): string {
        return $this->headers[strtolower($key)] ?? $default;
    }
}

class HttpResponse {
    public function __construct(
        public int    $statusCode = 200,
        public array  $body       = [],
        public array  $headers    = []
    ) {}

    public function withHeader(string $key, string $value): self {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    public function json(): string { return json_encode($this->body, JSON_PRETTY_PRINT); }
}

// ─── Middleware Interface ─────────────────────────────────────
interface Middleware {
    public function handle(HttpRequest $request, \Closure $next): HttpResponse;
}

// ─── Auth Middleware ──────────────────────────────────────────
class AuthMiddleware implements Middleware {
    private array $validTokens = ['Bearer token-alice', 'Bearer token-admin'];

    public function handle(HttpRequest $request, \Closure $next): HttpResponse {
        $auth = $request->getHeader('authorization');
        if (empty($auth) || !in_array($auth, $this->validTokens)) {
            echo "  [Auth] ✗ Unauthorized: {$request->path}\n";
            return new HttpResponse(401, ['error' => 'Unauthorized']);
        }
        echo "  [Auth] ✓ {$auth}\n";
        return $next($request);
    }
}

// ─── Rate Limit Middleware ────────────────────────────────────
class RateLimitMiddleware implements Middleware {
    /** @var array<string,int[]> userId → timestamps */
    private array $windows = [];
    private int   $maxReqs = 5;
    private int   $window  = 60;

    public function handle(HttpRequest $request, \Closure $next): HttpResponse {
        $userId = $request->getHeader('authorization', 'anonymous');
        $now    = time();
        // Sliding window
        $this->windows[$userId] = array_values(array_filter($this->windows[$userId] ?? [], fn($t) => $now - $t < $this->window));

        if (count($this->windows[$userId]) >= $this->maxReqs) {
            echo "  [RateLimit] ✗ Too many requests\n";
            return new HttpResponse(429, ['error' => 'Rate limit exceeded']);
        }

        $this->windows[$userId][] = $now;
        $remaining = $this->maxReqs - count($this->windows[$userId]);
        echo "  [RateLimit] ✓ remaining={$remaining}\n";
        $resp = $next($request);
        return $resp->withHeader('X-RateLimit-Remaining', (string)$remaining);
    }
}

// ─── Logging Middleware ───────────────────────────────────────
class LoggingMiddleware implements Middleware {
    public function handle(HttpRequest $request, \Closure $next): HttpResponse {
        $start = microtime(true);
        echo "  [Log] → {$request->method} {$request->path} [{$request->requestId}]\n";
        $resp = $next($request);
        $ms   = round((microtime(true) - $start) * 1000, 2);
        echo "  [Log] ← {$resp->statusCode} ({$ms}ms)\n";
        return $resp;
    }
}

// ─── Router ───────────────────────────────────────────────────
class Router {
    /** @var array<string,\Closure> "METHOD /path" → handler */
    private array $routes = [];

    public function register(string $method, string $path, \Closure $handler): void {
        $this->routes["{$method} {$path}"] = $handler;
    }

    public function dispatch(HttpRequest $req): HttpResponse {
        $key = "{$req->method} {$req->path}";
        $handler = $this->routes[$key] ?? null;
        if (!$handler) {
            return new HttpResponse(404, ['error' => "Route not found: {$key}"]);
        }
        return $handler($req);
    }
}

// ─── API Gateway (Middleware Pipeline) ────────────────────────
class ApiGateway {
    /** @var Middleware[] */
    private array $middlewares = [];

    public function __construct(private Router $router) {}

    public function use(Middleware $m): void { $this->middlewares[] = $m; }

    public function handle(HttpRequest $request): HttpResponse {
        $pipeline = $this->buildPipeline(0, $request);
        return $pipeline($request);
    }

    private function buildPipeline(int $index, HttpRequest $req): \Closure {
        if ($index >= count($this->middlewares)) {
            return fn($r) => $this->router->dispatch($r);
        }
        $middleware = $this->middlewares[$index];
        $next       = $this->buildPipeline($index + 1, $req);
        return fn($r) => $middleware->handle($r, $next);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C18. API Gateway ===\n\n";

$router = new Router();
$router->register('GET',  '/products',        fn($r) => new HttpResponse(200, ['products' => ['p1','p2']]));
$router->register('GET',  '/orders',          fn($r) => new HttpResponse(200, ['orders'   => ['o1']]));
$router->register('POST', '/orders',          fn($r) => new HttpResponse(201, ['orderId'  => uniqid()]));

$gateway = new ApiGateway($router);
$gateway->use(new LoggingMiddleware());
$gateway->use(new AuthMiddleware());
$gateway->use(new RateLimitMiddleware());

$requests = [
    ['desc' => 'No auth',      'req' => new HttpRequest('GET', '/products', [])],
    ['desc' => 'Valid auth',   'req' => new HttpRequest('GET', '/products', ['authorization' => 'Bearer token-alice'])],
    ['desc' => 'Create order', 'req' => new HttpRequest('POST', '/orders',  ['authorization' => 'Bearer token-alice'])],
    ['desc' => 'Not found',    'req' => new HttpRequest('GET',  '/unknown', ['authorization' => 'Bearer token-alice'])],
];

foreach ($requests as $test) {
    echo "\n--- {$test['desc']} ---\n";
    $resp = $gateway->handle($test['req']);
    echo "  Status: {$resp->statusCode}\n";
}
