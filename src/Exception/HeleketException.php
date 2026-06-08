<?php

declare(strict_types=1);

namespace Heleket\Exception;

use RuntimeException;

/**
 * Base exception for all SDK errors. Catch this to handle any SDK failure.
 */
class HeleketException extends RuntimeException
{
}
