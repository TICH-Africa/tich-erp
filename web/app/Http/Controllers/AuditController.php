<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->only([
            'action', 'module', 'entity_type', 'user_id', 'status',
            'from', 'to', 'search', 'account', 'account_type',
        ]);

        $logs = $this->auditService->query($filters)->paginate($request->integer('per_page', 50));

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'modules' => $this->auditService->moduleOptions(),
            'actions' => array_keys(config('audit.actions', [])),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse|View
    {
        $log = AuditLog::query()
            ->with([
                'user:id,email,user_type,staff_id,student_id',
                'user.staff:id,first_name,surname,employee_number',
                'user.student:id,registration_number,application_id',
                'user.student.applicant:id,first_name,surname',
            ])
            ->find($id);

        if (! $log) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Audit log not found'], 404);
            }

            abort(404);
        }

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

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $filters = $request->only([
            'action', 'module', 'entity_type', 'user_id', 'status',
            'from', 'to', 'search', 'account', 'account_type',
        ]);

        $logs = $this->auditService->query($filters)->limit(5000)->get();

        $this->auditService->log(
            'audit.export',
            'audit_logs',
            'export',
            null,
            [
                'filters' => $filters,
                'count' => $logs->count(),
                'format' => $request->expectsJson() ? 'json' : 'xlsx',
            ],
            $request->input('reason'),
            'success',
            $request->user()?->id,
            $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'exported_at' => now(),
                'count' => $logs->count(),
                'data' => $logs,
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit logs');

        $headers = ['When', 'Module', 'Action', 'User', 'Account type', 'Entity', 'Summary', 'Status', 'IP'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $user = $log->user;
            $accountType = 'System';
            if ($user?->staff_id) {
                $accountType = 'Employee';
            } elseif ($user?->student_id) {
                $accountType = 'Student';
            } elseif ($user) {
                $accountType = ucfirst((string) ($user->user_type ?? 'user'));
            }

            $sheet->setCellValue([1, $row], $log->created_at?->format('Y-m-d H:i:s'));
            $sheet->setCellValue([2, $row], $log->module ?? '');
            $sheet->setCellValue([3, $row], $log->action);
            $sheet->setCellValue([4, $row], $user?->displayName() ?? 'System');
            $sheet->setCellValue([5, $row], $accountType);
            $sheet->setCellValue([6, $row], trim(($log->entity_type ?? '').' '.($log->entity_id ? '#'.$log->entity_id : '')));
            $sheet->setCellValue([7, $row], $this->auditService->summary($log));
            $sheet->setCellValue([8, $row], $log->status ?? '');
            $sheet->setCellValue([9, $row], $log->ip_address ?? '');
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'audit-logs-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
