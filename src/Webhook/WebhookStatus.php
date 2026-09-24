<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Webhook;

/**
 * The `status` field of a YClients webhook envelope — confirmed against ~14k real production
 * payloads (all resources, several weeks of traffic): no value other than these three occurred.
 */
enum WebhookStatus: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
