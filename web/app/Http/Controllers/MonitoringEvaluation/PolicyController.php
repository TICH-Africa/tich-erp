<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MePolicy;
use App\Services\Me\MePolicyService;
use App\Services\StaffPortalService;
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

    public function download(MePolicy $policy): StreamedResponse
    {
        $user = request()->user();
        $canOfficer = $user && method_exists($user, 'can') && $user->can('monitoring_evaluation.read');
        abort_unless($policy->isPublished() || $canOfficer, 403);
        abort_unless(Storage::disk('public')->exists($policy->file_path), 404);

        return Storage::disk('public')->download($policy->file_path, basename($policy->file_path));
    }

    public function signForm(Request $request): View|RedirectResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        if (! $policy) {
            return redirect()
                ->route('monitoring_evaluation.dashboard')
                ->withErrors(['policy' => 'No published M&E policy is available to sign.']);
        }

        $staff = $this->staffPortal->staffForUser($request->user());

        return view('monitoring-evaluation.policies.sign', compact('policy', 'staff'));
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

        try {
            $this->policies->signOff($policy, $request->user(), $data, $request->ip());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['policy' => $e->getMessage()]);
        }

        return redirect()
            ->route('monitoring_evaluation.policy.sign')
            ->with('status', 'You have digitally signed the Standard M&E Policy. You may now submit annual budgets and departmental plans.');
    }
}
