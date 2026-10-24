<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Client;

use Dudev\YclientsPhpSdk\Client\ClientApi;
use Dudev\YclientsPhpSdk\Client\ClientWriteRequest;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientApiTest extends TestCase
{
    #[Test]
    public function searchPostsToTheNewEndpointAndParsesEveryClient(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Иван', 'phone' => '+79001112233', 'sold_amount' => 1500.5, 'visits_count' => 3, 'first_visit_date' => '2024-05-01 10:00:00', 'last_visit_date' => ''],
                ['id' => 2, 'name' => 'Мария'],
            ],
            'meta' => ['total_count' => 908],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $page = (new ClientApi($transport))->search(622905, page: 2, pageSize: 50, fields: ['id', 'name', 'phone'], orderBy: 'name', orderByDirection: 'DESC');

        $request = $httpClient->getLastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertStringEndsWith('/api/v1/company/622905/clients/search', (string) $request->getUri());
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(2, $body['page']);
        self::assertSame(50, $body['page_size']);
        self::assertSame(['id', 'name', 'phone'], $body['fields']);
        self::assertSame('name', $body['order_by']);
        self::assertSame('DESC', $body['order_by_direction']);
        self::assertSame('AND', $body['operation']);
        self::assertArrayNotHasKey('filters', $body);

        self::assertCount(2, $page->items);
        self::assertSame(908, $page->total);
        self::assertSame('Иван', $page->items[0]->name);
        self::assertSame(1500.5, $page->items[0]->soldAmount);
        self::assertSame(3, $page->items[0]->visitsCount);
        self::assertSame('2024-05-01 10:00:00', $page->items[0]->firstVisitDate?->format('Y-m-d H:i:s'));
        self::assertNull($page->items[0]->lastVisitDate, 'an empty string means "never visited"');
        self::assertNull($page->items[1]->phone, 'fields that were not requested stay null, not a fake default');
        self::assertNull($page->items[1]->visitsCount);
    }

    #[Test]
    public function searchWithoutFieldsReturnsOnlyIds(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 7]], 'meta' => ['total_count' => 1]], JSON_THROW_ON_ERROR)));

        $page = (new ClientApi(self::transport($httpClient, userToken: 'user')))->search(622905);

        self::assertSame(7, $page->items[0]->id);
        self::assertNull($page->items[0]->name);
    }

    #[Test]
    public function getParsesTheFullCardIncludingTheConsents(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                'id' => 438311700,
                'name' => 'Милана',
                'categories' => [['id' => 6327742, 'title' => 'Телеграм']],
                'client_agreements' => [
                    'id' => 71636691,
                    'client_id' => 438311700,
                    'company_id' => 622905,
                    'is_newsletter_allowed' => true,
                    'is_personal_data_processing_allowed' => false,
                    'is_yc_newsletter_allowed' => true,
                ],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));

        $client = (new ClientApi(self::transport($httpClient, userToken: 'user')))->get(622905, 438311700);

        self::assertSame([['id' => 6327742, 'title' => 'Телеграм']], $client->categories);
        self::assertNotNull($client->clientAgreements);
        self::assertTrue($client->clientAgreements->isMarketingCommunicationsAllowed);
        self::assertFalse($client->clientAgreements->isPersonalDataProcessingAllowed);
        self::assertTrue($client->clientAgreements->isYcMarketingCommunicationsAllowed);
        self::assertNull($client->clientAgreements->isYcPersonalDataProcessingAllowed, 'a flag YClients did not send is unknown, not refused');
    }

    #[Test]
    public function getLeavesTheConsentsNullWhenTheClientHasNone(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => ['id' => 1, 'name' => 'Иван', 'client_agreements' => null], 'meta' => []], JSON_THROW_ON_ERROR)));

        $client = (new ClientApi(self::transport($httpClient, userToken: 'user')))->get(622905, 1);

        self::assertNull($client->clientAgreements);
    }

    #[Test]
    public function searchCapsThePageSizeAtWhatYclientsAllows(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        (new ClientApi($transport))->search(622905, pageSize: 5000);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $httpClient->getLastRequest()->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(200, $body['page_size']);
    }

    #[Test]
    public function getAllWalksPagesUntilAnEmptyOneAndKeepsEveryClient(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 1, 'name' => 'Иван'], ['id' => 2, 'name' => 'Мария']], 'meta' => []], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 3, 'name' => 'Пётр']], 'meta' => []], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $clients = iterator_to_array((new ClientApi($transport))->getAll(622905));

        self::assertCount(3, $clients);
        self::assertSame(3, $clients[2]->id);
        self::assertCount(3, $httpClient->getRequests());
    }

    #[Test]
    public function changedSinceAsksTheDeprecatedListWithANaiveDateTimeInTheApisZone(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                ['id' => 438311700, 'name' => 'Милана', 'last_change_date' => '2026-10-22T20:01:19+0400', 'spent' => 3680],
                ['id' => 245501024, 'name' => 'Алла', 'last_change_date' => ''],
            ],
            'meta' => ['page' => 1, 'total_count' => 2],
        ], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => ['page' => 2, 'total_count' => 2]], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $changed = iterator_to_array((new ClientApi($transport))->changedSince(622905, new \DateTimeImmutable('2026-10-22 15:00:00 UTC')));

        self::assertCount(2, $changed);
        self::assertSame(438311700, $changed[0]->id);
        self::assertSame('2026-10-22T20:01:19+04:00', $changed[0]->lastChangeDate?->format('c'));
        self::assertNull($changed[1]->lastChangeDate);
        $first = $httpClient->getRequests()[0];
        self::assertSame('GET', $first->getMethod());
        self::assertStringContainsString('/api/v1/clients/622905', (string) $first->getUri());
        parse_str($first->getUri()->getQuery(), $query);
        self::assertSame('2026-10-22T19:00:00', $query['changed_after'], '15:00 UTC is 19:00 in +04:00, sent with no offset');
        self::assertSame('200', $query['count']);
        self::assertArrayNotHasKey('changed_before', $query);
    }

    #[Test]
    public function createSendsBothCategoriesAndLabelsForTheSameIds(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => ['id' => 1, 'name' => 'Иван'], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $client = (new ClientApi($transport))->create(622905, new ClientWriteRequest(
            name: 'Иван',
            phone: '+79001112233',
            categoryIds: [7],
        ));

        self::assertSame(1, $client->id);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $httpClient->getLastRequest()->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('POST', $httpClient->getLastRequest()->getMethod());
        self::assertSame([7], $body['categories']);
        self::assertSame([7], $body['labels']);
    }

    #[Test]
    public function deleteSendsNoBody(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(204));
        $transport = self::transport($httpClient, userToken: 'user');

        (new ClientApi($transport))->delete(622905, 1);

        self::assertSame('DELETE', $httpClient->getLastRequest()->getMethod());
        self::assertStringContainsString('/api/v1/client/622905/1', (string) $httpClient->getLastRequest()->getUri());
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
