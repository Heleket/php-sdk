<?php

declare(strict_types=1);

namespace Heleket\Exception;

use Throwable;

/**
 * Thrown when Heleket returns a response with state != 0 (a business error).
 * Carries the HTTP status code and raw response body so callers and the debug
 * dumper can show exactly what came back.
 */
class ApiException extends HeleketException
{
    private int $httpStatus;
    private string $rawBody;

    public function __construct(string $message, int $httpStatus, string $rawBody = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatus = $httpStatus;
        $this->rawBody = $rawBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }
}
