<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

use Dudev\YclientsPhpSdk\Pagination\Page;
use Dudev\YclientsPhpSdk\Pagination\PageWalker;
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

        return Page::fromResponse(array_map(Record::fromArray(...), $items), $response, $page);
    }

    /**
     * Walks every page of {@see self::list()} for you, 100 at a time (`count` accepted 1000 on the
     * live API, but the docs name no maximum). A short page is not taken as the last one — see
     * {@see PageWalker} for how the end, and a glitchy empty page, are told apart.
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
        return PageWalker::walk(fn (int $page): Page => $this->list(
            companyId: $companyId,
            page: $page,
            count: 100,
            staffId: $staffId,
            clientId: $clientId,
            startDate: $startDate,
            endDate: $endDate,
            changedAfter: $changedAfter,
            changedBefore: $changedBefore,
            withDeleted: $withDeleted,
        ));
    }

    /** `GET /api/v1/record/{company_id}/{record_id}` — `partner_token`+`user_token`. */
    public function get(int $companyId, int $recordId): Record
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/record/%d/%d', $companyId, $recordId), requireUserToken: true);

        /** @var array<string, mixed> $data */
        return Record::fromArray($data);
    }
}
