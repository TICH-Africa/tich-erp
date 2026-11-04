<?php

namespace App\Http\Controllers\Api\Procurement\mobile\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PaginatesMobileProcurementLists
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  callable(\Illuminate\Database\Eloquent\Model): array<string, mixed>  $mapItem
     */
    protected function paginatedItemsResponse(
        Request $request,
        Builder $query,
        callable $mapItem,
        int $defaultPerPage = 25,
    ): JsonResponse {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(10, (int) $request->input('per_page', $defaultPerPage)));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'items' => $paginator->getCollection()->map($mapItem)->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
