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
                ['id' => 1, 'name' => 'Иван', 'phone' => '+79001112233', 'categories' => [['id' => 5, 'title' => 'VIP']]],
                ['id' => 2, 'name' => 'Мария', 'labels' => [['id' => 6, 'title' => 'Постоянный']]],
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
        self::assertSame([['id' => 5, 'title' => 'VIP']], $page->items[0]->categories);
        self::assertSame([['id' => 6, 'title' => 'Постоянный']], $page->items[1]->categories);
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
