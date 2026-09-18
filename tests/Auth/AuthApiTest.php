<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Auth;

use Dudev\YclientsPhpSdk\Auth\AuthApi;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AuthApiTest extends TestCase
{
    #[Test]
    public function itAppliesTheUserTokenToTheSharedTransportOnASuccessfulLogin(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['success' => true, 'data' => ['user_token' => 'fresh-token'], 'meta' => []], JSON_THROW_ON_ERROR),
            ['http_code' => 201],
        ));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $result = (new AuthApi($transport))->authenticate('login', 'password');

        self::assertFalse($result->requiresTwoFactor);
        self::assertSame('fresh-token', $result->userToken);
        self::assertSame('fresh-token', $transport->userToken());
    }

    #[Test]
    public function itReturnsATwoFactorResultWithoutTouchingTheTransportsUserToken(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['success' => true, 'data' => ['uuid' => 'confirmation-uuid'], 'meta' => []], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $result = (new AuthApi($transport))->authenticate('login', 'password');

        self::assertTrue($result->requiresTwoFactor);
        self::assertSame('confirmation-uuid', $result->twoFactorUuid);
        self::assertNull($transport->userToken());
    }
}
