# 02 — Configuration

## Two API keys, two clients

Heleket has two distinct API keys:

| Key | Used by | SDK factory |
|---|---|---|
| Payment API key | `/v1/payment/*`, `/v1/wallet/*`, `/v1/balance`, `/v1/exchange-rate/*` | `Client::payment()` |
| Payout API key  | `/v1/payout/*`, `/v1/transfer/*` | `Client::payout()` |

Each key is also used to verify **its own** webhooks (payment webhooks → payment key; payout webhooks → payout key).

> **One cross-key exception:** `/v1/payment/refund` is a payment-domain path but is
> signed with the **payout** API key, so it is exposed as `PayoutClient::refund()`.
> `PaymentClient::refund()` is a deprecated stub that throws.

## Default construction

```php
use Heleket\Client;

$payment = Client::payment($paymentApiKey, $merchantId);
$payout  = Client::payout($payoutApiKey,  $merchantId);
```

`Client::payment()` and `Client::payout()` accept an optional third argument: `debug` (bool).

```php
$payment = Client::payment($paymentApiKey, $merchantId, debug: true);
```

When `debug = true`, every HTTP request and response is dumped to `stderr`. See [07 — Debugging](07-debugging.md).

## Advanced construction

`Config` is the immutable settings object. Use it when you want to override base URL, timeout, or inject a custom transport.

```php
use Heleket\Config;
use Heleket\PaymentClient;

$config = new Config(
    merchantId: $merchantId,
    apiKey: $paymentApiKey,
    baseUrl: 'https://api.heleket.com',
    timeoutSeconds: 30,
    debug: false
);

$payment = new PaymentClient($config);
```

`Config` provides immutable mutators:

```php
$debug = $config->withDebug(true);
$slow  = $config->withTimeoutSeconds(60);
```

## Custom HTTP transport

The SDK ships with `CurlTransport` (default). Pass any `TransportInterface` implementation to swap it out — useful for Guzzle middleware, retry policies, or test doubles.

```php
use Heleket\Client;

$payment = Client::paymentWith($config, new MyGuzzleTransport());
```

See the `TransportInterface` source — it's a single-method contract.

## Environment variables (recommended)

The example bootstrap reads these:

```env
HELEKET_MERCHANT_ID=...
HELEKET_PAYMENT_KEY=...
HELEKET_PAYOUT_KEY=...
HELEKET_DEBUG=0
```

Use the same names in your production environment for consistency.

## Timeouts

Default is 30 seconds (covers both connect and total). Heleket's `/v1/payout` and `/v1/payment` endpoints can take a few seconds during blockchain confirmations — don't drop below 10s.

```php
$config = $config->withTimeoutSeconds(60);
```

## Next

→ [03 — Architecture](03-architecture.md) or jump to [04 — Payments API](04-payments.md).
