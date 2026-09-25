<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Staff;

use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;
use Dudev\YclientsPhpSdk\Staff\Staff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StaffTest extends TestCase
{
    #[Test]
    public function itThrowsWhenIdIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Staff::fromArray(['name' => 'Анна']);
    }

    #[Test]
    public function itThrowsWhenNameIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        Staff::fromArray(['id' => 1]);
    }
}
