<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Staff;

use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Transport;

/**
 * Uses `GET /api/v1/company/{company_id}/staff[/{staff_id}]` — the replacement for the deprecated
 * `GET /api/v1/staff/{company_id}[/{staff_id}]`. Unlike the old one it needs a user token too.
 * Verified on the live API (2026-10-21): the list route without `staff_id` works, `staff_id = 0` is
 * rejected ("[masterId] must be positive"), and `rating` *is* returned (the docs' schema omits it).
 */
final readonly class StaffApi
{
    public function __construct(private Transport $transport)
    {
    }

    /** @return list<Staff> */
    public function list(int $companyId): array
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/company/%d/staff', $companyId), requireUserToken: true);

        /** @var list<array<string, mixed>> $items */
        $items = array_values($data);

        return array_map(Staff::fromArray(...), $items);
    }

    /** The docs say `data` is an array even for one employee; the live API sends a single object — both are accepted. */
    public function get(int $companyId, int $staffId): Staff
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/company/%d/staff/%d', $companyId, $staffId), requireUserToken: true);

        $item = isset($data['id']) ? $data : (array_values($data)[0] ?? null);
        if (!is_array($item) || $item === []) {
            throw new YclientsException(sprintf('No staff member %d returned for company %d', $staffId, $companyId));
        }

        /** @var array<string, mixed> $item */
        return Staff::fromArray($item);
    }
}
