<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Pagination;

use Dudev\YclientsPhpSdk\Exception\YclientsException;
use Dudev\YclientsPhpSdk\Pagination\Page;
use Dudev\YclientsPhpSdk\Pagination\PageWalker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PageWalkerTest extends TestCase
{
    /**
     * @param list<Page<int>> $pages served in order, one per call
     * @return array{callable(int): Page<int>, \ArrayObject<int, int>}
     */
    private static function source(array $pages): array
    {
        /** @var \ArrayObject<int, int> $calls */
        $calls = new \ArrayObject();
        $fetch = static function (int $page) use (&$pages, $calls): Page {
            $calls[] = $page;

            return array_shift($pages) ?? new Page([], 0, $page);
        };

        return [$fetch, $calls];
    }

    #[Test]
    public function itWalksPagesInOrderAndKeepsEveryItem(): void
    {
        [$fetch, $calls] = self::source([new Page([1, 2], 3, 1), new Page([3], 3, 2), new Page([], 3, 3)]);

        $items = iterator_to_array(PageWalker::walk($fetch));

        self::assertSame([1, 2, 3], $items);
        self::assertSame([1, 2, 3], $calls->getArrayCopy());
    }

    #[Test]
    public function itRetriesAnEmptyPageThatTotalCountSaysShouldNotBeEmpty(): void
    {
        [$fetch, $calls] = self::source([new Page([1, 2], 4, 1), new Page([], 4, 2), new Page([3, 4], 4, 2), new Page([], 4, 3)]);

        $items = iterator_to_array(PageWalker::walk($fetch));

        self::assertSame([1, 2, 3, 4], $items);
        self::assertSame([1, 2, 2, 3], $calls->getArrayCopy(), 'page 2 asked twice, the glitch was not believed');
    }

    #[Test]
    public function itFailsLoudlyRatherThanReturnAListTruncatedByAPersistentlyEmptyPage(): void
    {
        [$fetch] = self::source([new Page([1, 2], 10, 1), new Page([], 10, 2), new Page([], 10, 2), new Page([], 10, 2)]);

        $this->expectException(YclientsException::class);

        iterator_to_array(PageWalker::walk($fetch));
    }

    #[Test]
    public function itDoubtsAnEmptyFirstPageEvenWhenTotalCountIsZero(): void
    {
        // Seen live: HTTP 200, `total_count: 0` and no items on a query with 13k rows, once.
        [$fetch, $calls] = self::source([new Page([], 0, 1), new Page([7, 8], 2, 1), new Page([], 2, 2)]);

        $items = iterator_to_array(PageWalker::walk($fetch));

        self::assertSame([7, 8], $items);
        self::assertSame([1, 1, 2], $calls->getArrayCopy());
    }

    #[Test]
    public function aGenuinelyEmptyListEndsAfterTheRetries(): void
    {
        [$fetch, $calls] = self::source([]);

        self::assertSame([], iterator_to_array(PageWalker::walk($fetch)));
        self::assertCount(3, $calls, 'the first call plus two retries');
    }
}
