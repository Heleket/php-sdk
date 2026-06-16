<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Config;
use Heleket\Exception\ApiException;
use Heleket\Exception\HeleketException;
use Heleket\Exception\ValidationException;
use Heleket\PaymentClient;
use Heleket\Tests\Fakes\FakeTransport;
use Heleket\Version;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PaymentClientTest extends TestCase
{
    private const MERCHANT_ID = '8b03432e-385b-4670-8d06-064591096795';
    private const API_KEY = 'payment-key-abc';

    public function testCreateInvoiceSendsSignedJsonPostToCorrectUrl(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => ['uuid' => 'uuid-1', 'order_id' => 'order-1'],
        ]);
        $client = $this->makeClient($transport);

        $result = $client->createInvoice([
            'amount'   => '15',
            'currency' => 'USD',
            'order_id' => 'order-1',
        ]);

        self::assertSame(['uuid' => 'uuid-1', 'order_id' => 'order-1'], $result);

        $request = $transport->getLastRequest();
        self::assertSame('POST', $request['method']);
        self::assertSame('https://api.heleket.com/v1/payment', $request['url']);
        self::assertSame(self::MERCHANT_ID, $request['headers']['merchant']);
        self::assertSame('application/json', $request['headers']['Content-Type']);
        self::assertSame(Version::userAgent(), $request['headers']['User-Agent']);
        self::assertSame('heleket-php-sdk/' . Version::version(), $request['headers']['User-Agent']);

        $expectedBody = (string) json_encode([
            'amount'   => '15',
            'currency' => 'USD',
            'order_id' => 'order-1',
        ], JSON_UNESCAPED_UNICODE);
        self::assertSame($expectedBody, $request['body']);

        $expectedSign = md5(base64_encode($expectedBody) . self::API_KEY);
        self::assertSame($expectedSign, $request['headers']['sign']);
    }

    public function testEmptyParamsRequestSignsTheEmptyString(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => ['merchant' => [], 'user' => []],
        ]);
        $client = $this->makeClient($transport);

        $client->getBalance();

        $request = $transport->getLastRequest();
        self::assertSame('', $request['body']);
        self::assertSame(md5(self::API_KEY), $request['headers']['sign']);
    }

    public function testValidationErrorIsTranslatedTo422Exception(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 1,
            'errors' => ['amount' => ['validation.required']],
        ], 422);
        $client = $this->makeClient($transport);

        try {
            $client->createInvoice([]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(422, $exception->getHttpStatus());
            self::assertSame(['amount' => ['validation.required']], $exception->getErrors());
            self::assertStringContainsString('amount', $exception->getMessage());
        }
    }

    public function testApiErrorBecomesApiException(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'   => 1,
            'message' => 'The network was not found',
        ], 400);
        $client = $this->makeClient($transport);

        try {
            $client->createInvoice(['amount' => '1', 'currency' => 'USD', 'order_id' => 'x']);
            self::fail('Expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(400, $exception->getHttpStatus());
            self::assertSame('The network was not found', $exception->getMessage());
        }
    }

    public function testInfoRequiresUuidOrOrderId(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->expectException(InvalidArgumentException::class);
        $client->getInfo();
    }

    public function testInvalidUtf8InParamsThrowsInsteadOfSigningEmptyBody(): void
    {
        // Before the fix, json_encode() returned false on invalid UTF-8 and the
        // (string) cast silenced the failure: the SDK would sign the empty body
        // (md5(apiKey)) and POST nothing, leaving the merchant to debug a
        // misleading 422 from the server. After the fix this must throw locally.
        $transport = new FakeTransport();
        $client = $this->makeClient($transport);

        try {
            $client->createInvoice([
                'amount'   => '15',
                'currency' => 'USD',
                'order_id' => "bad-utf8-\xB1\x31",
            ]);
            self::fail('Expected HeleketException for invalid UTF-8 in params');
        } catch (HeleketException $exception) {
            self::assertStringContainsString('JSON-encode', $exception->getMessage());
            self::assertSame([], $transport->getRequests(), 'No HTTP request should have been issued');
        }
    }

    public function testListHistoryAppendsCursorAsQueryString(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => ['items' => []],
        ]);
        $client = $this->makeClient($transport);

        $client->listHistory(null, null, 'abc=def');

        self::assertSame('https://api.heleket.com/v1/payment/list?cursor=abc%3Ddef', $transport->getLastRequest()['url']);
    }

    public function testGetExchangeRatesIssuesSignedGetRequestWithEmptyBody(): void
    {
        $rates = [
            ['from' => 'USD', 'to' => 'BTC', 'course' => '0.000016', 'source' => 'Binance'],
        ];
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => $rates,
        ]);
        $client = $this->makeClient($transport);

        $result = $client->getExchangeRates('USD');

        self::assertSame($rates, $result);

        $request = $transport->getLastRequest();
        self::assertSame('GET', $request['method']);
        self::assertSame('https://api.heleket.com/v1/exchange-rate/USD/list', $request['url']);
        self::assertSame('', $request['body'], 'A GET carries its input in the path, so the signed body is empty');
        self::assertSame(self::MERCHANT_ID, $request['headers']['merchant']);
        self::assertSame(md5(self::API_KEY), $request['headers']['sign']);
        self::assertSame(Version::userAgent(), $request['headers']['User-Agent']);
    }

    public function testGetAmlLinksSendsSignedPostByUuid(): void
    {
        $links = [
            ['link' => 'https://some.link/1', 'expired_at' => '2025-10-23T18:23:40.000000Z', 'status' => 'completed'],
            ['link' => 'https://some.link/2', 'expired_at' => '2026-05-13T11:32:38.000000Z', 'status' => 'init'],
        ];
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => $links,
        ]);
        $client = $this->makeClient($transport);

        $result = $client->getAmlLinks('uuid-1');

        self::assertSame($links, $result);

        $request = $transport->getLastRequest();
        self::assertSame('POST', $request['method']);
        self::assertSame('https://api.heleket.com/v1/payment/aml-links', $request['url']);
        self::assertSame(self::MERCHANT_ID, $request['headers']['merchant']);

        $expectedBody = (string) json_encode(['uuid' => 'uuid-1'], JSON_UNESCAPED_UNICODE);
        self::assertSame($expectedBody, $request['body']);
        self::assertSame(md5(base64_encode($expectedBody) . self::API_KEY), $request['headers']['sign']);
    }

    public function testGetAmlLinksLooksUpByOrderId(): void
    {
        $transport = (new FakeTransport())->enqueueJson([
            'state'  => 0,
            'result' => [],
        ]);
        $client = $this->makeClient($transport);

        $client->getAmlLinks(null, 'order-1');

        $request = $transport->getLastRequest();
        self::assertSame('https://api.heleket.com/v1/payment/aml-links', $request['url']);
        self::assertSame(
            (string) json_encode(['order_id' => 'order-1'], JSON_UNESCAPED_UNICODE),
            $request['body']
        );
    }

    public function testAmlLinksRequireUuidOrOrderId(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->expectException(InvalidArgumentException::class);
        $client->getAmlLinks();
    }

    private function makeClient(FakeTransport $transport): PaymentClient
    {
        return new PaymentClient(new Config(self::MERCHANT_ID, self::API_KEY), $transport);
    }
}
