<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $certificationId = $this->route('certification')?->id;

        return [
            'slug' => [
                'required',
                'string',
                'max:60',
                Rule::unique('certifications', 'slug')->ignore($certificationId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'questions_required' => ['required', 'integer', 'min:1', 'max:999'],
            'pass_score_percentage' => ['required', 'numeric', 'between:0,100'],
            'cooldown_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'result_mode' => ['required', 'string', Rule::in(['binary_threshold', 'custom', 'generic'])],
            'pdf_view' => ['nullable', 'string', 'max:120'],
            'home_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'settings' => ['nullable', 'json'],
        ];
    }
}
