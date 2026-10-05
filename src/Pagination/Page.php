<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Pagination;

use Dudev\YclientsPhpSdk\Http\RawResponse;

/**
 * One page of a paginated list endpoint (`records` — `page`/`count`; `clients/search` —
 * `page`/`page_size`). `total` is YClients' `meta.total_count` when it sent one, otherwise just
 * the number of items on this page. `Api::getAll()` doesn't use it to decide when to stop (an empty
 * page is the signal, see its docblock) — it's here for a caller that wants a real "page N of M" UI.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
    ) {
    }

    /**
     * @template TItem
     * @param list<TItem> $items
     * @return self<TItem>
     */
    public static function fromResponse(array $items, RawResponse $response, int $page): self
    {
        $total = $response->meta['total_count'] ?? null;

        return new self($items, is_numeric($total) ? (int) $total : count($items), $page);
    }
}
