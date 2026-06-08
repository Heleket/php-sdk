<?php

declare(strict_types=1);

namespace Heleket\Webhook;

use Heleket\Exception\SignatureException;
use Heleket\Signature\Signer;

/**
 * Verifies the signature of an incoming Heleket webhook.
 *
 * Use the appropriate API key when constructing the verifier:
 *   - payment webhooks  → payment API key
 *   - payout webhooks   → payout API key
 *
 * Verification recipe (per docs):
 *   1. Decode the JSON body into an associative array.
 *   2. Read and remove the `sign` field.
 *   3. Re-encode the remaining data with JSON_UNESCAPED_UNICODE.
 *   4. Compute md5(base64_encode(<re-encoded>) . apiKey).
 *   5. Constant-time compare against the received `sign`.
 *
 * Two entry points, both implement the same recipe:
 *   - verifyRawBody(string)  — convenience for the typical handler that has
 *                              raw bytes from `php://input`.
 *   - verify(array)          — when you already decoded the JSON yourself.
 * Pick whichever fits your call site. They are functionally equivalent.
 *
 * IMPORTANT — the slash-escaping trap: PHP's json_encode auto-escapes forward
 * slashes (`\/`), but most other languages do not. Heleket signs using PHP-style
 * encoding, and BOTH entry points here re-encode the decoded payload with
 * json_encode(..., JSON_UNESCAPED_UNICODE) before hashing — JSON_UNESCAPED_UNICODE
 * keeps the slashes escaped. So if a webhook reaches you having been re-serialised
 * by a non-PHP service that left slashes raw, the re-encoded body will differ and
 * verification will fail. Heleket → your endpoint directly is safe; a proxy that
 * re-serialises JSON in between is the danger zone. See docs/06-webhooks.md
 * ("The slash-escaping trap") for the full discussion and mitigation.
 */
final class WebhookVerifier
{
    private string $apiKey;
    private Signer $signer;

    public function __construct(string $apiKey, ?Signer $signer = null)
    {
        $this->apiKey = $apiKey;
        $this->signer = $signer ?? new Signer();
    }

    /**
     * Convenience: verify against an already-decoded payload.
     *
     * @param array<string, mixed> $payload Decoded webhook body INCLUDING the `sign` field.
     *
     * @throws SignatureException when the signature is missing or invalid.
     */
    public function verify(array $payload): WebhookPayload
    {
        if (!isset($payload['sign']) || !is_string($payload['sign']) || $payload['sign'] === '') {
            throw new SignatureException('Webhook payload is missing the sign field');
        }
        $providedSign = $payload['sign'];
        unset($payload['sign']);

        $reencoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($reencoded === false) {
            throw new SignatureException('Failed to JSON-encode the webhook payload for verification');
        }

        $expectedSign = $this->signer->sign($reencoded, $this->apiKey);

        if (!$this->signer->equals($expectedSign, $providedSign)) {
            throw new SignatureException('Webhook signature mismatch');
        }

        return new WebhookPayload($payload);
    }

    /**
     * Convenience wrapper that JSON-decodes the raw body and delegates to verify().
     *
     * IMPORTANT: this is functionally equivalent to verify(json_decode($body, true)).
     * The decode-then-re-encode round trip means the slash-escaping caveat documented
     * at the top of this class still applies. Use this method when you control the
     * sender (Heleket's PHP-encoded webhooks), and prefer it over manual decoding
     * because it consolidates the JSON-error and signature-error paths into a single
     * exception type.
     *
     * @throws SignatureException when the body is not JSON, the signature is missing, or it fails to verify.
     */
    public function verifyRawBody(string $rawBody): WebhookPayload
    {
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new SignatureException('Webhook body is not valid JSON');
        }
        return $this->verify($decoded);
    }
}
