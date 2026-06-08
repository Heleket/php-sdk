<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$kind = $argv[1] ?? 'payment';

if ($kind === 'payment') {
    $services = make_payment_client()->listServices();
} elseif ($kind === 'payout') {
    $services = make_payout_client()->listServices();
} else {
    fwrite(STDERR, "Usage: php examples/09_list_services.php [payment|payout]\n");
    exit(1);
}

print_result(ucfirst($kind) . ' services', $services);
