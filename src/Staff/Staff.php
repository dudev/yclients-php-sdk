<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Staff;

/** `GET /api/v1/staff/{company_id}[/{staff_id}]` — fields confirmed against real webhook payloads and the API docs. */
final readonly class Staff
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $specialization,
        public ?string $positionTitle,
        public ?string $avatar,
        public float $rating,
        public bool $hidden,
        public bool $fired,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var mixed $position */
        $position = $data['position'] ?? null;
        $positionTitle = is_array($position) && is_string($position['title'] ?? null) ? $position['title'] : null;

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            specialization: is_string($data['specialization'] ?? null) ? $data['specialization'] : null,
            positionTitle: $positionTitle,
            avatar: is_string($data['avatar'] ?? null) ? $data['avatar'] : null,
            rating: is_numeric($data['rating'] ?? null) ? (float) $data['rating'] : 0.0,
            hidden: (bool) ($data['hidden'] ?? false),
            fired: (bool) ($data['fired'] ?? false),
        );
    }
}
