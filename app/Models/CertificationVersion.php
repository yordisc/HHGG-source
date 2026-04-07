<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificationVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'certification_id',
        'version_number',
        'snapshot',
        'questions_snapshot',
        'change_reason',
        'changes',
    ];

    protected $casts = [
        'snapshot' => 'json',
        'questions_snapshot' => 'json',
        'changes' => 'json',
    ];

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function getChangeSummary(): array
    {
        return $this->changes ?? [];
    }

    public function getChangedFields(): array
    {
        return array_keys($this->changes ?? []);
    }
}
