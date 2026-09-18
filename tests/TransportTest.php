<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests;

use Dudev\YclientsPhpSdk\Exception\YclientsApiException;
use Dudev\YclientsPhpSdk\Exception\YclientsRateLimitException;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TransportTest extends TestCase
{
    #[Test]
    public function itUnwrapsTheSuccessEnvelope(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['success' => true, 'data' => ['id' => 42, 'title' => 'Салон'], 'meta' => []], JSON_THROW_ON_ERROR),
        ));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $data = $transport->request('GET', '/api/v1/companies/42');

        self::assertSame(['id' => 42, 'title' => 'Салон'], $data);
    }

    #[Test]
    public function itSendsThePartnerOnlyAuthorizationHeaderByDefault(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('Bearer partner', $this->findHeader($options, 'Authorization'));

            return new MockResponse(json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR));
        });
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $transport->request('GET', '/api/v1/companies');
    }

    #[Test]
    public function itAddsTheUserTokenWhenRequired(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('Bearer partner, User user', $this->findHeader($options, 'Authorization'));

            return new MockResponse(json_encode(['success' => true, 'data' => [], 'meta' => []], JSON_THROW_ON_ERROR));
        });
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $transport->request('GET', '/api/v1/records/1', requireUserToken: true);
    }

    #[Test]
    public function itThrowsALogicExceptionWhenAUserTokenIsRequiredButMissing(): void
    {
        $transport = new Transport(new MockHttpClient(), partnerToken: 'partner', throttle: null);

        $this->expectException(\LogicException::class);

        $transport->request('GET', '/api/v1/records/1', requireUserToken: true);
    }

    #[Test]
    public function itThrowsOnSuccessFalse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['success' => false, 'data' => null, 'meta' => ['message' => 'бред какой-то']], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

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
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['errors' => ['code' => 404, 'message' => 'Не найдено'], 'meta' => ['message' => 'Не найдено']], JSON_THROW_ON_ERROR),
            ['http_code' => 404],
        ));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

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
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 429]));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $this->expectException(YclientsRateLimitException::class);

        $transport->request('GET', '/api/v1/companies');
    }

    #[Test]
    public function itReturnsAnEmptyArrayForANoContentResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 204]));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        self::assertSame([], $transport->request('DELETE', '/api/v1/client/1/2', requireUserToken: false));
    }

    /** @param array<string, mixed> $options */
    private function findHeader(array $options, string $name): ?string
    {
        /** @var mixed $normalizedHeaders */
        $normalizedHeaders = $options['normalized_headers'] ?? [];
        /** @var list<string> $headers */
        $headers = is_array($normalizedHeaders) ? ($normalizedHeaders[strtolower($name)] ?? []) : [];
        foreach ($headers as $header) {
            if (str_starts_with($header, $name . ':')) {
                return trim(substr($header, strlen($name) + 1));
            }
        }

        return null;
    }
}
