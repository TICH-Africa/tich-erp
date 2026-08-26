<?php

return [

    /** WebP encode quality (0–100). Lower = smaller files. */
    'webp_quality' => (int) env('MEDIA_WEBP_QUALITY', 78),

    /** Longest edge in pixels after resize (0 = no resize). */
    'max_dimension' => (int) env('MEDIA_MAX_DIMENSION', 1920),

    /**
     * Soft target size in bytes. If the first encode exceeds this, quality is
     * stepped down until under the target or the floor quality is reached.
     */
    'target_max_bytes' => (int) env('MEDIA_TARGET_MAX_BYTES', 900000),

    'webp_quality_floor' => (int) env('MEDIA_WEBP_QUALITY_FLOOR', 55),

    /**
     * MIME types that are never raster-compressed (SVG stays as-is when allowed).
     * WebP uploads are re-encoded so they are resized/compressed too.
     */
    'webp_skip_mimes' => [
        'image/svg+xml',
    ],

];
