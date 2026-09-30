<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Services\CeoGlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected CeoGlobalSearchService $search) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
            ]);
        }

        return response()->json([
            'query' => $query,
            'results' => $this->search->search($query),
        ]);
    }
}
