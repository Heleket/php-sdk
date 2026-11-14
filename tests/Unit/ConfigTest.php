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
        new Config('merchant', 'key', null, 0);
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

    public function testDefaultBaseUrlUsesHeleketHk(): void
    {
        $config = new Config('merchant', 'key');
        self::assertSame('https://api.heleket.hk', $config->getBaseUrl());
        self::assertSame('heleket.hk', $config->getDomain());
        self::assertSame('https://api.heleket.hk', Config::DEFAULT_BASE_URL);
    }

    public function testCustomDomainBuildsBaseUrl(): void
    {
        $config = new Config('merchant', 'key', null, 30, false, 'example.test');
        self::assertSame('https://api.example.test', $config->getBaseUrl());
        self::assertSame('https://api.other.test', $config->withDomain('other.test')->getBaseUrl());
    }

    public function testExplicitBaseUrlOverridesDomain(): void
    {
        $config = new Config('merchant', 'key', 'https://custom.local/', 30, false, 'example.test');
        self::assertSame('https://custom.local', $config->getBaseUrl());
        self::assertSame('https://custom.local', $config->withDomain('other.test')->getBaseUrl());
        self::assertSame('other.test', $config->withDomain('other.test')->getDomain());
    }

    /**
     * @dataProvider invalidDomains
     */
    public function testRejectsInvalidDomain(string $domain): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('merchant', 'key', null, 30, false, $domain);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDomains(): array
    {
        return [
            'empty' => [''],
            'scheme' => ['https://heleket.hk'],
            'slash' => ['heleket.hk/path'],
        ];
    }

    public function testClientFactoryAcceptsDomain(): void
    {
        $client = \Heleket\Client::payment('key', 'merchant', false, 'example.test');
        self::assertInstanceOf(\Heleket\PaymentClient::class, $client);
    }
}
