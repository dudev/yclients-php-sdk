<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Exception;

/**
 * YClients answered the request but signalled an error — either the envelope had `success: false`
 * (or an `errors` key, seen on 401/404 responses instead of `success`, YClients isn't consistent
 * between the two shapes) or the HTTP status was >= 400. $apiMessage is whatever text YClients gave
 * back (from `errors.message` or `meta.message`), not always present.
 */
class YclientsApiException extends YclientsException
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly int $statusCode,
        public readonly ?string $apiMessage,
    ) {
        parent::__construct(sprintf(
            'YClients API error on %s %s: HTTP %d%s',
            $this->method,
            $this->path,
            $this->statusCode,
            $this->apiMessage !== null ? ' — ' . $this->apiMessage : '',
        ));
    }
}
