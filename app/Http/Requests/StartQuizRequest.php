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
            'first_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\']+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\-\']+$/u'],
            'document' => ['required', 'string', 'min:5', 'max:20', 'alpha_num'],
            'country' => ['required', 'string', 'min:2', 'max:80'],
            'cert_type' => ['required', 'in:social_energy,life_style'],
        ];
    }
}
