<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\'\.]+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\'\.]+$/u'],
            'document' => ['required', 'string', 'min:5', 'max:30'],
            'country' => ['required', 'string', 'min:2', 'max:80'],
                'cert_type' => ['required', 'in:hetero,good_girl'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.required', ['attribute' => __('app.first_name')]),
            'first_name.regex' => __('validation.regex', ['attribute' => __('app.first_name')]),
            'last_name.required' => __('validation.required', ['attribute' => __('app.last_name')]),
            'last_name.regex' => __('validation.regex', ['attribute' => __('app.last_name')]),
            'document.required' => __('validation.required', ['attribute' => __('app.document')]),
            'document.min' => __('validation.min.string', ['attribute' => __('app.document'), 'min' => 5]),
            'country.required' => __('validation.required', ['attribute' => __('app.country')]),
            'cert_type.required' => __('validation.required', ['attribute' => 'Certificate Type']),
            'cert_type.in' => __('validation.in', ['attribute' => 'Certificate Type']),
        ];
    }
}
