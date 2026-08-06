<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->only(['action', 'module', 'entity_type', 'user_id', 'status', 'from', 'to', 'search']);
        $logs = $this->auditService->query($filters)->paginate($request->integer('per_page', 25));

        if ($request->expectsJson()) {
            $this->auditService->log(
                'audit.view',
                'audit_logs',
                'list',
                null,
                ['filters' => $filters, 'channel' => 'api'],
                null,
                'success',
                $request->user()?->id,
                $request
            );

            return response()->json($logs);
        }

        $this->auditService->log(
            'audit.view',
            'audit_logs',
            'list',
            null,
            ['filters' => $filters, 'channel' => 'web'],
            null,
            'success',
            $request->user()?->id,
            $request
        );

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => array_keys(config('audit.actions', [])),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse|View
    {
        $log = AuditLog::query()->with('user:id,email,user_type,staff_id,student_id')->find($id);

        if (! $log) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Audit log not found'], 404);
            }

            abort(404);
        }

        $this->auditService->log(
            'audit.view',
            'audit_logs',
            (string) $id,
            null,
            ['channel' => $request->expectsJson() ? 'api' : 'web'],
            null,
            'success',
            $request->user()?->id,
            $request
        );

        if ($request->expectsJson()) {
            return response()->json($log);
        }

        return view('admin.audit-logs.show', ['log' => $log]);
    }

    public function verifyChain(Request $request): JsonResponse
    {
        $result = $this->auditService->verifyChain($request->integer('limit') ?: null);

        return response()->json($result, $result['verified'] ? 200 : 422);
    }

    public function export(Request $request): JsonResponse
    {
        $filters = $request->only(['action', 'module', 'entity_type', 'user_id', 'status', 'from', 'to']);
        $logs = $this->auditService->query($filters)->limit(1000)->get();

        $this->auditService->log(
            'audit.export',
            'audit_logs',
            'export',
            null,
            [
                'filters' => $filters,
                'count' => $logs->count(),
                'channel' => $request->expectsJson() ? 'api' : 'web',
            ],
            $request->input('reason'),
            'success',
            $request->user()?->id,
            $request
        );

        return response()->json([
            'exported_at' => now(),
            'count' => $logs->count(),
            'data' => $logs,
        ]);
    }
}
