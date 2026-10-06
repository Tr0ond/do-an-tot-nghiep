<?php

return [
    'enabled' => env('EXPO_PUSH_ENABLED', false),
    // Chỉ BE sử dụng khóa tùy chọn của Expo Push Security; không trả về mobile.
    'access_token' => env('EXPO_PUSH_ACCESS_TOKEN'),
];
