<?php

use Illuminate\Http\Request;

return [
    'enabled' => env('ANALYTICS_ENABLED', true),

    'hash_secret' => env('ANALYTICS_HASH_SECRET'),

    'hash_rotation' => env('ANALYTICS_HASH_ROTATION', 'daily'),

    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),

    'store_raw_ip' => env('ANALYTICS_STORE_RAW_IP', false),

    'store_network_prefix' => env('ANALYTICS_STORE_NETWORK_PREFIX', false),

    'audit_log_retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    'audit_log_pruning_enabled' => env('AUDIT_LOG_PRUNING_ENABLED', false),

    'allowed_query_parameters' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ],

    'trusted_proxies' => array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', (string) env('ANALYTICS_TRUSTED_PROXIES', ''))
    ))),

    'trusted_proxy_headers' => Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO,
];
