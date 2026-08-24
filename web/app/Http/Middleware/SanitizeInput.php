<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalize and sanitize request string input (null bytes, control chars).
 * Leaves passwords / rich-text keys alone beyond null-byte stripping.
 * SQL stays parameterized via Eloquent / query bindings.
 */
class SanitizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.sanitize_input', true)) {
            return $next($request);
        }

        $except = array_fill_keys(array_map('strtolower', config('security.sanitize_except', [])), true);
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input, $except);

        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, true>  $except
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data, array $except, string $prefix = ''): array
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $leaf = strtolower((string) $key);
            $isExcept = isset($except[$leaf]) || $this->pathIsExcepted($path, $except);

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $except, $path);
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            // Always strip null bytes (path traversal / encoding tricks).
            $value = str_replace("\0", '', $value);

            if ($isExcept) {
                $data[$key] = $value;
                continue;
            }

            // Strip HTML tags from ordinary fields; keep printable text.
            $value = strip_tags($value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @param  array<string, true>  $except
     */
    private function pathIsExcepted(string $path, array $except): bool
    {
        $path = strtolower($path);
        if (isset($except[$path])) {
            return true;
        }

        foreach (array_keys($except) as $key) {
            if (str_ends_with($path, '.'.$key)) {
                return true;
            }
        }

        return false;
    }
}
