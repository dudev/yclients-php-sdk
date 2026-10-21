<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

use Dudev\YclientsPhpSdk\RequiredField;

/**
 * One row of `POST /api/v1/company/{company_id}/clients/search` — a listing, not a client card. Only
 * `id` is guaranteed; the rest is present if it was asked for in `fields` *and* the API knows that
 * name (observed 2026-10-21, see {@see ClientApi::search()}). Dates are naive `"Y-m-d H:i:s"`
 * strings in the API (no timezone) and empty when the client never visited.
 */
final readonly class ClientSummary
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $surname,
        public ?string $patronymic,
        public ?string $phone,
        public ?string $email,
        public ?int $discountPercent,
        public ?\DateTimeImmutable $firstVisitDate,
        public ?\DateTimeImmutable $lastVisitDate,
        /** Whatever unit YClients uses for `sold_amount` (rubles in every sample seen, fractional possible). */
        public ?float $soldAmount,
        public ?int $visitsCount,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: RequiredField::int($data, 'id', self::class),
            name: self::string($data['name'] ?? null),
            surname: self::string($data['surname'] ?? null),
            patronymic: self::string($data['patronymic'] ?? null),
            phone: self::string($data['phone'] ?? null),
            email: self::string($data['email'] ?? null),
            discountPercent: is_numeric($data['discount'] ?? null) ? (int) $data['discount'] : null,
            firstVisitDate: self::date($data['first_visit_date'] ?? null),
            lastVisitDate: self::date($data['last_visit_date'] ?? null),
            soldAmount: is_numeric($data['sold_amount'] ?? null) ? (float) $data['sold_amount'] : null,
            visitsCount: is_numeric($data['visits_count'] ?? null) ? (int) $data['visits_count'] : null,
        );
    }

    private static function string(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date !== false ? $date : null;
    }
}
