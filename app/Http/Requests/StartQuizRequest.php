<?php

namespace App\Http\Requests;

use App\Models\Certification;
use App\Support\CountryDocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $countryCode = strtoupper((string) $this->input('country_code', ''));
        $documentType = strtoupper((string) $this->input('document_type', ''));
        $documentRegex = CountryDocumentService::validationRegex($countryCode, $documentType);

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\'\.]+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\'\.]+$/u'],
            'country_code' => ['required', 'string', Rule::in(CountryDocumentService::countryCodes())],
            'document_type' => ['required', 'string', 'max:30'],
            'document' => ['required', 'string', 'min:5', 'max:30', 'regex:'.$documentRegex],
            'cert_type' => [
                'required',
                Rule::exists('certifications', 'slug')->where(fn ($query) => $query->where('active', true)),
            ],
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
            'document.regex' => __('app.document_format_invalid'),
            'document.min' => __('validation.min.string', ['attribute' => __('app.document'), 'min' => 5]),
            'country_code.required' => __('validation.required', ['attribute' => __('app.country')]),
            'country_code.in' => __('app.country_invalid'),
            'document_type.required' => __('validation.required', ['attribute' => __('app.document_type')]),
            'document_type.max' => __('app.document_type_invalid'),
            'cert_type.required' => __('validation.required', ['attribute' => 'Certificate Type']),
            'cert_type.exists' => __('app.certification_invalid'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $countryCode = strtoupper((string) $this->input('country_code', ''));
            $documentType = strtoupper((string) $this->input('document_type', ''));

            if ($countryCode === '' || $documentType === '') {
                return;
            }

            $certType = (string) $this->input('cert_type', '');
            if ($certType === '' || !Certification::query()->active()->where('slug', $certType)->exists()) {
                $validator->errors()->add('cert_type', __('app.certification_invalid'));
            }

            $allowedDocTypes = array_keys(CountryDocumentService::documentTypes($countryCode, app()->getLocale()));
            if (!in_array($documentType, $allowedDocTypes, true)) {
                $validator->errors()->add('document_type', __('app.document_type_invalid'));
            }
        });
    }
}
