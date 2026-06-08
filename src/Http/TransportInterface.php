<?php

declare(strict_types=1);

namespace Heleket\Http;

use Heleket\Exception\HttpException;

/**
 * HTTP transport contract. Implementations send an HTTP request and return a
 * Response with the raw response body and status code preserved exactly.
 *
 * Why an interface: the SDK ships a cURL-based transport by default, but tests
 * inject a FakeTransport that captures requests and returns canned responses.
 * Merchants who want to use Guzzle, Symfony HttpClient, or any other library
 * can also implement this interface and pass it to PaymentClient/PayoutClient.
 */
interface TransportInterface
{
    /**
     * @param array<string, string> $headers
     * @throws HttpException on transport-level failure (DNS, TCP, TLS, timeout)
     */
    public function request(string $method, string $url, array $headers, string $body): Response;
}
