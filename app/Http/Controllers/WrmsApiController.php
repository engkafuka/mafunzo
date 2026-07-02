<?php

namespace App\Http\Controllers;

use App\Services\WrmsApiService;
use App\Support\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WrmsApiController extends Controller
{
    public function __construct(
        protected WrmsApiService $wrmsApi
    ) {}

    /**
     * Display WRMS API data (Warehouse Receipts, Warehouses, Operators). Super Admin only.
     */
    public function index(Request $request): View
    {
        set_time_limit(120);

        $limit = 500;

        $warehouseReceipts = $this->wrmsApi->getWarehouseReceipts(0, $limit);
        $errorReceipts = $this->wrmsApi->getLastError();

        $warehouses = $this->wrmsApi->getWarehouses(0, $limit);
        $errorWarehouses = $this->wrmsApi->getLastError();

        $operators = $this->wrmsApi->getOperators(0, $limit);
        $errorOperators = $this->wrmsApi->getLastError();

        $warehouseReceipts = PaginationHelper::paginateCollection(
            $this->normalizeRows(is_array($warehouseReceipts) ? $warehouseReceipts : []),
            $request,
            'receipts_page',
        );

        $warehouses = PaginationHelper::paginateCollection(
            $this->normalizeRows(is_array($warehouses) ? $warehouses : []),
            $request,
            'warehouses_page',
        );

        $operators = PaginationHelper::paginateCollection(
            $this->normalizeRows(is_array($operators) ? $operators : []),
            $request,
            'operators_page',
        );

        return view('wrms-api.index', [
            'warehouseReceipts' => $warehouseReceipts,
            'warehouses' => $warehouses,
            'operators' => $operators,
            'errorReceipts' => $errorReceipts,
            'errorWarehouses' => $errorWarehouses,
            'errorOperators' => $errorOperators,
        ]);
    }

    /**
     * Ensure each row is an array with string keys (normalize for display).
     */
    private function normalizeRows(array $list): array
    {
        $out = [];
        foreach ($list as $row) {
            $row = is_array($row) ? $row : (array) $row;
            $out[] = array_change_key_case($row, CASE_LOWER);
        }

        return $out;
    }
}
