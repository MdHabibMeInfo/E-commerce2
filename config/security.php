<?php

return [
    'management_two_factor_lifetime' => (int) env('MANAGEMENT_TWO_FACTOR_LIFETIME', 480),
    'sensitive_reauthentication_lifetime' => (int) env('SENSITIVE_REAUTHENTICATION_LIFETIME', 10),
];
