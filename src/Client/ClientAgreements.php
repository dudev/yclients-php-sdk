<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

/**
 * A client's legal agreements in one company: `client_agreements` of the single-client
 * `GET /api/v1/client/{company_id}/{id}` and of the single-record `GET /api/v1/record/{company_id}/
 * {record_id}`. Observed on the live API (2026-10-21) — and the opposite of what the docs say: they
 * list it on the records *list* (`GET /api/v1/records/{company_id}`), which in practice never carries
 * it (0 of 100), and don't list it on the client `GET` at all. `null` on clients who never ticked
 * anything; not returned by `clients/search`, the deprecated clients list, the records list or
 * webhooks.
 *
 * All flags are nullable: the SDK reports faithfully that YClients did not send one, and leaves
 * what that means to the consumer (relsy treats anything but `true` as "no"). The `yc` pair is
 * consent to YClients itself (the platform), not to the salon.
 */
final readonly class ClientAgreements
{
    public function __construct(
        public ?int $clientId,
        public ?int $companyId,
        public ?bool $isNewsletterAllowed,
        public ?bool $isPersonalDataProcessingAllowed,
        public ?bool $isYcNewsletterAllowed,
        public ?bool $isYcPersonalDataProcessingAllowed,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: is_numeric($data['client_id'] ?? null) ? (int) $data['client_id'] : null,
            companyId: is_numeric($data['company_id'] ?? null) ? (int) $data['company_id'] : null,
            isNewsletterAllowed: self::flag($data['is_newsletter_allowed'] ?? null),
            isPersonalDataProcessingAllowed: self::flag($data['is_personal_data_processing_allowed'] ?? null),
            isYcNewsletterAllowed: self::flag($data['is_yc_newsletter_allowed'] ?? null),
            isYcPersonalDataProcessingAllowed: self::flag($data['is_yc_personal_data_processing_allowed'] ?? null),
        );
    }

    private static function flag(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
