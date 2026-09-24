# yclients-php-sdk

PHP SDK for the [YClients REST API](https://developers.yclients.com/ru/) — companies, staff,
clients, records, auth — plus parsing for YClients' webhook payloads (`Webhook\WebhookEvent`;
undocumented by YClients itself, confirmed against real production data). Receiving the HTTP POST
is your app's job — the SDK only decodes the body you hand it.

Framework-agnostic: talks PSR-18 (`psr/http-client`) + PSR-17 (`psr/http-factory`) rather than a
specific HTTP client. Your app needs an implementation of both installed (e.g. `guzzlehttp/guzzle`
+ `guzzlehttp/psr7`, or `symfony/http-client`'s `Psr18Client`) — `php-http/discovery` finds it
automatically, or pass your own client/factories to `YclientsClient`'s constructor explicitly.

## Installation

```bash
composer require dudev/yclients-php-sdk
```

## Development

```bash
composer install
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
