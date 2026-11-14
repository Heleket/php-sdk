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
     * @param string $paymentApiKey From dash.heleket.hk → Business → API key for payments.
     * @param string $merchantId    Merchant UUID.
     * @param bool   $debug         If true, every request/response is dumped to stderr.
     * @param string $domain        API domain; requests go to https://api.<domain> (default heleket.hk).
     */
    public static function payment(string $paymentApiKey, string $merchantId, bool $debug = false, string $domain = Config::DEFAULT_DOMAIN): PaymentClient
    {
        return new PaymentClient(new Config($merchantId, $paymentApiKey, null, Config::DEFAULT_TIMEOUT_SECONDS, $debug, $domain));
    }

    /**
     * @param string $payoutApiKey From dash.heleket.hk → Settings → API → Payout key.
     * @param string $merchantId   Merchant UUID.
     * @param bool   $debug        If true, every request/response is dumped to stderr.
     * @param string $domain       API domain; requests go to https://api.<domain> (default heleket.hk).
     */
    public static function payout(string $payoutApiKey, string $merchantId, bool $debug = false, string $domain = Config::DEFAULT_DOMAIN): PayoutClient
    {
        return new PayoutClient(new Config($merchantId, $payoutApiKey, null, Config::DEFAULT_TIMEOUT_SECONDS, $debug, $domain));
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
