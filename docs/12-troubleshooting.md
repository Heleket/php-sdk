# 12 — Troubleshooting

The most common merchant problems and their fixes.

## 1. `signature: INVALID` on webhooks

**Most likely cause:** verifying with the wrong API key.

- Payment + static-wallet webhooks (`type: payment`, `type: wallet`) → **payment** API key
- Payout webhooks (`type: payout`) → **payout** API key

```php
$verifier = new WebhookVerifier($paymentApiKey); // for payment webhooks
$verifier = new WebhookVerifier($payoutApiKey);  // for payout webhooks
```

**Other causes:**

- Your framework parsed and mutated the body before your handler read it. Read `php://input` directly.
- You re-encoded a decoded array with different settings (e.g., without `JSON_UNESCAPED_UNICODE`). Prefer `verifyRawBody($rawBody)`.
- The webhook came from a sender that does not escape forward slashes the way PHP does. Same fix: `verifyRawBody`.

Run the inspector:

```bash
echo '<paste payload>' | bin/heleket-webhook-inspect --key=$KEY
```

## 2. `HTTP 422` when creating an invoice

`getErrors()` lists the offending fields. The most-seen ones:

| Field | Common reason |
|---|---|
| `amount` | Empty, non-numeric, or below the minimum for the chosen currency |
| `order_id` | Duplicate (already exists in your merchant invoices) or contains unsupported characters |
| `currency` | Currency code not enabled for your merchant |
| `url_callback` | Not HTTPS, or shorter than 6 chars |

```php
catch (ValidationException $e) {
    print_r($e->getErrors());
}
```

## 3. `Wrong key` / `You are forbidden`

The merchant account is restricted or in moderation. Check the dashboard at <https://dash.heleket.com>. New accounts need domain verification before invoices are accepted.

## 4. `cURL error (28): Operation timed out`

Heleket can take 5–10 seconds during heavy blockchain confirmation. Raise the timeout:

```php
$config = $config->withTimeoutSeconds(60);
```

If the timeout fires on every call, your egress firewall is blocking `api.heleket.com:443`.

## 5. Payments stuck in `check` / `confirm_check`

`check` = waiting for the transaction to appear in the mempool. `confirm_check` = seen on-chain, awaiting confirmations (typically 1–6 depending on the network). Wait. Don't poll faster than every 30 seconds.

`wrong_amount_waiting` means the payer underpaid — the invoice is still open and additional top-ups are accepted.

## 6. Payout API key recently rotated

> "Withdrawals will be temporarily blocked for 24 hours after generating a new payout API key."

This is a security feature, not a bug. Schedule rotations.

## 7. `Sign` mismatch from outbound requests

If Heleket rejects your **outbound** requests with a signature error, the most likely cause is double-encoding: the body was JSON-encoded twice, or you passed an array instead of a JSON string into the signer.

The SDK handles this correctly internally. If you're hand-rolling HTTP, follow this exact order:

```php
$json = json_encode($params, JSON_UNESCAPED_UNICODE);
$sign = md5(base64_encode($json) . $apiKey);
// send $json as body, $sign in the header
```

## 8. Webhook is not received at all

- Confirm the `url_callback` is publicly reachable (curl from a different network).
- Use `testWebhook()` from the SDK to send a synthetic event without making a payment.
- Check your server logs for 4xx/5xx replies — if you respond non-2xx, Heleket retries; if you respond 2xx with the wrong logic, the event is recorded as delivered.
- Whitelist `31.133.220.8` rather than blocking unknown IPs at the firewall.

## 9. "is_subtract" confusion on payouts

| Value | Behaviour |
|---|---|
| `true` | Commission deducted from `amount`. Recipient receives `amount - commission`. |
| `false` | Commission added on top. Recipient receives `amount`. Your balance is debited `amount + commission`. |

Use `calculateWithdrawalAmount()` to preview the numbers before committing.

## 10. Static wallet shows `is_final=true` after a single deposit

By design — each top-up generates its own webhook with `is_final=true` for that particular event. The wallet itself remains open and accepts further deposits. Don't close it after one event.

## Still stuck?

Capture the failing request/response with `debug: true` (see [07 — Debugging](07-debugging.md)), then reach out to Heleket support at <https://heleket.com/contacts> with:

- The `order_id` involved
- Approximate timestamps (UTC+3)
- The exception class and message you see
- A redacted excerpt of the debug output (strip API keys)

## End of documentation

Back to [docs index](README.md) or [README](../README.md).
