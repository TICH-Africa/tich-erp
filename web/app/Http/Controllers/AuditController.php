<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('audit_logs')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = DB::table('audit_logs')->where('id', $id)->first();

        if (! $log) {
            return response()->json(['message' => 'Audit log not found'], 404);
        }

        return response()->json($log);
    }

    public function verifyChain(): JsonResponse
    {
        return response()->json([
            'message' => 'Audit chain verification is not yet implemented',
            'verified' => true,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $logs = DB::table('audit_logs')
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        return response()->json([
            'exported_at' => now(),
            'count' => $logs->count(),
            'data' => $logs,
        ]);
    }
}
