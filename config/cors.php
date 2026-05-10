<?php

declare(strict_types=1);

/**
 * Origens permitidas para requisições cross-origin (browser → API).
 *
 * Em produção, defina CORS_ALLOWED_ORIGINS com o domínio exato do front (incluindo https)
 * — não use apenas "onrender.com"; use por exemplo:
 * https://health-dashboard-tecsagroup-front.onrender.com
 *
 * Várias origens: lista separada por vírgula. Um único "*" libera todas (útil só em dev).
 */
$raw = (string) env('CORS_ALLOWED_ORIGINS', '*');

if ($raw === '*') {
    $allowedOrigins = ['*'];
} else {
    $allowedOrigins = array_values(array_filter(array_map(
        static fn (string $o): string => trim($o),
        explode(',', $raw),
    )));
    if ($allowedOrigins === []) {
        $allowedOrigins = ['*'];
    }
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
