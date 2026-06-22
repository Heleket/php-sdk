# 05 — Payouts API

Withdrawals from the merchant balance to external addresses. Uses the **payout API key** — different from the payment key.

```php
use Heleket\Client;
$client = Client::payout($payoutApiKey, $merchantId);
```

Error model identical to `PaymentClient` — see [09 — Error handling](09-error-handling.md).

## createPayout

`createPayout(array $params): array`  →  `POST /v1/payout`

Required: `amount`, `currency`, `order_id`, `address`, `is_subtract`.

Important fields:

| Field | Meaning |
|---|---|
| `is_subtract` | `true` → commission deducted from `amount`; `false` → commission added on top |
| `network` | Required for multi-network coins (USDT etc.). Omit for BTC, ETH-only chains |
| `priority` | `economy`, `high`, `highest`, or `recommended` (default) — BTC/ETH/POLY/BSC only |
| `to_currency` | Required if `currency` is fiat (e.g. USD → USDT) |
| `memo` | Required for TON (1–30 chars) |

```php
$payout = $client->createPayout([
    'amount'      => '5.00',
    'currency'    => 'USDT',
    'network'     => 'TRON',
    'order_id'    => 'payout-001',
    'address'     => 'TDD97yguPESTpcrJMqU6h2ozZbibv4Vaqm',
    'is_subtract' => true,
    'priority'    => 'recommended',
    'url_callback'=> 'https://your.site/heleket-payout-webhook',
]);

echo $payout['uuid'];
echo $payout['status'];   // typically "process" initially
echo $payout['balance'];  // remaining merchant balance after deduction
```

## refund

`refund(array $params): array`  →  `POST /v1/payment/refund`

Refund a paid invoice in full or in part. The endpoint sits under `/v1/payment/*`
but is **signed with the payout API key**, so it lives on `PayoutClient` (not
`PaymentClient`). Required: `address`, `is_subtract`; one of `uuid` / `order_id`.

```php
$client->refund([
    'uuid'        => $invoiceUuid,
    'address'     => 'TBaCkAdDrEsS',
    'is_subtract' => true,
]);
```

## getInfo

`getInfo(?string $uuid = null, ?string $orderId = null): array`  →  `POST /v1/payout/info`

```php
$info = $client->getInfo(uuid: $payout['uuid']);
echo $info['status']; // see docs/10-reference.md
```

## listHistory

`listHistory(?string $dateFrom = null, ?string $dateTo = null, ?string $cursor = null): array`  →  `POST /v1/payout/list`

Same shape as `PaymentClient::listHistory`.

```php
$page = $client->listHistory('2026-01-01 00:00:00', null);
foreach ($page['items'] as $payout) { /* ... */ }
```

## calculateWithdrawalAmount

`calculateWithdrawalAmount(string $currency, string $network, string $amount, bool $isSubtract = false): array`  →  `POST /v1/payout/calculate`

Preview the commission and final amount before committing.

```php
$preview = $client->calculateWithdrawalAmount('USDT', 'TRON', '100', true);
echo $preview['commission'];      // e.g. "1.00"
echo $preview['merchant_amount']; // amount debited from your balance
```

## listServices

`listServices(): array`  →  `POST /v1/payout/services`

Catalogue of supported (currency, network) pairs for withdrawals.

```php
$services = $client->listServices();
```

## transferToPersonal

`transferToPersonal(string $amount, string $currency): array`  →  `POST /v1/transfer/to-personal`

Move funds from the business balance to the personal wallet (same Heleket account).

```php
$client->transferToPersonal('10.00', 'USDT');
```

## transferToBusiness

`transferToBusiness(string $amount, string $currency): array`  →  `POST /v1/transfer/to-business`

The reverse transfer.

```php
$client->transferToBusiness('10.00', 'USDT');
```

## Next

→ [06 — Webhooks](06-webhooks.md) — **read before going to production**.
