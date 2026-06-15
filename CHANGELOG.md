# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-06-15

Complete, ground-up rewrite. Not source-compatible with 1.x — see
[`UPGRADING.md`](UPGRADING.md).

### Added
- `Heleket\Client` factory returning typed `PaymentClient` / `PayoutClient`.
- Payments API: invoices, info, history, static wallets, QR codes, refunds,
  webhook resend, test webhooks.
- Payouts API: payouts, info, history, fee calculation, transfers, services.
- Balance and exchange-rate endpoints.
- Signed webhook verification (`WebhookVerifier`, `WebhookPayload`) with
  constant-time comparison.
- Swappable HTTP transport (`TransportInterface`, `CurlTransport`,
  `FakeTransport` for offline tests).
- Debug mode (`DebugDumper`) and a webhook inspector CLI
  (`bin/heleket-webhook-inspect`).
- Status enums with `isFinal()` / `isSuccessful()` helpers.

### Changed
- Minimum PHP raised to **7.4** (was 5.6).
- **Zero runtime dependencies** (only `ext-curl`, `ext-json`).
- SDK version reported in the `User-Agent` is resolved from Composer at runtime.

[2.0.0]: https://github.com/Heleket/php-sdk/releases/tag/2.0.0
