<?php

declare(strict_types=1);

namespace Heleket\Enum;

/**
 * Heleket payment statuses.
 *
 * See https://doc.heleket.com/methods/payments/payment-statuses
 *
 * "Final" statuses are terminal — the invoice will not transition further.
 * "Intermediate" statuses may still change; keep polling or wait for the next
 * webhook.
 */
final class PaymentStatus
{
    public const PAID = 'paid';
    public const PAID_OVER = 'paid_over';
    public const WRONG_AMOUNT = 'wrong_amount';
    public const PROCESS = 'process';
    public const CONFIRM_CHECK = 'confirm_check';
    public const WRONG_AMOUNT_WAITING = 'wrong_amount_waiting';
    public const CHECK = 'check';
    public const FAIL = 'fail';
    public const CANCEL = 'cancel';
    public const SYSTEM_FAIL = 'system_fail';
    public const REFUND_PROCESS = 'refund_process';
    public const REFUND_FAIL = 'refund_fail';
    public const REFUND_PAID = 'refund_paid';
    public const LOCKED = 'locked';

    private const FINAL_STATUSES = [
        self::PAID,
        self::PAID_OVER,
        self::WRONG_AMOUNT,
        self::FAIL,
        self::CANCEL,
        self::SYSTEM_FAIL,
        self::REFUND_FAIL,
        self::REFUND_PAID,
        self::LOCKED,
    ];

    private const SUCCESSFUL_STATUSES = [
        self::PAID,
        self::PAID_OVER,
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
