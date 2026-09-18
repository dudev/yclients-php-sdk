<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Company;

use Dudev\YclientsPhpSdk\Transport;

/**
 * `GET /api/v1/group/{group_id}/clients/?phone=...` ("сетевые клиенты") isn't implemented here yet
 * — its response shape wasn't confirmed against a real account (the one example seen had an empty
 * `clients` array), and relsy's current design explicitly doesn't use it (see
 * `yclients-clients-design.md`). Add it once there's a real consumer and a confirmed shape to model,
 * not speculatively.
 */
final readonly class CompanyApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * `GET /api/v1/companies` — only `partner_token` needed. No pagination confirmed for this
     * endpoint (unlike records/clients) — returns everything the filters match in one call.
     *
     * @param list<int>|null $id filter by one or more company ids
     * @return list<Company>
     */
    public function list(?array $id = null, ?int $groupId = null, ?bool $active = null): array
    {
        $query = array_filter([
            'id' => $id !== null ? implode(',', $id) : null,
            'group_id' => $groupId,
            'active' => $active !== null ? (int) $active : null,
        ], static fn (mixed $value): bool => $value !== null);

        $data = $this->transport->request('GET', '/api/v1/companies', query: $query);

        /** @var list<array<string, mixed>> $items */
        $items = array_values($data);

        return array_map(Company::fromArray(...), $items);
    }

    /** `GET /api/v1/company/{id}/` — needs a user token. */
    public function get(int $id): Company
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/company/%d/', $id), requireUserToken: true);

        /** @var array<string, mixed> $data */
        return Company::fromArray($data);
    }
}
