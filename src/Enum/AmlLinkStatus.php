<?php

declare(strict_types=1);

namespace Heleket\Enum;

/**
 * Heleket AML/KYC/SoF questionnaire-link statuses.
 *
 * Returned per item by PaymentClient::getAmlLinks() for a blocked (locked)
 * payment. "Final" statuses are terminal — the link will not transition
 * further; "intermediate" statuses may still change while the user works
 * through the questionnaire.
 */
final class AmlLinkStatus
{
    public const INIT = 'init';
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const EXPIRED = 'expired';

    private const FINAL_STATUSES = [
        self::COMPLETED,
        self::EXPIRED,
    ];

    private const SUCCESSFUL_STATUSES = [
        self::COMPLETED,
    ];

    public static function isFinal(string $status): bool
    {
        return in_array($status, self::FINAL_STATUSES, true);
    }

    public static function isSuccessful(string $status): bool
    {
        return in_array($status, self::SUCCESSFUL_STATUSES, true);
    }
}
