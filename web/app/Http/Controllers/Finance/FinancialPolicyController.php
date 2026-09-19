<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinancePolicy;
use App\Services\DepartmentBudgetingService;
use App\Services\Finance\FinancePolicyService;
use App\Services\StaffPortalService;
use App\Services\StoredFileService;
use App\Support\SupportingDocumentResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialPolicyController extends Controller
{
    public function __construct(
        protected FinancePolicyService $policies,
        protected StaffPortalService $staffPortal,
        protected StoredFileService $files,
        protected DepartmentBudgetingService $budgeting,
    ) {}

    public function index(): View
    {
        $items = FinancePolicy::query()->with('uploader')->orderByDesc('id')->paginate(20);
        $current = $this->policies->currentPublishedPolicy();
        $signoff = $current ? $this->policies->signoffProgress($current) : null;

        return view('finance.policies.index', compact('items', 'current', 'signoff'));
    }

    public function create(): View
    {
        return view('finance.policies.create');
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
            ->route('finance.financial-policies.show', $policy)
            ->with('status', 'Financial policy uploaded as draft. Publish it to require HOD sign-off before budgeting.');
    }

    public function show(FinancePolicy $financePolicy): View
    {
        $policy = $financePolicy;
        $policy->load(['uploader', 'signoffs.department', 'signoffs.staff']);
        $signoff = $this->policies->signoffProgress($policy);

        return view('finance.policies.show', compact('policy', 'signoff'));
    }

    public function publish(FinancePolicy $financePolicy): RedirectResponse
    {
        $this->policies->publish($financePolicy);

        return back()->with('status', 'Financial policy published. HODs must sign off before submitting annual budgets.');
    }

    public function view(FinancePolicy $financePolicy): StreamedResponse
    {
        return $this->streamPolicyFile($financePolicy, inline: true);
    }

    public function download(FinancePolicy $financePolicy): StreamedResponse
    {
        return $this->streamPolicyFile($financePolicy, inline: false);
    }

    public function signForm(Request $request): View|RedirectResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        if (! $policy) {
            return redirect()
                ->route($this->fallbackDashboardRoute($request))
                ->withErrors(['policy' => 'No published financial policy is available to sign.']);
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

        return view('finance.policies.sign', compact(
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
            ->with('status', "You have digitally signed the Financial Policy for {$deptName}. You may now submit annual budgets and departmental plans.");
    }

    private function userCanViewSignoffRoster($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole([
            'Super Admin',
            'CEO',
            'Finance Manager',
            'Assistant Finance Manager',
        ])) {
            return true;
        }

        return method_exists($user, 'can') && $user->can('finance.read');
    }

    private function resolveModuleKey(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();
        if ($name === '' || str_starts_with($name, 'finance.financial-policies.sign')) {
            return null;
        }

        $module = explode('.', $name)[0] ?? '';

        return isset(DepartmentBudgetingService::MODULES[$module]) ? $module : null;
    }

    private function signFormRouteName(Request $request, ?string $module): string
    {
        if ($module && \Illuminate\Support\Facades\Route::has($module.'.finance-policy.sign')) {
            return $module.'.finance-policy.sign';
        }

        return 'finance.financial-policies.sign';
    }

    private function signStoreRouteName(Request $request, ?string $module): string
    {
        if ($module && \Illuminate\Support\Facades\Route::has($module.'.finance-policy.sign.store')) {
            return $module.'.finance-policy.sign.store';
        }

        return 'finance.financial-policies.sign.store';
    }

    private function fallbackDashboardRoute(Request $request): string
    {
        $module = $this->resolveModuleKey($request);
        if ($module) {
            return $this->budgeting->moduleContext($module)['dashboard_route'];
        }

        return 'finance.dashboard';
    }

    /**
     * @return array{filename: string, mime: string, is_previewable: bool, exists: bool}|null
     */
    private function documentMeta(FinancePolicy $policy): ?array
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

    private function streamPolicyFile(FinancePolicy $policy, bool $inline): StreamedResponse
    {
        $user = request()->user();
        $canOfficer = $user && method_exists($user, 'can') && $user->can('finance.read');
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

    private function relativePath(FinancePolicy $policy): ?string
    {
        return $this->files->relativePath($policy->file_path);
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'policy';
    }
}
