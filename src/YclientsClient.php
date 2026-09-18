<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk;

use Dudev\YclientsPhpSdk\Auth\AuthApi;
use Dudev\YclientsPhpSdk\Client\ClientApi;
use Dudev\YclientsPhpSdk\Company\CompanyApi;
use Dudev\YclientsPhpSdk\RateLimit\Throttle;
use Dudev\YclientsPhpSdk\Record\RecordApi;
use Dudev\YclientsPhpSdk\Staff\StaffApi;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

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
     *
     * $httpClient/$requestFactory/$streamFactory are all optional — pass your app's own PSR-18
     * client / PSR-17 factories to reuse them, or leave them out and `php-http/discovery` picks
     * whatever is installed. Either way, the app needs *some* PSR-18 + PSR-17 implementation
     * present (e.g. `guzzlehttp/guzzle` + `guzzlehttp/psr7`, or Symfony's `Psr18Client`).
     */
    public function __construct(
        string $partnerToken,
        ?ClientInterface $httpClient = null,
        ?string $userToken = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?Throttle $throttle = new Throttle(),
    ) {
        $this->transport = new Transport($partnerToken, $httpClient, $userToken, $requestFactory, $streamFactory, $throttle);
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
