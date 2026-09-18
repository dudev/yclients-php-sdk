<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Company;

/** `GET /api/v1/companies`, `GET /api/v1/company/{id}/` — fields confirmed against the real API docs. */
final readonly class Company
{
    /**
     * @param list<string> $phones
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $country,
        public ?string $city,
        public ?string $address,
        public array $phones,
        public ?string $site,
        public ?float $coordinateLat,
        public ?float $coordinateLon,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var mixed $rawPhones */
        $rawPhones = $data['phones'] ?? [];
        $phones = is_array($rawPhones) ? array_values(array_filter($rawPhones, is_string(...))) : [];

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            title: is_scalar($data['title'] ?? null) ? (string) $data['title'] : '',
            country: is_string($data['country'] ?? null) ? $data['country'] : null,
            city: is_string($data['city'] ?? null) ? $data['city'] : null,
            address: is_string($data['address'] ?? null) ? $data['address'] : null,
            phones: $phones,
            site: is_string($data['site'] ?? null) ? $data['site'] : null,
            coordinateLat: is_numeric($data['coordinate_lat'] ?? null) ? (float) $data['coordinate_lat'] : null,
            coordinateLon: is_numeric($data['coordinate_lon'] ?? null) ? (float) $data['coordinate_lon'] : null,
        );
    }
}
