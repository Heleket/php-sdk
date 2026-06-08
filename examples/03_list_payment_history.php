<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$client = make_payment_client();

$dateFrom = $argv[1] ?? null;
$dateTo   = $argv[2] ?? null;

$page = $client->listHistory($dateFrom, $dateTo);
print_result('Page 1', $page);

// Follow the cursor once for demonstration.
$cursor = $page['paginate']['nextCursor'] ?? null;
if (is_string($cursor) && $cursor !== '') {
    $next = $client->listHistory($dateFrom, $dateTo, $cursor);
    print_result('Page 2', $next);
}
