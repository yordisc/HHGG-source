<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cert_type' => ['required', 'in:social_energy,life_style'],
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
