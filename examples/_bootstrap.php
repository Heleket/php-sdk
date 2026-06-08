<?php

declare(strict_types=1);

/**
 * Shared bootstrap for every example script.
 *
 *  - Autoloads the SDK via Composer
 *  - Loads .env (or .env.example as a fallback for read-only smoke testing)
 *  - Exposes env_require(), env_optional(), make_payment_client(), make_payout_client(),
 *    print_result()
 *
 * Intentionally minimal — no external dependency on phpdotenv. ~30 lines.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Composer autoload not found. Run `composer install` first.\n");
    exit(1);
}
require $autoload;

function heleket_load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $name = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        $value = trim($value, "\"'");
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

heleket_load_env(__DIR__ . '/../.env');

function env_require(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        fwrite(STDERR, "Missing required env var: {$name}\nCopy .env.example to .env and fill it in.\n");
        exit(1);
    }
    return $value;
}

function env_optional(string $name, string $default = ''): string
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

function make_payment_client(): \Heleket\PaymentClient
{
    return \Heleket\Client::payment(
        env_require('HELEKET_PAYMENT_KEY'),
        env_require('HELEKET_MERCHANT_ID'),
        env_optional('HELEKET_DEBUG', '0') === '1'
    );
}

function make_payout_client(): \Heleket\PayoutClient
{
    return \Heleket\Client::payout(
        env_require('HELEKET_PAYOUT_KEY'),
        env_require('HELEKET_MERCHANT_ID'),
        env_optional('HELEKET_DEBUG', '0') === '1'
    );
}

/**
 * @param array<string, mixed>|mixed $value
 */
function print_result(string $label, $value): void
{
    echo "\n=== {$label} ===\n";
    echo json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
}
