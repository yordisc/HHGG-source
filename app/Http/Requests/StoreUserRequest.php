<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreUserRequest extends UpdateUserRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'email_verified' => ['nullable', 'boolean'],
        ];
    }
}
