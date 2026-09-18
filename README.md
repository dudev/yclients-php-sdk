# yclients-php-sdk

PHP SDK for the [YClients REST API](https://developers.yclients.com/ru/) — companies, staff,
clients, records, auth. See [`docs/scope.md`](docs/scope.md) for what's planned and why.

Status: infrastructure only, no client code yet.

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
