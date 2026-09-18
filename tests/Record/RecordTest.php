<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Record;

use Dudev\YclientsPhpSdk\Record\Attendance;
use Dudev\YclientsPhpSdk\Record\Record;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecordTest extends TestCase
{
    #[Test]
    public function itParsesADatetimeWithoutMilliseconds(): void
    {
        $record = Record::fromArray([
            'id' => 1,
            'company_id' => 622905,
            'datetime' => '2026-09-16T19:30:00+05:00',
            'attendance' => 2,
        ]);

        self::assertSame('2026-09-16 19:30:00', $record->datetime->format('Y-m-d H:i:s'));
        self::assertSame(Attendance::Confirmed, $record->attendance);
    }

    #[Test]
    public function itParsesADatetimeWithMilliseconds(): void
    {
        $record = Record::fromArray([
            'id' => 1,
            'company_id' => 622905,
            'datetime' => '2014-09-21T23:00:00.000+03:00',
        ]);

        self::assertSame('2014-09-21 23:00:00', $record->datetime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itFallsBackToAwaitingForAnUnknownAttendanceValue(): void
    {
        $record = Record::fromArray(['id' => 1, 'company_id' => 1, 'attendance' => 99]);

        self::assertSame(Attendance::Awaiting, $record->attendance);
    }

    #[Test]
    public function itReadsTheClientIdFromTheNestedClientObject(): void
    {
        $record = Record::fromArray([
            'id' => 1,
            'company_id' => 1,
            'client' => ['id' => 555, 'name' => 'Клиент'],
        ]);

        self::assertSame(555, $record->clientId);
    }

    #[Test]
    public function itParsesNestedServices(): void
    {
        $record = Record::fromArray([
            'id' => 1,
            'company_id' => 1,
            'services' => [
                ['id' => 10, 'title' => 'Массаж', 'cost' => 2500, 'cost_to_pay' => 2500, 'amount' => 1],
            ],
        ]);

        self::assertCount(1, $record->services);
        self::assertSame(250000, $record->services[0]->costCents);
    }
}
