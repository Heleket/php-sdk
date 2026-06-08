<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Exception\SignatureException;
use Heleket\Signature\Signer;
use Heleket\Webhook\WebhookVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookVerifierTest extends TestCase
{
    private const API_KEY = 'webhook-secret';

    public function testVerifyAcceptsValidPayloadAndReturnsTypedAccessor(): void
    {
        $payload = $this->makeSignedPayload([
            'type'     => 'payment',
            'uuid'     => 'pmt-1',
            'order_id' => 'order-1',
            'status'   => 'paid',
            'is_final' => true,
            'amount'   => '15.00',
            'txid'     => '0xabc',
            'network'  => 'bsc',
        ]);

        $result = (new WebhookVerifier(self::API_KEY))->verify($payload);

        self::assertTrue($result->isPayment());
        self::assertFalse($result->isPayout());
        self::assertSame('pmt-1', $result->getUuid());
        self::assertSame('order-1', $result->getOrderId());
        self::assertSame('paid', $result->getStatus());
        self::assertTrue($result->isFinal());
        self::assertTrue($result->isSuccessful());
        self::assertSame('15.00', $result->getAmount());
        self::assertSame('0xabc', $result->getTxid());
        self::assertSame('bsc', $result->getNetwork());
    }

    public function testTamperedPayloadFailsVerification(): void
    {
        $payload = $this->makeSignedPayload([
            'type'     => 'payment',
            'uuid'     => 'pmt-1',
            'order_id' => 'order-1',
            'status'   => 'paid',
        ]);
        $payload['status'] = 'fail';

        $this->expectException(SignatureException::class);
        (new WebhookVerifier(self::API_KEY))->verify($payload);
    }

    public function testMissingSignIsRejected(): void
    {
        $this->expectException(SignatureException::class);
        (new WebhookVerifier(self::API_KEY))->verify(['type' => 'payment']);
    }

    public function testWrongKeyFailsVerification(): void
    {
        $payload = $this->makeSignedPayload(['type' => 'payment', 'status' => 'paid']);

        $this->expectException(SignatureException::class);
        (new WebhookVerifier('a-different-key'))->verify($payload);
    }

    public function testVerifyRawBodyDecodesAndVerifies(): void
    {
        $payload = $this->makeSignedPayload([
            'type'     => 'payout',
            'uuid'     => 'po-7',
            'status'   => 'paid',
            'is_final' => true,
        ]);
        $body = (string) json_encode($payload, JSON_UNESCAPED_UNICODE);

        $result = (new WebhookVerifier(self::API_KEY))->verifyRawBody($body);

        self::assertTrue($result->isPayout());
        self::assertTrue($result->isFinal());
        self::assertTrue($result->isSuccessful());
        self::assertSame('po-7', $result->getUuid());
    }

    public function testVerifyRawBodyRejectsNonJson(): void
    {
        $this->expectException(SignatureException::class);
        (new WebhookVerifier(self::API_KEY))->verifyRawBody('not json');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function makeSignedPayload(array $payload): array
    {
        $body = (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
        $payload['sign'] = (new Signer())->sign($body, self::API_KEY);
        return $payload;
    }
}
