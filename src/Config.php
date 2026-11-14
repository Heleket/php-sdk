<?php

declare(strict_types=1);

namespace Heleket;

use InvalidArgumentException;

/**
 * Immutable configuration for a Heleket API client.
 *
 * Holds the merchant identity, API key, domain / base URL, request timeout, and
 * the debug toggle. The effective base URL is `https://api.<domain>` unless an
 * explicit base URL is given (an explicit base URL always wins over the domain). A separate Config instance is required per client kind
 * (payments vs payouts) because the two surfaces use different API keys.
 */
final class Config
{
    public const DEFAULT_DOMAIN = 'heleket.hk';
    public const DEFAULT_BASE_URL = 'https://api.heleket.hk';
    public const DEFAULT_TIMEOUT_SECONDS = 30;

    private string $merchantId;
    private string $apiKey;
    private ?string $explicitBaseUrl;
    private string $baseUrl;
    private string $domain;
    private int $timeoutSeconds;
    private bool $debug;

    public function __construct(
        string $merchantId,
        string $apiKey,
        ?string $baseUrl = null,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        bool $debug = false,
        string $domain = self::DEFAULT_DOMAIN
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
        $domain = trim($domain);
        if ($domain === '') {
            throw new InvalidArgumentException('domain must not be empty');
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $domain) === 1 || strpbrk($domain, '/\\?#@ ') !== false) {
            throw new InvalidArgumentException('domain must be a bare host name without scheme or path, e.g. "heleket.hk"');
        }

        $this->domain = $domain;
        $this->explicitBaseUrl = ($baseUrl === null || trim($baseUrl) === '') ? null : $baseUrl;
        $this->baseUrl = $this->explicitBaseUrl !== null
            ? rtrim($this->explicitBaseUrl, '/')
            : 'https://api.' . $domain;
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

    public function getDomain(): string
    {
        return $this->domain;
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
        return new self($this->merchantId, $this->apiKey, $this->explicitBaseUrl, $this->timeoutSeconds, $debug, $this->domain);
    }

    public function withBaseUrl(?string $baseUrl): self
    {
        return new self($this->merchantId, $this->apiKey, $baseUrl, $this->timeoutSeconds, $this->debug, $this->domain);
    }

    /**
     * Note: has no effect on the effective base URL while an explicit base URL is set.
     */
    public function withDomain(string $domain): self
    {
        return new self($this->merchantId, $this->apiKey, $this->explicitBaseUrl, $this->timeoutSeconds, $this->debug, $domain);
    }

    public function withTimeoutSeconds(int $timeoutSeconds): self
    {
        return new self($this->merchantId, $this->apiKey, $this->explicitBaseUrl, $timeoutSeconds, $this->debug, $this->domain);
    }
}
