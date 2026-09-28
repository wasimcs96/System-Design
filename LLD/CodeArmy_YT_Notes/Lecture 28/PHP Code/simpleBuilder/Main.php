<?php

// Run with:  php Main.php

namespace simpleBuilder;

require_once __DIR__ . '/HttpRequest.php';

class Main
{
    public static function main(): void
    {
        // Using Builder Pattern
        $request = (new HttpRequestBuilder())
            ->withUrl("https://api.example2.com")
            ->withMethod("POST")
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json")
            ->withQueryParams("key", "12345")
            ->withBody("{\"name\": \"Aditya\"}")
            ->withTimeout(60)
            ->build();

        $request->execute(); // Guaranteed to be in a consistent state
    }
}

Main::main();
