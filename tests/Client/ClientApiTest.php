<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Client;

use Dudev\YclientsPhpSdk\Client\ClientApi;
use Dudev\YclientsPhpSdk\Client\ClientWriteRequest;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ClientApiTest extends TestCase
{
    #[Test]
    public function listParsesEveryClientInTheResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Иван', 'phone' => '+79001112233', 'categories' => [['id' => 5, 'title' => 'VIP']]],
                ['id' => 2, 'name' => 'Мария', 'labels' => [['id' => 6, 'title' => 'Постоянный']]],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

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
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['success' => true, 'data' => $shortPage, 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $clients = iterator_to_array((new ClientApi($transport))->getAll(622905));

        self::assertCount(1, $clients);
    }

    #[Test]
    public function createSendsBothCategoriesAndLabelsForTheSameIds(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            /** @var array<string, mixed> $body */
            $body = json_decode((string) $options['body'], true, flags: JSON_THROW_ON_ERROR);
            self::assertSame([7], $body['categories']);
            self::assertSame([7], $body['labels']);

            return new MockResponse(json_encode(['success' => true, 'data' => ['id' => 1, 'name' => 'Иван'], 'meta' => []], JSON_THROW_ON_ERROR));
        });
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $client = (new ClientApi($transport))->create(622905, new ClientWriteRequest(
            name: 'Иван',
            phone: '+79001112233',
            categoryIds: [7],
        ));

        self::assertSame(1, $client->id);
    }

    #[Test]
    public function deleteSendsNoBody(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url) {
            self::assertSame('DELETE', $method);
            self::assertStringContainsString('/api/v1/client/622905/1', $url);

            return new MockResponse('', ['http_code' => 204]);
        });
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        (new ClientApi($transport))->delete(622905, 1);

        $this->addToAssertionCount(1);
    }
}
