<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Signature\Signer;
use PHPUnit\Framework\TestCase;

final class SignerTest extends TestCase
{
    public function testSignEmptyBodyUsesEmptyBase64Prefix(): void
    {
        // base64_encode('') === '', so the hash collapses to md5(apiKey).
        $signer = new Signer();
        $apiKey = 'super-secret-key';

        self::assertSame(md5($apiKey), $signer->sign('', $apiKey));
    }

    public function testSignNonEmptyBodyMatchesDocFormula(): void
    {
        $signer = new Signer();
        $body = '{"amount":"15","currency":"USD","order_id":"1"}';
        $apiKey = 'merchant-api-key-42';

        $expected = md5(base64_encode($body) . $apiKey);

        self::assertSame($expected, $signer->sign($body, $apiKey));
    }

    public function testEqualsIsConstantTimeAndCaseSensitive(): void
    {
        $signer = new Signer();
        self::assertTrue($signer->equals('abc', 'abc'));
        self::assertFalse($signer->equals('abc', 'abd'));
        self::assertFalse($signer->equals('abc', 'ABC'));
    }
}
