<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ArrayPaginator
{
    public static function paginate(array|Collection $items, Request $request, string $pageName = 'page', int $perPage = 15): LengthAwarePaginator
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        $page = max(1, (int) $request->input($pageName, 1));

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ]
        );
    }
}
