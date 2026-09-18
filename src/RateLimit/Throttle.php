<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\RateLimit;

/**
 * Keeps calls below YClients' documented 5 req/sec limit by sleeping just enough before each
 * request, rather than only reacting to HTTP 429 after the fact (see
 * `Dudev\YclientsPhpSdk\Transport::request()`). One instance is meant to be shared across every
 * call made through a given `YclientsClient` — it tracks the timestamp of its own last request.
 */
final class Throttle
{
    private readonly float $minIntervalSeconds;
    private float $lastRequestAt = 0.0;

    /** $requestsPerSecond <= 0 disables throttling entirely (wait() becomes a no-op). */
    public function __construct(float $requestsPerSecond = 5.0)
    {
        $this->minIntervalSeconds = $requestsPerSecond > 0.0 ? 1.0 / $requestsPerSecond : 0.0;
    }

    public function wait(): void
    {
        if ($this->minIntervalSeconds <= 0.0) {
            return;
        }

        $remaining = $this->minIntervalSeconds - ($this->now() - $this->lastRequestAt);
        if ($remaining > 0.0) {
            $this->sleep($remaining);
        }

        $this->lastRequestAt = $this->now();
    }

    private function now(): float
    {
        return microtime(true);
    }

    private function sleep(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }
}
