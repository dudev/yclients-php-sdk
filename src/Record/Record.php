<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

/** `GET /api/v1/records/{company_id}`, `GET /api/v1/record/{company_id}/{record_id}`. */
final readonly class Record
{
    /**
     * @param list<RecordService> $services
     */
    public function __construct(
        public int $id,
        public int $companyId,
        public ?int $staffId,
        public ?int $clientId,
        public \DateTimeImmutable $datetime,
        public ?\DateTimeImmutable $createDate,
        public ?\DateTimeImmutable $lastChangeDate,
        public ?string $comment,
        public bool $online,
        public ?int $visitId,
        public Attendance $attendance,
        public int $seanceLengthSeconds,
        public bool $deleted,
        public bool $paidFull,
        public array $services,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var mixed $client */
        $client = $data['client'] ?? null;
        $clientId = is_array($client) && is_numeric($client['id'] ?? null) ? (int) $client['id'] : null;

        /** @var mixed $rawServices */
        $rawServices = $data['services'] ?? [];
        $services = is_array($rawServices)
            ? array_values(array_map(
                static fn (mixed $service): RecordService => RecordService::fromArray(is_array($service) ? $service : []),
                $rawServices,
            ))
            : [];

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            companyId: is_numeric($data['company_id'] ?? null) ? (int) $data['company_id'] : 0,
            staffId: is_numeric($data['staff_id'] ?? null) ? (int) $data['staff_id'] : null,
            clientId: $clientId,
            datetime: self::parseDate($data['datetime'] ?? null) ?? new \DateTimeImmutable('@0'),
            createDate: self::parseDate($data['create_date'] ?? null),
            lastChangeDate: self::parseDate($data['last_change_date'] ?? null),
            comment: is_string($data['comment'] ?? null) ? $data['comment'] : null,
            online: (bool) ($data['online'] ?? false),
            visitId: is_numeric($data['visit_id'] ?? null) ? (int) $data['visit_id'] : null,
            attendance: Attendance::tryFrom(is_numeric($data['attendance'] ?? null) ? (int) $data['attendance'] : 0) ?? Attendance::Awaiting,
            seanceLengthSeconds: self::firstNumeric($data['seance_length'] ?? null, $data['length'] ?? null),
            deleted: (bool) ($data['deleted'] ?? false),
            paidFull: (bool) ($data['paid_full'] ?? false),
            services: $services,
        );
    }

    /**
     * YClients' own docs show `"2014-09-21T23:00:00.000+03:00"` (milliseconds) for ISO 8601 dates
     * in general, but real `record.datetime` samples seen had none (`"2026-09-16T19:30:00+05:00"`)
     * — try both rather than assume either is the only one that occurs.
     */
    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $value)
            ?: \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date !== false ? $date : null;
    }

    private static function firstNumeric(mixed ...$candidates): int
    {
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        return 0;
    }
}
