<?php

return [
    'default' => env('COURIER_DEFAULT', ''),

    'couriers' => [
        'steadfast' => [
            'name' => 'Steadfast',
            'enabled' => env('COURIER_STEADFAST_ENABLED', false),
            'api_url' => env('COURIER_STEADFAST_API_URL'),
            'api_key' => env('COURIER_STEADFAST_API_KEY'),
            'tracking_url' => env('COURIER_STEADFAST_TRACKING_URL'),
            'supports_history' => false,
            'supports_tracking_api' => false,
            'supports_cancel' => false,
            'free_api' => false,
            'notes' => 'Official Steadfast merchant credentials and API documentation must be verified before enabling live shipment creation.',
        ],
        'pathao' => [
            'name' => 'Pathao',
            'enabled' => env('COURIER_PATHAO_ENABLED', false),
            'api_url' => env('COURIER_PATHAO_API_URL'),
            'api_key' => env('COURIER_PATHAO_API_KEY'),
            'tracking_url' => env('COURIER_PATHAO_TRACKING_URL'),
            'supports_history' => false,
            'supports_tracking_api' => false,
            'supports_cancel' => false,
            'free_api' => false,
            'notes' => 'Pathao integration remains disabled until official merchant API access, credentials, and tracking rules are confirmed.',
        ],
        'redx' => [
            'name' => 'REDX',
            'enabled' => env('COURIER_REDX_ENABLED', false),
            'environment' => env('COURIER_REDX_ENV', 'production'),
            'api_token' => env('COURIER_REDX_API_TOKEN'),
            'pickup_store_id' => env('COURIER_REDX_PICKUP_STORE_ID'),
            'default_weight_grams' => (int) env('COURIER_REDX_DEFAULT_WEIGHT_GRAMS', 0),
            'tracking_url' => env('COURIER_REDX_TRACKING_URL'),
            'webhook_token' => env('COURIER_REDX_WEBHOOK_TOKEN'),
            'supports_history' => false,
            'supports_tracking_api' => true,
            'supports_cancel' => false,
            'free_api' => false,
            'notes' => 'Requires a merchant-issued OpenAPI token and pickup store ID. Delivery areas are resolved through the official REDX area API.',
        ],
    ],
];
