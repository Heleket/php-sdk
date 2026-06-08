<?php

declare(strict_types=1);

namespace Heleket;

/**
 * Single source of truth for the SDK version. Kept in a tiny final class with
 * no dependencies so AbstractClient can read it for the User-Agent header
 * without dragging transport concerns into the public surface.
 *
 * Keep this in sync with the "version" field in composer.json when cutting a
 * release.
 */
final class Version
{
    /** Current SDK version. */
    public const VERSION = '0.1.0';

    /** User-Agent value sent on every outgoing request. */
    public const USER_AGENT = 'heleket-php-sdk/' . self::VERSION;

    private function __construct()
    {
    }
}
