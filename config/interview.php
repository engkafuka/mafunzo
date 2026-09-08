<?php

return [
    'roles' => [
        'admin' => 'Interview Admin',
        'panelist' => 'Panelist',
        'chair' => 'Chairperson',
        'approver' => 'Approver',
        'viewer' => 'Viewer / Auditor',
    ],

    'session_statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In progress',
        'scoring' => 'Scoring',
        'under_review' => 'Under review',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'interview_types' => [
        'new_operator' => 'New operator',
        'renewal' => 'Renewal',
        'special_review' => 'Special review',
    ],

    'question_categories' => [
        'governance' => 'Company knowledge and governance',
        'operations' => 'Warehouse operations awareness',
        'compliance' => 'Licensing and compliance',
        'quality' => 'Quality and storage standards',
        'records' => 'Record keeping and receipts',
        'integrity' => 'Safety, integrity, and anti-fraud',
    ],

    'default_pass_mark' => 60,

    'variance_threshold' => 3, // flag when panelists differ by more than this on a question
];
