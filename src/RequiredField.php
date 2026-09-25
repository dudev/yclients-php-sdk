<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk;

use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;

/**
 * Shared by every DTO's `fromArray()`, for the few fields whose silent default would look like a
 * real value (an id defaulting to `0`, a date defaulting to epoch) — see
 * `YclientsMalformedPayloadException`. Optional fields keep their own inline `?? null` handling;
 * this is only for fields the DTO's constructor already types non-nullable.
 */
final class RequiredField
{
    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key, string $dtoClass): int
    {
        if (!is_numeric($data[$key] ?? null)) {
            throw new YclientsMalformedPayloadException($dtoClass, $key);
        }

        return (int) $data[$key];
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key, string $dtoClass): string
    {
        if (!is_scalar($data[$key] ?? null)) {
            throw new YclientsMalformedPayloadException($dtoClass, $key);
        }

        return (string) $data[$key];
    }

    /**
     * Asserts the key exists at all (even if its value turns out to need further validation by the
     * caller) — for fields like `attendance`, where an unrecognized *value* degrades gracefully on
     * purpose, but an absent key does not.
     *
     * @param array<string, mixed> $data
     */
    public static function key(array $data, string $key, string $dtoClass): mixed
    {
        if (!array_key_exists($key, $data)) {
            throw new YclientsMalformedPayloadException($dtoClass, $key);
        }

        return $data[$key];
    }
}
