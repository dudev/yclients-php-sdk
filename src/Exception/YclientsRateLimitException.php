<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Exception;

/** HTTP 429 — over the documented 200 req/min (5 req/sec) limit for the calling IP. */
final class YclientsRateLimitException extends YclientsApiException
{
    public function __construct(string $method, string $path)
    {
        parent::__construct($method, $path, 429, 'Rate limit exceeded (200 req/min, 5 req/sec per IP)');
    }
}
