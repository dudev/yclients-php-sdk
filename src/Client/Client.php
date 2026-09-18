<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Client;

/**
 * `GET /api/v1/clients/{company_id}`, `GET /api/v1/client/{company_id}/{id}` — a YClients customer.
 * Fields confirmed against real webhook payloads and the API docs (see `yclients-clients-design.md`
 * in the consuming project) — includes `spent`/`balance`/`visits` even though relsy's own `Client`
 * entity doesn't persist them, because this SDK mirrors the API, not any one consumer's schema.
 *
 * `categories` is kept as the raw array YClients sends (id/title/color/... per entry) rather than a
 * typed value object — its exact shape wasn't pinned down carefully enough to model, and YClients
 * itself is inconsistent about the field name for the same data (`categories` here, `labels` on
 * `PUT`) — see `yclients-clients-design.md` §3.
 */
final readonly class Client
{
    /**
     * @param list<array<string, mixed>> $categories
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $surname,
        public ?string $patronymic,
        public ?string $displayName,
        public ?string $phone,
        public ?string $email,
        public ?string $card,
        public ?\DateTimeImmutable $birthDate,
        public ?string $comment,
        public ?string $sex,
        public ?string $importance,
        public int $discountPercent,
        /** Whole rubles, not confirmed as precisely as `discountPercent` — see class docblock. */
        public int $spent,
        public int $balance,
        public int $visits,
        public array $categories,
        public array $customFields,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var mixed $rawCategories */
        $rawCategories = $data['categories'] ?? $data['labels'] ?? [];
        /** @var list<array<string, mixed>> $categories */
        $categories = is_array($rawCategories)
            ? array_values(array_filter($rawCategories, is_array(...)))
            : [];

        /** @var mixed $rawCustomFields */
        $rawCustomFields = $data['custom_fields'] ?? [];
        $customFields = is_array($rawCustomFields) ? $rawCustomFields : [];

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            surname: self::nonEmptyString($data['surname'] ?? null),
            patronymic: self::nonEmptyString($data['patronymic'] ?? null),
            displayName: self::nonEmptyString($data['display_name'] ?? null),
            phone: self::nonEmptyString($data['phone'] ?? null),
            email: self::nonEmptyString($data['email'] ?? null),
            card: self::nonEmptyString($data['card'] ?? null),
            birthDate: self::parseDate($data['birth_date'] ?? null),
            comment: self::nonEmptyString($data['comment'] ?? null),
            sex: self::nonEmptyString($data['sex'] ?? null),
            importance: self::nonEmptyString($data['importance'] ?? null),
            discountPercent: is_numeric($data['discount'] ?? null) ? (int) $data['discount'] : 0,
            spent: is_numeric($data['spent'] ?? null) ? (int) $data['spent'] : 0,
            balance: is_numeric($data['balance'] ?? null) ? (int) $data['balance'] : 0,
            visits: is_numeric($data['visits'] ?? null) ? (int) $data['visits'] : 0,
            categories: $categories,
            customFields: $customFields,
        );
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false ? $date : null;
    }
}
