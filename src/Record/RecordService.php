<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

/** One line of `Record::$services` — a service rendered during the visit. */
final readonly class RecordService
{
    public function __construct(
        public int $id,
        public string $title,
        public int $costCents,
        public int $costToPayCents,
        public int $amount,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            title: is_scalar($data['title'] ?? null) ? (string) $data['title'] : '',
            // YClients' money fields are whole rubles, not kopeks, everywhere this was directly
            // confirmed (client.spent/client.balance/client.discount, see yclients-clients-design.md)
            // — "cost"/"cost_to_pay" here weren't independently confirmed the same way, only inferred
            // by analogy; multiply so the rest of the SDK stays in "integer = minimal unit", but
            // verify against a real record before relying on this in production.
            costCents: (int) round((is_numeric($data['cost'] ?? null) ? (float) $data['cost'] : 0.0) * 100),
            costToPayCents: (int) round((is_numeric($data['cost_to_pay'] ?? null) ? (float) $data['cost_to_pay'] : 0.0) * 100),
            amount: is_numeric($data['amount'] ?? null) ? (int) $data['amount'] : 0,
        );
    }
}
