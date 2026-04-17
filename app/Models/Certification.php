<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'active',
        'questions_required',
        'pass_score_percentage',
        'cooldown_days',
        'result_mode',
        'pdf_view',
        'home_order',
        'settings',
        // Expiry & Retention
        'expiry_mode',
        'expiry_days',
        'allow_certificate_download_after_deactivation',
        'manual_user_data_purge_enabled',
        'require_question_bank_for_activation',
        // Randomization
        'shuffle_questions',
        'shuffle_options',
        // Auto-rules
        'auto_result_rule_mode',
        'auto_result_rule_config',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'pass_score_percentage' => 'decimal:2',
            'settings' => 'array',
            'allow_certificate_download_after_deactivation' => 'boolean',
            'manual_user_data_purge_enabled' => 'boolean',
            'require_question_bank_for_activation' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'auto_result_rule_config' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('home_order')->orderBy('name');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function certificateTemplate(): HasOne
    {
        return $this->hasOne(CertificateTemplate::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CertificationVersion::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(CertificationStatistic::class);
    }

    public function getCurrentVersion(): ?CertificationVersion
    {
        return $this->versions()
            ->orderBy('version_number', 'desc')
            ->first();
    }

    public function getLatestStatistics(int $days = 30): array
    {
        return $this->statistics()
            ->whereDate('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
