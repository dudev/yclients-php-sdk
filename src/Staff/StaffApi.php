<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Staff;

use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Transport;

/**
 * Uses `GET /api/v1/company/{company_id}/staff/{staff_id}` — the replacement for the deprecated
 * `GET /api/v1/staff/{company_id}[/{staff_id}]`. Unlike the old one it needs a user token too, and
 * its response has no `rating`/`show_rating`/`votes_count`/`information`.
 */
final readonly class StaffApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * The docs declare `staff_id` a required path segment ("if you need one specific employee") and
     * don't say how to ask for all of them — `0` is an assumption (not confirmed against a live
     * account), check it once before relying on this. Response `data` is an array.
     *
     * @return list<Staff>
     */
    public function list(int $companyId): array
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/company/%d/staff/0', $companyId), requireUserToken: true);

        /** @var list<array<string, mixed>> $items */
        $items = array_values($data);

        return array_map(Staff::fromArray(...), $items);
    }

    /** The response's `data` is an array even for one employee — takes its first element. */
    public function get(int $companyId, int $staffId): Staff
    {
        $data = $this->transport->request('GET', sprintf('/api/v1/company/%d/staff/%d', $companyId, $staffId), requireUserToken: true);

        $first = array_values($data)[0] ?? null;
        if (!is_array($first)) {
            throw new YclientsException(sprintf('No staff member %d returned for company %d', $staffId, $companyId));
        }

        /** @var array<string, mixed> $first */
        return Staff::fromArray($first);
    }
}
