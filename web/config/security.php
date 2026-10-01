<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When enabled, HTTP requests are redirected to HTTPS and generated URLs
    | use the https scheme. Enable in production behind TLS.
    |
    */

    'force_https' => (bool) env('FORCE_HTTPS', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Security response headers
    |--------------------------------------------------------------------------
    */

    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS', true),

        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),

        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(), payment=(), usb=()'
        ),

        /*
         * Keep CSP compatible with current assets (Blade inline scripts, Tailwind CDN).
         * Tighten further once scripts/styles are fully self-hosted.
         */
        'content_security_policy' => env(
            'SECURITY_CSP',
            "default-src 'self'; ".
            "base-uri 'self'; ".
            "form-action 'self'; ".
            "frame-ancestors 'self'; ".
            "object-src 'none'; ".
            "img-src 'self' data: blob: https:; ".
            "font-src 'self' data: https://fonts.bunny.net; ".
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; ".
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; ".
            "connect-src 'self' wss: ws: https:; ".
            "media-src 'self' blob:; ".
            'upgrade-insecure-requests'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input sanitization
    |--------------------------------------------------------------------------
    */

    'sanitize_input' => (bool) env('SECURITY_SANITIZE_INPUT', true),

    /*
    | Keys that must not be stripped/altered beyond null-byte removal
    | (passwords, tokens, rich HTML bodies, etc.).
    */
    'sanitize_except' => [
        'password',
        'password_confirmation',
        'password_hash',
        'current_password',
        'content',
        'body',
        'html',
        'job_description',
        'template_body',
        'preview_content',
        'crop_data',
        'photo_base64',
        'cropped_photo',
        '_token',
        '_submit_nonce',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client UI protection (context menu / common inspect shortcuts)
    |--------------------------------------------------------------------------
    |
    | This is a deterrent only; it cannot fully stop browser DevTools.
    |
    */

    'block_inspect_ui' => (bool) env('SECURITY_BLOCK_INSPECT_UI', false),

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated proxy IPs/CIDRs, or "*" to trust all (only behind a known
    | reverse proxy). Empty = trust none (direct connections only).
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin IP allowlist
    |--------------------------------------------------------------------------
    |
    | When enabled, /admin and /ict routes accept only listed client IPs.
    | Leave empty + enabled=false for local development.
    |
    */

    'admin_ip_allowlist' => [
        'enabled' => (bool) env('ADMIN_IP_ALLOWLIST_ENABLED', false),
        'ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ADMIN_IP_ALLOWLIST', '127.0.0.1,::1'))
        ))),
        'prefixes' => ['admin', 'ict'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password policy
    |--------------------------------------------------------------------------
    */

    'password' => [
        'min' => (int) env('PASSWORD_MIN_LENGTH', 10),
        'require_mixed_case' => (bool) env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => (bool) env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => (bool) env('PASSWORD_REQUIRE_SYMBOLS', true),
        'uncompromised' => (bool) env('PASSWORD_CHECK_UNCOMPROMISED', false),
    ],

];
