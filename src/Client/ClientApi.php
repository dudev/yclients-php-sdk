<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

use Dudev\YclientsPhpSdk\Pagination\Page;
use Dudev\YclientsPhpSdk\Transport;

final readonly class ClientApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * `GET /api/v1/clients/{company_id}` — `partner_token`+`user_token`. No `withDeleted` here —
     * unlike records, YClients doesn't document an equivalent flag for clients (not confirmed
     * whether client deletion is signalled at all, see `yclients-clients-design.md` §2/§8).
     *
     * @return Page<Client>
     */
    public function list(
        int $companyId,
        int $page = 1,
        int $count = 50,
        ?string $fullname = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $card = null,
        ?\DateTimeInterface $changedAfter = null,
        ?\DateTimeInterface $changedBefore = null,
    ): Page {
        $query = array_filter([
            'page' => $page,
            'count' => $count,
            'fullname' => $fullname,
            'phone' => $phone,
            'email' => $email,
            'card' => $card,
            'changed_after' => $changedAfter?->format(\DateTimeInterface::ATOM),
            'changed_before' => $changedBefore?->format(\DateTimeInterface::ATOM),
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->transport->requestWithStatus('GET', sprintf('/api/v1/clients/%d', $companyId), query: $query, requireUserToken: true);

        /** @var list<array<string, mixed>> $items */
        $items = array_values($response->data);
        $clients = array_map(Client::fromArray(...), $items);

        return new Page($clients, count($clients), $page);
    }

    /**
     * Same "walk every page" helper as `RecordApi::getAll()` — see there for why the loop ends the
     * way it does.
     *
     * @return \Generator<int, Client>
     */
    public function getAll(
        int $companyId,
        ?string $fullname = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $card = null,
        ?\DateTimeInterface $changedAfter = null,
        ?\DateTimeInterface $changedBefore = null,
    ): \Generator {
        $pageSize = 200;
        $page = 1;
        while (true) {
            $result = $this->list(
                companyId: $companyId,
                page: $page,
                count: $pageSize,
                fullname: $fullname,
                phone: $phone,
                email: $email,
                card: $card,
                changedAfter: $changedAfter,
                changedBefore: $changedBefore,
            );

            if ($result->items === []) {
                return;
            }

            // Not `yield from` — see the identical comment in RecordApi::getAll().
            foreach ($result->items as $item) {
                yield $item;
            }

            if (count($result->items) < $pageSize) {
                return;
            }

            ++$page;
        }
    }

    /** `GET /api/v1/client/{company_id}/{id}` — `partner_token`+`user_token`. */
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
