<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Client;

use Dudev\YclientsPhpSdk\Client\Client;
use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    #[Test]
    public function itThrowsWhenIdIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Client::fromArray(['name' => 'Милана']);
    }

    #[Test]
    public function itThrowsWhenNameIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Client::fromArray(['id' => 1]);
    }
}
