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
    public function getAllWalksPagesUntilAnEmptyOneEvenIfYclientsCapsThePageSize(): void
    {
        // A server that caps `count` below what we ask for returns short pages that are not the last one.
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [self::minimalRecordData(1), self::minimalRecordData(2)], 'meta' => []], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [self::minimalRecordData(3)], 'meta' => []], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertCount(3, $records);
        self::assertSame(3, $records[2]->id);
        self::assertCount(3, $httpClient->getRequests());
    }

    #[Test]
    public function listReturnsTheRealTotalFromMeta(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [self::minimalRecordData(1)],
            'meta' => ['page' => 1, 'total_count' => 43520],
        ], JSON_THROW_ON_ERROR)));

        $page = (new RecordApi(self::transport($httpClient, userToken: 'user')))->list(622905);

        self::assertSame(43520, $page->total);
        self::assertNull($page->items[0]->clientAgreements, 'the live records list never carries client_agreements');
    }

    #[Test]
    public function getParsesTheClientAgreementsOnlyTheSingleRecordReturns(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => self::minimalRecordData(1) + ['client_agreements' => [
                'client_id' => 245501024,
                'company_id' => 622905,
                'is_newsletter_allowed' => false,
                'is_personal_data_processing_allowed' => true,
            ]],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));

        $record = (new RecordApi(self::transport($httpClient, userToken: 'user')))->get(622905, 1);

        self::assertNotNull($record->clientAgreements);
        self::assertSame(245501024, $record->clientAgreements->clientId);
        self::assertFalse($record->clientAgreements->isMarketingCommunicationsAllowed);
        self::assertTrue($record->clientAgreements->isPersonalDataProcessingAllowed);
    }

    #[Test]
    public function getAllReturnsNothingForAGenuinelyEmptyListAfterDoubtingTheFirstPage(): void
    {
        $httpClient = new Client();
        $httpClient->setDefaultResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $records = iterator_to_array((new RecordApi($transport))->getAll(622905));

        self::assertSame([], $records);
        self::assertCount(3, $httpClient->getRequests(), 'an empty first page is retried twice before it is believed');
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
