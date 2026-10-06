<?php

return [
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'mobile_wallets' => [
        'bkash' => [
            'label' => 'bKash',
            'logo' => 'images/payments/bkash.png',
            'accent' => '#e2136e',
            'merchant_number' => env('BKASH_MERCHANT_NUMBER'),
        ],
        'nagad' => [
            'label' => 'Nagad',
            'logo' => 'images/payments/nagad.png',
            'accent' => '#f7941d',
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER'),
        ],
        'rocket' => [
            'label' => 'Rocket',
            'logo' => 'images/payments/rocket.png',
            'accent' => '#8f2d91',
            'merchant_number' => env('ROCKET_MERCHANT_NUMBER'),
        ],
    ],
];
