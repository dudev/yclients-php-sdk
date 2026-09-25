<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Record;

use Dudev\YclientsPhpSdk\Exception\YclientsMalformedPayloadException;
use Dudev\YclientsPhpSdk\Record\RecordService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecordServiceTest extends TestCase
{
    #[Test]
    public function itThrowsWhenIdIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        RecordService::fromArray(['title' => 'Массаж']);
    }

    #[Test]
    public function itThrowsWhenTitleIsMissing(): void
    {
        $this->expectException(YclientsMalformedPayloadException::class);

        RecordService::fromArray(['id' => 1]);
    }
}
