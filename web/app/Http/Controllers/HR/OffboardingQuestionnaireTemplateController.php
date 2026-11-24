<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\OffboardingQuestionnaireQuestion;
use App\Models\OffboardingQuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OffboardingQuestionnaireTemplateController extends Controller
{
    public function index(): View
    {
        $templates = OffboardingQuestionnaireTemplate::query()
            ->withCount('questions')
            ->orderByDesc('updated_at')
            ->paginate(20);

        $categoryLabels = config('tich-payroll.employment_categories', []);

        return view('hr.offboarding.questionnaires.templates.index', [
            'templates' => $templates,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function create(): View
    {
        return view('hr.offboarding.questionnaires.templates.form', [
            'template' => new OffboardingQuestionnaireTemplate(['is_active' => true]),
            'categoryLabels' => config('tich-payroll.employment_categories', []),
            'assignable' => OffboardingQuestionnaireTemplate::assignableEmploymentCategories(),
            'inputTypes' => OffboardingQuestionnaireQuestion::inputTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedTemplate($request);
        $template = OffboardingQuestionnaireTemplate::query()->create([
            ...$validated,
            'created_by_user_id' => $request->user()?->id,
        ]);

        $this->syncQuestions($template, $request->input('questions', []));

        if ($template->questions()->count() < 1) {
            return back()->withErrors(['questions' => 'Add at least one question.'])->withInput();
        }

        return redirect()
            ->route('hr.offboarding.questionnaires.templates.edit', $template)
            ->with('success', 'Questionnaire template created.');
    }

    public function edit(OffboardingQuestionnaireTemplate $template): View
    {
        $template->load('questions');

        return view('hr.offboarding.questionnaires.templates.form', [
            'template' => $template,
            'categoryLabels' => config('tich-payroll.employment_categories', []),
            'assignable' => OffboardingQuestionnaireTemplate::assignableEmploymentCategories(),
            'inputTypes' => OffboardingQuestionnaireQuestion::inputTypes(),
        ]);
    }

    public function update(Request $request, OffboardingQuestionnaireTemplate $template): RedirectResponse
    {
        $template->update($this->validatedTemplate($request));
        $this->syncQuestions($template, $request->input('questions', []));

        if ($template->questions()->count() < 1) {
            return back()->withErrors(['questions' => 'Add at least one question.'])->withInput();
        }

        return back()->with('success', 'Template saved.');
    }

    public function destroy(OffboardingQuestionnaireTemplate $template): RedirectResponse
    {
        if ($template->invitations()->exists()) {
            return back()->with('error', 'Cannot delete a template that has already been sent to staff.');
        }

        $template->delete();

        return redirect()
            ->route('hr.offboarding.questionnaires.templates.index')
            ->with('success', 'Template deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTemplate(Request $request): array
    {
        $allowed = OffboardingQuestionnaireTemplate::assignableEmploymentCategories();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'employment_categories' => ['required', 'array', 'min:1'],
            'employment_categories.*' => ['required', 'string', 'in:'.implode(',', $allowed)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['employment_categories'] = array_values(array_unique($validated['employment_categories']));

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncQuestions(OffboardingQuestionnaireTemplate $template, array $rows): void
    {
        $kept = [];

        foreach (array_values($rows) as $index => $row) {
            $text = trim((string) ($row['question_text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $inputType = $row['input_type'] ?? OffboardingQuestionnaireQuestion::TYPE_TEXTAREA;
            if (! array_key_exists($inputType, OffboardingQuestionnaireQuestion::inputTypes())) {
                $inputType = OffboardingQuestionnaireQuestion::TYPE_TEXTAREA;
            }

            $options = null;
            if ($inputType === OffboardingQuestionnaireQuestion::TYPE_SELECT) {
                $options = array_values(array_filter(array_map(
                    'trim',
                    preg_split('/\r\n|\r|\n/', (string) ($row['options_text'] ?? '')) ?: []
                )));
            }

            $payload = [
                'question_text' => $text,
                'input_type' => $inputType,
                'options' => $options ?: null,
                'is_required' => ! empty($row['is_required']),
                'display_order' => $index,
            ];

            if (! empty($row['id'])) {
                $question = OffboardingQuestionnaireQuestion::query()
                    ->where('template_id', $template->id)
                    ->whereKey($row['id'])
                    ->first();
                $question?->update($payload);
                if ($question) {
                    $kept[] = $question->id;
                }
            } else {
                $kept[] = OffboardingQuestionnaireQuestion::query()->create([
                    ...$payload,
                    'template_id' => $template->id,
                ])->id;
            }
        }

        if ($kept !== []) {
            OffboardingQuestionnaireQuestion::query()
                ->where('template_id', $template->id)
                ->whereNotIn('id', $kept)
                ->delete();
        } else {
            OffboardingQuestionnaireQuestion::query()
                ->where('template_id', $template->id)
                ->delete();
        }
    }
}