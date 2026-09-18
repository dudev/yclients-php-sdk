<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk;

use Dudev\YclientsPhpSdk\Auth\AuthApi;
use Dudev\YclientsPhpSdk\Client\ClientApi;
use Dudev\YclientsPhpSdk\Company\CompanyApi;
use Dudev\YclientsPhpSdk\RateLimit\Throttle;
use Dudev\YclientsPhpSdk\Record\RecordApi;
use Dudev\YclientsPhpSdk\Staff\StaffApi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Entry point — one instance per `partner_token` (+ optional `user_token`, see {@see self::auth()}).
 * Not a singleton/static facade on purpose (unlike some third-party YClients SDKs, see
 * `yclients-php-sdk-design.md`) — construct it through your own DI container like any other service.
 */
final class YclientsClient
{
    private readonly Transport $transport;
    private ?AuthApi $auth = null;
    private ?CompanyApi $companies = null;
    private ?StaffApi $staff = null;
    private ?RecordApi $records = null;
    private ?ClientApi $clients = null;

    /**
     * $userToken can be omitted and obtained later via `auth()->authenticate()`, or passed directly
     * if you already have a stored session for this user — either way it's mutable afterwards
     * (`setUserToken()`), since a real login session outlives the token you start with.
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $partnerToken,
        ?string $userToken = null,
        ?Throttle $throttle = new Throttle(),
    ) {
        $this->transport = new Transport($httpClient, $partnerToken, $userToken, $throttle);
    }

    public function getUserToken(): ?string
    {
        return $this->transport->userToken();
    }

    public function setUserToken(?string $userToken): void
    {
        $this->transport->setUserToken($userToken);
    }

    public function auth(): AuthApi
    {
        return $this->auth ??= new AuthApi($this->transport);
    }

    public function companies(): CompanyApi
    {
        return $this->companies ??= new CompanyApi($this->transport);
    }

    public function staff(): StaffApi
    {
        return $this->staff ??= new StaffApi($this->transport);
    }

    public function records(): RecordApi
    {
        return $this->records ??= new RecordApi($this->transport);
    }

    public function clients(): ClientApi
    {
        return $this->clients ??= new ClientApi($this->transport);
    }
}
