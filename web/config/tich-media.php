<?php

return [

    'webp_quality' => (int) env('MEDIA_WEBP_QUALITY', 85),

    /**
     * MIME types that are not re-encoded (still stored under a .webp filename when
     * already WebP; SVG is never treated as a raster upload).
     */
    'webp_skip_mimes' => [
        'image/webp',
        'image/svg+xml',
    ],

];
