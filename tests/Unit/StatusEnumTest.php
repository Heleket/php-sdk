<?php

declare(strict_types=1);

namespace Heleket\Tests\Unit;

use Heleket\Enum\AmlLinkStatus;
use Heleket\Enum\PaymentStatus;
use Heleket\Enum\PayoutStatus;
use PHPUnit\Framework\TestCase;

final class StatusEnumTest extends TestCase
{
    public function testPaymentStatusClassification(): void
    {
        self::assertTrue(PaymentStatus::isFinal(PaymentStatus::PAID));
        self::assertTrue(PaymentStatus::isSuccessful(PaymentStatus::PAID));
        self::assertTrue(PaymentStatus::isSuccessful(PaymentStatus::PAID_OVER));

        self::assertFalse(PaymentStatus::isFinal(PaymentStatus::CHECK));
        self::assertFalse(PaymentStatus::isFinal(PaymentStatus::CONFIRM_CHECK));
        self::assertFalse(PaymentStatus::isSuccessful(PaymentStatus::WRONG_AMOUNT));
        self::assertTrue(PaymentStatus::isFinal(PaymentStatus::WRONG_AMOUNT));
    }

    public function testPayoutStatusClassification(): void
    {
        self::assertTrue(PayoutStatus::isFinal(PayoutStatus::PAID));
        self::assertTrue(PayoutStatus::isSuccessful(PayoutStatus::PAID));
        self::assertFalse(PayoutStatus::isFinal(PayoutStatus::CHECK));
        self::assertFalse(PayoutStatus::isSuccessful(PayoutStatus::FAIL));
    }

    public function testAmlLinkStatusClassification(): void
    {
        self::assertTrue(AmlLinkStatus::isFinal(AmlLinkStatus::COMPLETED));
        self::assertTrue(AmlLinkStatus::isFinal(AmlLinkStatus::EXPIRED));
        self::assertFalse(AmlLinkStatus::isFinal(AmlLinkStatus::INIT));
        self::assertFalse(AmlLinkStatus::isFinal(AmlLinkStatus::PENDING));

        self::assertTrue(AmlLinkStatus::isSuccessful(AmlLinkStatus::COMPLETED));
        self::assertFalse(AmlLinkStatus::isSuccessful(AmlLinkStatus::EXPIRED));
        self::assertFalse(AmlLinkStatus::isSuccessful(AmlLinkStatus::PENDING));
        self::assertFalse(AmlLinkStatus::isSuccessful(AmlLinkStatus::INIT));
    }
}
