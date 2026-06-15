# Heleket PHP SDK

[![Packagist Version](https://img.shields.io/packagist/v/heleket/php-sdk)](https://packagist.org/packages/heleket/php-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/heleket/php-sdk)](https://packagist.org/packages/heleket/php-sdk)
[![License](https://img.shields.io/packagist/l/heleket/php-sdk)](LICENSE)
[![CI](https://github.com/Heleket/php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/Heleket/php-sdk/actions/workflows/ci.yml)

The official PHP SDK for the [Heleket](https://heleket.com) cryptocurrency payment API. Covers the full documented surface (payments, payouts, balance, services, exchange rates), ships with unit tests, runnable examples, a debug mode, a webhook inspector CLI, and a Docker harness.

Built to be copied or `composer require`'d directly into your project. Zero runtime dependencies — only `ext-curl` and `ext-json` (bundled with PHP).

## Quickstart

```php
require __DIR__ . '/vendor/autoload.php';

use Heleket\Client;

$payment = Client::payment($paymentApiKey, $merchantId);

$invoice = $payment->createInvoice([
    'amount'   => '15.00',
    'currency' => 'USD',
    'order_id' => 'order-42',
]);

echo $invoice['url']; // → https://pay.heleket.com/pay/<uuid>
```

## Install

```bash
composer require heleket/php-sdk
```

…or clone this folder into your project and add it to your autoloader.

Requirements: PHP 7.4+, `ext-curl`, `ext-json`. **For new deployments, target PHP 8.2+ — 7.4 is end-of-life.** The bundled Docker image uses PHP 8.3.

## Documentation

Full reference lives in [`docs/`](docs/README.md):

- [01 — Installation](docs/01-installation.md)
- [02 — Configuration](docs/02-configuration.md)
- [03 — Architecture](docs/03-architecture.md)
- [04 — Payments API](docs/04-payments.md)
- [05 — Payouts API](docs/05-payouts.md)
- [06 — Webhooks](docs/06-webhooks.md) ⚑ critical reading
- [07 — Debugging](docs/07-debugging.md)
- [08 — Testing](docs/08-testing.md)
- [09 — Error handling](docs/09-error-handling.md)
- [10 — Reference (statuses, currencies, endpoints)](docs/10-reference.md)
- [12 — Troubleshooting (top mistakes)](docs/12-troubleshooting.md)

## What's in the box

```
src/         Production code — zero dependencies beyond ext-curl, ext-json
tests/       PHPUnit unit tests + FakeTransport for offline testing
examples/    Ten runnable scripts (01..10) demonstrating every endpoint
bin/         heleket-webhook-inspect — CLI to verify and dump any webhook payload
docker/      php:7.4-cli-alpine with Composer baked in
docs/        Full module documentation in English
```

## Common tasks

```bash
make install              # Composer install
make test                 # PHPUnit
make stan                 # PHPStan level 8
make qa                   # All quality gates
make example-invoice      # Create a real invoice (needs .env)
make example-webhook      # Run the webhook listener on :8000
make docker-shell         # Drop into a containerized PHP 7.4 shell
make help                 # Full target list
```

## Security notes (read this)

- **Always verify webhook signatures.** See [docs/06-webhooks.md](docs/06-webhooks.md). Never trust the payload otherwise.
- **Whitelist Heleket's webhook source IP `31.133.220.8`** at your firewall or reverse proxy.
- **Never log raw API keys** — the debug dumper logs only the request method, URL, and body (never headers), but a misconfigured logger wrapping the SDK can still capture credentials.
- **Two separate API keys** — payments and payouts. Don't mix them up; webhooks of the wrong kind will fail verification.

## Releasing

Versions are published to Packagist automatically from git tags on
[`github.com/Heleket/php-sdk`](https://github.com/Heleket/php-sdk) (the Packagist ↔ GitHub webhook is already wired up).

1. Land changes on `main`; make sure `make qa` is green.
2. Update [`CHANGELOG.md`](CHANGELOG.md).
3. Tag and push: `git tag 2.1.0 && git push origin 2.1.0`.

Packagist picks up the new tag within a minute. The version reported in the `User-Agent` header is read from Composer at runtime, so there is nothing else to bump.

**Major versions:** the 1.x line lives on the `1.x` branch (tag `1.0.0`) and must keep working — never delete or move old tags. See [`UPGRADING.md`](UPGRADING.md) for the 1.x → 2.x migration guide.

## License

MIT — see [`LICENSE`](LICENSE).
