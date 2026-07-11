<?php

return [
    'login_hint' => [
        'enabled' => filter_var(env('CAMR_LOGIN_HINT_ENABLED', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOL),
        'label' => env('CAMR_LOGIN_HINT_LABEL', 'Test login'),
        'username' => env('CAMR_LOGIN_HINT_USERNAME', 'admin'),
        'password' => env('CAMR_LOGIN_HINT_PASSWORD', '123456'),
    ],
];
