# 01 — Installation

## Requirements

| Component | Minimum version | Notes |
|---|---|---|
| PHP | 7.4 | Minimum supported. Typed properties used throughout |
| `ext-curl` | bundled | Default HTTP transport |
| `ext-json` | bundled | Request and response encoding |
| Composer | 2.x | For dependency management |

The SDK has **no runtime third-party dependencies**. Everything else (PHPUnit, PHPStan, PHP-CS-Fixer) is dev-only.

### Which PHP version should I actually use?

The SDK targets PHP 7.4+ so it installs on shared hosts that haven't upgraded yet. **For new deployments**, use a security-supported release — PHP 8.2 or 8.3 at the time of writing. PHP 7.4 reached end-of-life in November 2022 and receives no further security patches.

The bundled Docker image (see `docker/Dockerfile`) uses PHP 8.3 for exactly this reason — your `composer.json` can stay at `>=7.4` for compatibility while your runtime stays current.

## Install via Composer

```bash
composer require heleket/php-integration-reference
```

Then in your bootstrap:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Install by copying

If your project doesn't use Composer (legacy app, hosting restrictions, etc.), copy the contents of `src/` into your project and register a PSR-4 autoloader for the `Heleket\` namespace.

```php
spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'Heleket\\', 8) !== 0) {
        return;
    }
    $relative = substr($class, 8);
    $file = __DIR__ . '/heleket-sdk/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require $file;
    }
});
```

## Verifying the install

A one-liner that signs an empty body — does not hit the network:

```php
use Heleket\Signature\Signer;
echo (new Signer())->sign('', 'your-api-key'), PHP_EOL;
```

If you see a 32-character lowercase hex string, the SDK is wired up correctly.

## Getting API credentials

You need three pieces of information from <https://dash.heleket.com>:

1. **Merchant UUID** — Settings → API. Looks like `8b03432e-385b-4670-8d06-064591096795`.
2. **Payment API key** — Business → Domain → API key. Used for invoices, balance, exchange rates, and **payment webhook verification**.
3. **Payout API key** — Settings → API → Payout. Used for withdrawals and **payout webhook verification**. Generating a new key locks withdrawals for 24h.

Store them outside source control (use `.env`, secrets manager, or your hosting platform's environment-variable UI). The SDK never logs them in plaintext.

## Next

→ [02 — Configuration](02-configuration.md)
