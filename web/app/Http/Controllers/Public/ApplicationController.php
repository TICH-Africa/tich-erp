<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Services\ProgramsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected ProgramsService $programsService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $programCode = $request->query('program');
        if ($programCode) {
            $program = $this->programsService->findProgramByCode($programCode);
            if ($program) {
                $payload = [
                    'program_code' => strtoupper($program->program_code ?? $programCode),
                ];
                if (! empty($program->id)) {
                    $payload['program_id'] = (int) $program->id;
                }
                $this->applicationService->saveDraft($payload, 1);
            }
        }

        $step = max(1, min(7, (int) $request->query('step', $this->applicationService->currentStep())));

        return view('apply.portal', $this->portalPayload($step));
    }

    public function handleStep(Request $request, int $step): RedirectResponse
    {
        $step = max(1, min(7, $step));

        if ($request->input('action') === 'back') {
            $previous = max(1, $step - 1);
            $this->applicationService->setStep($previous);

            return redirect()->route('apply.index', ['step' => $previous]);
        }

        if ($step === 7) {
            $this->applicationService->validateStep($request, 7);
            $applicant = $this->applicationService->submit($request);

            return redirect()
                ->route('apply.confirmation', ['number' => $applicant->application_number])
                ->with('applicant_email', $applicant->email);
        }

        $validated = $this->applicationService->validateStep($request, $step);
        $this->applicationService->saveDraft($validated, $step);

        $next = min(7, $step + 1);
        $this->applicationService->setStep($next);

        return redirect()->route('apply.index', ['step' => $next]);
    }

    public function confirmation(string $number): View
    {
        return view('apply.confirmation', [
            'applicationNumber' => $number,
            'email' => session('applicant_email'),
        ]);
    }

    public function checkStatus(Request $request): View
    {
        $applicationNumber = old('application_number', $request->query('application_number'));
        $email = old('email', $request->query('email'));
        $applicant = null;

        if ($request->isMethod('post') || ($applicationNumber && $email)) {
            if ($request->isMethod('post')) {
                $validated = $request->validate([
                    'application_number' => ['required', 'string', 'max:50'],
                    'email' => ['required', 'email', 'max:255'],
                ]);
                $applicationNumber = $validated['application_number'];
                $email = $validated['email'];
            }

            $applicant = $this->applicationService->lookupStatus(
                (string) $applicationNumber,
                (string) $email
            );
        }

        return view('apply.status', [
            'applicant' => $applicant,
            'applicationNumber' => $applicationNumber,
            'email' => $email,
            'lookedUp' => $applicant !== null || ($applicationNumber && $email && ($request->isMethod('post') || $request->query('application_number'))),
        ]);
    }

    public function reset(): RedirectResponse
    {
        $this->applicationService->clearDraft();

        return redirect()->route('apply.index')->with('status', 'Application draft cleared. You can start again.');
    }

    private function portalPayload(int $step): array
    {
        $draft = $this->applicationService->draft();
        $catalog = $this->programsService->getCatalog();

        return [
            'step' => $step,
            'steps' => config('tich-application.steps', []),
            'draft' => $draft['data'] ?? [],
            'programs' => $this->programsService->getProgramOptions()->filter(fn ($program) => ! empty($program->id))->values(),
            'campuses' => $catalog['campuses'],
            'entryQualifications' => config('tich-application.entry_qualifications', []),
            'documentTypes' => config('tich-application.document_types', []),
            'counties' => config('tich-application.counties', []),
            'sponsorshipOptions' => config('tich-application.sponsorship_options', []),
            'relationshipOptions' => config('tich-application.next_of_kin_relationships', []),
            'programIntakes' => $this->applicationService->intakesByProgram(),
            'review' => $step === 7 ? $this->applicationService->reviewSummary() : null,
        ];
    }
}
