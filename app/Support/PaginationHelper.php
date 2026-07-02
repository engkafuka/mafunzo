<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaginationHelper
{
    public const PER_PAGE = 10;

    public static function paginateCollection(
        iterable $items,
        Request $request,
        string $pageName = 'page',
        int $perPage = self::PER_PAGE,
    ): LengthAwarePaginator {
        $collection = $items instanceof Collection ? $items : collect($items);
        $page = max(1, (int) $request->query($pageName, 1));

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ],
        );
    }
}
