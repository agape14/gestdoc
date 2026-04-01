<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GridPagination
{
    public const DEFAULT_PER_PAGE = 50;

    public const ALLOWED = [10, 50, 100];

    /**
     * @return array{0: int|null, 1: string} [perPage o null si "todos", valor para filters al cliente]
     */
    public static function resolvePerPage(Request $request): array
    {
        $raw = $request->input('per_page');
        if ($raw === 'all' || $raw === 'todos') {
            return [null, 'all'];
        }
        if ($raw === null || $raw === '') {
            return [self::DEFAULT_PER_PAGE, (string) self::DEFAULT_PER_PAGE];
        }
        $n = (int) $raw;
        if (in_array($n, self::ALLOWED, true)) {
            return [$n, (string) $n];
        }

        return [self::DEFAULT_PER_PAGE, (string) self::DEFAULT_PER_PAGE];
    }

    public static function perPageFilterValue(Request $request): string
    {
        return self::resolvePerPage($request)[1];
    }

    /**
     * Acepta consultas Eloquent o relaciones (p. ej. $folder->documents() → HasMany).
     */
    public static function paginate(Builder|Relation $query, Request $request): LengthAwarePaginator
    {
        [$perPage] = self::resolvePerPage($request);

        if ($perPage === null) {
            $collection = $query->get();
            $total = $collection->count();
            $pageSize = max(1, $total);

            return new LengthAwarePaginator(
                $collection,
                $total,
                $pageSize,
                1,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
