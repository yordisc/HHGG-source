<?php

namespace App\Http\Requests;

use App\Enums\AutoResultRuleMode;
use App\Enums\ResultMode;
use Illuminate\Validation\Rule;

class StoreCertificationRequest extends UpdateCertificationRequest
{
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:60', Rule::unique('certifications', 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'questions_required' => ['required', 'integer', 'min:1', 'max:999'],
            'pass_score_percentage' => ['required', 'numeric', 'between:0,100'],
            'cooldown_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'result_mode' => ['required', 'string', Rule::in(ResultMode::values())],
            'pdf_view' => ['nullable', 'string', 'max:120'],
            'home_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'settings' => ['nullable', 'json'],
            // Phase 3: Expiry & Retention
            'expiry_mode' => ['nullable', 'string', Rule::in(['indefinite', 'fixed'])],
            'expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'allow_certificate_download_after_deactivation' => ['nullable', 'boolean'],
            'manual_user_data_purge_enabled' => ['nullable', 'boolean'],
            'require_question_bank_for_activation' => ['nullable', 'boolean'],
            // Phase 3: Randomization
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            // Phase 3: Auto-rules
            'auto_result_rule_mode' => ['nullable', 'string', Rule::in(AutoResultRuleMode::values())],
            'auto_result_rule_config' => ['nullable', 'json'],
        ];
    }
}
