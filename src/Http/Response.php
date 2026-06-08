<?php

declare(strict_types=1);

namespace Heleket\Http;

/**
 * Immutable HTTP response captured by a Transport. Holds the raw body verbatim
 * (no decoding) so signature/debug logic can work on the exact bytes received.
 */
final class Response
{
    private int $statusCode;
    private string $body;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, string $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
