<?php

namespace App\Support;

use Illuminate\Http\Request;

class AcademicsRouteParams
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function for(array $params = []): array
    {
        unset($params['department']);

        return array_filter(
            $params,
            static fn ($value) => $value !== null && $value !== '',
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, array $extra = []): array
    {
        return self::for(array_merge([
            'learning_department' => $request->integer('learning_department') ?: null,
        ], $extra));
    }
}
