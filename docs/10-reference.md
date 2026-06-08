# 10 — Reference

Quick lookup tables.

## Payment statuses

| Status | Final? | Successful? | Meaning |
|---|---|---|---|
| `paid` | ✓ | ✓ | Exact amount received |
| `paid_over` | ✓ | ✓ | Overpayment — also a success |
| `wrong_amount` | ✓ | — | Underpaid, no further attempts allowed |
| `wrong_amount_waiting` | — | — | Underpaid, additional top-ups still accepted |
| `check` | — | — | Waiting for the transaction to appear on-chain |
| `confirm_check` | — | — | Seen on-chain; waiting for confirmations |
| `process` | — | — | Generic processing state |
| `fail` | ✓ | — | Payment error |
| `cancel` | ✓ | — | Client abandoned the invoice |
| `system_fail` | ✓ | — | System-side error |
| `locked` | ✓ | — | AML hold |
| `refund_process` | — | — | Refund in flight |
| `refund_paid` | ✓ | — | Refund completed |
| `refund_fail` | ✓ | — | Refund failed |

Helpers in `Heleket\Enum\PaymentStatus`:

```php
PaymentStatus::isFinal($status);
PaymentStatus::isSuccessful($status);
```

## Payout statuses

| Status | Final? | Successful? |
|---|---|---|
| `process` | — | — |
| `check` | — | — |
| `paid` | ✓ | ✓ |
| `fail` | ✓ | — |
| `cancel` | ✓ | — |
| `system_fail` | ✓ | — |

Helpers in `Heleket\Enum\PayoutStatus`.

## Exchange-rate sources

`Heleket\Enum\CourseSource`:

- `Binance`
- `BinanceP2P`
- `Exmo`
- `Kucoin`

## Endpoint table

| Method | Path | Surface |
|---|---|---|
| `createInvoice` | `POST /v1/payment` | PaymentClient |
| `getInfo` (payment) | `POST /v1/payment/info` | PaymentClient |
| `listHistory` (payment) | `POST /v1/payment/list` | PaymentClient |
| `refund` | `POST /v1/payment/refund` | PaymentClient |
| `resendWebhook` | `POST /v1/payment/resend` | PaymentClient |
| `listServices` (payment) | `POST /v1/payment/services` | PaymentClient |
| `createStaticWallet` | `POST /v1/wallet` | PaymentClient |
| `generateQrCode` | `POST /v1/wallet/qr` | PaymentClient |
| `blockStaticWallet` | `POST /v1/wallet/block-address` | PaymentClient |
| `refundBlockedWallet` | `POST /v1/wallet/blocked-address-refund` | PaymentClient |
| `testWebhook` | `POST /v1/test-webhook/{payment\|wallet}` | PaymentClient |
| `getBalance` | `POST /v1/balance` | PaymentClient |
| `getExchangeRates` | `POST /v1/exchange-rate/{currency}/list` | PaymentClient |
| `createPayout` | `POST /v1/payout` | PayoutClient |
| `getInfo` (payout) | `POST /v1/payout/info` | PayoutClient |
| `listHistory` (payout) | `POST /v1/payout/list` | PayoutClient |
| `calculateWithdrawalAmount` | `POST /v1/payout/calculate` | PayoutClient |
| `listServices` (payout) | `POST /v1/payout/services` | PayoutClient |
| `transferToPersonal` | `POST /v1/transfer/to-personal` | PayoutClient |
| `transferToBusiness` | `POST /v1/transfer/to-business` | PayoutClient |

## Currency and network codes

Heleket's catalogue evolves. Always source the authoritative list from `listServices()` at runtime rather than hard-coding values. Examples seen at the time of writing:

- Currencies: `BTC`, `ETH`, `USDT`, `USDC`, `DAI`, `LTC`, `BCH`, `XRP`, `TRX`, `TON`, `BNB`, `MATIC`, `DOGE`, `SHIB`, `DASH`, `XMR`
- Networks: `bitcoin`, `ethereum`, `tron`, `bsc`, `polygon`, `ton`, `litecoin`, `bch`, `ripple`, `dogecoin`, `dash`, `monero`

## HTTP / signing reminders

- All requests use `POST` (even reads).
- All requests include `merchant`, `sign`, and `Content-Type: application/json` headers.
- `sign` = `md5(base64_encode(json_body) . apiKey)`. For no-arg endpoints, body and sign are computed over the empty string.
- Webhook payload's `sign` field uses the same formula against the corresponding API key.

## Next

→ [12 — Troubleshooting](12-troubleshooting.md).
