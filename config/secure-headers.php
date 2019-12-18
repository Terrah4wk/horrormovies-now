<?php

$protocol = 'https://';
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $protocol = 'http://';
}

return [
    'x-content-type-options'            => 'nosniff',
    'x-download-options'                => 'noopen',
    'x-frame-options'                   => 'sameorigin',
    'x-permitted-cross-domain-policies' => 'none',
    'x-xss-protection'                  => '1; mode=block',
    'hsts'                              => [
        'enable'              => env('SECURITY_HEADER_HSTS_ENABLE', true),
        'max-age'             => 31536000,
        'include-sub-domains' => true,
    ],
    'hpkp' => [
        'hashes'              => false,
        'include-sub-domains' => false,
        'max-age'             => 15552000,
        'report-only'         => false,
        'report-uri'          => null,
    ],
    'custom-csp' => env('SECURITY_HEADER_CUSTOM_CSP', null),
    'csp'        => [
        'report-only'               => false,
        'report-uri'                => env('CONTENT_SECURITY_POLICY_REPORT_URI', false),
        'upgrade-insecure-requests' => false,
        'default-src'               => [
            'self' => true,
        ],
        'script-src' => [
            'allow' => [
                $protocol.'ajax.googleapis.com',
                $protocol.'code.jquery.com',
                $protocol.'cdnjs.cloudflare.com',
                $protocol.'stackpath.bootstrapcdn.com',
                $protocol.'cdn.jsdelivr.net',
                $protocol.'getbootstrap.com',
            ],
            'self'          => true,
            'unsafe-inline' => false,
            'unsafe-eval'   => false,
            'data'          => true,
        ],
        'style-src' => [
            'allow' => [
                $protocol.'fonts.googleapis.com',
                $protocol.'stackpath.bootstrapcdn.com',
                $protocol.'cdn.jsdelivr.net',
            ],
            'self'          => true,
            'unsafe-inline' => false,
        ],
        'img-src' => [
            'allow' => [
                '*',
            ],
            'self' => true,
            'data' => true,
        ],
        'font-src' => [
            'allow' => [
                $protocol.'fonts.gstatic.com',
            ],
            'self' => true,
            'data' => true,
        ],
        'object-src' => [
            'allow' => [],
            'self'  => false,
        ],
    ],
];
