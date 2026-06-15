<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testVersionIsNonEmptyString(): void
    {
        self::assertNotSame('', Version::version());
    }

    public function testUserAgentCarriesPackageNameAndVersion(): void
    {
        self::assertStringStartsWith('heleket-php-sdk/', Version::userAgent());
        self::assertStringEndsWith(Version::version(), Version::userAgent());
    }

    public function testFallbackVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::FALLBACK_VERSION);
    }
}
