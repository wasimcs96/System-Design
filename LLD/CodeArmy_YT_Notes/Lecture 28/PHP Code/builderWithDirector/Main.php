<?php

// Run with:  php Main.php

namespace builderWithDirector;

require_once __DIR__ . '/HttpRequest.php';
require_once __DIR__ . '/HttpRequestDirector.php';

class Main
{
    public static function main(): void
    {
        // Normal Request from Builder Directly
        $normalRequest = (new HttpRequestBuilder())
            ->withUrl("https://api.example.com")
            ->withMethod("POST")
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json")
            ->withQueryParams("key", "12345")
            ->withBody("{\"name\": \"Aditya\"}")
            ->withTimeout(60)
            ->build();

        $normalRequest->execute(); // Guaranteed to be in a consistent state

        echo PHP_EOL . "----------------------------" . PHP_EOL . PHP_EOL;

        $getRequest = HttpRequestDirector::createGetRequest("https://api.example.com/users");
        $getRequest->execute();

        echo PHP_EOL . "----------------------------" . PHP_EOL . PHP_EOL;

        $postRequest = HttpRequestDirector::createJsonPostRequest(
            "https://api.example.com/users",
            "{\"name\": \"Aditya\", \"email\": \"aditya@example.com\"}");
        $postRequest->execute();
    }
}

Main::main();
