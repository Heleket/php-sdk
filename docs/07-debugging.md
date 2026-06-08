# 07 — Debugging

Two tools ship with the SDK: a **debug-mode flag** for runtime tracing, and a **webhook inspector CLI** for ad-hoc payload verification.

## Debug mode

Pass `debug: true` to the client factory, or set `HELEKET_DEBUG=1` in your `.env` if you use the example bootstrap.

```php
$client = Client::payment($paymentApiKey, $merchantId, debug: true);
$client->createInvoice([...]);
```

Output goes to **stderr** (so `STDOUT` capture is unaffected):

```
[heleket][→ POST https://api.heleket.com/v1/payment] {"amount":"15.00","currency":"USD","order_id":"order-42"}
[heleket][← 200] {"state":0,"result":{"uuid":"...", ...}}
```

Empty bodies render as `(empty body)`.

> ⚠️ Debug output contains the **request body** (which may include `payer_email` etc.) and the **response body**. It does **not** contain the API key. Still, scrub debug logs before sharing them.

## Webhook inspector CLI

`bin/heleket-webhook-inspect` reads a JSON webhook payload, prints the parsed fields, and verifies the signature.

### Usage

```bash
# From stdin (most common)
cat webhook.json | bin/heleket-webhook-inspect --key=$HELEKET_PAYMENT_KEY

# From a file
bin/heleket-webhook-inspect --key=$KEY --file=webhook.json

# Hint the type for clearer warnings
bin/heleket-webhook-inspect --key=$KEY --type=payout < webhook.json
```

### Sample output

```
Heleket webhook inspector
----------------------------------------
  type       payment
  uuid       1ec87133-b22d-4643-988f-cac29a6ac85d
  order_id   order-42
  status     paid
  amount     15.00
  network    tron
  txid       deadbeef
  is_final   yes
  sign       4a8f2c2c4a8f2c2c…

signature: valid
```

If the signature does not verify the tool prints `signature: INVALID` and a checklist of common causes.

### Exit codes

| Code | Meaning |
|---|---|
| 0 | Payload valid, signature verified |
| 1 | Input not parseable as JSON |
| 2 | Signature mismatch |
| 3 | Missing arguments |

### Where to get the payload

Capture it in your webhook handler before verification fails — e.g., write `php://input` to a file when the verifier throws. Then pipe that file into the inspector.

```php
try {
    $verifier->verifyRawBody($rawBody);
} catch (SignatureException $e) {
    file_put_contents('/tmp/heleket-failed.json', $rawBody);
    throw $e;
}
```

## Common error shapes

| Symptom | Likely cause |
|---|---|
| Always `signature: INVALID` | Wrong API key — using payment key for a payout webhook (or vice versa) |
| Signature valid in `--file` but invalid in production | Your framework is mutating the body before your handler reads it. Read `php://input` directly. |
| HTTP 422 from `createInvoice` | A required field is missing — the exception's `getErrors()` lists them |
| HTTP 200 but `ApiException("Server error, #1")` | Heleket-side outage; retry after a short backoff |
| `HttpException("cURL error 28")` | Network timeout — raise `Config::withTimeoutSeconds()` or check egress firewalls |

## Verbose curl trace (advanced)

`CurlTransport` does not expose `CURLOPT_VERBOSE` directly to keep the API small. If you need wire-level debugging, plug in a custom transport:

```php
final class VerboseTransport implements TransportInterface {
    public function request(string $method, string $url, array $headers, string $body): Response {
        // ... CURLOPT_VERBOSE = true, CURLOPT_STDERR = fopen('php://stderr', 'wb'), etc.
    }
}

$client = Client::paymentWith($config, new VerboseTransport());
```

## Next

→ [08 — Testing](08-testing.md)
