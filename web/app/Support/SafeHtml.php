<?php

namespace App\Support;

/**
 * Sanitize HTML before unescaped Blade output ({!! !!}).
 */
class SafeHtml
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'a', 'b', 'br', 'em', 'i', 'li', 'ol', 'p', 'strong', 'u', 'ul',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'span', 'div', 'hr', 'blockquote', 'pre', 'code',
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $cleaned = strip_tags($html, $allowed);

        // Remove dangerous attributes / event handlers while keeping safe markup.
        $cleaned = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', ' $1="#"', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/<(script|iframe|object|embed|link|meta|style)\b[^>]*>.*?<\/\1>/is', '', $cleaned) ?? $cleaned;

        return $cleaned;
    }

    public static function escape(?string $value): string
    {
        return e((string) $value);
    }
}
