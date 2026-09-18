<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Auth;

/**
 * `POST /api/v1/auth` answers one of two shapes depending on whether the account has 2FA enabled
 * (HTTP 200, `requiresTwoFactor: true`, no token yet) or not (HTTP 201, `requiresTwoFactor: false`,
 * `userToken` set — and already applied to the `YclientsClient` that made the call, see
 * `AuthApi::authenticate()`).
 *
 * Completing a 2FA login (submitting the confirmation code sent via `transport` in the 200
 * response) isn't implemented yet — the confirmation endpoint's shape wasn't confirmed against the
 * real API while researching this SDK's scope, only that the 200 response exists at all. Don't
 * guess at it; confirm against `developers.yclients.com` (or a real account) before adding it.
 */
final readonly class AuthResult
{
    private function __construct(
        public bool $requiresTwoFactor,
        public ?string $userToken,
        public ?string $twoFactorUuid,
    ) {
    }

    public static function authenticated(string $userToken): self
    {
        return new self(requiresTwoFactor: false, userToken: $userToken, twoFactorUuid: null);
    }

    public static function twoFactorRequired(string $uuid): self
    {
        return new self(requiresTwoFactor: true, userToken: null, twoFactorUuid: $uuid);
    }
}
