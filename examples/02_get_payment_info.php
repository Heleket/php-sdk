<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Enum\PaymentStatus;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php examples/02_get_payment_info.php <uuid-or-order-id>\n");
    exit(1);
}
$identifier = $argv[1];

$client = make_payment_client();

// Caller can pass either a UUID or an order_id; we try UUID first.
$isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier);

$info = $isUuid
    ? $client->getInfo($identifier, null)
    : $client->getInfo(null, $identifier);

print_result('Payment info', $info);

$status = (string) ($info['status'] ?? $info['payment_status'] ?? '');
echo "\nStatus: ", $status, "\n";
echo PaymentStatus::isFinal($status) ? "(final)\n" : "(intermediate — keep polling or wait for webhook)\n";
