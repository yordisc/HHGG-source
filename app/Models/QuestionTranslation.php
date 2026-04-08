<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'language',
        'locale',
        'prompt',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
    ];

    public function setLocaleAttribute(?string $value): void
    {
        $this->attributes['language'] = $value;
    }

    public function getLocaleAttribute(): ?string
    {
        return $this->attributes['language'] ?? null;
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
