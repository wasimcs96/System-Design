<?php

/*
NOTE (PHP): PHP does not allow multiple constructors (no constructor overloading).
The Java version shows the "telescoping constructor" problem with 6 constructors.
In PHP the same problem shows up as ONE constructor with a long list of optional
parameters – callers must remember the order and pass placeholders for values
they don't care about (e.g. new HttpRequest($url, "GET", 30, [], [], $body)).
*/
class HttpRequest
{
    private string $url;                    // required
    private string $method;                 // required
    /** @var array<string,string> */
    private array $headers;
    /** @var array<string,string> */
    private array $queryParams;
    private ?string $body;
    private int $timeout;                   // required

    // Telescoping parameters: 1-arg ... 6-args all squeezed into one constructor
    public function __construct(
        string $url,
        string $method = "GET",     // Default method
        int $timeout = 30,          // Default timeout
        array $headers = [],
        array $queryParams = [],
        ?string $body = null
    ) {
        $this->url = $url;
        $this->method = $method;
        $this->timeout = $timeout;
        $this->headers = $headers;
        $this->queryParams = $queryParams;
        $this->body = $body;
    }

    // Setters (leads to mutable object)
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function addQueryParam(string $key, string $value): void
    {
        $this->queryParams[$key] = $value;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
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

        if ($this->body !== null && $this->body !== "") {
            echo "Body: " . $this->body . PHP_EOL;
        }

        echo "Timeout: " . $this->timeout . " seconds" . PHP_EOL;
        echo "Request executed successfully!" . PHP_EOL;
    }
}

class WithoutBuilder
{
    public static function main(): void
    {
        // Using constructors (telescoping constructor problem)
        $request1 = new HttpRequest("https://api.example.com");
        $request2 = new HttpRequest("https://api.example.com", "POST");
        $request3 = new HttpRequest("https://api.example.com", "PUT", 60);

        // Using setters (mutable object problem)
        $request4 = new HttpRequest("https://api.example.com");
        $request4->setMethod("POST");
        $request4->addHeader("Content-Type", "application/json");
        $request4->addQueryParam("key", "12345");
        $request4->setBody("{\"name\": \"Aditya\"}");
        $request4->setTimeout(60);

        // The problem: what if we forgot to set an important field?
        $request4->execute();
    }
}

WithoutBuilder::main();
