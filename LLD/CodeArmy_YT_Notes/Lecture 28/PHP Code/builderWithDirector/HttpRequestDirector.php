<?php

namespace builderWithDirector;

require_once __DIR__ . '/HttpRequest.php';

class HttpRequestDirector
{
    public static function createGetRequest(string $url): HttpRequest
    {
        return (new HttpRequestBuilder())
            ->withUrl($url)
            ->withMethod("GET")
            ->build();
    }

    // Creates a JSON POST request
    public static function createJsonPostRequest(string $url, string $jsonBody): HttpRequest
    {
        return (new HttpRequestBuilder())
            ->withUrl($url)
            ->withMethod("POST")
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json")
            ->withBody($jsonBody)
            ->build();
    }
}
