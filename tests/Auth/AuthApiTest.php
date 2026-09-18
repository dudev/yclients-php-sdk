<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Auth;

use Dudev\YclientsPhpSdk\Auth\AuthApi;
use Dudev\YclientsPhpSdk\Transport;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AuthApiTest extends TestCase
{
    #[Test]
    public function itAppliesTheUserTokenToTheSharedTransportOnASuccessfulLogin(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(201, [], json_encode(['success' => true, 'data' => ['user_token' => 'fresh-token'], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient);

        $result = (new AuthApi($transport))->authenticate('login', 'password');

        self::assertFalse($result->requiresTwoFactor);
        self::assertSame('fresh-token', $result->userToken);
        self::assertSame('fresh-token', $transport->userToken());
    }

    #[Test]
    public function itReturnsATwoFactorResultWithoutTouchingTheTransportsUserToken(): void
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], json_encode(['success' => true, 'data' => ['uuid' => 'confirmation-uuid'], 'meta' => []], JSON_THROW_ON_ERROR)));
        $transport = self::transport($httpClient);

        $result = (new AuthApi($transport))->authenticate('login', 'password');

        self::assertTrue($result->requiresTwoFactor);
        self::assertSame('confirmation-uuid', $result->twoFactorUuid);
        self::assertNull($transport->userToken());
    }

    private static function transport(Client $httpClient): Transport
    {
        $psr17 = new Psr17Factory();

        return new Transport(
            partnerToken: 'partner',
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
            throttle: null,
        );
    }
}
