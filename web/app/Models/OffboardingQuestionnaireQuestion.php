<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingQuestionnaireQuestion extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_YES_NO = 'yes_no';

    protected $fillable = [
        'template_id',
        'question_text',
        'input_type',
        'options',
        'is_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OffboardingQuestionnaireTemplate::class, 'template_id');
    }

    /**
     * @return array<string, string>
     */
    public static function inputTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Short text',
            self::TYPE_TEXTAREA => 'Long text',
            self::TYPE_SELECT => 'Multiple choice',
            self::TYPE_YES_NO => 'Yes / No',
        ];
    }
}