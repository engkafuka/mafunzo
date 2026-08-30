<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'question_set_id',
        'sort_order',
        'category',
        'question_text',
        'max_mark',
        'weight',
        'rubric',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_mark' => 'integer',
            'weight' => 'decimal:2',
        ];
    }

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestionSet::class, 'question_set_id');
    }

    public function categoryLabel(): string
    {
        return config('interview.question_categories')[$this->category] ?? $this->category;
    }
}
