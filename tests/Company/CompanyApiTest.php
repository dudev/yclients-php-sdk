<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Company;

use Dudev\YclientsPhpSdk\Company\CompanyApi;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompanyApiTest extends TestCase
{
    #[Test]
    public function listParsesEveryCompanyInTheResponse(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                // As in YClients' own example: ids and coordinates arrive as strings.
                ['id' => '1050', 'title' => 'Ля Визаж', 'city' => 'Москва', 'phone' => '+7 495 509-24-46', 'coordinate_lat' => '55.735662'],
                ['id' => '1051', 'title' => 'Ля Визаж 2', 'city' => 'Москва', 'phone' => ''],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient);

        $companies = (new CompanyApi($transport))->list(groupId: 83, active: true);

        self::assertCount(2, $companies);
        self::assertSame(1050, $companies[0]->id);
        self::assertSame('+7 495 509-24-46', $companies[0]->phone);
        self::assertSame(55.735662, $companies[0]->coordinateLat);
        self::assertNull($companies[1]->phone);
        parse_str($httpClient->getLastRequest()->getUri()->getQuery(), $query);
        self::assertSame(['group_id' => '83', 'active' => '1'], $query);
    }

    #[Test]
    public function getRequiresAUserToken(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => ['id' => 1, 'title' => 'Мамина-Сибиряка'],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient, userToken: 'user');

        $company = (new CompanyApi($transport))->get(1);

        self::assertSame('Мамина-Сибиряка', $company->title);
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
