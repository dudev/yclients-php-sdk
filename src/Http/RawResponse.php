<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Http;

/** @see \Dudev\YclientsPhpSdk\Transport::requestWithStatus() */
final readonly class RawResponse
{
    /**
     * @param array<int|string, mixed> $data the `data` member of the envelope, already unwrapped —
     *        0-indexed for a list endpoint, string-keyed for a detail endpoint
     */
    public function __construct(
        public int $statusCode,
        public array $data,
    ) {
    }
}
