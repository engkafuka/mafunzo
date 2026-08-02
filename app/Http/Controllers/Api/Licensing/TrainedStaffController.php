<?php

namespace App\Http\Controllers\Api\Licensing;

use App\Http\Controllers\Controller;
use App\Support\LicensingTrainedStaffQuery;
use App\Support\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainedStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LicensingTrainedStaffQuery::applyFilters(
            LicensingTrainedStaffQuery::baseQuery(),
            $request,
        );

        $paginator = $query
            ->paginate(min(100, max(1, $request->integer('per_page', PaginationHelper::PER_PAGE))))
            ->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn ($application) => LicensingTrainedStaffQuery::toApiRow($application))
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'eligible_positions' => config('licensing.eligible_positions'),
            ],
        ]);
    }
}
