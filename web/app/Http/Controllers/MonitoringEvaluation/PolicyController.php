<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Me\MePolicy;
use App\Services\DepartmentBudgetingService;
use App\Services\Me\MePolicyService;
use App\Services\StaffPortalService;
use App\Services\StoredFileService;
use App\Support\SupportingDocumentResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyController extends Controller
{
    public function __construct(
        protected MePolicyService $policies,
        protected StaffPortalService $staffPortal,
        protected StoredFileService $files,
        protected DepartmentBudgetingService $budgeting,
    ) {}

    public function index(): View
    {
        $items = MePolicy::query()->with('uploader')->orderByDesc('id')->paginate(20);
        $current = $this->policies->currentPublishedPolicy();
        $signoff = $current ? $this->policies->signoffProgress($current) : null;

        return view('monitoring-evaluation.policies.index', compact('items', 'current', 'signoff'));
    }

    public function create(): View
    {
        return view('monitoring-evaluation.policies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:300'],
            'version' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'effective_date' => ['nullable', 'date'],
            'policy_file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
        ]);

        $policy = $this->policies->uploadPolicy($data, $request->file('policy_file'), $request->user());

        return redirect()
            ->route('monitoring_evaluation.policies.show', $policy)
            ->with('status', 'M&E policy uploaded as draft. Publish it to require HOD sign-off.');
    }

    public function show(MePolicy $policy): View
    {
        $policy->load(['uploader', 'signoffs.department', 'signoffs.staff']);
        $signoff = $this->policies->signoffProgress($policy);

        return view('monitoring-evaluation.policies.show', compact('policy', 'signoff'));
    }

    public function publish(MePolicy $policy): RedirectResponse
    {
        $this->policies->publish($policy);

        return back()->with('status', 'Policy published. HODs must sign off before submitting annual budgets and plans.');
    }

    public function view(MePolicy $policy): StreamedResponse
    {
        return $this->streamPolicyFile($policy, inline: true);
    }

    public function download(MePolicy $policy): StreamedResponse
    {
        return $this->streamPolicyFile($policy, inline: false);
    }

    public function signForm(Request $request): View|RedirectResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        if (! $policy) {
            return redirect()
                ->route($this->fallbackDashboardRoute($request))
                ->withErrors(['policy' => 'No published M&E policy is available to sign.']);
        }

        $module = $this->resolveModuleKey($request);
        $moduleContext = $module ? $this->budgeting->moduleContext($module) : null;
        $department = $module ? $this->budgeting->departmentForModule($module) : null;
        $staff = $this->staffPortal->staffForUser($request->user());
        $document = $this->documentMeta($policy);
        $signoff = $this->policies->signoffProgress($policy);
        $canViewSignoffRoster = $this->userCanViewSignoffRoster($request->user());
        $alreadySigned = $department
            ? $this->policies->departmentHasHodSignoff($policy, $department)
            : false;
        $signStoreRoute = $this->signStoreRouteName($request, $module);
        $signFormRoute = $this->signFormRouteName($request, $module);

        return view('monitoring-evaluation.policies.sign', compact(
            'policy',
            'staff',
            'document',
            'module',
            'moduleContext',
            'department',
            'signoff',
            'canViewSignoffRoster',
            'alreadySigned',
            'signStoreRoute',
            'signFormRoute',
        ));
    }

    public function sign(Request $request): RedirectResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        abort_unless($policy, 404);

        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:200'],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'signature' => ['nullable', 'string', 'max:300'],
        ]);

        $module = $this->resolveModuleKey($request);
        $department = $module ? $this->budgeting->departmentForModule($module) : null;

        try {
            $this->policies->signOff($policy, $request->user(), $data, $request->ip(), $department);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['policy' => $e->getMessage()]);
        }

        $deptName = $department?->dept_name ?? 'your department';

        return redirect()
            ->route($this->signFormRouteName($request, $module))
            ->with('status', "You have digitally signed the Standard M&E Policy for {$deptName}. You may now submit annual budgets and departmental plans.");
    }

    private function userCanViewSignoffRoster($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole([
            'Super Admin',
            'CEO',
            'Monitoring and Evaluation Officer',
            'Assistant Monitoring and Evaluation Officer',
        ])) {
            return true;
        }

        return method_exists($user, 'can') && $user->can('monitoring_evaluation.read');
    }

    private function resolveModuleKey(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();
        if ($name === '' || str_starts_with($name, 'monitoring_evaluation.policy.sign')) {
            return null;
        }

        $module = explode('.', $name)[0] ?? '';

        return isset(DepartmentBudgetingService::MODULES[$module]) ? $module : null;
    }

    private function signFormRouteName(Request $request, ?string $module): string
    {
        if ($module && \Illuminate\Support\Facades\Route::has($module.'.me-policy.sign')) {
            return $module.'.me-policy.sign';
        }

        return 'monitoring_evaluation.policy.sign';
    }

    private function signStoreRouteName(Request $request, ?string $module): string
    {
        if ($module && \Illuminate\Support\Facades\Route::has($module.'.me-policy.sign.store')) {
            return $module.'.me-policy.sign.store';
        }

        return 'monitoring_evaluation.policy.sign.store';
    }

    private function fallbackDashboardRoute(Request $request): string
    {
        $module = $this->resolveModuleKey($request);
        if ($module) {
            return $this->budgeting->moduleContext($module)['dashboard_route'];
        }

        return 'monitoring_evaluation.dashboard';
    }

    /**
     * @return array{filename: string, mime: string, is_previewable: bool, exists: bool}|null
     */
    private function documentMeta(MePolicy $policy): ?array
    {
        $relative = $this->relativePath($policy);
        if (! $relative) {
            return null;
        }

        $exists = Storage::disk('public')->exists($relative);
        $filename = basename($relative);
        $mime = $exists
            ? (Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream')
            : 'application/octet-stream';

        $previewable = SupportingDocumentResponse::isPreviewable($mime, $filename)
            || str_starts_with($mime, 'text/')
            || in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['txt', 'md', 'csv'], true);

        return [
            'filename' => $filename,
            'mime' => $mime,
            'is_previewable' => $previewable,
            'exists' => $exists,
        ];
    }

    private function streamPolicyFile(MePolicy $policy, bool $inline): StreamedResponse
    {
        $user = request()->user();
        $canOfficer = $user && method_exists($user, 'can') && $user->can('monitoring_evaluation.read');
        abort_unless($policy->isPublished() || $canOfficer, 403);

        $relative = $this->relativePath($policy);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = $this->safeFilename(basename($relative));
        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';

        if ($inline) {
            return Storage::disk('public')->response(
                $relative,
                $filename,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ]
            );
        }

        return Storage::disk('public')->download($relative, $filename, ['Content-Type' => $mime]);
    }

    private function relativePath(MePolicy $policy): ?string
    {
        return $this->files->relativePath($policy->file_path);
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'policy';
    }
}
