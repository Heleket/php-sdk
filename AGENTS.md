# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Cursor, …) working on or
integrating the **Heleket PHP SDK**. Humans: this doubles as a fast architecture
tour — see [`docs/`](docs/README.md) for the full reference.

## What this is

The official PHP SDK for the [Heleket](https://heleket.com) cryptocurrency
payment API: payments, payouts, balance, exchange rates, services, and signed
webhooks. **Zero runtime dependencies** — only `ext-curl` and `ext-json`.
Minimum PHP **7.4** (typed properties, but no constructor promotion / native
enums, so it installs on legacy hosts).

- Package: `heleket/php-sdk` on Packagist.
- Namespace: `Heleket\` (PSR-4) → `src/`.
- Upstream API docs: <https://doc.heleket.com>.

## Repository map

| Path | Purpose |
|---|---|
| `src/` | Production code (shipped, along with `bin/` and `AGENTS.md`). |
| `tests/` | PHPUnit unit tests + `tests/Fakes/FakeTransport.php` for offline HTTP. |
| `examples/` | Eleven runnable scripts (`01`..`11`), one per endpoint. Need `.env`. |
| `bin/heleket-webhook-inspect` | CLI to verify/dump a webhook payload. |
| `docs/` | Full English reference (architecture, every endpoint, webhooks, …). |
| `Makefile` | `make qa`, `make test`, `make stan`, `make cs-fix`, examples, Docker. |

## Architecture

```
Client (static factory: payment(), payout())
   └─ PaymentClient / PayoutClient   (one method per endpoint)
        └─ AbstractClient            (the ONLY place that does HTTP)
             ├─ Config               (immutable: merchant id, api key, base url, timeout, debug)
             ├─ Signature\Signer     (md5(base64(body) . apiKey), constant-time equals)
             ├─ Http\TransportInterface → Http\CurlTransport (default, swappable)
             └─ Debug\DebugDumper    (stderr request/response trace when debug on)

Webhook\WebhookVerifier → Webhook\WebhookPayload   (verify inbound signatures)
Enum\PaymentStatus / Enum\PayoutStatus             (isFinal(), isSuccessful())
Exception\* : HeleketException ← Http / Api / Validation / Signature
```

`src/AbstractClient.php::post()` is the heart: it JSON-encodes params, signs the
exact bytes, sets the `merchant` / `sign` / `Content-Type` / `User-Agent`
headers, dispatches via the transport, and turns the response into either the
`result` array or a typed exception.

## Conventions (follow these)

- **Arrays in, arrays out.** Endpoints take `array<string,mixed>` params and
  return the API's `result` as an associative array. No response value objects.
- **Sign the exact wire bytes.** `Signer` takes an already-encoded JSON string,
  never an array — so the signed bytes equal the sent bytes. Empty body signs
  `''`. Never re-encode between signing and sending.
- **Immutable `Config`.** Change it via withers (`withDebug()`,
  `withTimeoutSeconds()`, `withBaseUrl()`), which return new instances.
- **Drop nulls.** Use `AbstractClient::compact()` to strip `null` params before
  sending optional fields.
- **Error model:** HTTP 422 → `ValidationException` (with per-field errors);
  `state != 0` → `ApiException`; transport failure → `HttpException`; bad webhook
  signature → `SignatureException`. All extend `HeleketException`.
- **`declare(strict_types=1)`** in every file; `final` classes; PHP 7.4-compatible
  syntax only.

## How to add an endpoint

1. Add a method to `PaymentClient` or `PayoutClient` that calls
   `$this->post('/v1/...', $params)` (call with no params for no-body endpoints).
   Use `$this->compact([...])` for optional fields; guard "one of uuid/order_id"
   preconditions with `InvalidArgumentException`.
2. Add a unit test under `tests/Unit/` using `FakeTransport` (see existing
   `PaymentClientTest` / `PayoutClientTest`) — assert the path, signed headers,
   and parsed result; no network.
3. If it is user-facing, add a runnable `examples/NN_*.php` script.
4. Run `make qa`.

## Running things

```bash
make install      # composer install
make test         # PHPUnit
make stan         # PHPStan level 8
make cs-fix       # auto-fix coding style (php-cs-fixer)
make qa           # test + stan + cs (the gate; CI runs this on PHP 7.4–8.3)
```

Tests are fully offline via `FakeTransport` — never hit the real API in tests.
Examples read credentials from `.env` (copy `.env.example`).

## Gotchas (do not get these wrong)

- **Webhook slash-escaping trap.** PHP's `json_encode` escapes `/` as `\/`;
  other languages don't. Heleket signs PHP-style. A proxy that re-serialises the
  JSON between Heleket and your endpoint breaks verification. See
  [`docs/06-webhooks.md`](docs/06-webhooks.md).
- **Two separate API keys.** Payments and payouts use different keys; a webhook
  of the wrong kind fails verification. `Client::payment()` vs `Client::payout()`.
- **Always verify webhook signatures** before trusting a payload; whitelist
  Heleket's source IP `31.133.220.8`.
- **Never log API keys.** `DebugDumper` logs method, URL, and body only — never
  headers.

## Releasing / versioning

- One Packagist package = one repo: published from `github.com/Heleket/php-sdk`.
- Versions come from **git tags** (no `version` field in `composer.json`). Current
  major: **v2**. The 1.x line (tag `1.0.0`) is a different, older codebase kept
  alive on the `1.x` branch — **never delete or move old tags**.
- The `User-Agent` version is resolved from Composer at runtime
  (`src/Version.php` → `Composer\InstalledVersions`), so there is no constant to
  bump at release.
- See [`UPGRADING.md`](UPGRADING.md) and [`CHANGELOG.md`](CHANGELOG.md).
