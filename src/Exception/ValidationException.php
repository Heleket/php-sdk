<?php

declare(strict_types=1);

namespace Heleket\Exception;

use Throwable;

/**
 * Thrown when Heleket returns HTTP 422 with a field-level errors map.
 * Use getErrors() to surface validation messages back to the caller.
 *
 * Shape returned by getErrors():
 *     [
 *         'amount'   => ['validation.required'],
 *         'currency' => ['validation.required'],
 *     ]
 */
final class ValidationException extends ApiException
{
    /** @var array<string, list<string>> */
    private array $errors;

    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(string $message, int $httpStatus, array $errors, string $rawBody = '', ?Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $rawBody, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
