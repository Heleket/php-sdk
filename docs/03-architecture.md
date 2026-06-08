# 03 — Architecture

## High-level diagram

```
                ┌─────────────────────┐
                │      Client         │   factories: payment(), payout()
                └──────────┬──────────┘
                           │
        ┌──────────────────┴───────────────────┐
        ▼                                      ▼
┌──────────────────┐                  ┌──────────────────┐
│  PaymentClient   │                  │  PayoutClient    │
└────────┬─────────┘                  └────────┬─────────┘
         │ extends                              │ extends
         └────────────┬─────────────────────────┘
                      ▼
              ┌────────────────────┐
              │  AbstractClient    │   builds body, signs, dispatches,
              │                    │   parses response or throws
              └────────┬───────────┘
       Config ────────►│
       Signer ────────►│
       TransportInterface ────────►│
       DebugDumper ────────►│
                      ▼
              ┌────────────────────┐
              │   CurlTransport    │   ← default, swappable
              └────────────────────┘
```

## Class responsibilities

| Class | Responsibility |
|---|---|
| `Client` | Static factory façade. Returns a configured `PaymentClient` or `PayoutClient`. No state. |
| `Config` | Immutable value object holding merchant UUID, API key, base URL, timeout, debug flag. |
| `AbstractClient` | The only place that performs HTTP. Builds the JSON body, signs it, sends, parses the response, and translates errors. Subclasses describe endpoints. |
| `PaymentClient` | Method-per-endpoint surface for the Payments API + balance + exchange rates. |
| `PayoutClient` | Method-per-endpoint surface for the Payouts API + transfers. |
| `Http\TransportInterface` | Single-method HTTP contract: `request($method, $url, $headers, $body): Response`. |
| `Http\CurlTransport` | Default implementation using `ext-curl`. Forwards request bytes verbatim. |
| `Http\Response` | Captures status code, raw body, response headers. |
| `Signature\Signer` | `md5(base64_encode($body) . $apiKey)` + constant-time `equals()`. Body must be the exact bytes that travel over the wire. |
| `Webhook\WebhookVerifier` | Verifies incoming webhook signatures. Two entry points: `verify(array)` and `verifyRawBody(string)`. |
| `Webhook\WebhookPayload` | Verified payload wrapper with typed accessors (`getType()`, `isFinal()`, etc.). |
| `Debug\DebugDumper` | Writes `→ request` and `← response` lines to stderr when debug is on. |
| `Exception\*` | `HeleketException` (base) → `HttpException`, `ApiException`, `ValidationException`, `SignatureException`. |
| `Enum\*` | Class-constant containers for statuses and exchange-rate sources. |

## Request flow

1. Caller invokes `$payment->createInvoice($params)`.
2. `PaymentClient::createInvoice` calls `AbstractClient::post('/v1/payment', $params)`.
3. `AbstractClient::post`:
   - Encodes the params to JSON (or empty string for no-arg endpoints).
   - Signs the JSON with the API key.
   - Builds headers: `merchant`, `sign`, `Content-Type: application/json`.
   - Dumps the request via `DebugDumper` when debug is on.
   - Calls `Transport::request('POST', $url, $headers, $body)`.
   - Dumps the response.
   - Parses `state`/`result`/`errors`. Returns the `result` array on success, or throws.
4. Caller receives the result array or catches an exception.

## Signature flow

```
params (array)                  apiKey (string)
   │                                  │
   ▼                                  ▼
json_encode($params, JSON_UNESCAPED_UNICODE)
   │
   ▼
$body (string)
   │
   ▼
base64_encode($body) . $apiKey
   │
   ▼
md5(...)
   │
   ▼
$sign  ──►  HTTP header `sign: <hex>`
```

The same `Signer` is used to **produce** outgoing request signatures and to **verify** incoming webhook signatures. The math is identical; only the key changes (payment key vs payout key).

## Design choices

- **No PSR-3 dependency.** A minimal `DebugDumper` writes to stderr. Wrapping a PSR-3 logger around the SDK is trivial for callers who want one.
- **Pure cURL.** Keeps the SDK installable in any host environment without Composer dependency conflicts (Heleket merchants often run shared hosting).
- **Transport is an interface.** Tests inject `FakeTransport`. Merchants who want Guzzle can write a 20-line adapter.
- **Configuration is immutable.** Withers (`withDebug()`, `withTimeoutSeconds()`, …) return new instances. Easier to reason about thread/request safety.
- **Arrays in, arrays out.** Strongly-typed value objects would be nicer, but Heleket's response shape evolves and arrays let merchants pluck fields the SDK doesn't know about yet.

## Next

→ [04 — Payments API](04-payments.md)
