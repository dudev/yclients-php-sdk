<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

/** Request body shared by `ClientApi::create()`/`update()` — both require `name`+`phone`. */
final readonly class ClientWriteRequest
{
    /**
     * @param list<int> $categoryIds
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $surname = null,
        public ?string $patronymic = null,
        public ?string $email = null,
        public ?int $sexId = null,
        public ?int $importanceId = null,
        public ?int $discountPercent = null,
        public ?string $card = null,
        public ?\DateTimeInterface $birthDate = null,
        public ?string $comment = null,
        public array $categoryIds = [],
        public array $customFields = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $body = array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'surname' => $this->surname,
            'patronymic' => $this->patronymic,
            'email' => $this->email,
            'sex_id' => $this->sexId,
            'importance_id' => $this->importanceId,
            'discount' => $this->discountPercent,
            'card' => $this->card,
            'birth_date' => $this->birthDate?->format('Y-m-d'),
            'comment' => $this->comment,
        ], static fn (mixed $value): bool => $value !== null);

        if ($this->categoryIds !== []) {
            // YClients names this field `categories` on create, `labels` on edit, for the same
            // data (see `yclients-clients-design.md` §3) — send both rather than pick one per
            // endpoint, the one it doesn't recognise should just be ignored.
            $body['categories'] = $this->categoryIds;
            $body['labels'] = $this->categoryIds;
        }

        if ($this->customFields !== []) {
            $body['custom_fields'] = $this->customFields;
        }

        return $body;
    }
}
