# Upgrading

## 2.1 → 2.2

`refund` moved from `PaymentClient` to `PayoutClient`. The Heleket API now signs
`POST /v1/payment/refund` with the **payout** API key, so a payment client can no
longer produce a valid signature for it.

`PaymentClient::refund()` is kept as a deprecated stub that throws
`\BadMethodCallException`. Switch to `PayoutClient::refund()`:

```php
// Before (2.1 and earlier)
$payment = Client::payment($paymentApiKey, $merchantId);
$payment->refund(['uuid' => $invoiceUuid, 'address' => $addr, 'is_subtract' => true]);

// After (2.2)
$payout = Client::payout($payoutApiKey, $merchantId);
$payout->refund(['uuid' => $invoiceUuid, 'address' => $addr, 'is_subtract' => true]);
```

Everything else is unchanged — this is an additive minor release.

## 1.x → 2.x

Version 2.0 is a **complete, ground-up rewrite** of the SDK with a new, fully
documented API. It is not source-compatible with 1.x — treat the upgrade as a
small reintegration rather than a drop-in bump.

### What changed at a glance

- **Minimum PHP is now 7.4** (1.x supported 5.6). Projects on older PHP stay on
  1.x automatically — Composer will not offer 2.0 to them.
- **Zero runtime dependencies** — only `ext-curl` and `ext-json`.
- **New entry point:** a `Heleket\Client` factory returns typed
  `PaymentClient` / `PayoutClient` instances.
- **Arrays in, arrays out:** every endpoint takes an associative array of
  parameters and returns the API's `result` as an associative array.
- **Typed exceptions:** `ValidationException` (HTTP 422), `ApiException`
  (non-zero `state`), `HttpException` (transport), `SignatureException`
  (webhook verification).
- **First-class webhook verification:** `Heleket\Webhook\WebhookVerifier` with
  constant-time signature comparison and a typed `WebhookPayload`.

### Install

```bash
composer require heleket/php-sdk:^2.0
```

### The 2.x way

```php
use Heleket\Client;

$payment = Client::payment($paymentApiKey, $merchantId);

$invoice = $payment->createInvoice([
    'amount'   => '15.00',
    'currency' => 'USD',
    'order_id' => 'order-42',
]);

echo $invoice['url'];
```

Webhooks:

```php
use Heleket\Webhook\WebhookVerifier;
use Heleket\Exception\SignatureException;

$verifier = new WebhookVerifier($paymentApiKey);
try {
    $payload = $verifier->verifyRawBody(file_get_contents('php://input'));
} catch (SignatureException $e) {
    http_response_code(400);
    exit;
}

if ($payload->isSuccessful() && $payload->isFinal()) {
    // fulfil the order ...
}
```

### Migration checklist

1. Bump the constraint to `^2.0` and run `composer update heleket/php-sdk`.
2. Replace 1.x client construction with the `Heleket\Client` factory.
3. Use the new typed exceptions instead of inspecting raw return codes.
4. Move webhook handling to `WebhookVerifier` and **always** verify signatures.
5. Re-read [`docs/`](docs/README.md) — every endpoint is documented, and
   [`docs/06-webhooks.md`](docs/06-webhooks.md) covers the signature pitfalls.

> Need to stay on 1.x for now? Pin `"heleket/php-sdk": "^1.0"`. The 1.x line
> remains installable; it is just no longer actively developed.
