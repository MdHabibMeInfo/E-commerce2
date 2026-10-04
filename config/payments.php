<?php

return [
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'mobile_wallets' => [
        'bkash' => [
            'label' => 'bKash',
            'merchant_number' => env('BKASH_MERCHANT_NUMBER'),
        ],
        'nagad' => [
            'label' => 'Nagad',
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER'),
        ],
    ],
];
