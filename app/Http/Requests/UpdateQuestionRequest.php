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
                'nullable',
                Rule::exists('certifications', 'slug')->where(fn ($query) => $query->where('active', true)),
            ],
            'certification_id' => [
                'nullable',
                Rule::exists('certifications', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'prompt' => ['required', 'string', 'min:8'],
            'option_1' => ['nullable', 'string', 'min:1'],
            'option_2' => ['nullable', 'string', 'min:1'],
            'option_3' => ['nullable', 'string', 'min:1'],
            'option_4' => ['nullable', 'string', 'min:1'],
            'correct_option' => ['required', 'integer', 'between:1,4'],
            'type' => ['nullable', 'string', 'in:mcq_4,mcq_3,true_false,matching,fill_blank'],
            'explanation' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'image_path' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'is_test_question' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.prompt' => ['nullable', 'string'],
            'translations.*.option_1' => ['nullable', 'string'],
            'translations.*.option_2' => ['nullable', 'string'],
            'translations.*.option_3' => ['nullable', 'string'],
            'translations.*.option_4' => ['nullable', 'string'],
        ];
    }
}
