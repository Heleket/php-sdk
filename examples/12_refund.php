<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Exception\ApiException;
use Heleket\Exception\ValidationException;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php examples/12_refund.php <invoice-uuid> <refund-address>\n");
    exit(1);
}
[, $uuid, $address] = $argv;

// Refund lives on the PAYOUT client: /v1/payment/refund is signed with the
// payout API key, not the payment key.
$client = make_payout_client();

try {
    $refund = $client->refund([
        'uuid'        => $uuid,
        'address'     => $address,
        'is_subtract' => true,
    ]);

    print_result('Refund requested', $refund);
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
