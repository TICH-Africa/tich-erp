<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaComplianceScore;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaPlan;
use App\Models\Qa\QcaFlag;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(): View
    {
        $totalCompliance = QaComplianceScore::query()
            ->where('items_submitted', '>', 0)
            ->avg('weighted_score');
        $totalCompliance = round($totalCompliance ?? 0, 2);

        $status = $totalCompliance >= 80 ? 'green' : ($totalCompliance >= 60 ? 'amber' : 'red');

        $openFlags = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $criticalFlags = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereIn('severity', ['High', 'Critical'])
            ->count();

        $actionCount = QaCorrectiveAction::query()
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->count();

        $failingDepartments = QaComplianceScore::query()
            ->with(['department', 'plan'])
            ->where('is_below_threshold', 1)
            ->orderByDesc('calculated_at')
            ->limit(10)
            ->get();

        $complianceDist = QaComplianceScore::query()
            ->where('items_submitted', '>', 0)
            ->get()
            ->reduce(function ($carry, $score) {
                $s = $score->weighted_score;
                if ($s >= 80) {
                    $carry['green']++;
                } elseif ($s >= 60) {
                    $carry['amber']++;
                } else {
                    $carry['red']++;
                }
                return $carry;
            }, ['green' => 0, 'amber' => 0, 'red' => 0]);

        $flagsBySeverity = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->get()
            ->groupBy('severity')
            ->map(fn ($flags) => $flags->count());

        $actionsByStatus = QaCorrectiveAction::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        $plansByStatus = QaPlan::query()
            ->whereIn('status', ['draft', 'dispatched', 'in_progress', 'compiled'])
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        $complianceByHubData = QaComplianceScore::query()
            ->with(['department'])
            ->where('items_submitted', '>', 0)
            ->get()
            ->groupBy(fn ($score) => $score->department?->campus_id ?? 0)
            ->map(fn ($scores) => round($scores->avg('weighted_score'), 2));

        return view('qa.executive-dashboard.index', [
            'totalCompliance' => $totalCompliance,
            'complianceStatus' => $status,
            'openFlags' => $openFlags,
            'criticalFlags' => $criticalFlags,
            'actionCount' => $actionCount,
            'complianceByHub' => QaComplianceScore::query()
                ->with(['department'])
                ->where('items_submitted', '>', 0)
                ->get()
                ->groupBy(fn ($score) => $score->department?->campus_id ?? 0),
            'failingDepartments' => $failingDepartments,
            'activePlans' => QaPlan::query()
                ->whereIn('status', ['dispatched', 'in_progress', 'compiled'])
                ->count(),
            'chartData' => [
                'compliance' => [
                    'green' => $complianceDist['green'],
                    'amber' => $complianceDist['amber'],
                    'red' => $complianceDist['red'],
                ],
                'flagsBySeverity' => [
                    'Low' => $flagsBySeverity->get('Low', 0),
                    'Medium' => $flagsBySeverity->get('Medium', 0),
                    'High' => $flagsBySeverity->get('High', 0),
                    'Critical' => $flagsBySeverity->get('Critical', 0),
                ],
                'actionsByStatus' => $actionsByStatus,
                'plansByStatus' => $plansByStatus,
                'complianceByHub' => $complianceByHubData,
            ],
        ]);
    }

    public function complianceByHub(Request $request): JsonResponse
    {
        $hubId = $request->integer('hub_id');

        $scores = QaComplianceScore::query()
            ->with(['department', 'plan'])
            ->where('department_id', $hubId)
            ->where('items_submitted', '>', 0)
            ->get();

        return response()->json([
            'scores' => $scores->map(fn ($s) => [
                'department' => $s->department?->dept_name,
                'score' => $s->weighted_score,
                'status' => $s->pass_fail_status,
                'plan' => $s->plan?->plan_name,
            ]),
            'average' => $scores->isNotEmpty() ? round($scores->avg('weighted_score'), 2) : 0,
        ]);
    }

    public function chartCompliance(Request $request): JsonResponse
    {
        $scores = QaComplianceScore::query()
            ->where('items_submitted', '>', 0)
            ->get();

        $dist = ['green' => 0, 'amber' => 0, 'red' => 0];
        foreach ($scores as $s) {
            if ($s->weighted_score >= 80) {
                $dist['green']++;
            } elseif ($s->weighted_score >= 60) {
                $dist['amber']++;
            } else {
                $dist['red']++;
            }
        }

        return response()->json($dist);
    }

    public function chartFlags(Request $request): JsonResponse
    {
        $query = QcaFlag::query();

        if ($request->boolean('active')) {
            $query->whereIn('status', ['open', 'in_progress']);
        }

        $flags = $query->get()->groupBy('severity')->map(fn ($f) => $f->count());

        return response()->json([
            'Low' => $flags->get('Low', 0),
            'Medium' => $flags->get('Medium', 0),
            'High' => $flags->get('High', 0),
            'Critical' => $flags->get('Critical', 0),
        ]);
    }

    public function chartActions(Request $request): JsonResponse
    {
        $actions = QaCorrectiveAction::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        return response()->json($actions);
    }

    public function chartAuditTrail(Request $request): JsonResponse
    {
        $days = $request->integer('days', 14);
        $from = now()->subDays($days)->toDateString();

        $logs = DB::table('audit_logs')
            ->where('module', 'qa')
            ->where('created_at', '>=', $from)
            ->selectRaw("DATE(created_at) as date, count(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'labels' => $logs->pluck('date'),
            'counts' => $logs->pluck('count'),
        ]);
    }

    public function auditTrail(Request $request): JsonResponse
    {
        $query = $this->audit->query([
            'module' => 'qa',
            'action' => $request->get('action'),
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'search' => $request->get('search'),
        ]);

        $logs = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 50));

        return response()->json([
            'logs' => $logs->map(fn ($log) => [
                'id' => $log->id,
                'timestamp' => $log->created_at?->toIso8601String(),
                'actor' => $log->user?->displayName() ?? 'System',
                'action' => $log->action,
                'module' => $log->module,
                'entity' => $log->entity_type.' '.($log->entity_id ?: ''),
                'summary' => $log->reason ?: $log->summary(),
                'hash' => $log->record_hash ? substr($log->record_hash, 0, 16).'...' : null,
                'previous_hash' => $log->previous_hash ? substr($log->previous_hash, 0, 16).'...' : null,
            ]),
            'total' => $logs->total(),
            'has_more' => $logs->hasMorePages(),
        ]);
    }
}
