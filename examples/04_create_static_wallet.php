<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$client = make_payment_client();

$wallet = $client->createStaticWallet([
    'currency' => 'USDT',
    'network'  => 'tron',
    'order_id' => 'wallet-' . bin2hex(random_bytes(4)),
]);

print_result('Static wallet', $wallet);
echo "\nAddress: ", $wallet['address'] ?? '(unknown)', "\n";
