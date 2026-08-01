<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #7 — DECORATOR                       ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Structural Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You want to add behavior to individual objects, not all │
 * │ objects of that class. Subclassing leads to an explosion of      │
 * │ subclasses for every combination:                                │
 * │                                                                  │
 * │  Coffee + Milk → MilkCoffee                                      │
 * │  Coffee + Sugar → SugarCoffee                                    │
 * │  Coffee + Milk + Sugar → MilkSugarCoffee    (grows fast!)        │
 * │  Coffee + Milk + Sugar + Caramel → ???                          │
 * │                                                                  │
 * │ Decorator solution: Wrap objects dynamically at runtime.         │
 * │  $coffee = new MilkDecorator(new SugarDecorator(new Coffee()))  │
 * │  → stacks behaviors: Coffee + Sugar + Milk                      │
 * │                                                                  │
 * │ KEY RULE: Decorator and Component implement the SAME interface.  │
 * │ The decorator wraps a component, calling it AND adding behavior. │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Component (interface)                                           │
 * │    │ implements                                                   │
 * │    ├── ConcreteComponent       ← the "real" object              │
 * │    └── BaseDecorator           ← wraps a Component              │
 * │           │ extends                                              │
 * │           ├── ConcreteDecoratorA  (adds feature A)              │
 * │           └── ConcreteDecoratorB  (adds feature B)              │
 * │                                                                  │
 * │  Request flows: Client → DecoratorB → DecoratorA → Component   │
 * │  (like an onion: outer layers add behavior around the core)     │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE DECORATOR                            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Component interface                               │
 * │ STEP 2: Create ConcreteComponent (the base object)              │
 * │ STEP 3: Create BaseDecorator: wraps a Component, delegates calls│
 * │ STEP 4: Create ConcreteDecorators: extend BaseDecorator,        │
 * │         call parent first, then add their own behavior          │
 * │ STEP 5: Client stacks decorators: new D1(new D2(new Component))  │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Coffee Shop — Stack add-ons, compute total cost
// ═══════════════════════════════════════════════════════════════

// STEP 1: Component interface
interface Coffee
{
    public function getCost(): float;
    public function getDescription(): string;
}

// STEP 2: Concrete Component — the "plain" coffee
class SimpleCoffee implements Coffee
{
    public function getCost(): float          { return 50.0; }
    public function getDescription(): string  { return "Simple Coffee"; }
}

class Espresso implements Coffee
{
    public function getCost(): float         { return 80.0; }
    public function getDescription(): string { return "Espresso"; }
}

// STEP 3: Base Decorator — holds a Component and delegates
abstract class CoffeeDecorator implements Coffee
{
    // The wrapped component (could be a concrete or another decorator)
    public function __construct(protected Coffee $coffee) {}

    // Default: just delegate to wrapped component
    public function getCost(): float
    {
        return $this->coffee->getCost();
    }

    public function getDescription(): string
    {
        return $this->coffee->getDescription();
    }
}

// STEP 4: Concrete Decorators — each adds to cost + description
class MilkDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return parent::getCost() + 15.0; // Add milk price
    }

    public function getDescription(): string
    {
        return parent::getDescription() . " + Milk";
    }
}

class SugarDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return parent::getCost() + 5.0;
    }

    public function getDescription(): string
    {
        return parent::getDescription() . " + Sugar";
    }
}

class VanillaDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return parent::getCost() + 25.0;
    }

    public function getDescription(): string
    {
        return parent::getDescription() . " + Vanilla";
    }
}

class WhipDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return parent::getCost() + 20.0;
    }

    public function getDescription(): string
    {
        return parent::getDescription() . " + Whip";
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: HTTP Middleware Decorator
// Each middleware wraps the next, adding behavior around the request
// ═══════════════════════════════════════════════════════════════

interface HttpHandler
{
    public function handle(array $request): array;
}

// ConcreteComponent: the actual request handler
class AppHandler implements HttpHandler
{
    public function handle(array $request): array
    {
        echo "    [App] Handling {$request['method']} {$request['path']}\n";
        return ['status' => 200, 'body' => 'Hello from app!'];
    }
}

// Base Middleware Decorator
abstract class MiddlewareDecorator implements HttpHandler
{
    public function __construct(protected HttpHandler $next) {}

    public function handle(array $request): array
    {
        return $this->next->handle($request);
    }
}

// Auth Middleware
class AuthMiddleware extends MiddlewareDecorator
{
    public function handle(array $request): array
    {
        echo "    [Auth] Checking authorization...\n";
        if (!isset($request['headers']['Authorization'])) {
            echo "    [Auth] UNAUTHORIZED\n";
            return ['status' => 401, 'body' => 'Unauthorized'];
        }
        echo "    [Auth] Authorized ✓\n";
        return parent::handle($request); // Pass to next in chain
    }
}

// Logging Middleware
class LoggingMiddleware extends MiddlewareDecorator
{
    public function handle(array $request): array
    {
        $start = microtime(true);
        echo "    [Logger] --> {$request['method']} {$request['path']}\n";
        $response = parent::handle($request); // Pass to next
        $ms       = round((microtime(true) - $start) * 1000, 2);
        echo "    [Logger] <-- Status: {$response['status']} ({$ms}ms)\n";
        return $response;
    }
}

// Rate Limiting Middleware
class RateLimitMiddleware extends MiddlewareDecorator
{
    private static array $requestCounts = [];

    public function handle(array $request): array
    {
        $ip = $request['ip'] ?? '127.0.0.1';
        self::$requestCounts[$ip] = (self::$requestCounts[$ip] ?? 0) + 1;

        if (self::$requestCounts[$ip] > 100) {
            echo "    [RateLimit] Too Many Requests from $ip\n";
            return ['status' => 429, 'body' => 'Too Many Requests'];
        }
        echo "    [RateLimit] Request {$self::$requestCounts[$ip]} from $ip ✓\n";
        return parent::handle($request);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== DECORATOR PATTERN DEMO ===\n\n";

echo "--- Example 1: Coffee Decorators ---\n";

// STEP 5: Stack decorators at runtime
$plain = new SimpleCoffee();
echo "{$plain->getDescription()}: ₹{$plain->getCost()}\n";

$withMilk = new MilkDecorator(new SimpleCoffee());
echo "{$withMilk->getDescription()}: ₹{$withMilk->getCost()}\n";

// Stack multiple decorators
$fancy = new WhipDecorator(new VanillaDecorator(new MilkDecorator(new SugarDecorator(new Espresso()))));
echo "{$fancy->getDescription()}: ₹{$fancy->getCost()}\n";

// Double sugar (add same decorator twice!)
$extraSweet = new SugarDecorator(new SugarDecorator(new SimpleCoffee()));
echo "{$extraSweet->getDescription()}: ₹{$extraSweet->getCost()}\n";

echo "\n--- Example 2: HTTP Middleware Stack ---\n";

// Build middleware stack (outer → inner): Logger → Auth → App
$handler = new LoggingMiddleware(
    new AuthMiddleware(
        new AppHandler()
    )
);

echo "Request WITHOUT auth header:\n";
$response = $handler->handle(['method' => 'GET', 'path' => '/api/users', 'ip' => '10.0.0.1']);
echo "Response: {$response['status']}\n\n";

echo "Request WITH auth header:\n";
$response = $handler->handle([
    'method'  => 'GET',
    'path'    => '/api/users',
    'ip'      => '10.0.0.1',
    'headers' => ['Authorization' => 'Bearer token123']
]);
echo "Response: {$response['status']}\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Decorator pattern?                               │
 * │ A: Attaches additional behavior to objects dynamically by        │
 * │    wrapping them. Both the Decorator and the Component implement │
 * │    the same interface, so they're interchangeable.               │
 * │                                                                  │
 * │ Q2: Decorator vs Inheritance for adding behavior?                │
 * │ A: Inheritance: Static, decided at compile time, causes class    │
 * │    explosion (N features → 2^N subclasses).                     │
 * │    Decorator: Dynamic, at runtime, composes features. You can   │
 * │    apply the same decorator twice (double sugar). Flexible.      │
 * │                                                                  │
 * │ Q3: Decorator vs Adapter?                                        │
 * │ A: Decorator: SAME interface, adds behavior.                     │
 * │    Adapter: DIFFERENT interface, converts one to another.        │
 * │                                                                  │
 * │ Q4: What design principle does Decorator follow?                 │
 * │ A: Open/Closed Principle — add new behavior (new Decorator)     │
 * │    without modifying existing classes.                           │
 * │    Composition over Inheritance.                                 │
 * │                                                                  │
 * │ Q5: Real-world PHP examples?                                     │
 * │ A: PSR-15 HTTP Middleware (Laravel Pipeline, Slim middleware)   │
 * │    PHP streams: `new GzipStream(new BufferedStream(new FileStream))` │
 * │    Doctrine's lazy-loading proxies.                              │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Can apply same decorator multiple times (SugarDecorator x2)   │
 * │ ✓ Order matters: Logger wrapping Auth logs even 401 responses    │
 * │ ✓ Decorator should not break the Liskov Substitution Principle   │
 * └─────────────────────────────────────────────────────────────────┘
 */
