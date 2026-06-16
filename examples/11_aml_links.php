<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Enum\AmlLinkStatus;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php examples/11_aml_links.php <uuid-or-order-id>\n");
    exit(1);
}
$identifier = $argv[1];

$client = make_payment_client();

// Caller can pass either a UUID or an order_id; we try UUID first.
$isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier);

$links = $isUuid
    ? $client->getAmlLinks($identifier, null)
    : $client->getAmlLinks(null, $identifier);

print_result('AML links', $links);

// Hand each `link` to the end user so they can complete the questionnaire.
foreach ($links as $link) {
    $status = (string) ($link['status'] ?? '');
    printf(
        "\n%s\n  status: %s %s\n  expires: %s\n",
        (string) ($link['link'] ?? ''),
        $status,
        AmlLinkStatus::isFinal($status) ? '(final)' : '(in progress)',
        (string) ($link['expired_at'] ?? '')
    );
}
