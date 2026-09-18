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
    public function listParsesEveryClientInTheResponse(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Иван', 'phone' => '+79001112233', 'categories' => [['id' => 5, 'title' => 'VIP']]],
                ['id' => 2, 'name' => 'Мария', 'labels' => [['id' => 6, 'title' => 'Постоянный']]],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $page = (new ClientApi($transport))->list(622905);

        self::assertCount(2, $page->items);
        self::assertSame('Иван', $page->items[0]->name);
        self::assertSame([['id' => 5, 'title' => 'VIP']], $page->items[0]->categories);
        self::assertSame([['id' => 6, 'title' => 'Постоянный']], $page->items[1]->categories);
    }

    #[Test]
    public function getAllStopsAsSoonAsAPageComesBackShortOfTheFullPageSize(): void
    {
        $shortPage = [['id' => 1, 'name' => 'Иван']];
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => $shortPage, 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $clients = iterator_to_array((new ClientApi($transport))->getAll(622905));

        self::assertCount(1, $clients);
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
