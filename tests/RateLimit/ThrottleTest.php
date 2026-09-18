<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\RateLimit;

use Dudev\YclientsPhpSdk\RateLimit\Throttle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThrottleTest extends TestCase
{
    #[Test]
    public function disabledThrottleNeverSleeps(): void
    {
        $throttle = new Throttle(requestsPerSecond: 0);

        $start = microtime(true);
        for ($i = 0; $i < 5; ++$i) {
            $throttle->wait();
        }
        $elapsed = microtime(true) - $start;

        self::assertLessThan(0.05, $elapsed, 'A disabled throttle should add no measurable delay');
    }

    #[Test]
    public function itSleepsToStayUnderTheConfiguredRate(): void
    {
        // 20 req/sec = 50ms apart; three calls back-to-back should take at least ~2 intervals.
        $throttle = new Throttle(requestsPerSecond: 20);

        $start = microtime(true);
        $throttle->wait();
        $throttle->wait();
        $throttle->wait();
        $elapsed = microtime(true) - $start;

        self::assertGreaterThanOrEqual(0.08, $elapsed);
    }
}
