<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($argc < 2) {
    fwrite(STDERR, "Usage: php examples/08_get_payout_info.php <uuid-or-order-id>\n");
    exit(1);
}
$identifier = $argv[1];

$client = make_payout_client();

$isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier);
$info = $isUuid ? $client->getInfo($identifier, null) : $client->getInfo(null, $identifier);

print_result('Payout info', $info);
