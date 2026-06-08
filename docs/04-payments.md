# 04 — Payments API

Every method on `PaymentClient`. Each section shows the signature, parameters, return shape sketch, and a runnable example.

All examples assume:

```php
use Heleket\Client;
$client = Client::payment($paymentApiKey, $merchantId);
```

Errors:

- `Heleket\Exception\ValidationException` on HTTP 422 (with `getErrors()`)
- `Heleket\Exception\ApiException` on any other API failure (with `getHttpStatus()`, `getRawBody()`)
- `Heleket\Exception\HttpException` on transport failure
- `\InvalidArgumentException` when the SDK rejects bad arguments before sending

See [09 — Error handling](09-error-handling.md) for catch strategies.

## createInvoice

`createInvoice(array $params): array`  →  `POST /v1/payment`

Required: `amount`, `currency`, `order_id`.

Common optional parameters: `network`, `to_currency`, `url_return`, `url_success`, `url_callback`, `lifetime`, `is_payment_multiple`, `subtract`, `accuracy_payment_percent`, `additional_data`, `currencies`, `except_currencies`, `course_source`, `discount_percent`, `is_refresh`, `payer_email`.

```php
$invoice = $client->createInvoice([
    'amount'       => '15.00',
    'currency'     => 'USD',
    'order_id'     => 'order-42',
    'lifetime'     => 3600,
    'url_callback' => 'https://your.site/heleket-webhook',
]);

echo $invoice['url'];           // payment page
echo $invoice['address'];       // wallet address (for payer)
echo $invoice['payment_status']; // initial status (typically "check")
```

## getInfo

`getInfo(?string $uuid = null, ?string $orderId = null): array`  →  `POST /v1/payment/info`

Provide one of `uuid` or `orderId`. The server prioritises `order_id` when both are passed.

```php
$info = $client->getInfo(uuid: $invoiceUuid);
// or
$info = $client->getInfo(orderId: 'order-42');

echo $info['status']; // see docs/10-reference.md for the full enumeration
```

## listHistory

`listHistory(?string $dateFrom = null, ?string $dateTo = null, ?string $cursor = null): array`  →  `POST /v1/payment/list`

Date format: `Y-m-d H:i:s`. Pagination cursors come from the previous response's `paginate.nextCursor` / `paginate.previousCursor`.

```php
$page = $client->listHistory('2026-01-01 00:00:00', '2026-05-20 23:59:59');

foreach ($page['items'] as $invoice) {
    // ...
}

if (!empty($page['paginate']['nextCursor'])) {
    $next = $client->listHistory(null, null, $page['paginate']['nextCursor']);
}
```

## createStaticWallet

`createStaticWallet(array $params): array`  →  `POST /v1/wallet`

Required: `currency`, `network`, `order_id`. Optional `url_callback`.

A static wallet is a persistent address bound to an order ID. Any incoming transfer is credited to the merchant.

```php
$wallet = $client->createStaticWallet([
    'currency' => 'USDT',
    'network'  => 'tron',
    'order_id' => 'topup-user-7',
]);

echo $wallet['address'];
```

## generateQrCode

`generateQrCode(string $merchantPaymentUuid): array`  →  `POST /v1/wallet/qr`

Returns a base64-encoded QR-code image (data URI) for the given static wallet.

```php
$qr = $client->generateQrCode($wallet['uuid']);
echo '<img src="' . $qr['image'] . '">';
```

## blockStaticWallet

`blockStaticWallet(?string $uuid = null, ?string $orderId = null, bool $isRefund = false): array`  →  `POST /v1/wallet/block-address`

Stop accepting transfers on a static wallet. Set `isRefund: true` to release locked AML funds back to the original sender.

```php
$client->blockStaticWallet(orderId: 'topup-user-7', isRefund: false);
```

## refundBlockedWallet

`refundBlockedWallet(string $uuid, string $address): array`  →  `POST /v1/wallet/blocked-address-refund`

Send the contents of a blocked wallet to a recovery address.

```php
$client->refundBlockedWallet($wallet['uuid'], 'TXyz...recovery-address...');
```

## refund

`refund(array $params): array`  →  `POST /v1/payment/refund`

Required: `address`, `is_subtract`; one of `uuid` / `order_id`.

```php
$client->refund([
    'uuid'        => $invoiceUuid,
    'address'     => 'TBaCkAdDrEsS',
    'is_subtract' => true,
]);
```

## resendWebhook

`resendWebhook(?string $uuid = null, ?string $orderId = null): array`  →  `POST /v1/payment/resend`

Asks Heleket to redeliver the last webhook for the invoice. Useful for replaying a missed callback.

```php
$client->resendWebhook(orderId: 'order-42');
```

## testWebhook

`testWebhook(string $type, string $url, string $currency, string $network, string $status, ?string $uuid = null, ?string $orderId = null): array`  →  `POST /v1/test-webhook/{payment|wallet}`

Sends a synthetic webhook event to your callback URL — used to verify your handler in development.

```php
$client->testWebhook(
    type:    'payment',
    url:     'https://your.site/heleket-webhook',
    currency:'USD',
    network: 'tron',
    status:  'paid',
    orderId: 'order-42'
);
```

## listServices

`listServices(): array`  →  `POST /v1/payment/services`

Returns the catalogue of supported (currency, network) combinations and their per-pair limits.

```php
$services = $client->listServices();
foreach ($services as $service) {
    printf("%s on %s: min=%s max=%s\n",
        $service['currency'], $service['network'],
        $service['min_amount'], $service['max_amount']);
}
```

## getBalance

`getBalance(): array`  →  `POST /v1/balance`

Returns merchant and personal wallet balances by currency.

```php
$balance = $client->getBalance();
foreach ($balance[0]['balance']['merchant'] as $row) {
    printf("%s: %s\n", $row['currency_code'], $row['balance']);
}
```

## getExchangeRates

`getExchangeRates(string $currency): array`  →  `POST /v1/exchange-rate/{currency}/list`

```php
$rates = $client->getExchangeRates('USD');
foreach ($rates as $rate) {
    printf("%s -> %s = %s (from %s)\n",
        $rate['from'], $rate['to'], $rate['course'], $rate['source']);
}
```

## Next

→ [05 — Payouts API](05-payouts.md)
