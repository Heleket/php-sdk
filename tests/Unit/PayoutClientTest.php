<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Config;
use Heleket\PayoutClient;
use Heleket\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class PayoutClientTest extends TestCase
{
    private const MERCHANT_ID = 'fffefe1a-2111-4dd8-9bcc-000000000000';
    private const API_KEY = 'payout-key-xyz';

    public function testCreatePayoutSendsExpectedRequest(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => ['uuid' => 'po-1', 'status' => 'process', 'is_final' => false],
        ]);
        $client = new PayoutClient(new Config(self::MERCHANT_ID, self::API_KEY), $transport);

        $result = $client->createPayout([
            'amount'      => '5',
            'currency'    => 'USDT',
            'network'     => 'TRON',
            'order_id'    => 'po-1',
            'address'     => 'TDD97yguPESTpcrJMqU6h2ozZbibv4Vaqm',
            'is_subtract' => true,
        ]);

        self::assertSame('po-1', $result['uuid']);
        self::assertSame('https://api.heleket.com/v1/payout', $transport->getLastRequest()['url']);
    }

    public function testCalculateWithdrawalAmountSerializesBooleanAsExpected(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => ['commission' => '0.5'],
        ]);
        $client = new PayoutClient(new Config(self::MERCHANT_ID, self::API_KEY), $transport);

        $client->calculateWithdrawalAmount('USDT', 'TRON', '10', true);

        $body = $transport->getLastRequest()['body'];
        $decoded = json_decode($body, true);
        self::assertSame('USDT', $decoded['currency']);
        self::assertSame('TRON', $decoded['network']);
        self::assertSame('10', $decoded['amount']);
        self::assertTrue($decoded['is_subtract']);
    }

    public function testTransferToPersonalAndBusiness(): void
    {
        $transport = (new FakeTransport())
            ->enqueueJson(['state' => 0, 'result' => ['ok' => true]])
            ->enqueueJson(['state' => 0, 'result' => ['ok' => true]]);
        $client = new PayoutClient(new Config(self::MERCHANT_ID, self::API_KEY), $transport);

        $client->transferToPersonal('1.5', 'USDT');
        $client->transferToBusiness('1.5', 'USDT');

        $requests = $transport->getRequests();
        self::assertSame('https://api.heleket.com/v1/transfer/to-personal', $requests[0]['url']);
        self::assertSame('https://api.heleket.com/v1/transfer/to-business', $requests[1]['url']);
    }
}
