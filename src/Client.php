<?php

declare(strict_types=1);

namespace Heleket;

use Heleket\Debug\DebugDumper;
use Heleket\Http\TransportInterface;
use Heleket\Signature\Signer;

/**
 * Entry point for the SDK. Use the static factories to construct a typed
 * client with sensible defaults.
 *
 * Quickstart:
 *     $payment = Client::payment($paymentKey, $merchantId);
 *     $invoice = $payment->createInvoice([
 *         'amount'   => '15',
 *         'currency' => 'USD',
 *         'order_id' => 'order-42',
 *     ]);
 *
 *     $payout = Client::payout($payoutKey, $merchantId);
 *
 * For dependency injection (custom transport, signer, debug dumper) construct
 * PaymentClient / PayoutClient directly with a Config instance.
 */
final class Client
{
    /**
     * @param string $paymentApiKey From dash.heleket.com → Business → API key for payments.
     * @param string $merchantId    Merchant UUID.
     * @param bool   $debug         If true, every request/response is dumped to stderr.
     */
    public static function payment(string $paymentApiKey, string $merchantId, bool $debug = false): PaymentClient
    {
        return new PaymentClient(new Config($merchantId, $paymentApiKey, Config::DEFAULT_BASE_URL, Config::DEFAULT_TIMEOUT_SECONDS, $debug));
    }

    /**
     * @param string $payoutApiKey From dash.heleket.com → Settings → API → Payout key.
     * @param string $merchantId   Merchant UUID.
     * @param bool   $debug        If true, every request/response is dumped to stderr.
     */
    public static function payout(string $payoutApiKey, string $merchantId, bool $debug = false): PayoutClient
    {
        return new PayoutClient(new Config($merchantId, $payoutApiKey, Config::DEFAULT_BASE_URL, Config::DEFAULT_TIMEOUT_SECONDS, $debug));
    }

    /**
     * Build a PaymentClient with custom transport / signer / debug dumper.
     * Use this for testing or to plug in a different HTTP backend.
     */
    public static function paymentWith(
        Config $config,
        ?TransportInterface $transport = null,
        ?Signer $signer = null,
        ?DebugDumper $debugDumper = null
    ): PaymentClient {
        return new PaymentClient($config, $transport, $signer, $debugDumper);
    }

    /**
     * Build a PayoutClient with custom transport / signer / debug dumper.
     */
    public static function payoutWith(
        Config $config,
        ?TransportInterface $transport = null,
        ?Signer $signer = null,
        ?DebugDumper $debugDumper = null
    ): PayoutClient {
        return new PayoutClient($config, $transport, $signer, $debugDumper);
    }
}
