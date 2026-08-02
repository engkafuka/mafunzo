<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Licensing integration API token
    |--------------------------------------------------------------------------
    |
    | The licensing application must send: Authorization: Bearer <token>
    |
    */
    'api_token' => env('LICENSING_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Days until a pending nomination expires
    |--------------------------------------------------------------------------
    */
    'nomination_expires_days' => (int) env('LICENSING_NOMINATION_EXPIRES_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Positions eligible for warehouse license staff seats
    |--------------------------------------------------------------------------
    |
    | Keys are Mafunzo final_position values (effectivePosition).
    | Aliases map external names (e.g. weight_clerk) to internal keys.
    |
    */
    'eligible_positions' => [
        'manager' => 'Manager',
        'documentation' => 'Documentation',
        'quality_assurance' => 'Quality Assurance',
        'weight_assistant' => 'Weight Assistant',
        'store_keeper' => 'Store Keeper',
    ],

    'position_aliases' => [
        'weight_clerk' => 'weight_assistant',
        'weight' => 'weight_assistant',
        'storekeeper' => 'store_keeper',
        'qa' => 'quality_assurance',
    ],
];
