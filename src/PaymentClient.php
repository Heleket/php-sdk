<?php

declare(strict_types=1);

namespace Heleket;

use Heleket\Exception\ApiException;
use Heleket\Exception\ValidationException;

/**
 * Client for the Heleket Payments API and related read endpoints (balance,
 * exchange rates). Construct via Client::payment() or directly.
 *
 * Every method returns the API's "result" object decoded as an associative
 * array, throws ValidationException on HTTP 422, and ApiException on any
 * other failure. Transport errors throw HttpException.
 *
 * See https://doc.heleket.com/methods/payments/ for parameter reference.
 */
final class PaymentClient extends AbstractClient
{
    /**
     * Create a new invoice.
     *
     * Required parameters: amount (string), currency (string), order_id (string).
     * See docs for the full optional parameter list.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws ValidationException
     * @throws ApiException
     */
    public function createInvoice(array $params): array
    {
        return $this->post('/v1/payment', $params);
    }

    /**
     * Look up an invoice by uuid OR order_id (server prioritises order_id).
     *
     * @return array<string, mixed>
     */
    public function getInfo(?string $uuid = null, ?string $orderId = null): array
    {
        if ($uuid === null && $orderId === null) {
            throw new \InvalidArgumentException('Either $uuid or $orderId must be provided');
        }
        return $this->post('/v1/payment/info', $this->compact([
            'uuid'     => $uuid,
            'order_id' => $orderId,
        ]));
    }

    /**
     * Paginated payment history with optional date filtering.
     *
     * @return array<string, mixed>
     */
    public function listHistory(?string $dateFrom = null, ?string $dateTo = null, ?string $cursor = null): array
    {
        $params = $this->compact([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);
        $path = '/v1/payment/list';
        if ($cursor !== null && $cursor !== '') {
            $path .= '?cursor=' . rawurlencode($cursor);
        }
        return $this->post($path, $params);
    }

    /**
     * Create a static (top-up) wallet bound to an order_id.
     *
     * Required params: currency, network, order_id.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createStaticWallet(array $params): array
    {
        return $this->post('/v1/wallet', $params);
    }

    /**
     * Generate a QR-code image (base64) for an existing static wallet.
     *
     * @return array<string, mixed>
     */
    public function generateQrCode(string $merchantPaymentUuid): array
    {
        return $this->post('/v1/wallet/qr', ['merchant_payment_uuid' => $merchantPaymentUuid]);
    }

    /**
     * Block a static wallet (no further top-ups will be processed).
     *
     * @return array<string, mixed>
     */
    public function blockStaticWallet(?string $uuid = null, ?string $orderId = null, bool $isRefund = false): array
    {
        if ($uuid === null && $orderId === null) {
            throw new \InvalidArgumentException('Either $uuid or $orderId must be provided');
        }
        return $this->post('/v1/wallet/block-address', $this->compact([
            'uuid'      => $uuid,
            'order_id'  => $orderId,
            'is_refund' => $isRefund,
        ]));
    }

    /**
     * Refund funds previously locked on a blocked static wallet.
     *
     * @return array<string, mixed>
     */
    public function refundBlockedWallet(string $uuid, string $address): array
    {
        return $this->post('/v1/wallet/blocked-address-refund', [
            'uuid'    => $uuid,
            'address' => $address,
        ]);
    }

    /**
     * Refund a paid invoice in full or in part.
     *
     * Required: address, is_subtract; one of uuid/order_id.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function refund(array $params): array
    {
        return $this->post('/v1/payment/refund', $params);
    }

    /**
     * Resend the IPN/webhook for an existing invoice.
     *
     * @return array<string, mixed>
     */
    public function resendWebhook(?string $uuid = null, ?string $orderId = null): array
    {
        if ($uuid === null && $orderId === null) {
            throw new \InvalidArgumentException('Either $uuid or $orderId must be provided');
        }
        return $this->post('/v1/payment/resend', $this->compact([
            'uuid'     => $uuid,
            'order_id' => $orderId,
        ]));
    }

    /**
     * Send a synthetic test webhook to the configured callback URL.
     *
     * $type must be 'payment' or 'wallet'. Status drives the synthetic event.
     *
     * @return array<string, mixed>
     */
    public function testWebhook(string $type, string $url, string $currency, string $network, string $status, ?string $uuid = null, ?string $orderId = null): array
    {
        if (!in_array($type, ['payment', 'wallet'], true)) {
            throw new \InvalidArgumentException('type must be "payment" or "wallet"');
        }
        if ($uuid === null && $orderId === null) {
            throw new \InvalidArgumentException('Either $uuid or $orderId must be provided');
        }
        return $this->post('/v1/test-webhook/' . $type, $this->compact([
            'url_callback' => $url,
            'currency'     => $currency,
            'network'      => $network,
            'status'       => $status,
            'uuid'         => $uuid,
            'order_id'     => $orderId,
        ]));
    }

    /**
     * List supported payment networks and currencies.
     *
     * @return array<string, mixed>
     */
    public function listServices(): array
    {
        return $this->post('/v1/payment/services');
    }

    /**
     * Merchant and personal wallet balances.
     *
     * @return array<string, mixed>
     */
    public function getBalance(): array
    {
        return $this->post('/v1/balance');
    }

    /**
     * Exchange-rate list for a given fiat currency.
     *
     * @return array<string, mixed>
     */
    public function getExchangeRates(string $currency): array
    {
        return $this->post('/v1/exchange-rate/' . rawurlencode($currency) . '/list');
    }
}
