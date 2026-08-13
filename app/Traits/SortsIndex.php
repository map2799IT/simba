<?php

namespace App\Traits;

trait SortsIndex
{
    protected function indexSortParams(array $whitelist): array
    {
        $sort = request()->query('sort');
        $validSort = is_string($sort) && in_array($sort, $whitelist, true) ? $sort : null;

        $direction = strtolower((string) request()->query('direction', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $perPage = (int) request()->query('per_page', 25);
        if (! in_array($perPage, [10, 25, 100], true)) {
            $perPage = 25;
        }

        return [$validSort, $direction, $perPage];
    }

    protected function applyIndexOrder(object $query, ?string $sort, string $direction): void
    {
        if ($sort !== null) {
            $query->orderBy($sort, $direction);
        }
    }
}