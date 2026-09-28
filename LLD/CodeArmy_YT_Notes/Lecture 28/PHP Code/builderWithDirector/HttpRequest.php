<?php

namespace builderWithDirector;

use RuntimeException;

/*
NOTE (PHP): PHP has no nested/inner classes and no "friend" access, so the Java
"public static class HttpRequestBuilder" (nested inside HttpRequest) becomes a
separate class in the same file. The builder collects the values and hands itself
to HttpRequest's constructor; HttpRequest has NO setters, so once built it is immutable.
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

    // Only meant to be called by HttpRequestBuilder::build()
    public function __construct(HttpRequestBuilder $builder)
    {
        $this->url         = $builder->getUrl();
        $this->method      = $builder->getMethod();
        $this->headers     = $builder->getHeaders();
        $this->queryParams = $builder->getQueryParams();
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

// Builder class (Java: nested static class HttpRequest.HttpRequestBuilder)
class HttpRequestBuilder
{
    private ?string $url = null;
    private ?string $method = null;
    /** @var array<string,string> */
    private array $headers = [];
    /** @var array<string,string> */
    private array $queryParams = [];
    private string $body = "";
    private int $timeout = 0;

    public function __construct()
    {
    }

    // Method chaining
    public function withUrl(string $u): self
    {
        $this->url = $u;
        return $this;
    }

    public function withMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withQueryParams(string $key, string $value): self
    {
        $this->queryParams[$key] = $value;
        return $this;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function withTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    // Build method to create the immutable HttpRequest object
    public function build(): HttpRequest
    {
        // Validation logic can be added here
        if ($this->url === null || $this->url === "") {
            throw new RuntimeException("URL cannot be empty");
        }
        return new HttpRequest($this);
    }

    // Read-only accessors used by HttpRequest's constructor
    public function getUrl(): ?string { return $this->url; }
    public function getMethod(): ?string { return $this->method; }
    public function getHeaders(): array { return $this->headers; }
    public function getQueryParams(): array { return $this->queryParams; }
    public function getBody(): string { return $this->body; }
    public function getTimeout(): int { return $this->timeout; }
}
