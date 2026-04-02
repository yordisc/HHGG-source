<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cert_type' => [
                'required',
                Rule::exists('certifications', 'slug')->where(fn ($query) => $query->where('active', true)),
            ],
            'prompt' => ['required', 'string', 'min:8'],
            'option_1' => ['required', 'string', 'min:1'],
            'option_2' => ['required', 'string', 'min:1'],
            'option_3' => ['required', 'string', 'min:1'],
            'option_4' => ['required', 'string', 'min:1'],
            'correct_option' => ['required', 'integer', 'between:1,4'],
            'active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.prompt' => ['nullable', 'string'],
            'translations.*.option_1' => ['nullable', 'string'],
            'translations.*.option_2' => ['nullable', 'string'],
            'translations.*.option_3' => ['nullable', 'string'],
            'translations.*.option_4' => ['nullable', 'string'],
        ];
    }
}
