<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Staff;

use Dudev\YclientsPhpSdk\Staff\StaffApi;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class StaffApiTest extends TestCase
{
    #[Test]
    public function listParsesPositionAndRating(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'success' => true,
            'data' => [
                [
                    'id' => 5963751,
                    'name' => 'Анна Кирменская',
                    'specialization' => 'Мастер',
                    'position' => ['id' => 1, 'title' => 'Массажист'],
                    'rating' => 4.92,
                    'hidden' => 0,
                    'fired' => 0,
                ],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $staff = (new StaffApi($transport))->list(622905);

        self::assertCount(1, $staff);
        self::assertSame(5963751, $staff[0]->id);
        self::assertSame('Массажист', $staff[0]->positionTitle);
        self::assertSame(4.92, $staff[0]->rating);
        self::assertFalse($staff[0]->hidden);
    }
}
