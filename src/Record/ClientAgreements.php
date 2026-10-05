<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

/**
 * `data[].client_agreements` of `GET /api/v1/records/{company_id}` (and of the `POST` that creates a
 * record) — the client's legal agreements, tied to one client in one company. Not returned by the
 * single-record `GET`, by any client endpoint, or by webhooks (0 of ~12k real `record` webhooks).
 *
 * Both flags are nullable on purpose: a flag YClients didn't send is "unknown", which for consent
 * is not the same thing as "refused".
 */
final readonly class ClientAgreements
{
    public function __construct(
        public ?int $clientId,
        public ?int $companyId,
        public ?bool $isNewsletterAllowed,
        public ?bool $isPersonalDataProcessingAllowed,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: is_numeric($data['client_id'] ?? null) ? (int) $data['client_id'] : null,
            companyId: is_numeric($data['company_id'] ?? null) ? (int) $data['company_id'] : null,
            isNewsletterAllowed: is_bool($data['is_newsletter_allowed'] ?? null) ? $data['is_newsletter_allowed'] : null,
            isPersonalDataProcessingAllowed: is_bool($data['is_personal_data_processing_allowed'] ?? null) ? $data['is_personal_data_processing_allowed'] : null,
        );
    }
}
