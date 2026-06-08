<?php

declare(strict_types=1);

namespace Heleket\Exception;

/**
 * Thrown when the HTTP transport itself fails (DNS, TCP, TLS, timeout).
 * The API was never reached, or no response was received.
 */
final class HttpException extends HeleketException
{
}
