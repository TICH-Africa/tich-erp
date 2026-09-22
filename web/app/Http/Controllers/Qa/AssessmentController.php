<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\IqaAssessment;
use App\Services\Qa\IqaAssessmentSchema;
use App\Services\Qa\IqaAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssessmentController extends Controller
{
    public function __construct(protected IqaAssessmentService $iqa) {}

    public function index(Request $request): View
    {
        $this->iqa->ensureCanView($request->user());

        $assessments = IqaAssessment::query()
            ->orderByDesc('id')
            ->paginate(20);

        return view('qa.assessments.index', [
            'assessments' => $assessments,
            'canManage' => $this->iqa->isQaOfficer($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->iqa->ensureQaOfficer($request->user());

        $validated = $request->validate([
            'assessment_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $assessment = $this->iqa->createDraft(
            $request->user(),
            isset($validated['assessment_year']) ? (int) $validated['assessment_year'] : null
        );

        return redirect()
            ->route('qa.assessments.edit', ['assessment' => $assessment, 'section' => 1])
            ->with('status', 'Draft IQA assessment created. Complete each section, then publish.');
    }

    public function show(Request $request, IqaAssessment $assessment): View
    {
        $this->iqa->ensureCanView($request->user());

        return view('qa.assessments.show', [
            'assessment' => $assessment,
            'meta' => IqaAssessmentSchema::sectionMeta(),
            'payload' => $assessment->payload ?? IqaAssessmentSchema::emptyPayload(),
            'canManage' => $this->iqa->isQaOfficer($request->user()),
        ]);
    }

    public function edit(Request $request, IqaAssessment $assessment, int $section): View|RedirectResponse
    {
        $this->iqa->ensureQaOfficer($request->user());
        abort_unless($section >= 1 && $section <= 8, 404);

        if ($assessment->isPublished()) {
            return redirect()
                ->route('qa.assessments.show', $assessment)
                ->withErrors(['assessment' => 'Published assessments are locked. Create a new assessment to capture another audit.']);
        }

        return view('qa.assessments.wizard', [
            'assessment' => $assessment,
            'section' => $section,
            'meta' => IqaAssessmentSchema::sectionMeta(),
            'payload' => $assessment->payload ?? IqaAssessmentSchema::emptyPayload(),
            'mode' => 'edit',
            'canManage' => true,
        ]);
    }

    public function update(Request $request, IqaAssessment $assessment, int $section): RedirectResponse
    {
        $this->iqa->ensureQaOfficer($request->user());
        abort_unless($section >= 1 && $section <= 8, 404);
        abort_unless($assessment->isEditable(), 403, 'Published assessments are locked.');

        $direction = (string) $request->input('direction', 'stay');
        $next = match ($direction) {
            'next' => min(8, $section + 1),
            'back' => max(1, $section - 1),
            'publish-review' => null,
            default => $section,
        };

        $input = $request->except(['_token', '_method', 'direction']);
        $this->iqa->saveSection($request->user(), $assessment, $section, $input, $next);

        if ($direction === 'publish-review') {
            return redirect()->route('qa.assessments.publish-review', [
                'assessment' => $assessment,
                'section' => 1,
            ]);
        }

        return redirect()
            ->route('qa.assessments.edit', ['assessment' => $assessment, 'section' => $next ?? $section])
            ->with('status', 'Section saved.');
    }

    public function publishReview(Request $request, IqaAssessment $assessment, ?int $section = 1): View|RedirectResponse
    {
        $this->iqa->ensureQaOfficer($request->user());
        abort_unless($assessment->isEditable(), 403, 'Published assessments are locked.');

        $section = max(1, min(8, (int) ($section ?: 1)));
        $this->iqa->markWalkthrough($request->user(), $assessment, $section);
        $assessment->refresh();

        $payload = $assessment->payload ?? IqaAssessmentSchema::emptyPayload();
        $walked = array_map('intval', $payload['walkthrough'] ?? []);
        $complete = count(array_intersect(range(1, 8), $walked)) === 8;

        return view('qa.assessments.wizard', [
            'assessment' => $assessment,
            'section' => $section,
            'meta' => IqaAssessmentSchema::sectionMeta(),
            'payload' => $payload,
            'mode' => 'publish-review',
            'walked' => $walked,
            'walkthroughComplete' => $complete,
            'canManage' => true,
        ]);
    }

    public function publish(Request $request, IqaAssessment $assessment): RedirectResponse
    {
        $this->iqa->ensureQaOfficer($request->user());

        $request->validate([
            'confirm' => ['accepted'],
        ]);

        try {
            $this->iqa->publish($request->user(), $assessment, true);
        } catch (HttpException $e) {
            return redirect()
                ->route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => 1])
                ->withErrors(['publish' => $e->getMessage()]);
        }

        return redirect()
            ->route('qa.assessments.show', $assessment)
            ->with('status', 'IQA assessment published and locked. Download the PDF for printing.');
    }

    public function pdf(Request $request, IqaAssessment $assessment): StreamedResponse|Response
    {
        $this->iqa->ensureCanView($request->user());

        return $this->iqa->downloadPdf($assessment);
    }
}
