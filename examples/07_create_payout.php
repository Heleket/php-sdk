<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Exception\ApiException;
use Heleket\Exception\ValidationException;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php examples/07_create_payout.php <amount> <address>\n");
    exit(1);
}
[, $amount, $address] = $argv;

$client = make_payout_client();

try {
    $payout = $client->createPayout([
        'amount'       => $amount,
        'currency'     => 'USDT',
        'network'      => 'TRON',
        'order_id'     => 'payout-' . bin2hex(random_bytes(4)),
        'address'      => $address,
        'is_subtract'  => true,
        'url_callback' => env_optional('HELEKET_PAYOUT_WEBHOOK_URL', ''),
    ]);

    print_result('Payout created', $payout);
} catch (ValidationException $exception) {
    fwrite(STDERR, "Validation failed:\n");
    foreach ($exception->getErrors() as $field => $messages) {
        fwrite(STDERR, "  {$field}: " . implode(', ', $messages) . "\n");
    }
    exit(2);
} catch (ApiException $exception) {
    fwrite(STDERR, "API error (HTTP {$exception->getHttpStatus()}): {$exception->getMessage()}\n");
    exit(3);
}
