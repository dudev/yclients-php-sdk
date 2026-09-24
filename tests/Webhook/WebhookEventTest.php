<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Webhook;

use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Webhook\WebhookEvent;
use Dudev\YclientsPhpSdk\Webhook\WebhookStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookEventTest extends TestCase
{
    #[Test]
    public function itParsesTheEnvelope(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'record',
            'resource_id' => 1947900183,
            'status' => 'update',
            'data' => ['id' => 1947900183, 'company_id' => 622905],
        ]);

        self::assertSame(622905, $event->companyId);
        self::assertSame('record', $event->resource);
        self::assertSame(1947900183, $event->resourceId);
        self::assertSame(WebhookStatus::Update, $event->status);
    }

    #[Test]
    public function itThrowsOnAnUnrecognizedStatus(): void
    {
        $this->expectException(YclientsException::class);

        WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'record',
            'resource_id' => 1,
            'status' => 'archive',
            'data' => [],
        ]);
    }

    #[Test]
    public function recordParsesARealRecordPayloadIncludingTheNestedClient(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'record',
            'resource_id' => 1947900183,
            'status' => 'update',
            'data' => [
                'id' => 1947900183,
                'company_id' => 622905,
                'staff_id' => 2391603,
                'attendance' => 0,
                'seance_length' => 4500,
                'deleted' => false,
                'paid_full' => 0,
                'online' => true,
                'datetime' => '2026-09-16T19:30:00+05:00',
                'create_date' => '2026-09-02T21:20:36+0500',
                'last_change_date' => '2026-09-09T20:05:21+0500',
                'services' => [
                    ['id' => 10371262, 'title' => 'Сеанс 60 мин', 'cost' => 3490, 'cost_to_pay' => 3490, 'amount' => 1],
                ],
                'client' => ['id' => 245501024, 'name' => 'Алла Пьянкова'],
            ],
        ]);

        $record = $event->record();

        self::assertSame(1947900183, $record->id);
        self::assertSame(2391603, $record->staffId);
        self::assertSame(245501024, $record->clientId);
        self::assertSame('2026-09-02T21:20:36+05:00', $record->createDate?->format('c'));
        self::assertCount(1, $record->services);
    }

    #[Test]
    public function recordThrowsWhenTheEventIsForADifferentResource(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'client',
            'resource_id' => 1,
            'status' => 'update',
            'data' => ['id' => 1, 'name' => 'Милана'],
        ]);

        $this->expectException(\LogicException::class);

        $event->record();
    }

    #[Test]
    public function clientParsesARealClientPayload(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'client',
            'resource_id' => 438311700,
            'status' => 'update',
            'data' => [
                'id' => 438311700,
                'name' => 'Милана Куликова',
                'phone' => '+79658355257',
                'discount' => 0,
                'spent' => 3680,
                'balance' => -3680,
                'categories' => [['id' => 6327742, 'title' => 'Телеграм']],
            ],
        ]);

        $client = $event->client();

        self::assertSame(438311700, $client->id);
        self::assertSame('Милана Куликова', $client->name);
        self::assertSame(-3680, $client->balance);
    }

    #[Test]
    public function staffParsesARealStaffPayloadWithAnEmptyPositionArray(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 798764,
            'resource' => 'staff',
            'resource_id' => 5963751,
            'status' => 'update',
            'data' => [
                'id' => 5963751,
                'name' => 'Анна Кирменская',
                // Real payloads send `"position": []` (empty array, not an object) when unset.
                'position' => [],
                'rating' => 0,
                'hidden' => 0,
                'fired' => 0,
            ],
        ]);

        $staff = $event->staff();

        self::assertSame(5963751, $staff->id);
        self::assertNull($staff->positionTitle);
    }

    #[Test]
    public function recordStillParsesFullDataOnDelete(): void
    {
        $event = WebhookEvent::fromArray([
            'company_id' => 622905,
            'resource' => 'record',
            'resource_id' => 1900413435,
            'status' => 'delete',
            'data' => ['id' => 1900413435, 'company_id' => 622905, 'deleted' => true],
        ]);

        self::assertSame(WebhookStatus::Delete, $event->status);
        self::assertTrue($event->record()->deleted);
    }
}
