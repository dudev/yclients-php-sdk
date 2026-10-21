<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Pagination;

use Dudev\YclientsPhpSdk\Exception\YclientsException;

/**
 * The loop behind every `Api::getAll()`. A page that comes back empty is not trusted on its own:
 * the live API was seen (2026-10-21) answering HTTP 200 with an empty `data` or `total_count: 0` for a
 * query that has thousands of rows, once, and fine on the next call. So an empty page is retried — when
 * `total_count` says there should be more, or when it is the very first page — and only then believed.
 * If it stays empty while `total_count` promises more, the walk fails loudly instead of returning a
 * silently truncated list.
 */
final class PageWalker
{
    private const EMPTY_PAGE_RETRIES = 2;
    private const RETRY_DELAY_MICROSECONDS = 300_000;

    /**
     * @template T
     * @param callable(int): Page<T> $fetch fetches the 1-based page number
     * @return \Generator<int, T>
     */
    public static function walk(callable $fetch): \Generator
    {
        $page = 1;
        $collected = 0;
        while (true) {
            $result = $fetch($page);

            for ($retry = 0; $result->items === [] && self::suspicious($collected, $result) && $retry < self::EMPTY_PAGE_RETRIES; ++$retry) {
                usleep(self::RETRY_DELAY_MICROSECONDS);
                $result = $fetch($page);
            }

            if ($result->items === []) {
                if ($result->total > $collected) {
                    throw new YclientsException(sprintf(
                        'Page %d came back empty after %d retries although total_count is %d and only %d were read — refusing to return a truncated list',
                        $page,
                        self::EMPTY_PAGE_RETRIES,
                        $result->total,
                        $collected,
                    ));
                }

                return;
            }

            // Not `yield from` — every page's items are 0-indexed, so `yield from` would repeat keys
            // across pages and silently drop items for a caller using `iterator_to_array()`.
            foreach ($result->items as $item) {
                yield $item;
            }

            $collected += count($result->items);
            ++$page;
        }
    }

    /** @param Page<mixed> $result */
    private static function suspicious(int $collected, Page $result): bool
    {
        return $collected === 0 || $collected < $result->total;
    }
}
