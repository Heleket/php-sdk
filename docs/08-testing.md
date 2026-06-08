# 08 — Testing

## Running the SDK's own tests

```bash
make test           # PHPUnit
make stan           # PHPStan level 8
make cs             # PHP-CS-Fixer dry-run
make qa             # All three
```

Or directly:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

In Docker:

```bash
make docker-qa
```

## Writing tests against the SDK

The SDK is fully testable offline thanks to `Heleket\Http\TransportInterface`. Inject `Heleket\Tests\Fakes\FakeTransport` (or your own implementation) and the client will skip the network entirely.

```php
use Heleket\Config;
use Heleket\PaymentClient;
use Heleket\Tests\Fakes\FakeTransport;

$transport = (new FakeTransport())->enqueueJson([
    'state' => 0,
    'result' => ['uuid' => 'uuid-1', 'url' => 'https://pay/...'],
]);

$client = new PaymentClient(new Config('merchant', 'key'), $transport);

$result = $client->createInvoice(['amount' => '1', 'currency' => 'USD', 'order_id' => 'x']);
self::assertSame('uuid-1', $result['uuid']);

// Inspect what the SDK sent:
$request = $transport->getLastRequest();
self::assertSame('POST', $request['method']);
self::assertStringContainsString('/v1/payment', $request['url']);
```

## FakeTransport API

| Method | Purpose |
|---|---|
| `enqueue(Response $response)` | Queue any pre-built `Response` |
| `enqueueJson(array $payload, int $status = 200)` | Convenience: JSON-encode the array |
| `getRequests()` | All recorded requests in order |
| `getLastRequest()` | Most recent request |
| `failNext(string $message)` | Make the next call throw `HttpException` |

If the queue runs dry, FakeTransport throws — your test will see a clear "no enqueued response" message.

## Integration tests against real Heleket

For end-to-end smoke tests, gate them on environment variables and skip when missing:

```php
public function setUp(): void
{
    if (!getenv('HELEKET_PAYMENT_KEY')) {
        self::markTestSkipped('Set HELEKET_PAYMENT_KEY to run integration tests');
    }
    $this->client = Client::payment(getenv('HELEKET_PAYMENT_KEY'), getenv('HELEKET_MERCHANT_ID'));
}
```

Group them with `@group integration` and exclude from CI by default.

## CI example (GitHub Actions)

```yaml
name: PHP SDK
on: [push, pull_request]
jobs:
  qa:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          tools: composer:v2
          extensions: curl, json
      - run: composer install --no-progress --prefer-dist
      - run: make qa
```

## Next

→ [09 — Error handling](09-error-handling.md)
