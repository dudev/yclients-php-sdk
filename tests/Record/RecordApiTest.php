<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Record;

use Dudev\YclientsPhpSdk\Record\RecordApi;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RecordApiTest extends TestCase
{
    #[Test]
    public function listRequiresAUserToken(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertStringContainsString('User user', (string) ($options['normalized_headers']['authorization'][0] ?? ''));

            return new MockResponse(json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR));
        });
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $page = (new RecordApi($transport))->list(622905);

        self::assertSame([], $page->items);
    }

    #[Test]
    public function getAllStopsAsSoonAsAPageComesBackShortOfTheFullPageSize(): void
    {
        $fullPage = array_map(
            static fn (int $i): array => ['id' => $i, 'company_id' => 1],
            range(1, 200),
        );
        $shortPage = [['id' => 201, 'company_id' => 1]];

        $responses = [
            new MockResponse(json_encode(['success' => true, 'data' => $fullPage, 'meta' => []], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['success' => true, 'data' => $shortPage, 'meta' => []], JSON_THROW_ON_ERROR)),
        ];
        $httpClient = new MockHttpClient($responses);
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertCount(201, $records);
        self::assertSame(201, $records[200]->id);
    }

    #[Test]
    public function getAllStopsImmediatelyOnAnEmptyFirstPage(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertSame([], $records);
    }

    #[Test]
    public function getFetchesASingleRecordByCompanyAndRecordId(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'success' => true,
            'data' => ['id' => 42, 'company_id' => 622905, 'comment' => 'Заметка'],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $record = (new RecordApi($transport))->get(622905, 42);

        self::assertSame(42, $record->id);
        self::assertSame('Заметка', $record->comment);
    }
}
