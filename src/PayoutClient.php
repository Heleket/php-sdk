<?php

declare(strict_types=1);

namespace Heleket;

use Heleket\Exception\ApiException;
use Heleket\Exception\ValidationException;

/**
 * Client for the Heleket Payouts API. Construct via Client::payout() or
 * directly. Uses the payout API key — distinct from the payment key.
 *
 * Return shape and error model match PaymentClient: result-array on success,
 * ValidationException on 422, ApiException otherwise, HttpException on
 * transport failure.
 *
 * See https://doc.heleket.com/methods/payouts/ for parameter reference.
 */
final class PayoutClient extends AbstractClient
{
    /**
     * Create a withdrawal.
     *
     * Required: amount, currency, order_id, address, is_subtract.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws ValidationException
     * @throws ApiException
     */
    public function createPayout(array $params): array
    {
        return $this->post('/v1/payout', $params);
    }

    /**
     * Refund a paid invoice in full or in part.
     *
     * Hits POST /v1/payment/refund but is signed with the PAYOUT API key, so it
     * lives on PayoutClient rather than PaymentClient.
     *
     * Required: address, is_subtract; one of uuid / order_id.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws ValidationException
     * @throws ApiException
     */
    public function refund(array $params): array
    {
        return $this->post('/v1/payment/refund', $params);
    }

    /**
     * Look up a payout by uuid OR order_id.
     *
     * @return array<string, mixed>
     */
    public function getInfo(?string $uuid = null, ?string $orderId = null): array
    {
        if ($uuid === null && $orderId === null) {
            throw new \InvalidArgumentException('Either $uuid or $orderId must be provided');
        }
        return $this->post('/v1/payout/info', $this->compact([
            'uuid'     => $uuid,
            'order_id' => $orderId,
        ]));
    }

    /**
     * Paginated payout history with optional date filtering.
     *
     * @return array<string, mixed>
     */
    public function listHistory(?string $dateFrom = null, ?string $dateTo = null, ?string $cursor = null): array
    {
        $params = $this->compact([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);
        $path = '/v1/payout/list';
        if ($cursor !== null && $cursor !== '') {
            $path .= '?cursor=' . rawurlencode($cursor);
        }
        return $this->post($path, $params);
    }

    /**
     * Calculate fees and final amounts for a hypothetical withdrawal.
     *
     * @return array<string, mixed>
     */
    public function calculateWithdrawalAmount(string $currency, string $network, string $amount, bool $isSubtract = false): array
    {
        return $this->post('/v1/payout/calculate', [
            'currency'    => $currency,
            'network'     => $network,
            'amount'      => $amount,
            'is_subtract' => $isSubtract,
        ]);
    }

    /**
     * List supported payout networks and currencies.
     *
     * @return array<string, mixed>
     */
    public function listServices(): array
    {
        return $this->post('/v1/payout/services');
    }

    /**
     * Move funds from the business balance into the personal balance.
     *
     * @return array<string, mixed>
     */
    public function transferToPersonal(string $amount, string $currency): array
    {
        return $this->post('/v1/transfer/to-personal', [
            'amount'   => $amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Move funds from the personal balance into the business balance.
     *
     * @return array<string, mixed>
     */
    public function transferToBusiness(string $amount, string $currency): array
    {
        return $this->post('/v1/transfer/to-business', [
            'amount'   => $amount,
            'currency' => $currency,
        ]);
    }
}
