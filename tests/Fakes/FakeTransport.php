<?php

declare(strict_types=1);

namespace Heleket\Tests\Fakes;

use Heleket\Exception\HttpException;
use Heleket\Http\Response;
use Heleket\Http\TransportInterface;
use LogicException;

/**
 * In-memory transport for unit tests.
 *
 * Enqueue canned Response objects with enqueue(), then inspect what the
 * client sent via getRequests(). Calls to request() pop responses in FIFO
 * order — the test fails with a LogicException if the queue runs dry.
 */
final class FakeTransport implements TransportInterface
{
    /** @var list<Response> */
    private array $queue = [];

    /** @var list<array{method: string, url: string, headers: array<string, string>, body: string}> */
    private array $recorded = [];

    public function enqueue(Response $response): self
    {
        $this->queue[] = $response;
        return $this;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueueJson(array $payload, int $statusCode = 200): self
    {
        return $this->enqueue(new Response($statusCode, (string) json_encode($payload), ['content-type' => 'application/json']));
    }

    public function request(string $method, string $url, array $headers, string $body): Response
    {
        $this->recorded[] = [
            'method'  => $method,
            'url'     => $url,
            'headers' => $headers,
            'body'    => $body,
        ];
        if ($this->pendingFailure !== null) {
            $message = $this->pendingFailure;
            $this->pendingFailure = null;
            throw new HttpException($message);
        }
        if ($this->queue === []) {
            throw new LogicException(sprintf('FakeTransport has no enqueued response for %s %s', $method, $url));
        }
        return array_shift($this->queue);
    }

    /**
     * @return list<array{method: string, url: string, headers: array<string, string>, body: string}>
     */
    public function getRequests(): array
    {
        return $this->recorded;
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: string}
     */
    public function getLastRequest(): array
    {
        if ($this->recorded === []) {
            throw new LogicException('No requests have been recorded yet');
        }
        return $this->recorded[count($this->recorded) - 1];
    }

    private ?string $pendingFailure = null;

    /**
     * Helper to simulate a transport failure on the next call.
     */
    public function failNext(string $message = 'simulated transport failure'): self
    {
        $this->pendingFailure = $message;
        return $this;
    }
}
