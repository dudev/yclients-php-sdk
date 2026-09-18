<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests;

use Dudev\YclientsPhpSdk\Exception\YclientsApiException;
use Dudev\YclientsPhpSdk\Exception\YclientsRateLimitException;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransportTest extends TestCase
{
    #[Test]
    public function itUnwrapsTheSuccessEnvelope(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => ['id' => 42, 'title' => 'Салон'], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = $this->transport($httpClient);

        $data = $transport->request('GET', '/api/v1/companies/42');

        self::assertSame(['id' => 42, 'title' => 'Салон'], $data);
    }

    #[Test]
    public function itSendsThePartnerOnlyAuthorizationHeaderByDefault(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = $this->transport($httpClient);

        $transport->request('GET', '/api/v1/companies');

        self::assertSame('Bearer partner', $httpClient->getLastRequest()->getHeaderLine('Authorization'));
    }

    #[Test]
    public function itAddsTheUserTokenWhenRequired(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = $this->transport($httpClient, userToken: 'user');

        $transport->request('GET', '/api/v1/records/1', requireUserToken: true);

        self::assertSame('Bearer partner, User user', $httpClient->getLastRequest()->getHeaderLine('Authorization'));
    }

    #[Test]
    public function itThrowsALogicExceptionWhenAUserTokenIsRequiredButMissing(): void
    {
        $transport = $this->transport(new Client());

        $this->expectException(\LogicException::class);

        $transport->request('GET', '/api/v1/records/1', requireUserToken: true);
    }

    #[Test]
    public function itThrowsOnSuccessFalse(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => false, 'data' => null, 'meta' => ['message' => 'бред какой-то']], JSON_THROW_ON_ERROR)));
        $transport = $this->transport($httpClient);

        try {
            $transport->request('GET', '/api/v1/companies');
            self::fail('Expected a YclientsApiException');
        } catch (YclientsApiException $e) {
            self::assertSame('бред какой-то', $e->apiMessage);
        }
    }

    #[Test]
    public function itThrowsOnTheErrorsEnvelopeShapeSeenOn401And404(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(404, [], json_encode(['errors' => ['code' => 404, 'message' => 'Не найдено'], 'meta' => ['message' => 'Не найдено']], JSON_THROW_ON_ERROR)));
        $transport = $this->transport($httpClient);

        try {
            $transport->request('GET', '/api/v1/company/999999/');
            self::fail('Expected a YclientsApiException');
        } catch (YclientsApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('Не найдено', $e->apiMessage);
        }
    }

    #[Test]
    public function itThrowsATypedExceptionOn429(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(429));
        $transport = $this->transport($httpClient);

        $this->expectException(YclientsRateLimitException::class);

        $transport->request('GET', '/api/v1/companies');
    }

    #[Test]
    public function itReturnsAnEmptyArrayForANoContentResponse(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(204));
        $transport = $this->transport($httpClient);

        self::assertSame([], $transport->request('DELETE', '/api/v1/client/1/2', requireUserToken: false));
    }

    private function transport(Client $httpClient, ?string $userToken = null): Transport
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
