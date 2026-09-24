<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Webhook;

use Dudev\YclientsPhpSdk\Client\Client;
use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Record\Record;
use Dudev\YclientsPhpSdk\Staff\Staff;

/**
 * A single YClients webhook. The envelope — `{company_id, resource, resource_id, status, data}` —
 * isn't in YClients' own API docs at all (they don't document webhooks); it's confirmed against
 * real production payloads instead (see the consuming project's `yclients-php-sdk-design.md` §6).
 *
 * `data` turned out to use the exact same shape as the matching REST resource's detail response
 * (confirmed for `record`/`client`/`staff` against real payloads), so it's parsed with that
 * resource's own `fromArray()` rather than a separate webhook-specific DTO — including for
 * `status: delete`, where `data` is still the full object (with its own `deleted` flag set), not
 * just a bare id.
 *
 * Parsing the envelope is all this class does. Receiving the HTTP POST itself — routing, verifying
 * it actually came from YClients (or whatever relay sits in front, e.g. a Cloud Function) — is the
 * consuming app's job, not the SDK's.
 */
final readonly class WebhookEvent
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public int $companyId,
        public string $resource,
        public int $resourceId,
        public WebhookStatus $status,
        public array $data,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $status = is_string($payload['status'] ?? null) ? WebhookStatus::tryFrom($payload['status']) : null;
        if ($status === null) {
            throw new YclientsException(sprintf(
                'Unrecognized webhook status %s — expected one of: %s',
                json_encode($payload['status'] ?? null, JSON_THROW_ON_ERROR),
                implode(', ', array_map(static fn (WebhookStatus $s): string => $s->value, WebhookStatus::cases())),
            ));
        }

        /** @var mixed $rawData */
        $rawData = $payload['data'] ?? [];

        return new self(
            companyId: is_numeric($payload['company_id'] ?? null) ? (int) $payload['company_id'] : 0,
            resource: is_scalar($payload['resource'] ?? null) ? (string) $payload['resource'] : '',
            resourceId: is_numeric($payload['resource_id'] ?? null) ? (int) $payload['resource_id'] : 0,
            status: $status,
            data: is_array($rawData) ? $rawData : [],
        );
    }

    /** @throws \LogicException if {@see self::$resource} isn't `"record"` */
    public function record(): Record
    {
        $this->assertResource('record');

        return Record::fromArray($this->data);
    }

    /** @throws \LogicException if {@see self::$resource} isn't `"client"` */
    public function client(): Client
    {
        $this->assertResource('client');

        return Client::fromArray($this->data);
    }

    /** @throws \LogicException if {@see self::$resource} isn't `"staff"` */
    public function staff(): Staff
    {
        $this->assertResource('staff');

        return Staff::fromArray($this->data);
    }

    private function assertResource(string $expected): void
    {
        if ($this->resource !== $expected) {
            throw new \LogicException(sprintf(
                'This event is for resource "%s", not "%s" — check $event->resource before calling %s().',
                $this->resource,
                $expected,
                $expected,
            ));
        }
    }
}
