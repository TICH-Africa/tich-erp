<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Pagination
{
    /** @var list<int> */
    public const OPTIONS = [10, 25, 50, 100, 200];

    public static function perPage(Request $request, int $default = 25): int
    {
        $raw = (int) $request->input('per_page', $default);

        if (in_array($raw, self::OPTIONS, true)) {
            return $raw;
        }

        return max(1, min(200, $raw > 0 ? $raw : $default));
    }
}
