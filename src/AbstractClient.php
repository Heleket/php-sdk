<?php

declare(strict_types=1);

namespace Heleket;

use Heleket\Debug\DebugDumper;
use Heleket\Exception\ApiException;
use Heleket\Exception\HeleketException;
use Heleket\Exception\ValidationException;
use Heleket\Http\CurlTransport;
use Heleket\Http\TransportInterface;
use Heleket\Signature\Signer;

/**
 * Shared plumbing for PaymentClient and PayoutClient: builds the JSON body,
 * signs it, dispatches via the configured Transport, runs the debug dumper,
 * and translates the response into either an associative array (success) or
 * an exception (failure).
 *
 * Subclasses only need to declare endpoint paths and parameter shapes.
 */
abstract class AbstractClient
{
    protected Config $config;
    protected TransportInterface $transport;
    protected Signer $signer;
    protected DebugDumper $debugDumper;

    public function __construct(
        Config $config,
        ?TransportInterface $transport = null,
        ?Signer $signer = null,
        ?DebugDumper $debugDumper = null
    ) {
        $this->config = $config;
        $this->transport = $transport ?? new CurlTransport($config->getTimeoutSeconds());
        $this->signer = $signer ?? new Signer();
        $this->debugDumper = $debugDumper ?? new DebugDumper();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Send an authenticated POST request and return the API result on success.
     *
     * Signing rule (per docs):
     *   - empty params  → sign = md5(base64_encode('') . apiKey), body = ''
     *   - other         → sign = md5(base64_encode(json_encode($params)) . apiKey), body = that same JSON string
     *
     * @param string                $path   Endpoint path, e.g. "/v1/payment".
     * @param array<string, mixed>  $params Request parameters; sent as JSON.
     * @return array<string, mixed>          The "result" field of the response.
     *
     * @throws ValidationException on HTTP 422.
     * @throws ApiException on any other non-zero state.
     * @throws HeleketException if $params cannot be JSON-encoded (e.g. invalid UTF-8).
     */
    protected function post(string $path, array $params = []): array
    {
        if ($params === []) {
            $body = '';
        } else {
            $encoded = json_encode($params, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new HeleketException(
                    'Failed to JSON-encode request params for ' . $path . ': ' . json_last_error_msg()
                );
            }
            $body = $encoded;
        }
        $sign = $this->signer->sign($body, $this->config->getApiKey());

        $url = $this->config->getBaseUrl() . $path;
        $headers = [
            'merchant'     => $this->config->getMerchantId(),
            'sign'         => $sign,
            'Content-Type' => 'application/json',
            'User-Agent'   => Version::USER_AGENT,
        ];

        if ($this->config->isDebug()) {
            $this->debugDumper->dumpRequest('POST', $url, $body);
        }

        $response = $this->transport->request('POST', $url, $headers, $body);

        if ($this->config->isDebug()) {
            $this->debugDumper->dumpResponse($response->getStatusCode(), $response->getBody());
        }

        return $this->parseResponse($response->getStatusCode(), $response->getBody());
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(int $statusCode, string $body): array
    {
        $decoded = json_decode($body, true);

        if ($statusCode === 422) {
            $errors = is_array($decoded) && isset($decoded['errors']) && is_array($decoded['errors'])
                ? $decoded['errors']
                : [];
            /** @var array<string, list<string>> $normalisedErrors */
            $normalisedErrors = [];
            foreach ($errors as $field => $messages) {
                if (!is_array($messages)) {
                    $messages = [(string) $messages];
                }
                $normalisedErrors[(string) $field] = array_map('strval', $messages);
            }
            throw new ValidationException(
                'Validation failed: ' . $this->summariseErrors($normalisedErrors),
                $statusCode,
                $normalisedErrors,
                $body
            );
        }

        if (!is_array($decoded)) {
            throw new ApiException(
                sprintf('Unexpected non-JSON response from Heleket (HTTP %d)', $statusCode),
                $statusCode,
                $body
            );
        }

        $state = $decoded['state'] ?? null;
        if ($state !== 0) {
            $message = isset($decoded['message']) && is_string($decoded['message'])
                ? $decoded['message']
                : sprintf('Heleket API error (state=%s, HTTP %d)', var_export($state, true), $statusCode);
            throw new ApiException($message, $statusCode, $body);
        }

        $result = $decoded['result'] ?? [];
        if (!is_array($result)) {
            throw new ApiException('Malformed Heleket response: "result" is not an object/array', $statusCode, $body);
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function summariseErrors(array $errors): string
    {
        $parts = [];
        foreach ($errors as $field => $messages) {
            $parts[] = $field . ' (' . implode(', ', $messages) . ')';
        }
        return implode('; ', $parts);
    }

    /**
     * Drop null values so we don't send them to the API.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function compact(array $params): array
    {
        return array_filter($params, static fn ($value): bool => $value !== null);
    }
}
