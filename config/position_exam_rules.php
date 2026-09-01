<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exam pass threshold (all applicants)
    |--------------------------------------------------------------------------
    */
    'pass_score' => 50,

    /*
    |--------------------------------------------------------------------------
    | Shared fallback when a gated position is not earned
    |--------------------------------------------------------------------------
    */
    'fallback_positions' => [
        'documentation',
        'weight_assistant',
        'store_keeper',
    ],

    /*
    |--------------------------------------------------------------------------
    | Position-specific rules after exam scoring
    |--------------------------------------------------------------------------
    |
    | Only listed applied positions have extra education + score gates.
    | required_education entries must match one education background row.
    | program is optional (omit to match any program for that level).
    |
    */
    'positions' => [
        'manager' => [
            'required_education' => [
                ['level' => 'degree'],
            ],
            'min_score' => 70,
        ],
        'quality_assurance' => [
            'required_education' => [
                ['level' => 'diploma', 'program' => 'agriculture'],
            ],
            'min_score' => 50,
        ],
    ],
];
