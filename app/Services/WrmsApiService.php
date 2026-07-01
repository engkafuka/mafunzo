<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WrmsApiService
{
    protected string $baseUrl;

    protected string $username;

    protected string $token;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('wrms.base_url'), '/');
        $this->username = config('wrms.username');
        $this->token = config('wrms.token');
        $this->timeout = config('wrms.timeout', 30);
    }

    /** Last error message for debugging (e.g. 401, connection failed). */
    protected ?string $lastError = null;

    /**
     * Get last API error message (for display when config fails).
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * PDF format: response is a raw JSON array [ {...}, {...} ]. Accept if root is array and key 0 exists.
     */
    protected function isPdfStyleList($arr): bool
    {
        if (! is_array($arr)) {
            return false;
        }
        if ($arr === []) {
            return true;
        }
        if (! array_key_exists(0, $arr) && ! array_key_exists('0', $arr)) {
            return false;
        }
        $first = $arr[0] ?? $arr['0'] ?? null;
        return is_array($first) || is_object($first);
    }

    /**
     * Check if array is a list of records (numeric or string-numeric keys, values are arrays/objects).
     */
    protected function isListOfRecords($arr): bool
    {
        if (! is_array($arr)) {
            return false;
        }
        if ($arr === []) {
            return true;
        }
        $first = reset($arr);
        if (! is_array($first) && ! is_object($first)) {
            return false;
        }
        if (function_exists('array_is_list') && array_is_list($arr)) {
            return true;
        }
        $n = count($arr);
        for ($i = 0; $i < $n; $i++) {
            if (! array_key_exists($i, $arr) && ! array_key_exists((string) $i, $arr)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Extract list of records from a value (may be array or object).
     */
    protected function extractList($value): ?array
    {
        if ($value === null) {
            return null;
        }
        $arr = is_array($value) ? $value : (array) $value;
        return $this->isListOfRecords($arr) ? $arr : null;
    }

    /**
     * Normalize API response. PDF: raw array [ {...}, {...} ]. Also handle wrapped in result/data/records/items.
     */
    protected function normalizeResponse(mixed $json): ?array
    {
        if ($json === null) {
            return null;
        }
        $arr = is_array($json) ? $json : (array) $json;

        if ($this->isPdfStyleList($arr)) {
            return $this->ensureArrayOfArrays($arr);
        }
        if ($this->isListOfRecords($arr)) {
            return $this->ensureArrayOfArrays($arr);
        }
        foreach (['result', 'data', 'records', 'items', 'results', 'output'] as $key) {
            if (! array_key_exists($key, $arr)) {
                continue;
            }
            $val = $arr[$key];
            $list = $this->extractList($val);
            if ($list !== null) {
                return $this->ensureArrayOfArrays($list);
            }
            if (is_array($val) || is_object($val)) {
                $inner = (array) $val;
                foreach (['items', 'records', 'data'] as $innerKey) {
                    if (array_key_exists($innerKey, $inner)) {
                        $list = $this->extractList($inner[$innerKey]);
                        if ($list !== null) {
                            return $this->ensureArrayOfArrays($list);
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Convert list of records to array of arrays (for objects from json_decode).
     */
    protected function ensureArrayOfArrays(array $list): array
    {
        $out = [];
        foreach ($list as $row) {
            $out[] = is_array($row) ? $row : (array) $row;
        }
        return $out;
    }

    /**
     * Call WRMS search_read endpoint. Method is PATCH, Basic Auth (username, token).
     *
     * @param  string  $path  e.g. /api/v1/warehouse-receipt/stock.picking/call/search_read
     * @param  array  $domain  Filter criteria
     * @param  array  $fields  Fields to return
     * @param  int  $offset
     * @param  int  $limit
     * @param  string  $sort  e.g. "create_date DESC"
     * @return array|null List of records, or null on failure
     */
    public function searchRead(string $path, array $domain, array $fields, int $offset = 0, int $limit = 100, string $sort = 'create_date DESC'): ?array
    {
        $this->lastError = null;
        $url = $this->baseUrl . $path;
        $args = [$domain, $fields, $offset, $limit, $sort];
        $body = config('wrms.request_format') === 'jsonrpc'
            ? [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => $args,
                'id' => 1,
            ]
            : ['args' => $args];

        try {
            $client = Http::withBasicAuth($this->username, $this->token)
                ->timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                ]);

            if (config('wrms.verify_ssl') === false) {
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->patch($url, $body);

            if ($response->successful()) {
                $body = $response->body();
                $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
                $decoded = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->lastError = 'Invalid JSON: ' . json_last_error_msg();
                    Log::warning('WRMS API invalid JSON', ['path' => $path, 'body_start' => substr($body, 0, 200)]);
                    return [];
                }
                if (is_array($decoded) && array_key_exists('error', $decoded)) {
                    $err = $decoded['error'];
                    $msg = is_array($err) ? ($err['message'] ?? 'Unknown error') : (string) $err;
                    $name = is_array($err) && isset($err['data']['name']) ? $err['data']['name'] : null;
                    $this->lastError = $name ? "Odoo Server Error: {$name} ({$msg})" : "Odoo Server Error: {$msg}";
                    Log::warning('WRMS API Odoo error', ['path' => $path, 'error' => $err]);
                    return [];
                }
                $list = $this->normalizeResponse($decoded);
                if ($list !== null) {
                    return $list;
                }
                $this->lastError = 'Unexpected response format';
                $decodedArr = is_array($decoded) ? $decoded : (array) $decoded;
                Log::warning('WRMS API unexpected format', [
                    'path' => $path,
                    'top_level_keys' => array_keys($decodedArr),
                    'body_preview' => substr($body, 0, 500),
                ]);
                return [];
            }

            $this->lastError = $response->status() . ' ' . $response->reason();
            Log::warning('WRMS API error', ['path' => $path, 'status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->lastError = 'Connection failed: ' . $e->getMessage();
            Log::error('WRMS API connection', ['path' => $path, 'message' => $e->getMessage()]);
            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('WRMS API exception', ['path' => $path, 'message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Warehouse Receipts (stock.picking, state=done, picking_type_code=incoming).
     */
    public function getWarehouseReceipts(int $offset = 0, int $limit = 100): ?array
    {
        $path = '/api/v1/warehouse-receipt/stock.picking/call/search_read';
        $domain = [
            ['state', '=', 'done'],
            ['picking_type_code', '=', 'incoming'],
        ];
        $fields = [
            'id', 'name', 'receipt_no', 'state', 'scheduled_date',
            'partner_id', 'depositor', 'product_id', 'warehouse_id',
            'picking_type_id', 'origin', 'tally_sheet_no', 'wnote_no',
            'bag_count', 'qty_done', 'grade', 'pdn', 'create_uid', 'create_date',
        ];
        return $this->searchRead($path, $domain, $fields, $offset, $limit);
    }

    /**
     * Get Warehouses (stock.warehouse, active=true).
     */
    public function getWarehouses(int $offset = 0, int $limit = 100): ?array
    {
        $path = '/api/v1/warehouse/stock.warehouse/call/search_read';
        $domain = [['active', '=', true]];
        $fields = [
            'id', 'name', 'code', 'grade', 'total_capacity', 'physical_address',
            'length', 'width', 'height', 'longitude', 'latitude',
            'state_id', 'district_id', 'partner_id', 'create_date',
        ];
        return $this->searchRead($path, $domain, $fields, $offset, $limit);
    }

    /**
     * Get Operators (res.partner, is_operator=true, active=true).
     */
    public function getOperators(int $offset = 0, int $limit = 100): ?array
    {
        $path = '/api/v1/operator/res.partner/call/search_read';
        $domain = [
            ['is_operator', '=', true],
            ['active', '=', true],
        ];
        $fields = [
            'id', 'name', 'email', 'phone', 'street', 'street2', 'city',
            'registration_number', 'is_operator', 'is_customer', 'is_amcos',
            'state_id', 'district_id', 'country_id', 'create_date',
        ];
        return $this->searchRead($path, $domain, $fields, $offset, $limit);
    }
}
