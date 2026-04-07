<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'certification_id',
        'prompt',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'correct_option',
        'active',
        'is_test_question',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_test_question' => 'boolean',
        ];
    }

    public function scopeTestQuestions($query)
    {
        return $query->where('is_test_question', true);
    }

    public function scopeRealQuestions($query)
    {
        return $query->where('is_test_question', false);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionTranslation::class);
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }
}
