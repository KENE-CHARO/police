<?php

return [
    'api_url' => env('CAMPAY_API_URL', ''),
    'api_key' => env('CAMPAY_API_KEY', ''),
    'default_amount' => (int) env('CAMPAY_PLAINT_AMOUNT', 100),
];
