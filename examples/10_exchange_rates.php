<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$currency = $argv[1] ?? 'USD';

$client = make_payment_client();
$rates = $client->getExchangeRates($currency);

print_result('Exchange rates for ' . $currency, $rates);
