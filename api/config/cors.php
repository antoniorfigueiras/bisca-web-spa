<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*', 'storage/*'],
    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        // O ANTIGO (com pontos) - podes deixar por segurança
        'http://web-dad-group-12.172.22.21.253.sslip.io',
        // O NOVO (com traços) - ESTE É O QUE VAI RESOLVER O ERRO
        'http://web-dad-group-12-172.22.21.253.sslip.io',
    ],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
