<?php

// Run with:  php Main.php

namespace stepBuilder;

require_once __DIR__ . '/HttpRequest.php';

class Main
{
    public static function main(): void
    {
        $stepRequest = HttpRequestStepBuilder::getBuilder()
            ->withUrl("https://api.example.com/products")
            ->withMethod("POST")
            ->withHeader("Content-Type", "application/json")
            ->withBody("{\"product\": \"Laptop\", \"price\": 49999}")
            ->withTimeout(45)
            ->build();

        $stepRequest->execute();
    }
}

Main::main();
