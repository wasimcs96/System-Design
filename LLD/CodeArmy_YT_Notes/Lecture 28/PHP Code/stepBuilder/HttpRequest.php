<?php

namespace stepBuilder;

use RuntimeException;

/*
NOTE (PHP): PHP has no nested classes/interfaces, so Java's nested step
interfaces (UrlStep, MethodStep, HeaderStep, OptionalStep) and the nested
HttpRequestStepBuilder are declared as top-level types in this same file.
The step interfaces force the caller to go: url -> method -> header -> optional -> build.
*/
class HttpRequest
{
    private ?string $url;
    private ?string $method;
    /** @var array<string,string> */
    private array $headers;
    /** @var array<string,string> */
    private array $queryParams;
    private string $body;
    private int $timeout; // in seconds

    // Only meant to be called by HttpRequestStepBuilder::build()
    public function __construct(HttpRequestStepBuilder $builder)
    {
        $this->url         = $builder->getUrl();
        $this->method      = $builder->getMethod();
        $this->headers     = $builder->getHeaders();
        $this->queryParams = [];
        $this->body        = $builder->getBody();
        $this->timeout     = $builder->getTimeout();
    }

    // Method to execute the HTTP request
    public function execute(): void
    {
        echo "Executing " . $this->method . " request to " . $this->url . PHP_EOL;

        if (!empty($this->queryParams)) {
            echo "Query Parameters:" . PHP_EOL;
            foreach ($this->queryParams as $key => $value) {
                echo "  " . $key . "=" . $value . PHP_EOL;
            }
        }

        echo "Headers:" . PHP_EOL;
        foreach ($this->headers as $key => $value) {
            echo "  " . $key . ": " . $value . PHP_EOL;
        }

        if ($this->body !== "") {
            echo "Body: " . $this->body . PHP_EOL;
        }

        echo "Timeout: " . $this->timeout . " seconds" . PHP_EOL;
        echo "Request executed successfully!" . PHP_EOL;
    }
}

// Step interfaces
interface UrlStep
{
    public function withUrl(string $url): MethodStep;
}

interface MethodStep
{
    public function withMethod(string $method): HeaderStep;
}

interface HeaderStep
{
    public function withHeader(string $key, string $value): OptionalStep;
}

interface OptionalStep
{
    public function withBody(string $body): OptionalStep;
    public function withTimeout(int $timeout): OptionalStep;
    public function build(): HttpRequest;
}

// Concrete step builder
class HttpRequestStepBuilder implements UrlStep, MethodStep, HeaderStep, OptionalStep
{
    private ?string $url = null;
    private ?string $method = null;
    /** @var array<string,string> */
    private array $headers = [];
    private string $body = "";
    private int $timeout = 0;

    private function __construct()
    {
    }

    // UrlStep implementation
    public function withUrl(string $url): MethodStep
    {
        $this->url = $url;
        return $this;
    }

    // MethodStep implementation
    public function withMethod(string $method): HeaderStep
    {
        $this->method = $method;
        return $this;
    }

    // HeaderStep implementation
    public function withHeader(string $key, string $value): OptionalStep
    {
        $this->headers[$key] = $value;
        return $this;
    }

    // OptionalStep implementation
    public function withBody(string $body): OptionalStep
    {
        $this->body = $body;
        return $this;
    }

    public function withTimeout(int $timeout): OptionalStep
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function build(): HttpRequest
    {
        if ($this->url === null || $this->url === "") {
            throw new RuntimeException("URL cannot be empty");
        }
        return new HttpRequest($this);
    }

    // Static method to start the building process
    public static function getBuilder(): UrlStep
    {
        return new HttpRequestStepBuilder();
    }

    // Read-only accessors used by HttpRequest's constructor
    public function getUrl(): ?string { return $this->url; }
    public function getMethod(): ?string { return $this->method; }
    public function getHeaders(): array { return $this->headers; }
    public function getBody(): string { return $this->body; }
    public function getTimeout(): int { return $this->timeout; }
}
