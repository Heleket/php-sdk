<?php

declare(strict_types=1);

namespace Heleket;

use InvalidArgumentException;

/**
 * Immutable configuration for a Heleket API client.
 *
 * Holds the merchant identity, API key, base URL, request timeout, and the
 * debug toggle. A separate Config instance is required per client kind
 * (payments vs payouts) because the two surfaces use different API keys.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.heleket.com';
    public const DEFAULT_TIMEOUT_SECONDS = 30;

    private string $merchantId;
    private string $apiKey;
    private string $baseUrl;
    private int $timeoutSeconds;
    private bool $debug;

    public function __construct(
        string $merchantId,
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        bool $debug = false
    ) {
        $merchantId = trim($merchantId);
        $apiKey = trim($apiKey);

        if ($merchantId === '') {
            throw new InvalidArgumentException('merchantId must not be empty');
        }
        if ($apiKey === '') {
            throw new InvalidArgumentException('apiKey must not be empty');
        }
        if ($timeoutSeconds < 1) {
            throw new InvalidArgumentException('timeoutSeconds must be >= 1');
        }

        $this->merchantId = $merchantId;
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->debug = $debug;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function withDebug(bool $debug): self
    {
        return new self($this->merchantId, $this->apiKey, $this->baseUrl, $this->timeoutSeconds, $debug);
    }

    public function withBaseUrl(string $baseUrl): self
    {
        return new self($this->merchantId, $this->apiKey, $baseUrl, $this->timeoutSeconds, $this->debug);
    }

    public function withTimeoutSeconds(int $timeoutSeconds): self
    {
        return new self($this->merchantId, $this->apiKey, $this->baseUrl, $timeoutSeconds, $this->debug);
    }
}
