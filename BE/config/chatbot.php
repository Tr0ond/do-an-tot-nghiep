<?php

return [
    'key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
    'ca_bundle' => env('GEMINI_CA_BUNDLE', base_path('resources/certs/cacert.pem')),
    'timeout' => 30,
    'max_output_tokens' => 2048,
    'phien_ban_prompt' => 'fitforge-v3-giao-an',
];
