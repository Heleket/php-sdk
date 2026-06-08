<?php

declare(strict_types=1);

namespace Heleket\Enum;

/**
 * Heleket payout statuses.
 *
 * See https://doc.heleket.com/methods/payouts/payout-statuses
 */
final class PayoutStatus
{
    public const PROCESS = 'process';
    public const CHECK = 'check';
    public const PAID = 'paid';
    public const FAIL = 'fail';
    public const CANCEL = 'cancel';
    public const SYSTEM_FAIL = 'system_fail';

    private const FINAL_STATUSES = [
        self::PAID,
        self::FAIL,
        self::CANCEL,
        self::SYSTEM_FAIL,
    ];

    public static function isFinal(string $status): bool
    {
        return in_array($status, self::FINAL_STATUSES, true);
    }

    public static function isSuccessful(string $status): bool
    {
        return $status === self::PAID;
    }
}
