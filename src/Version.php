<?php

declare(strict_types=1);

namespace Heleket;

/**
 * Single source of truth for the SDK version, used for the outgoing User-Agent
 * header. No dependencies, so AbstractClient can read it without dragging
 * transport concerns into the public surface.
 *
 * The version is resolved from Composer's runtime metadata
 * (\Composer\InstalledVersions) so it tracks the actually-installed git tag and
 * never needs a manual bump at release time. When that metadata is unavailable
 * — the SDK was copied in manually, or it runs as the root package during its
 * own development — we fall back to FALLBACK_VERSION.
 */
final class Version
{
    /** Packagist package name, used to look up the installed version at runtime. */
    public const PACKAGE = 'heleket/php-sdk';

    /**
     * Fallback version, used only when Composer's runtime metadata cannot
     * resolve the package version. Keep it roughly in step with the latest tag;
     * it is a fallback, not the source of truth.
     */
    public const FALLBACK_VERSION = '2.0.0';

    private function __construct()
    {
    }

    /**
     * Resolve the installed SDK version, e.g. "2.0.0" or "dev-main".
     */
    public static function version(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion(self::PACKAGE);
                if (is_string($version) && $version !== '') {
                    return $version;
                }
            } catch (\OutOfBoundsException $exception) {
                // Package not installed via Composer (copied in, or unknown). Fall through.
            }
        }

        return self::FALLBACK_VERSION;
    }

    /**
     * User-Agent value sent on every outgoing request, e.g. "heleket-php-sdk/2.0.0".
     */
    public static function userAgent(): string
    {
        return 'heleket-php-sdk/' . self::version();
    }
}
