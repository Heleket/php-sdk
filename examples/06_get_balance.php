<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$client = make_payment_client();
$balance = $client->getBalance();

print_result('Balance', $balance);
