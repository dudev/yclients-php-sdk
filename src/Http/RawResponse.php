<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Http;

/** @see \Dudev\YclientsPhpSdk\Transport::requestWithStatus() */
final readonly class RawResponse
{
    /**
     * @param array<int|string, mixed> $data the `data` member of the envelope, already unwrapped —
     *        0-indexed for a list endpoint, string-keyed for a detail endpoint
     * @param array<int|string, mixed> $meta the `meta` member of the envelope (`[]` when absent)
     * @param array<string, mixed> $body the whole decoded response body, for the odd endpoint that
     *        doesn't use the `{success, data, meta}` envelope at all (`POST /api/v1/auth` → 201)
     */
    public function __construct(
        public int $statusCode,
        public array $data,
        public array $meta = [],
        public array $body = [],
    ) {
    }
}
