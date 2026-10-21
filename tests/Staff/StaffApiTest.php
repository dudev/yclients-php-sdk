<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Staff;

use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Staff\StaffApi;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StaffApiTest extends TestCase
{
    #[Test]
    public function getUsesTheNewEndpointAndTakesTheFirstElementOfDataArray(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [[
                'id' => 1001539,
                'name' => 'Сотрудник 1',
                'company_id' => 176275,
                'specialization' => 'специалист',
                'position' => ['id' => 1, 'title' => 'Администратор'],
                'fired' => 0,
                'hidden' => 0,
            ]],
            'meta' => ['total_count' => 1],
        ], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient);

        $staff = (new StaffApi($transport))->get(176275, 1001539);

        self::assertStringEndsWith('/api/v1/company/176275/staff/1001539', (string) $httpClient->getLastRequest()->getUri());
        self::assertSame('Bearer partner, User user', $httpClient->getLastRequest()->getHeaderLine('Authorization'));
        self::assertSame(1001539, $staff->id);
        self::assertSame('Администратор', $staff->positionTitle);
        self::assertNull($staff->rating, 'the non-deprecated endpoint returns no rating');
        self::assertFalse($staff->hidden);
    }

    #[Test]
    public function getAlsoAcceptsASingleObjectAsTheLiveApiSendsIt(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => ['id' => 5702076, 'name' => 'Елизавета', 'rating' => 5, 'position' => ['id' => 41734, 'title' => 'Мастер']],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));

        $staff = (new StaffApi(self::transport($httpClient)))->get(622905, 5702076);

        self::assertSame(5702076, $staff->id);
        self::assertSame(5.0, $staff->rating);
        self::assertSame('Мастер', $staff->positionTitle);
    }

    #[Test]
    public function getThrowsWhenNothingComesBack(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));

        $this->expectException(YclientsException::class);

        (new StaffApi(self::transport($httpClient)))->get(176275, 1);
    }

    #[Test]
    public function listParsesEveryEmployee(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Анна'],
                ['id' => 2, 'name' => 'Ольга', 'rating' => 4.98],
            ],
            'meta' => ['total_count' => 2],
        ], JSON_THROW_ON_ERROR)));

        $staff = (new StaffApi(self::transport($httpClient)))->list(176275);

        self::assertCount(2, $staff);
        self::assertSame(4.98, $staff[1]->rating);
        self::assertStringEndsWith('/api/v1/company/176275/staff', (string) $httpClient->getLastRequest()->getUri());
    }

    private static function transport(Client $httpClient): Transport
    {
        $psr17 = new Psr17Factory();

        return new Transport(
            partnerToken: 'partner',
            httpClient: $httpClient,
            userToken: 'user',
            requestFactory: $psr17,
            streamFactory: $psr17,
            throttle: null,
        );
    }
}
