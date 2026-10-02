<?php

return [
    'client_id' => env('PAYOS_CLIENT_ID'),
    'api_key' => env('PAYOS_API_KEY'),
    'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
    'api_url' => env('PAYOS_API_URL', 'https://api-merchant.payos.vn'),
    'ca_bundle' => env('PAYOS_CA_BUNDLE', base_path('resources/certs/cacert.pem')),
];
