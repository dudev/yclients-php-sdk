<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Pagination;

/**
 * One page of a `page`/`count`-paginated list endpoint (`records`, `clients` — the only two
 * confirmed to support it, see `docs/yclients-php-sdk-design.md` in the consuming project).
 * `total` is whatever YClients' `meta.total_count` said on this call; not used to decide when
 * `Api::getAll()` should stop (a short page is enough signal, see its docblock) — kept here only
 * for a caller that wants to render a real "page N of M" UI.
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
}
