<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Heleket\Exception\SignatureException;
use Heleket\Webhook\WebhookVerifier;

/**
 * Run with PHP's built-in server:
 *     php -S 0.0.0.0:8000 examples/05_handle_webhook.php
 *
 * Then point your Heleket invoice's url_callback to http://<your-host>:8000/
 * (use ngrok or any tunnel during local development).
 *
 * The handler reads the raw POST body, verifies the signature against the
 * payment API key, and responds 200 OK. Anything that's not a verified POST
 * is rejected with a 400.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo "POST required\n";
    return;
}

$rawBody = (string) file_get_contents('php://input');

$paymentKey = env_require('HELEKET_PAYMENT_KEY');
$verifier = new WebhookVerifier($paymentKey);

try {
    $payload = $verifier->verifyRawBody($rawBody);
} catch (SignatureException $exception) {
    error_log('[heleket] signature mismatch: ' . $exception->getMessage());
    http_response_code(400);
    echo "Invalid signature\n";
    return;
}

error_log(sprintf(
    '[heleket] received %s for order %s: status=%s is_final=%s',
    $payload->getType(),
    $payload->getOrderId(),
    $payload->getStatus(),
    $payload->isFinal() ? 'yes' : 'no'
));

// Here you would update your database, send a receipt email, etc.
// Heleket retries until you return 200, so respond fast and queue heavy work.
//
// PRODUCTION: this example does NOT de-duplicate. Heleket will replay events
// after retries and operators can resend manually. See docs/06-webhooks.md
// ("Idempotency and replay protection") for the recommended pattern using a
// unique (uuid, status) key in your database.

http_response_code(200);
echo "OK\n";
