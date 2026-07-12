<?php

return [
    'expiry_years' => (int) env('IDENTITY_CARD_EXPIRY_YEARS', 3),
    'title' => 'Warehouse Worker Identity Card',
    'title_sw' => 'Kitambulisho cha Mfanyakazi wa Ghala',
    'organization' => 'Warehouse Receipt Regulatory Board (WRRB)',
    'organization_sw' => 'Bodi ya Usimamizi wa Stakabadhi za Ghala (WRRB)',
    'pdf_width_pt' => 242.65,   // 85.6mm — horizontal edge
    'pdf_height_pt' => 153.07,  // 53.98mm — vertical edge
    // DomPDF swaps width/height when orientation is "landscape", so use portrait
    // when the size array is already wider than tall (true ID-card landscape).
    'pdf_orientation' => 'portrait',
];
