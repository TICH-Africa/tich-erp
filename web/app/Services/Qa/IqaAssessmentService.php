<?php

namespace App\Services\Qa;

use App\Models\Qa\IqaAssessment;
use App\Models\User;
use App\Services\PrintDocumentService;
use App\Services\RBACService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IqaAssessmentService
{
    public function __construct(
        protected RBACService $rbac,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function isQaOfficer(User $user): bool
    {
        return $this->rbac->hasRole($user, 'QA Officer');
    }

    public function canView(User $user): bool
    {
        return $this->isQaOfficer($user)
            || $this->rbac->hasRole($user, 'Super Admin')
            || $this->rbac->isPlatformAdministrator($user)
            || $this->rbac->hasUnscopedPermission($user, 'qa.read');
    }

    public function ensureCanView(User $user): void
    {
        if (! $this->canView($user)) {
            throw new HttpException(403, 'You do not have access to IQA assessments.');
        }
    }

    public function ensureQaOfficer(User $user): void
    {
        if (! $this->isQaOfficer($user)) {
            throw new HttpException(403, 'Only a QA Officer can create, edit, or publish IQA assessments.');
        }
    }

    public function createDraft(User $user, ?int $assessmentYear = null): IqaAssessment
    {
        $this->ensureQaOfficer($user);

        return IqaAssessment::query()->create([
            'title' => IqaAssessmentSchema::TITLE,
            'assessment_year' => $assessmentYear ?? (int) now()->format('Y'),
            'status' => IqaAssessment::STATUS_DRAFT,
            'current_section' => 1,
            'payload' => IqaAssessmentSchema::emptyPayload(),
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);
    }

    public function saveSection(User $user, IqaAssessment $assessment, int $section, array $input, ?int $nextSection = null): IqaAssessment
    {
        $this->ensureQaOfficer($user);
        abort_unless($assessment->isEditable(), 403, 'Published assessments are locked.');
        abort_unless($section >= 1 && $section <= 8, 404);

        $payload = $assessment->payload ?? IqaAssessmentSchema::emptyPayload();
        if ($section <= 7) {
            $payload = IqaAssessmentSchema::mergeSectionInput($payload, $section, $input);
        }

        $assessment->payload = $payload;
        $assessment->updated_by_user_id = $user->id;
        if ($nextSection !== null && $nextSection >= 1 && $nextSection <= 8) {
            $assessment->current_section = $nextSection;
        } else {
            $assessment->current_section = $section;
        }
        $assessment->save();

        return $assessment->refresh();
    }

    /**
     * Record that the publisher walked through a section during publish review.
     */
    public function markWalkthrough(User $user, IqaAssessment $assessment, int $section): IqaAssessment
    {
        $this->ensureQaOfficer($user);
        abort_unless($assessment->isEditable(), 403, 'Published assessments are locked.');

        $payload = $assessment->payload ?? IqaAssessmentSchema::emptyPayload();
        $walked = array_map('intval', $payload['walkthrough'] ?? []);
        if (! in_array($section, $walked, true)) {
            $walked[] = $section;
            sort($walked);
            $payload['walkthrough'] = $walked;
            $assessment->payload = $payload;
            $assessment->updated_by_user_id = $user->id;
            $assessment->save();
        }

        return $assessment->refresh();
    }

    public function publish(User $user, IqaAssessment $assessment, bool $confirmed = false): IqaAssessment
    {
        $this->ensureQaOfficer($user);
        abort_unless($assessment->isEditable(), 403, 'This assessment is already published.');

        if (! $confirmed) {
            throw new HttpException(422, 'Confirm publication after reviewing all sections.');
        }

        $payload = $assessment->payload ?? IqaAssessmentSchema::emptyPayload();
        $walked = array_map('intval', $payload['walkthrough'] ?? []);
        $required = range(1, 8);
        $missing = array_values(array_diff($required, $walked));
        if ($missing !== []) {
            throw new HttpException(
                422,
                'Walk through all sections (1–8) before publishing. Missing: '.implode(', ', $missing)
            );
        }

        $publisherName = $user->displayName();
        $publishDate = now()->format('Y-m-d');

        $payload['auditors'] = [
            [
                'name' => $publisherName,
                'date' => $publishDate,
                'signature' => '',
            ],
        ];
        $payload['walkthrough'] = $required;

        $assessment->payload = $payload;
        $assessment->status = IqaAssessment::STATUS_PUBLISHED;
        $assessment->published_by_user_id = $user->id;
        $assessment->publisher_name = $publisherName;
        $assessment->published_at = now();
        $assessment->updated_by_user_id = $user->id;
        $assessment->current_section = 8;
        $assessment->save();

        return $assessment->refresh();
    }

    public function downloadPdf(IqaAssessment $assessment): StreamedResponse
    {
        $filename = 'IQA-'.$assessment->id.'-'.($assessment->assessment_year ?: now()->format('Y')).'.pdf';

        return $this->printDocuments->downloadPdf(
            'qa.assessments.pdf',
            [
                'assessment' => $assessment,
                'schema' => IqaAssessmentSchema::class,
                'meta' => IqaAssessmentSchema::sectionMeta(),
                'payload' => $assessment->payload ?? IqaAssessmentSchema::emptyPayload(),
            ],
            $filename,
            'landscape'
        );
    }

    public function inlinePdf(IqaAssessment $assessment): Response
    {
        $filename = 'IQA-'.$assessment->id.'-'.($assessment->assessment_year ?: now()->format('Y')).'.pdf';

        return $this->printDocuments->inlinePdf(
            'qa.assessments.pdf',
            [
                'assessment' => $assessment,
                'schema' => IqaAssessmentSchema::class,
                'meta' => IqaAssessmentSchema::sectionMeta(),
                'payload' => $assessment->payload ?? IqaAssessmentSchema::emptyPayload(),
            ],
            $filename,
            'landscape'
        );
    }
}
