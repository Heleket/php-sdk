<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Exception\ApiException;
use Heleket\Exception\ValidationException;

$client = make_payment_client();

try {
    $invoice = $client->createInvoice([
        'amount'       => '15.00',
        'currency'     => 'USD',
        'order_id'     => 'demo-' . bin2hex(random_bytes(4)),
        'lifetime'     => 3600,
        'url_callback' => env_optional('HELEKET_WEBHOOK_URL', ''),
    ]);

    print_result('Invoice created', $invoice);
    echo "\nPayment page: ", $invoice['url'] ?? '(no url)', "\n";
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
