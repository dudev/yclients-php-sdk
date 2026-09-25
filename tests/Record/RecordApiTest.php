<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Record;

use Dudev\YclientsPhpSdk\Record\RecordApi;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecordApiTest extends TestCase
{
    #[Test]
    public function listRequiresAUserToken(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $page = (new RecordApi($transport))->list(622905);

        self::assertSame([], $page->items);
        self::assertStringContainsString('User user', $httpClient->getLastRequest()->getHeaderLine('Authorization'));
    }

    #[Test]
    public function getAllStopsAsSoonAsAPageComesBackShortOfTheFullPageSize(): void
    {
        $fullPage = array_map(
            static fn (int $i): array => self::minimalRecordData($i),
            range(1, 200),
        );
        $shortPage = [self::minimalRecordData(201)];

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => $fullPage, 'meta' => []], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => $shortPage, 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertCount(201, $records);
        self::assertSame(201, $records[200]->id);
    }

    #[Test]
    public function getAllStopsImmediatelyOnAnEmptyFirstPage(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertSame([], $records);
    }

    #[Test]
    public function getFetchesASingleRecordByCompanyAndRecordId(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => self::minimalRecordData(42) + ['comment' => 'Заметка'],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $record = (new RecordApi($transport))->get(622905, 42);

        self::assertSame(42, $record->id);
        self::assertSame('Заметка', $record->comment);
    }

    /** @return array<string, mixed> */
    private static function minimalRecordData(int $id): array
    {
        return [
            'id' => $id,
            'company_id' => 1,
            'datetime' => '2026-09-16T19:30:00+05:00',
            'attendance' => 0,
            'seance_length' => 3600,
        ];
    }

    private static function transport(Client $httpClient, ?string $userToken = null): Transport
    {
        $psr17 = new Psr17Factory();

        return new Transport(
            partnerToken: 'partner',
            httpClient: $httpClient,
            userToken: $userToken,
            requestFactory: $psr17,
            streamFactory: $psr17,
            throttle: null,
        );
    }
}
