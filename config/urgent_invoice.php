<?php

return [
    'enabled'         => (bool) env('TIARA_URGENT_INVOICE_ENABLED', false),
    'preview_enabled' => (bool) env('TIARA_INVOICE_PREVIEW_ENABLED', false),
    // Enable only after the company confirms that Phase 1 is appropriate and reviews the output.
    'phase_one_reviewed' => (bool) env('TIARA_INVOICE_PHASE_ONE_REVIEWED', false),

    'timezone' => 'Asia/Riyadh',

    'currency' => 'SAR',

    'seller' => [
        'legal_name_ar' => env('TIARA_INVOICE_LEGAL_NAME_AR'),
        'legal_name_en' => env('TIARA_INVOICE_LEGAL_NAME_EN'),

        'vat_number' => env('TIARA_INVOICE_VAT_NUMBER'),
        'cr_number'  => env('TIARA_INVOICE_CR_NUMBER'),

        'street'        => env('TIARA_INVOICE_STREET'),
        'building'      => env('TIARA_INVOICE_BUILDING'),
        'district'      => env('TIARA_INVOICE_DISTRICT'),
        'city'          => env('TIARA_INVOICE_CITY'),
        'postal_code'   => env('TIARA_INVOICE_POSTAL_CODE'),
        'country_code'  => 'SA',

        'address_ar' => env('TIARA_INVOICE_ADDRESS_AR'),
        'address_en' => env('TIARA_INVOICE_ADDRESS_EN'),
    ],
];
