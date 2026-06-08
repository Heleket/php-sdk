<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testRejectsEmptyMerchantId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('   ', 'key');
    }

    public function testRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('merchant', '');
    }

    public function testRejectsNonPositiveTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('merchant', 'key', Config::DEFAULT_BASE_URL, 0);
    }

    public function testTrimsTrailingSlashFromBaseUrl(): void
    {
        $config = new Config('merchant', 'key', 'https://example.com/');
        self::assertSame('https://example.com', $config->getBaseUrl());
    }

    public function testWithersReturnNewInstances(): void
    {
        $config = new Config('merchant', 'key');
        $debug = $config->withDebug(true);

        self::assertFalse($config->isDebug());
        self::assertTrue($debug->isDebug());
        self::assertNotSame($config, $debug);
    }
}
