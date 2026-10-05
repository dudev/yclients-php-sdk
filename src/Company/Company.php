<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Company;

/**
 * `GET /api/v1/companies`, `GET /api/v1/company/{id}/` — fields confirmed against the API docs'
 * schema. `phone` is a single string, and only the list endpoint returns it (the detail endpoint's
 * schema has no phone at all); `site` is the other way round.
 */
final readonly class Company
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $country,
        public ?string $city,
        public ?string $address,
        public ?string $phone,
        public ?string $site,
        public ?float $coordinateLat,
        public ?float $coordinateLon,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            title: is_scalar($data['title'] ?? null) ? (string) $data['title'] : '',
            country: is_string($data['country'] ?? null) ? $data['country'] : null,
            city: is_string($data['city'] ?? null) ? $data['city'] : null,
            address: is_string($data['address'] ?? null) ? $data['address'] : null,
            phone: is_string($data['phone'] ?? null) && $data['phone'] !== '' ? $data['phone'] : null,
            site: is_string($data['site'] ?? null) ? $data['site'] : null,
            coordinateLat: is_numeric($data['coordinate_lat'] ?? null) ? (float) $data['coordinate_lat'] : null,
            coordinateLon: is_numeric($data['coordinate_lon'] ?? null) ? (float) $data['coordinate_lon'] : null,
        );
    }
}
