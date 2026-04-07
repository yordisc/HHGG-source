<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationDraft extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'questions_required',
        'pass_score_percentage',
        'cooldown_days',
        'result_mode',
        'pdf_view',
        'home_order',
        'settings',
        'current_step',
        'expires_at',
    ];

    protected $casts = [
        'settings' => 'json',
        'pass_score_percentage' => 'float',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getProgressPercentage(): int
    {
        return ($this->current_step / 5) * 100;
    }
}
