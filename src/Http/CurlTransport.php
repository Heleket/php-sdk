<?php

declare(strict_types=1);

namespace Heleket\Http;

use Heleket\Exception\HttpException;

/**
 * Default transport. Uses ext-curl directly to keep the SDK dependency-free.
 *
 * The transport never touches the JSON body — it sends the exact bytes the
 * caller built, so the request signature computed by Signer stays valid.
 */
final class CurlTransport implements TransportInterface
{
    private int $timeoutSeconds;

    public function __construct(int $timeoutSeconds = 30)
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function request(string $method, string $url, array $headers, string $body): Response
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new HttpException('Failed to initialise cURL handle');
        }

        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name . ': ' . $value;
        }

        $responseHeaders = [];

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($handle, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $this->timeoutSeconds);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, static function ($_h, string $header) use (&$responseHeaders): int {
            $length = strlen($header);
            $separator = strpos($header, ':');
            if ($separator !== false) {
                $name = strtolower(trim(substr($header, 0, $separator)));
                $value = trim(substr($header, $separator + 1));
                $responseHeaders[$name] = $value;
            }
            return $length;
        });

        if ($body !== '') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        } elseif (strtoupper($method) === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, '');
        }

        $rawResponse = curl_exec($handle);

        if ($rawResponse === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            curl_close($handle);
            throw new HttpException(sprintf('cURL error (%d): %s', $errno, $error));
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return new Response($statusCode, is_string($rawResponse) ? $rawResponse : '', $responseHeaders);
    }
}
