<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk;

use Dudev\YclientsPhpSdk\Exception\YclientsApiException;
use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Exception\YclientsRateLimitException;
use Dudev\YclientsPhpSdk\Http\RawResponse;
use Dudev\YclientsPhpSdk\RateLimit\Throttle;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Everything every `*Api` class needs to actually call YClients: builds the request, throttles,
 * unwraps the `{success, data, meta}` envelope (or the differently-shaped `{errors, meta}` one seen
 * on 401/404 — YClients isn't consistent between the two, see `YclientsApiException`), and maps
 * failures to typed exceptions. Not part of the public API surface directly — reach it only through
 * `YclientsClient`'s `*Api` accessors.
 *
 * Talks PSR-18 (`ClientInterface`) + PSR-17 (`RequestFactoryInterface`/`StreamFactoryInterface`)
 * rather than a specific HTTP client library, since this SDK is meant to be usable from any PHP
 * app, not just Symfony ones. Any of the three can be passed explicitly (e.g. to reuse an app's
 * existing Guzzle/Symfony client); omitted ones fall back to whatever `php-http/discovery` finds
 * installed — the consuming app still needs *some* PSR-18 client + PSR-17 factories present.
 */
final class Transport
{
    private const BASE_URI = 'https://api.yclients.ru';

    private readonly string $partnerToken;
    private ?string $userToken;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        string $partnerToken,
        ?ClientInterface $httpClient = null,
        ?string $userToken = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        private readonly ?Throttle $throttle = new Throttle(),
    ) {
        $this->partnerToken = $partnerToken;
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->userToken = $userToken;
    }

    public function userToken(): ?string
    {
        return $this->userToken;
    }

    public function setUserToken(?string $userToken): void
    {
        $this->userToken = $userToken;
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<string, mixed>|null $json
     * @return array<int|string, mixed> the `data` member of the envelope, decoded as-is — a
     *         detail endpoint's `data` is a JSON object (string keys), a list endpoint's is a JSON
     *         array (0-indexed): callers know which shape to expect from their own endpoint.
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        bool $requireUserToken = false,
    ): array {
        return $this->requestWithStatus($method, $path, $query, $json, $requireUserToken)->data;
    }

    /**
     * Same as {@see self::request()}, but also hands back the HTTP status code — needed only where
     * a 2xx status itself carries meaning beyond "success" (so far: `POST /api/v1/auth`, 200 vs 201,
     * see `Auth\AuthApi`). Prefer `request()` everywhere else.
     *
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<string, mixed>|null $json
     */
    public function requestWithStatus(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        bool $requireUserToken = false,
    ): RawResponse {
        $this->throttle?->wait();

        $uri = self::BASE_URI . $path;
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Accept', 'application/vnd.yclients.v2+json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', $this->authorizationHeader($requireUserToken));

        if ($json !== null) {
            $request = $request->withBody($this->streamFactory->createStream(json_encode($json, JSON_THROW_ON_ERROR)));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new YclientsException(sprintf('HTTP transport error calling %s %s', $method, $path), previous: $e);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode === 429) {
            throw new YclientsRateLimitException($method, $path);
        }

        $rawBody = $response->getBody()->getContents();

        // 204/DELETE responses have no body — nothing to unwrap, no error to check beyond the status.
        if ($rawBody === '') {
            if ($statusCode >= 400) {
                throw new YclientsApiException($method, $path, $statusCode, null);
            }

            return new RawResponse($statusCode, []);
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new YclientsApiException($method, $path, $statusCode, 'Non-JSON response body');
        }
        /** @var array<string, mixed> $decoded */

        if ($statusCode >= 400 || ($decoded['success'] ?? true) === false) {
            throw new YclientsApiException($method, $path, $statusCode, self::extractErrorMessage($decoded));
        }

        /** @var mixed $data */
        $data = $decoded['data'] ?? null;

        return new RawResponse($statusCode, is_array($data) ? $data : []);
    }

    private function authorizationHeader(bool $requireUserToken): string
    {
        if (!$requireUserToken) {
            return sprintf('Bearer %s', $this->partnerToken);
        }

        if ($this->userToken === null) {
            throw new \LogicException(
                'This call requires a user token — none is set. Authenticate via '
                . 'YclientsClient::auth()->authenticate() first, or pass one to the constructor '
                . 'if you already have a stored session for this user.',
            );
        }

        return sprintf('Bearer %s, User %s', $this->partnerToken, $this->userToken);
    }

    /** @param array<string, mixed> $decoded */
    private static function extractErrorMessage(array $decoded): ?string
    {
        /** @var mixed $errors */
        $errors = $decoded['errors'] ?? null;
        if (is_array($errors) && is_string($errors['message'] ?? null)) {
            return $errors['message'];
        }

        /** @var mixed $meta */
        $meta = $decoded['meta'] ?? null;
        if (is_array($meta) && is_string($meta['message'] ?? null)) {
            return $meta['message'];
        }

        return null;
    }
}
