<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Record;

use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;
use Dudev\YclientsPhpSdk\Record\Attendance;
use Dudev\YclientsPhpSdk\Record\Record;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecordTest extends TestCase
{
    #[Test]
    public function itParsesADatetimeWithoutMilliseconds(): void
    {
        $record = Record::fromArray(self::minimal([
            'datetime' => '2026-09-16T19:30:00+05:00',
            'attendance' => 2,
        ]));

        self::assertSame('2026-09-16 19:30:00', $record->datetime->format('Y-m-d H:i:s'));
        self::assertSame(Attendance::Confirmed, $record->attendance);
    }

    #[Test]
    public function itParsesADatetimeWithMilliseconds(): void
    {
        $record = Record::fromArray(self::minimal([
            'datetime' => '2014-09-21T23:00:00.000+03:00',
        ]));

        self::assertSame('2014-09-21 23:00:00', $record->datetime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itFallsBackToAwaitingForAnUnknownAttendanceValue(): void
    {
        $record = Record::fromArray(self::minimal(['attendance' => 99]));

        self::assertSame(Attendance::Awaiting, $record->attendance);
    }

    #[Test]
    public function itThrowsWhenTheAttendanceKeyIsMissingEntirely(): void
    {
        $data = self::minimal();
        unset($data['attendance']);

        $this->expectException(YclientsMalformedPayloadException::class);

        Record::fromArray($data);
    }

    #[Test]
    public function itThrowsWhenDatetimeIsMissing(): void
    {
        $data = self::minimal();
        unset($data['datetime']);

        $this->expectException(YclientsMalformedPayloadException::class);

        Record::fromArray($data);
    }

    #[Test]
    public function itThrowsWhenNeitherSeanceLengthNorLengthIsPresent(): void
    {
        $data = self::minimal();
        unset($data['seance_length'], $data['length']);

        $this->expectException(YclientsMalformedPayloadException::class);

        Record::fromArray($data);
    }

    #[Test]
    public function itReadsTheClientIdFromTheNestedClientObject(): void
    {
        $record = Record::fromArray(self::minimal([
            'client' => ['id' => 555, 'name' => 'Клиент'],
        ]));

        self::assertSame(555, $record->clientId);
    }

    #[Test]
    public function itParsesNestedServices(): void
    {
        $record = Record::fromArray(self::minimal([
            'services' => [
                ['id' => 10, 'title' => 'Массаж', 'cost' => 2500, 'cost_to_pay' => 2500, 'amount' => 1],
            ],
        ]));

        self::assertCount(1, $record->services);
        self::assertSame(250000, $record->services[0]->costCents);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function minimal(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'company_id' => 622905,
            'datetime' => '2026-09-16T19:30:00+05:00',
            'attendance' => 0,
            'seance_length' => 3600,
        ], $overrides);
    }
}
