<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Staff;

use Dudev\YclientsPhpSdk\Transport;

final readonly class StaffApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * `GET /api/v1/staff/{company_id}` — only `partner_token` needed. No pagination confirmed for
     * this endpoint — returns everything in one call.
     *
     * @return list<Staff>
     */
    public function list(int $companyId): array
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/staff/%d', $companyId));

        /** @var list<array<string, mixed>> $items */
        $items = array_values($data);

        return array_map(Staff::fromArray(...), $items);
    }

    /** `GET /api/v1/staff/{company_id}/{staff_id}` — only `partner_token` needed. */
    public function get(int $companyId, int $staffId): Staff
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/staff/%d/%d', $companyId, $staffId));

        /** @var array<string, mixed> $data */
        return Staff::fromArray($data);
    }
}
