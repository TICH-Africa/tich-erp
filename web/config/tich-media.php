<?php

return [

    'webp_quality' => (int) env('MEDIA_WEBP_QUALITY', 85),

    /** Skip conversion for these MIME types (already optimal or not raster). */
    'webp_skip_mimes' => [
        'image/webp',
        'image/svg+xml',
    ],

];
