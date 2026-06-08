<?php

declare(strict_types=1);

namespace Heleket\Exception;

/**
 * Thrown by WebhookVerifier when an incoming webhook signature does not
 * match the expected hash. Treat as a hard security failure — never trust
 * the payload contents in this case.
 */
final class SignatureException extends HeleketException
{
}
