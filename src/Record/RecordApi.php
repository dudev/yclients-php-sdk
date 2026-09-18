<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

use Dudev\YclientsPhpSdk\Pagination\Page;
use Dudev\YclientsPhpSdk\Transport;

final readonly class RecordApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * `GET /api/v1/records/{company_id}` — `partner_token`+`user_token`. `changedAfter`/
     * `changedBefore` are the ones to use for incremental sync, not `startDate`/`endDate` (those
     * filter by appointment date, not by when the record was created/changed) — see
     * `yclients-records-design.md` §5.
     *
     * @return Page<Record>
     */
    public function list(
        int $companyId,
        int $page = 1,
        int $count = 50,
        ?int $staffId = null,
        ?int $clientId = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        ?\DateTimeInterface $changedAfter = null,
        ?\DateTimeInterface $changedBefore = null,
        bool $withDeleted = false,
    ): Page {
        $query = array_filter([
            'page' => $page,
            'count' => $count,
            'staff_id' => $staffId,
            'client_id' => $clientId,
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
            'changed_after' => $changedAfter?->format(\DateTimeInterface::ATOM),
            'changed_before' => $changedBefore?->format(\DateTimeInterface::ATOM),
            'with_deleted' => $withDeleted ? 1 : null,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->transport->requestWithStatus('GET', sprintf('/api/v1/records/%d', $companyId), query: $query, requireUserToken: true);

        /** @var list<array<string, mixed>> $items */
        $items = array_values($response->data);
        $records = array_map(Record::fromArray(...), $items);

        return new Page($records, count($records), $page);
    }

    /**
     * Walks every page of {@see self::list()} for you, largest page size the API accepts (200),
     * stopping as soon as a page comes back short of that (or empty) — not relying on `meta.total`
     * being accurate. See `yclients-php-sdk-design.md` §1 for why this exists.
     *
     * @return \Generator<int, Record>
     */
    public function getAll(
        int $companyId,
        ?int $staffId = null,
        ?int $clientId = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        ?\DateTimeInterface $changedAfter = null,
        ?\DateTimeInterface $changedBefore = null,
        bool $withDeleted = false,
    ): \Generator {
        $pageSize = 200;
        $page = 1;
        while (true) {
            $result = $this->list(
                companyId: $companyId,
                page: $page,
                count: $pageSize,
                staffId: $staffId,
                clientId: $clientId,
                startDate: $startDate,
                endDate: $endDate,
                changedAfter: $changedAfter,
                changedBefore: $changedBefore,
                withDeleted: $withDeleted,
            );

            if ($result->items === []) {
                return;
            }

            // Not `yield from` — every page's items are 0-indexed, so `yield from` would repeat
            // keys across pages and silently drop records for any caller collecting the generator
            // with `iterator_to_array()` (which preserves keys by default).
            foreach ($result->items as $item) {
                yield $item;
            }

            if (count($result->items) < $pageSize) {
                return;
            }

            ++$page;
        }
    }

    /** `GET /api/v1/record/{company_id}/{record_id}` — `partner_token`+`user_token`. */
    public function get(int $companyId, int $recordId): Record
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/record/%d/%d', $companyId, $recordId), requireUserToken: true);

        /** @var array<string, mixed> $data */
        return Record::fromArray($data);
    }
}
