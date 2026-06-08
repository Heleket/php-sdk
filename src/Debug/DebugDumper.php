<?php

declare(strict_types=1);

namespace Heleket\Debug;

/**
 * Writes request/response traces to stderr when debug mode is on.
 * No dependency on a logger library; merchants who want PSR-3 can wrap the SDK.
 *
 * Output format:
 *     [heleket][→ POST /v1/payment] {"amount":"15", ...}
 *     [heleket][← 200] {"state":0,"result":{ ... }}
 */
final class DebugDumper
{
    /** @var resource|null */
    private $stream;

    /**
     * @param resource|null $stream Defaults to STDERR when null.
     */
    public function __construct($stream = null)
    {
        $this->stream = $stream;
    }

    public function dumpRequest(string $method, string $url, string $body): void
    {
        $this->write(sprintf('[heleket][→ %s %s] %s', strtoupper($method), $url, $body === '' ? '(empty body)' : $body));
    }

    public function dumpResponse(int $statusCode, string $body): void
    {
        $this->write(sprintf('[heleket][← %d] %s', $statusCode, $body === '' ? '(empty body)' : $body));
    }

    public function dumpError(string $message): void
    {
        $this->write('[heleket][error] ' . $message);
    }

    private function write(string $line): void
    {
        $stream = $this->stream ?? fopen('php://stderr', 'wb');
        if ($stream === false) {
            return;
        }
        fwrite($stream, $line . PHP_EOL);
    }
}
