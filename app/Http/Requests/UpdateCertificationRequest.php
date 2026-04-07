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
                'min:3',
                'regex:/^[a-z0-9_-]{3,60}$/',
                Rule::unique('certifications', 'slug')->ignore($certificationId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'questions_required' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                function ($attribute, $value, $fail) {
                    $certification = $this->route('certification');
                    if ($certification) {
                        $currentValue = (int) $certification->questions_required;
                        $newValue = (int) $value;

                        // Only enforce availability when required count changes and cert remains active.
                        $willBeActive = $this->has('active')
                            ? $this->boolean('active')
                            : (bool) $certification->active;

                        if ($newValue === $currentValue || ! $willBeActive) {
                            return;
                        }

                        $activeQuestionsCount = $certification->questions()
                            ->where('active', true)
                            ->count();
                        
                        if ($newValue > $activeQuestionsCount) {
                            $fail("Se requieren {$newValue} preguntas pero solo hay {$activeQuestionsCount} preguntas activas.");
                        }
                    }
                },
            ],
            'pass_score_percentage' => ['required', 'numeric', 'between:0,100'],
            'cooldown_days' => [
                'required',
                'integer',
                'min:0',
                'max:1825',
                function ($attribute, $value, $fail) {
                    if ($value > 1825) {
                        $fail('El cooldown no puede ser mayor a 5 años (1825 días).');
                    }
                },
            ],
            'result_mode' => ['required', 'string', Rule::in(['binary_threshold', 'custom', 'generic'])],
            'pdf_view' => ['nullable', 'string', 'max:120'],
            'home_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'settings' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    
                    if (is_string($value)) {
                        try {
                            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                            if (!is_array($decoded)) {
                                $fail('La configuración debe ser un objeto JSON válido.');
                            }
                            // Validate structure if provided
                            $this->validateSettingsStructure($decoded, $fail);
                        } catch (\JsonException $e) {
                            $fail('La configuración contiene JSON inválido: ' . $e->getMessage());
                        }
                    } elseif (is_array($value)) {
                        $this->validateSettingsStructure($value, $fail);
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'El slug debe contener solo letras minúsculas, números, guiones y guiones bajos.',
            'slug.min' => 'El slug debe tener al menos 3 caracteres.',
            'slug.unique' => 'Este slug ya está registrado. Elige uno diferente.',
        ];
    }

    protected function validateSettingsStructure(array $settings, callable $fail): void
    {
        // Allowed settings keys
        $allowedKeys = ['timer_minutes', 'pass_score_percentage', 'result_mode', 'randomize_questions', 'show_answers'];
        
        // Validate each key
        foreach ($settings as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                // Log warning but don't fail - allow flexibility for future expansions
                \Log::warning("Unknown settings key in certification: {$key}");
            }
        }

        // Optional: validate specific field types
        if (isset($settings['timer_minutes']) && !is_numeric($settings['timer_minutes'])) {
            $fail('timer_minutes debe ser un número.');
        }

        if (isset($settings['pass_score_percentage']) && !is_numeric($settings['pass_score_percentage'])) {
            $fail('pass_score_percentage debe ser un número.');
        }

        if (isset($settings['randomize_questions']) && !is_bool($settings['randomize_questions'])) {
            $fail('randomize_questions debe ser booleano.');
        }

        if (isset($settings['show_answers']) && !is_bool($settings['show_answers'])) {
            $fail('show_answers debe ser booleano.');
        }
    }
}
