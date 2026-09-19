<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #4 — BUILDER                         ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Creational Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You need to create a COMPLEX object that requires       │
 * │ many optional parameters. Using a constructor leads to:          │
 * │                                                                  │
 * │ BAD — Telescoping Constructor:                                   │
 * │   new Pizza(dough, sauce, cheese, null, null, true, false, ...)  │
 * │   → Unreadable, error-prone (which null goes where?)             │
 * │                                                                  │
 * │ Builder Pattern Solution:                                        │
 * │   $pizza = (new PizzaBuilder('large'))                           │
 * │                ->setDough('thin')                                │
 * │                ->setSauce('tomato')                              │
 * │                ->addTopping('mushrooms')                         │
 * │                ->addTopping('pepperoni')                         │
 * │                ->build();                                        │
 * │ → Readable, fluent, optional steps in any order                  │
 * │                                                                  │
 * │ Two flavors:                                                      │
 * │  1. FLUENT BUILDER  — Builder IS the configuration object        │
 * │  2. DIRECTOR+BUILDER — Director knows HOW to build, Builder knows│
 * │     how to store. Reuse build sequences.                         │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Director (knows BUILD sequences)                                │
 * │      │ uses                                                       │
 * │      ▼                                                            │
 * │  Builder (interface)    setPartA() setPartB() getResult()        │
 * │      │                                                            │
 * │      ├── ConcreteBuilderA  → ProductA                            │
 * │      └── ConcreteBuilderB  → ProductB                            │
 * │                                                                   │
 * │  Fluent Builder:                                                  │
 * │  $obj = Builder::new()->setA()->setB()->setC()->build();          │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE BUILDER                              │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define the Product (the complex object to build)         │
 * │ STEP 2: Create a Builder class with private fields + setters     │
 * │ STEP 3: Each setter returns $this → enables method chaining      │
 * │ STEP 4: build() validates and constructs the final object        │
 * │ STEP 5 (optional): Director class encapsulates build recipes     │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: HTTP Request Builder (Fluent)
// ═══════════════════════════════════════════════════════════════

// STEP 1: Product
class HttpRequest
{
    private function __construct(
        public readonly string  $method,
        public readonly string  $url,
        public readonly array   $headers,
        public readonly array   $queryParams,
        public readonly ?string $body,
        public readonly int     $timeoutSeconds,
        public readonly bool    $followRedirects
    ) {}

    public function __toString(): string
    {
        $qs = $this->queryParams
            ? '?' . http_build_query($this->queryParams)
            : '';
        $body = $this->body ? " | body=" . substr($this->body, 0, 30) : '';
        return "{$this->method} {$this->url}{$qs}{$body} [timeout={$this->timeoutSeconds}s]";
    }

    // Only the Builder can create HttpRequest (private constructor)
    public static function builder(string $method, string $url): HttpRequestBuilder
    {
        return new HttpRequestBuilder($method, $url);
    }
}

// STEP 2: Fluent Builder
class HttpRequestBuilder
{
    private array   $headers         = [];
    private array   $queryParams     = [];
    private ?string $body            = null;
    private int     $timeoutSeconds  = 30;
    private bool    $followRedirects = true;

    // Constructor only requires mandatory fields
    public function __construct(
        private string $method,
        private string $url
    ) {}

    // STEP 3: Setters return $this for chaining
    public function withHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withBearerToken(string $token): static
    {
        return $this->withHeader('Authorization', "Bearer $token");
    }

    public function withQueryParam(string $key, string $value): static
    {
        $this->queryParams[$key] = $value;
        return $this;
    }

    public function withBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function withJsonBody(array $data): static
    {
        $this->headers['Content-Type'] = 'application/json';
        return $this->withBody(json_encode($data));
    }

    public function withTimeout(int $seconds): static
    {
        $this->timeoutSeconds = $seconds;
        return $this;
    }

    public function noRedirects(): static
    {
        $this->followRedirects = false;
        return $this;
    }

    // STEP 4: Validate + build the final immutable product
    public function build(): HttpRequest
    {
        // Validation before construction
        if (empty($this->url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }
        if (!in_array($this->method, ['GET','POST','PUT','PATCH','DELETE','HEAD'])) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$this->method}");
        }
        if ($this->body !== null && $this->method === 'GET') {
            throw new \LogicException("GET request cannot have a body");
        }

        // Use Reflection or named constructor to pass to private ctor
        return new HttpRequest(
            $this->method,
            $this->url,
            $this->headers,
            $this->queryParams,
            $this->body,
            $this->timeoutSeconds,
            $this->followRedirects
        );
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Database Query Builder (Director + Builder)
// ═══════════════════════════════════════════════════════════════

// STEP 1: Product
class SqlQuery
{
    public function __construct(
        public readonly string  $sql,
        public readonly array   $bindings = []
    ) {}

    public function __toString(): string { return $this->sql; }
}

// Builder interface
interface QueryBuilderInterface
{
    public function select(string $table, array $columns = ['*']): static;
    public function where(string $column, mixed $value, string $op = '='): static;
    public function orderBy(string $column, string $direction = 'ASC'): static;
    public function limit(int $limit): static;
    public function offset(int $offset): static;
    public function build(): SqlQuery;
}

// Concrete Builder
class SelectQueryBuilder implements QueryBuilderInterface
{
    private string  $table;
    private array   $columns    = ['*'];
    private array   $conditions = [];
    private array   $bindings   = [];
    private array   $orderBy    = [];
    private ?int    $limit      = null;
    private ?int    $offset     = null;

    public function select(string $table, array $columns = ['*']): static
    {
        $this->table   = $table;
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, mixed $value, string $op = '='): static
    {
        $placeholder          = ':' . $column . count($this->conditions);
        $this->conditions[]   = "$column $op $placeholder";
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function build(): SqlQuery
    {
        if (empty($this->table)) {
            throw new \LogicException("Table must be set before building query");
        }
        $cols = implode(', ', $this->columns);
        $sql  = "SELECT $cols FROM {$this->table}";
        if ($this->conditions) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }
        if ($this->orderBy) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        if ($this->limit !== null)  $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        return new SqlQuery($sql . ';', $this->bindings);
    }
}

// STEP 5: Director — reusable build "recipes"
class QueryDirector
{
    public function __construct(private QueryBuilderInterface $builder) {}

    /** Standard paginated query */
    public function paginatedQuery(string $table, int $page, int $perPage): SqlQuery
    {
        return $this->builder
            ->select($table, ['id', 'name', 'created_at'])
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->build();
    }

    /** Active users query */
    public function activeUsers(): SqlQuery
    {
        return $this->builder
            ->select('users', ['id', 'email', 'last_login'])
            ->where('status', 'active')
            ->where('email_verified', 1)
            ->orderBy('last_login', 'DESC')
            ->limit(100)
            ->build();
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== BUILDER PATTERN DEMO ===\n\n";

echo "--- Example 1: HTTP Request Builder ---\n";

// GET request
$getReq = HttpRequest::builder('GET', 'https://api.example.com/users')
    ->withBearerToken('mytoken123')
    ->withQueryParam('page', '2')
    ->withQueryParam('per_page', '20')
    ->withTimeout(15)
    ->build();
echo "GET: $getReq\n";

// POST request with JSON body
$postReq = HttpRequest::builder('POST', 'https://api.example.com/users')
    ->withBearerToken('mytoken123')
    ->withJsonBody(['name' => 'Alice', 'email' => 'alice@example.com'])
    ->withTimeout(30)
    ->build();
echo "POST: $postReq\n";

// Edge case: body on GET throws exception
try {
    HttpRequest::builder('GET', 'https://api.example.com')
        ->withBody('some body')
        ->build();
} catch (\LogicException $e) {
    echo "Validation caught: " . $e->getMessage() . " ✓\n";
}

echo "\n--- Example 2: SQL Query Builder + Director ---\n";

$builder  = new SelectQueryBuilder();
$director = new QueryDirector($builder);

$paginatedQuery = $director->paginatedQuery('products', page: 2, perPage: 10);
echo "Paginated: $paginatedQuery\n";

// Reset and build active users
$activeUsersQuery = (new QueryDirector(new SelectQueryBuilder()))->activeUsers();
echo "Active users: $activeUsersQuery\n";

// Custom query without director
$customQuery = (new SelectQueryBuilder())
    ->select('orders', ['id', 'total', 'status'])
    ->where('status', 'pending')
    ->where('total', 500, '>')
    ->orderBy('created_at', 'DESC')
    ->limit(50)
    ->build();
echo "Custom: $customQuery\n";
echo "Bindings: " . json_encode($customQuery->bindings) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What problem does the Builder pattern solve?                 │
 * │ A: The "telescoping constructor" problem — when a class has many │
 * │    optional parameters, constructors become unreadable. Builder │
 * │    lets you set only what you need, in any order, readably.      │
 * │                                                                  │
 * │ Q2: What is method chaining and how does Builder use it?         │
 * │ A: Each setter returns $this (the builder object), allowing you  │
 * │    to chain calls: builder->setA()->setB()->setC()->build().     │
 * │    The final build() constructs and returns the actual product.  │
 * │                                                                  │
 * │ Q3: What is the role of the Director?                            │
 * │ A: The Director encapsulates construction "recipes" — it knows  │
 * │    WHICH steps to call and in WHAT ORDER, but doesn't know how  │
 * │    the product is actually built (that's the Builder's job).     │
 * │    Directors allow reuse of complex construction sequences.      │
 * │                                                                  │
 * │ Q4: Builder vs Factory — when do you use which?                  │
 * │ A: Factory: You know WHICH type to create, straightforward ctor. │
 * │    Builder: The object has many OPTIONAL/COMPLEX configuration   │
 * │    steps, or the same construction process yields different      │
 * │    representations (SQL for MySQL vs PostgreSQL).                │
 * │                                                                  │
 * │ Q5: How do you make the Product immutable?                       │
 * │ A: Make the Product's constructor private, expose only via the  │
 * │    Builder's build() method. Declare properties readonly.        │
 * │    After build(), the product cannot be modified.               │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Validate in build() — throw if required fields missing        │
 * │ ✓ Reuse builder by adding reset() to start fresh                 │
 * │ ✓ Method return type `static` (not `self`) supports subclassing  │
 * └─────────────────────────────────────────────────────────────────┘
 */
