<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMX Auction API (Mode B — WRRB pulls from TMX)
    |--------------------------------------------------------------------------
    | Use ots.tmx.co.tz (production) or ets.tmx.co.tz (test). Do NOT use api.tmx.go.tz.
    | OAuth client_credentials to get Bearer token; then POST export-pending or export-by-receipt.
    */

    'base_url' => env('TMX_AUCTION_BASE_URL', 'https://ots.tmx.co.tz'),

    /*
    | Optional path prefix. Per TMX spec, leave empty — Production: https://ots.tmx.co.tz,
    | Test: http://ets.tmx.co.tz. Paths are /integration/oauth/token, /integration/wrrb/v1/export-pending, etc.
    */
    'path_prefix' => rtrim(env('TMX_AUCTION_PATH_PREFIX', ''), '/'),

    'client_id' => env('TMX_AUCTION_CLIENT_ID', ''),

    'client_secret' => env('TMX_AUCTION_CLIENT_SECRET', ''),

    'oauth_path' => env('TMX_AUCTION_OAUTH_PATH', '/integration/oauth/token'),

    'export_pending_path' => env('TMX_AUCTION_EXPORT_PENDING_PATH', '/integration/wrrb/v1/export-pending'),

    'export_by_receipt_path' => env('TMX_AUCTION_EXPORT_BY_RECEIPT_PATH', '/integration/wrrb/v1/export-by-receipt/{receipt_no}'),

    'timeout' => (int) env('TMX_AUCTION_TIMEOUT', 60),

    /*
    | Set to false only in local/dev if you get "unable to get local issuer certificate".
    | Do not disable in production.
    */
    'verify_ssl' => env('TMX_AUCTION_VERIFY_SSL', true),

];
