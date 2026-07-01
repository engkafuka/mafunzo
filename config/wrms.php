<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WRMS (WRRB-TMX) API Configuration
    |--------------------------------------------------------------------------
    | Base URL and credentials for Warehouse Receipt Management System API.
    | Used by Super Admin to view Warehouse Receipts, Warehouses, and Operators.
    */

    'base_url' => env('WRMS_API_BASE_URL', 'https://wrms.wrrb.go.tz'),

    'username' => env('WRMS_API_USERNAME', ''),

    'token' => env('WRMS_API_TOKEN', ''),

    'timeout' => (int) env('WRMS_API_TIMEOUT', 60),

    /*
    | Set to false only in local/dev if you get "unable to get local issuer certificate".
    | Do not disable in production.
    */
    'verify_ssl' => env('WRMS_API_VERIFY_SSL', true),

    /*
    | Request body format. "simple" = {"args": [...]} per PDF; "jsonrpc" = Odoo JSON-RPC envelope.
    | Use "jsonrpc" if the server returns BadRequest with the simple format.
    */
    'request_format' => env('WRMS_API_REQUEST_FORMAT', 'jsonrpc'),

];
