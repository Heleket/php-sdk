# 09 — Error handling

## Exception hierarchy

```
RuntimeException
└── Heleket\Exception\HeleketException        (base — catch this for "any SDK failure")
    ├── Heleket\Exception\HttpException        (transport: DNS, TCP, TLS, timeout)
    ├── Heleket\Exception\ApiException         (state != 0; non-422)
    │     └── Heleket\Exception\ValidationException  (HTTP 422 with errors map)
    └── Heleket\Exception\SignatureException   (webhook signature mismatch)
```

Plus `\InvalidArgumentException` from the SDK when you pass clearly bad arguments (e.g. neither `uuid` nor `orderId`).

## Quick reference

| Exception | Thrown by | What to do |
|---|---|---|
| `HttpException` | Any client method | Retry with backoff; check network/timeout |
| `ValidationException` | Any client method | Surface `getErrors()` to the caller; do not retry |
| `ApiException` | Any client method | Inspect `getMessage()` + `getHttpStatus()` + `getRawBody()`; retry only for 5xx |
| `SignatureException` | `WebhookVerifier::verify*` | Drop the request, return HTTP 400; investigate (likely key mismatch) |
| `InvalidArgumentException` | Pre-flight argument checks | Fix the call site |

## Recommended catch shape

```php
use Heleket\Exception\ApiException;
use Heleket\Exception\HttpException;
use Heleket\Exception\ValidationException;

try {
    $invoice = $client->createInvoice($params);
} catch (ValidationException $e) {
    // 422 — never retry. Show errors to the user.
    foreach ($e->getErrors() as $field => $messages) { /* ... */ }
} catch (ApiException $e) {
    // Business error. Retry only if 5xx and idempotent.
    if ($e->getHttpStatus() >= 500) {
        scheduleRetry();
    } else {
        logHeleketError($e->getMessage(), $e->getRawBody());
    }
} catch (HttpException $e) {
    // Transport — usually safe to retry.
    scheduleRetry();
}
```

## Retry guidance

| Status / exception | Safe to retry? |
|---|---|
| `HttpException` (timeout, DNS, TLS) | Yes — exponential backoff |
| `ApiException` HTTP 5xx | Yes — exponential backoff |
| `ApiException` HTTP 4xx (non-422) | No — fix the request first |
| `ValidationException` (422) | No |
| `SignatureException` | No — never trust the payload |

For idempotency on retries of create-* calls, **reuse the same `order_id`**. Heleket rejects duplicates and returns the existing record, which is what you want.

## Logging

The SDK never logs anything itself. Use `try / catch` and route messages through your application's logger. The exception carries enough context (status, message, raw body) for forensic analysis.

```php
} catch (ApiException $e) {
    $logger->warning('Heleket API rejected create-invoice', [
        'http_status' => $e->getHttpStatus(),
        'message'     => $e->getMessage(),
        'raw_body'    => $e->getRawBody(),
        'order_id'    => $params['order_id'] ?? null,
    ]);
    throw $e;
}
```

## Validation error shape

`ValidationException::getErrors()` returns:

```php
[
    'amount'   => ['validation.required'],
    'currency' => ['validation.required'],
]
```

Values are arrays of messages (sometimes more than one per field).

## Next

→ [10 — Reference](10-reference.md)
