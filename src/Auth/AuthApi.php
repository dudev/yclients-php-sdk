<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Auth;

use Dudev\YclientsPhpSdk\Exception\YclientsApiException;
use Dudev\YclientsPhpSdk\Transport;

final readonly class AuthApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * `POST /api/v1/auth` — only `partner_token` needed (no user token yet, that's the point).
     * On success (HTTP 201), the returned `user_token` is applied to the `Transport` this client
     * shares automatically — no separate `setUserToken()` call needed for the common case.
     *
     * YClients' own spec shows the 201 body as a bare object (`{id, user_token, name, ...}`, no
     * `{success, data, meta}` envelope) while the 200 (2FA) one does use the envelope — so
     * `user_token` is looked up both at the top level and inside `data`, whichever turns out to
     * be real.
     */
    public function authenticate(string $login, string $password): AuthResult
    {
        $response = $this->transport->requestWithStatus('POST', '/api/v1/auth', json: [
            'login' => $login,
            'password' => $password,
        ]);

        if ($response->statusCode === 201) {
            $userToken = $response->body['user_token'] ?? $response->data['user_token'] ?? null;
            if (!is_string($userToken)) {
                throw new YclientsApiException('POST', '/api/v1/auth', $response->statusCode, 'Response had no user_token');
            }

            $this->transport->setUserToken($userToken);

            return AuthResult::authenticated($userToken);
        }

        $uuid = $response->data['uuid'] ?? $response->body['uuid'] ?? null;
        if (!is_string($uuid)) {
            throw new YclientsApiException('POST', '/api/v1/auth', $response->statusCode, 'Response had no uuid for 2FA flow');
        }

        return AuthResult::twoFactorRequired($uuid);
    }
}
