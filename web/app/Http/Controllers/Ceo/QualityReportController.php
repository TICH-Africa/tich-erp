<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Qa\IqaAssessment;
use App\Services\Qa\IqaAssessmentSchema;
use App\Services\Qa\IqaAssessmentService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityReportController extends Controller
{
    public function __construct(protected IqaAssessmentService $iqa) {}

    public function index(): View
    {
        $assessments = Schema::hasTable('iqa_assessments')
            ? IqaAssessment::query()
                ->where('status', IqaAssessment::STATUS_PUBLISHED)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(15)
            : collect();

        return view('ceo.quality.index', compact('assessments'));
    }

    public function show(IqaAssessment $assessment): View
    {
        abort_unless($assessment->isPublished(), 404);

        return view('ceo.quality.show', [
            'assessment' => $assessment,
            'meta' => IqaAssessmentSchema::sectionMeta(),
            'payload' => $this->iqa->payloadFor($assessment),
        ]);
    }

    public function pdf(IqaAssessment $assessment): StreamedResponse|Response
    {
        abort_unless($assessment->isPublished(), 404);

        return $this->iqa->downloadPdf($assessment);
    }
}
