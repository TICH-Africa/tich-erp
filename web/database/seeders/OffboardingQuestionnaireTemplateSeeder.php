<?php

namespace Database\Seeders;

use App\Models\OffboardingQuestionnaireQuestion;
use App\Models\OffboardingQuestionnaireTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OffboardingQuestionnaireTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('offboarding_questionnaire_templates')) {
            $this->command?->warn('Offboarding questionnaire tables missing — run migrations first.');

            return;
        }

        foreach ($this->definitions() as $definition) {
            $template = OffboardingQuestionnaireTemplate::query()->firstOrCreate(
                ['title' => $definition['title']],
                [
                    'description' => $definition['description'],
                    'employment_categories' => $definition['employment_categories'],
                    'is_active' => true,
                ]
            );

            $template->update([
                'description' => $definition['description'],
                'employment_categories' => $definition['employment_categories'],
                'is_active' => true,
            ]);

            if ($template->questions()->exists()) {
                continue;
            }

            foreach ($definition['questions'] as $order => $row) {
                OffboardingQuestionnaireQuestion::query()->create([
                    'template_id' => $template->id,
                    'question_text' => $row['question_text'],
                    'input_type' => $row['input_type'],
                    'options' => $row['options'] ?? null,
                    'is_required' => $row['is_required'] ?? true,
                    'display_order' => $order,
                ]);
            }
        }

        $this->command?->info('Ensured default offboarding questionnaire templates (intern; contract & consultancy).');
    }

    /**
     * @return list<array{title: string, description: string, employment_categories: list<string>, questions: list<array<string, mixed>>}>
     */
    private function definitions(): array
    {
        return [
            [
                'title' => 'Internship exit questionnaire',
                'description' => 'Standard exit feedback for intern placements when the internship period ends.',
                'employment_categories' => ['intern'],
                'questions' => [
                    [
                        'question_text' => 'Which department or programme were you attached to during your internship?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXT,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'How clearly were your learning objectives and expectations explained at the start?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_SELECT,
                        'options' => ['Very clear', 'Mostly clear', 'Somewhat unclear', 'Not clear'],
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'Did you receive adequate supervision and feedback during your placement?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_YES_NO,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'What skills or knowledge did you gain during this internship?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXTAREA,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'What could the institution improve for future interns?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXTAREA,
                        'is_required' => false,
                    ],
                    [
                        'question_text' => 'Would you recommend this internship to other students?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_YES_NO,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'Would you be interested in future employment or engagement with the institution if an opportunity arose?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_SELECT,
                        'options' => ['Yes', 'Maybe', 'No'],
                        'is_required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Contract & consultancy exit questionnaire',
                'description' => 'Exit feedback for fixed-term contract staff and Consultancy/Independent contractor engagements.',
                'employment_categories' => ['contract', 'consultancy'],
                'questions' => [
                    [
                        'question_text' => 'Were all agreed deliverables and handover tasks completed before your last working day?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_YES_NO,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'Briefly describe what was handed over (documents, access, equipment, or institutional knowledge).',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXTAREA,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'How would you rate your working relationship with your line manager or supervisor?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_SELECT,
                        'options' => ['Excellent', 'Good', 'Fair', 'Poor'],
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'Did you receive adequate support from HR and your department during your engagement?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_YES_NO,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'What could we improve in how we onboard and support contract and consultancy staff?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXTAREA,
                        'is_required' => false,
                    ],
                    [
                        'question_text' => 'Would you be open to working with the institution again on a similar engagement?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_YES_NO,
                        'is_required' => true,
                    ],
                    [
                        'question_text' => 'Any additional comments for HR?',
                        'input_type' => OffboardingQuestionnaireQuestion::TYPE_TEXTAREA,
                        'is_required' => false,
                    ],
                ],
            ],
        ];
    }
}