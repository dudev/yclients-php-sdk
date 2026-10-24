<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Client;

use Dudev\YclientsPhpSdk\Client\Client;
use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    #[Test]
    public function itThrowsWhenIdIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Client::fromArray(['name' => 'Милана']);
    }

    #[Test]
    public function itThrowsWhenNameIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Client::fromArray(['id' => 1]);
    }

    #[Test]
    public function itAcceptsAPhoneSentAsANumber(): void
    {
        // YClients' own REST example shows `"phone": 79112345679` (an int), webhooks send a string.
        $client = Client::fromArray(['id' => 16, 'name' => 'Василий', 'phone' => 79112345679]);

        self::assertSame('79112345679', $client->phone);
    }

    #[Test]
    public function itParsesLastChangeDateInBothOffsetForms(): void
    {
        $compact = Client::fromArray(['id' => 1, 'name' => 'Милана', 'last_change_date' => '2026-10-22T20:01:19+0400']);
        $colon = Client::fromArray(['id' => 1, 'name' => 'Милана', 'last_change_date' => '2026-10-22T20:01:19+04:00']);

        self::assertSame('2026-10-22T20:01:19+04:00', $compact->lastChangeDate?->format('c'));
        self::assertEquals($compact->lastChangeDate, $colon->lastChangeDate);
    }

    #[Test]
    public function lastChangeDateIsNullWhenMissingEmptyOrUnparseable(): void
    {
        self::assertNull(Client::fromArray(['id' => 1, 'name' => 'Милана'])->lastChangeDate);
        self::assertNull(Client::fromArray(['id' => 1, 'name' => 'Милана', 'last_change_date' => ''])->lastChangeDate);
        self::assertNull(Client::fromArray(['id' => 1, 'name' => 'Милана', 'last_change_date' => 'вчера'])->lastChangeDate);
    }
}
