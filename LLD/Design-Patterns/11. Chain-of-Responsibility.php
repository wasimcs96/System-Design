<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║         DESIGN PATTERN #11 — CHAIN OF RESPONSIBILITY             ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★★ (HTTP middleware, approval flows)           ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: A request must pass through a series of processing      │
 * │ steps. Each step either:                                         │
 * │   (a) handles the request and STOPS the chain, OR               │
 * │   (b) passes it to the NEXT handler                              │
 * │                                                                  │
 * │ The sender does NOT know which handler will process it.          │
 * │                                                                  │
 * │ Example 1 — Expense Approval:                                    │
 * │   ₹500 → Employee can approve                                   │
 * │   ₹5000 → needs Manager approval                                │
 * │   ₹25000 → needs Director approval                              │
 * │   ₹100000 → needs CEO approval                                  │
 * │                                                                  │
 * │ Example 2 — HTTP Request Pipeline:                               │
 * │   Request → RateLimit → Auth → Validate → Log → AppHandler      │
 * │   Each middleware can stop (reject) or forward the request.      │
 * │                                                                  │
 * │ KEY INSIGHT: Handlers are decoupled — easy to add/remove/reorder│
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Request ──► [Handler1] ──► [Handler2] ──► [Handler3] ──► null  │
 * │               handles?      handles?       handles?              │
 * │               NO→next       YES→stop       (never reached)       │
 * │                                                                   │
 * │  AbstractHandler                                                  │
 * │  ├─ next: ?Handler                                               │
 * │  ├─ setNext(Handler): Handler  ← returns next (for chaining)    │
 * │  └─ handle(request): mixed     ← abstract in subclasses         │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE CHAIN OF RESPONSIBILITY              │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Handler interface (setNext + handle)             │
 * │ STEP 2: Create AbstractHandler — stores next, provides default  │
 * │         passToNext() behavior                                    │
 * │ STEP 3: Create ConcreteHandlers — each checks if it can handle; │
 * │         if yes, handles; if no, calls passToNext()               │
 * │ STEP 4: Chain handlers: $h1->setNext($h2)->setNext($h3)         │
 * │ STEP 5: Send request to first handler in chain                   │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DRY RUN — Expense Approval                                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Request: ₹8000                                                  │
 * │  EmployeeHandler: 8000 > 1000 → pass                            │
 * │  ManagerHandler:  8000 > 5000 → pass                            │
 * │  DirectorHandler: 8000 ≤ 25000 → APPROVE ✓                     │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ─── STEP 1 & 2: Handler Interface + Abstract Handler ─────────────────────────

interface Handler
{
    public function setNext(Handler $handler): Handler;
    public function handle(array $request): ?string;
}

/**
 * AbstractHandler provides the base chaining logic.
 * Subclasses override handle() and call $this->passToNext($request)
 * when they cannot process the request.
 */
abstract class AbstractHandler implements Handler
{
    private ?Handler $next = null;

    /**
     * setNext() returns the $handler itself — allows fluent chaining:
     *   $h1->setNext($h2)->setNext($h3)->setNext($h4)
     */
    public function setNext(Handler $handler): Handler
    {
        $this->next = $handler;
        return $handler; // Return next so we can chain: a->setNext(b)->setNext(c)
    }

    /**
     * Pass to next if exists, otherwise return null (no handler found).
     */
    protected function passToNext(array $request): ?string
    {
        if ($this->next !== null) {
            return $this->next->handle($request);
        }
        return null; // End of chain, nobody handled it
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Expense Approval Chain
// ═══════════════════════════════════════════════════════════════

// STEP 3: Concrete Handlers

class EmployeeHandler extends AbstractHandler
{
    private const MAX_APPROVAL = 1000;

    public function handle(array $request): ?string
    {
        if ($request['amount'] <= self::MAX_APPROVAL) {
            return "  [Employee] APPROVED ₹{$request['amount']} — ref: {$request['description']}\n";
        }
        echo "  [Employee] ₹{$request['amount']} exceeds my limit (₹" . self::MAX_APPROVAL . ") → escalating\n";
        return $this->passToNext($request);
    }
}

class ManagerHandler extends AbstractHandler
{
    private const MAX_APPROVAL = 5000;

    public function handle(array $request): ?string
    {
        if ($request['amount'] <= self::MAX_APPROVAL) {
            return "  [Manager] APPROVED ₹{$request['amount']} — ref: {$request['description']}\n";
        }
        echo "  [Manager] ₹{$request['amount']} exceeds my limit (₹" . self::MAX_APPROVAL . ") → escalating\n";
        return $this->passToNext($request);
    }
}

class DirectorHandler extends AbstractHandler
{
    private const MAX_APPROVAL = 25000;

    public function handle(array $request): ?string
    {
        if ($request['amount'] <= self::MAX_APPROVAL) {
            return "  [Director] APPROVED ₹{$request['amount']} — ref: {$request['description']}\n";
        }
        echo "  [Director] ₹{$request['amount']} exceeds my limit (₹" . self::MAX_APPROVAL . ") → escalating\n";
        return $this->passToNext($request);
    }
}

class CEOHandler extends AbstractHandler
{
    public function handle(array $request): ?string
    {
        // CEO approves everything
        return "  [CEO] APPROVED ₹{$request['amount']} — ref: {$request['description']}\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: HTTP Request Middleware Pipeline
// ═══════════════════════════════════════════════════════════════

class RateLimitHandler extends AbstractHandler
{
    private static array $requestLog = [];

    public function handle(array $request): ?string
    {
        $ip = $request['ip'] ?? '0.0.0.0';
        self::$requestLog[$ip] = (self::$requestLog[$ip] ?? 0) + 1;

        if (self::$requestLog[$ip] > 100) {
            return "  [RateLimit] 429 Too Many Requests from $ip\n";
        }
        echo "  [RateLimit] ✓ IP $ip — request #{$self::$requestLog[$ip]}\n";
        return $this->passToNext($request);
    }
}

class AuthenticationHandler extends AbstractHandler
{
    private array $validTokens = ['token_abc', 'token_xyz', 'admin_token'];

    public function handle(array $request): ?string
    {
        // Public endpoints don't need auth
        if (in_array($request['path'], ['/health', '/login', '/register'], true)) {
            echo "  [Auth] Public endpoint — skipping auth\n";
            return $this->passToNext($request);
        }

        $token = $request['headers']['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        if (!in_array($token, $this->validTokens, true)) {
            return "  [Auth] 401 Unauthorized — invalid or missing token\n";
        }
        echo "  [Auth] ✓ Token valid\n";
        return $this->passToNext($request);
    }
}

class InputValidationHandler extends AbstractHandler
{
    public function handle(array $request): ?string
    {
        if ($request['method'] === 'POST') {
            $body = $request['body'] ?? [];
            if (empty($body)) {
                return "  [Validation] 400 Bad Request — empty POST body\n";
            }
        }
        echo "  [Validation] ✓ Input valid\n";
        return $this->passToNext($request);
    }
}

class LoggingHandler extends AbstractHandler
{
    public function handle(array $request): ?string
    {
        $start    = microtime(true);
        $response = $this->passToNext($request); // Forward first
        $ms       = round((microtime(true) - $start) * 1000, 2);
        echo "  [Logger] {$request['method']} {$request['path']} → {$ms}ms\n";
        return $response;
    }
}

class AppRouterHandler extends AbstractHandler
{
    public function handle(array $request): ?string
    {
        $routes = [
            'GET /api/users'    => "200 OK — [alice, bob, carol]",
            'GET /api/products' => "200 OK — [iPhone, MacBook, iPad]",
            'POST /api/orders'  => "201 Created — order_id=ORD-001",
        ];

        $key = "{$request['method']} {$request['path']}";
        if (isset($routes[$key])) {
            echo "  [Router] Routing to handler for $key\n";
            return "  [Response] {$routes[$key]}\n";
        }
        return "  [Router] 404 Not Found — {$request['path']}\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== CHAIN OF RESPONSIBILITY PATTERN DEMO ===\n\n";

// STEP 4: Build the approval chain
echo "--- Example 1: Expense Approval Chain ---\n";

$employee = new EmployeeHandler();
$manager  = new ManagerHandler();
$director = new DirectorHandler();
$ceo      = new CEOHandler();

// Chain: Employee → Manager → Director → CEO
$employee->setNext($manager)->setNext($director)->setNext($ceo);

$expenses = [
    ['amount' =>    500, 'description' => 'Taxi fare'],
    ['amount' =>   3000, 'description' => 'Team lunch'],
    ['amount' =>  12000, 'description' => 'Conference ticket'],
    ['amount' =>  80000, 'description' => 'Office equipment'],
];

foreach ($expenses as $expense) {
    echo "\n  Request: ₹{$expense['amount']} ({$expense['description']})\n";
    $result = $employee->handle($expense); // Always start from the first handler
    echo $result;
}

echo "\n--- Example 2: HTTP Middleware Pipeline ---\n";

// Build middleware pipeline: RateLimit → Auth → Validate → Log → Router
$rateLimiter  = new RateLimitHandler();
$auth         = new AuthenticationHandler();
$validator    = new InputValidationHandler();
$logger       = new LoggingHandler();
$router       = new AppRouterHandler();

$rateLimiter->setNext($auth)->setNext($validator)->setNext($logger)->setNext($router);

$requests = [
    [
        'method'  => 'GET',
        'path'    => '/health',
        'ip'      => '10.0.0.1',
        'headers' => [],
    ],
    [
        'method'  => 'GET',
        'path'    => '/api/users',
        'ip'      => '10.0.0.2',
        'headers' => ['Authorization' => 'Bearer token_abc'],
    ],
    [
        'method'  => 'GET',
        'path'    => '/api/users',
        'ip'      => '10.0.0.3',
        'headers' => [], // No auth token
    ],
    [
        'method'  => 'POST',
        'path'    => '/api/orders',
        'ip'      => '10.0.0.4',
        'headers' => ['Authorization' => 'Bearer token_xyz'],
        'body'    => ['product_id' => 42, 'qty' => 1],
    ],
];

foreach ($requests as $req) {
    echo "\n  → {$req['method']} {$req['path']}\n";
    $response = $rateLimiter->handle($req);
    echo $response;
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Chain of Responsibility pattern?                 │
 * │ A: A request passes through a chain of handlers. Each handler   │
 * │    either handles the request (and stops the chain) or passes   │
 * │    it to the next handler. Sender doesn't know who handles it.  │
 * │                                                                  │
 * │ Q2: Difference between CoR and Decorator?                        │
 * │ A: Decorator: ALL decorators process the request and add to it. │
 * │    CoR: A handler either handles OR passes (one handler stops). │
 * │    Decorator always applies ALL layers; CoR stops at first match.│
 * │                                                                  │
 * │ Q3: When should each handler pass vs stop?                       │
 * │ A: Stop: when the handler fully processes the request (approve   │
 * │    expense, reject unauthorized request, return 404).           │
 * │    Pass: when the handler partially processes OR cannot handle  │
 * │    (logging middleware always passes after logging).             │
 * │                                                                  │
 * │ Q4: What if NO handler handles the request?                      │
 * │ A: The chain returns null (or a default response). Good practice:│
 * │    have a catch-all handler at the end. Document this behavior.  │
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: Laravel Middleware Pipeline ($middleware array in Kernel).    │
 * │    Symfony's EventDispatcher (listener chains).                  │
 * │    PHP's error handler chain (set_error_handler).                │
 * │    PSR-15 Request Handlers.                                      │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Circular chains → detect with visited set or max hops limit   │
 * │ ✓ Exception in handler → handle in invoker or bubble up         │
 * │ ✓ Order matters — RateLimit MUST come before Auth in HTTP       │
 * └─────────────────────────────────────────────────────────────────┘
 */
