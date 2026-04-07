<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationStatistic extends Model
{
    protected $fillable = [
        'certification_id',
        'date',
        'attempts_count',
        'completions_count',
        'passes_count',
        'failures_count',
        'average_score',
        'average_time_seconds',
        'unique_users',
        'abandonment_count',
    ];

    protected $casts = [
        'date' => 'date',
        'average_score' => 'float',
        'average_time_seconds' => 'float',
    ];

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function getPassRatePercentage(): float
    {
        if ($this->completions_count == 0) {
            return 0;
        }

        return ($this->passes_count / $this->completions_count) * 100;
    }

    public function getCompletionRate(): float
    {
        if ($this->attempts_count == 0) {
            return 0;
        }

        return ($this->completions_count / $this->attempts_count) * 100;
    }
}
