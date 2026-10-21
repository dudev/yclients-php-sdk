<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

use Dudev\YclientsPhpSdk\Pagination\Page;
use Dudev\YclientsPhpSdk\Pagination\PageWalker;
use Dudev\YclientsPhpSdk\Transport;

final readonly class ClientApi
{
    public const MAX_PAGE_SIZE = 200;

    public function __construct(private Transport $transport)
    {
    }

    /**
     * `POST /api/v1/company/{company_id}/clients/search` — `partner_token`+`user_token`. Replaces
     * the deprecated `GET /api/v1/clients/{company_id}`. `$pageSize` is capped at 200 by YClients.
     *
     * **This is a listing, not a full client card.** Verified on the live API (2026-10-21): without
     * `$fields` only `id` comes back; unknown names are silently ignored; the only names that
     * returned data were `id`, `name`, `surname`, `patronymic`, `phone`, `email`, `discount`,
     * `first_visit_date`, `last_visit_date`, `sold_amount`, `visits_count`. Card, birth date, comment,
     * sex, importance, categories, custom fields, `last_change_date` and consents are NOT available
     * here — use {@see self::get()} for those. There is no "changed since" filter either (the
     * deprecated `GET /api/v1/clients/{company_id}` has `changed_after`). `$filters` is passed
     * through as-is: a list of `{"type": "...", "state": {...}}` objects (`id`, `quick_search`, ...).
     *
     * @param list<string>|null $fields
     * @param list<array<string, mixed>>|null $filters
     * @param 'ASC'|'DESC'|null $orderByDirection
     * @param 'AND'|'OR' $operation how multiple `$filters` combine
     * @return Page<ClientSummary>
     */
    public function search(
        int $companyId,
        int $page = 1,
        int $pageSize = 25,
        ?array $fields = null,
        ?array $filters = null,
        ?string $orderBy = null,
        ?string $orderByDirection = null,
        string $operation = 'AND',
    ): Page {
        $body = array_filter([
            'page' => $page,
            'page_size' => min($pageSize, self::MAX_PAGE_SIZE),
            'fields' => $fields,
            'order_by' => $orderBy,
            'order_by_direction' => $orderByDirection,
            'operation' => $operation,
            'filters' => $filters,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->transport->requestWithStatus(
            'POST',
            sprintf('/api/v1/company/%d/clients/search', $companyId),
            json: $body,
            requireUserToken: true,
        );

        /** @var list<array<string, mixed>> $items */
        $items = array_values($response->data);

        return Page::fromResponse(array_map(ClientSummary::fromArray(...), $items), $response, $page);
    }

    /**
     * Walks every page of {@see self::search()} for you, at the largest page size YClients allows
     * (see {@see PageWalker} for how the end, and a glitchy empty page, are told apart). No "changed
     * since" filter exists here — verified: neither `last_change_date` nor any similar name works as a
     * filter or sort key — so this is always a full walk, which is what a periodic reconcile wants.
     *
     * @param list<string>|null $fields
     * @param list<array<string, mixed>>|null $filters
     * @param 'AND'|'OR' $operation
     * @return \Generator<int, ClientSummary>
     */
    public function getAll(
        int $companyId,
        ?array $fields = null,
        ?array $filters = null,
        string $operation = 'AND',
    ): \Generator {
        return PageWalker::walk(fn (int $page): Page => $this->search(
            companyId: $companyId,
            page: $page,
            pageSize: self::MAX_PAGE_SIZE,
            fields: $fields,
            filters: $filters,
            orderBy: 'id',
            orderByDirection: 'ASC',
            operation: $operation,
        ));
    }

    /**
     * `GET /api/v1/client/{company_id}/{id}` — `partner_token`+`user_token`. The only endpoint that
     * returns a full card *and* `client_agreements` (consents).
     */
    public function get(int $companyId, int $id): Client
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/client/%d/%d', $companyId, $id), requireUserToken: true);

        /** @var array<string, mixed> $data */
        return Client::fromArray($data);
    }

    /**
     * `POST /api/v1/clients/{company_id}` — creates a client in YClients. relsy doesn't call this
     * (it only reads from YClients, see `yclients-clients-design.md` §3), kept for completeness —
     * other consumers of this SDK may need it.
     */
    public function create(int $companyId, ClientWriteRequest $request): Client
    {
        $data = $this->transport->request(
            'POST',
            sprintf('/api/v1/clients/%d', $companyId),
            json: $request->toArray(),
            requireUserToken: true,
        );

        /** @var array<string, mixed> $data */
        return Client::fromArray($data);
    }

    /** `PUT /api/v1/client/{company_id}/{id}` — see {@see self::create()} on why relsy doesn't call this. */
    public function update(int $companyId, int $id, ClientWriteRequest $request): Client
    {
        $data = $this->transport->request(
            'PUT',
            sprintf('/api/v1/client/%d/%d', $companyId, $id),
            json: $request->toArray(),
            requireUserToken: true,
        );

        /** @var array<string, mixed> $data */
        return Client::fromArray($data);
    }

    /** `DELETE /api/v1/client/{company_id}/{id}`. */
    public function delete(int $companyId, int $id): void
    {
        $this->transport->request('DELETE', sprintf('/api/v1/client/%d/%d', $companyId, $id), requireUserToken: true);
    }
}
