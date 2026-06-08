<?php

declare(strict_types=1);

namespace Heleket\Signature;

/**
 * Heleket request and webhook signature.
 *
 * Formula (per https://doc.heleket.com/general/request-format):
 *     sign = md5( base64_encode(json_body) . apiKey )
 *
 * For requests with an empty body the JSON string passed must be the empty
 * string '' (so base64_encode('') = '').
 *
 * The signer takes the ALREADY-ENCODED JSON string — never an array — so the
 * caller controls JSON encoding exactly once. This guarantees the bytes the
 * server hashes match the bytes the SDK sent.
 */
final class Signer
{
    public function sign(string $jsonBody, string $apiKey): string
    {
        return md5(base64_encode($jsonBody) . $apiKey);
    }

    /**
     * Constant-time comparison of two signatures.
     */
    public function equals(string $expected, string $actual): bool
    {
        return hash_equals($expected, $actual);
    }
}
