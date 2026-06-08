<?php

declare(strict_types=1);

namespace Heleket\Webhook;

use Heleket\Enum\PaymentStatus;
use Heleket\Enum\PayoutStatus;

/**
 * Parsed and signature-verified webhook payload.
 *
 * Instances are constructed by WebhookVerifier — never directly by user code.
 * Exposes typed convenience getters for the most common fields plus toArray()
 * for the full decoded payload.
 */
final class WebhookPayload
{
    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data Decoded JSON of the webhook body (sign field already removed for verification, but kept here for reference).
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getType(): string
    {
        return (string) ($this->data['type'] ?? '');
    }

    public function isPayment(): bool
    {
        return in_array($this->getType(), ['payment', 'wallet'], true);
    }

    public function isPayout(): bool
    {
        return $this->getType() === 'payout';
    }

    public function getUuid(): string
    {
        return (string) ($this->data['uuid'] ?? '');
    }

    public function getOrderId(): string
    {
        return (string) ($this->data['order_id'] ?? '');
    }

    public function getStatus(): string
    {
        return (string) ($this->data['status'] ?? '');
    }

    public function isFinal(): bool
    {
        if (isset($this->data['is_final'])) {
            return (bool) $this->data['is_final'];
        }
        $status = $this->getStatus();
        return $this->isPayout() ? PayoutStatus::isFinal($status) : PaymentStatus::isFinal($status);
    }

    public function isSuccessful(): bool
    {
        $status = $this->getStatus();
        return $this->isPayout() ? PayoutStatus::isSuccessful($status) : PaymentStatus::isSuccessful($status);
    }

    public function getAmount(): string
    {
        return (string) ($this->data['amount'] ?? '');
    }

    public function getTxid(): ?string
    {
        return isset($this->data['txid']) ? (string) $this->data['txid'] : null;
    }

    public function getNetwork(): ?string
    {
        return isset($this->data['network']) ? (string) $this->data['network'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
