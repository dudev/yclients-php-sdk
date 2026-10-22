<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

use Dudev\YclientsPhpSdk\RequiredField;

/**
 * Just enough of a client to know it changed: its id and when. What {@see ClientApi::changedSince()}
 * returns — the card itself is meant to be re-read with {@see ClientApi::get()}.
 */
final readonly class ChangedClient
{
    public function __construct(
        public int $id,
        public ?\DateTimeImmutable $lastChangeDate,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $raw = $data['last_change_date'] ?? null;
        $date = is_string($raw) && $raw !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sO', $raw) : false;

        return new self(RequiredField::int($data, 'id', self::class), $date !== false ? $date : null);
    }
}
