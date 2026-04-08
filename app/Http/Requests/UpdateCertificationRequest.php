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
            // Phase 3: Expiry & Retention
            'expiry_mode' => ['nullable', 'string', Rule::in(['indefinite', 'fixed'])],
            'expiry_days' => [
                Rule::requiredIf(fn () => $this->input('expiry_mode') === 'fixed'),
                'nullable',
                'integer',
                'min:1',
                'max:3650',
                function ($attribute, $value, $fail) {
                    if ($this->input('expiry_mode') === 'indefinite' && $value) {
                        $fail('Días de caducidad debe estar vacío cuando el modo es "indefinido".');
                    }
                },
            ],
            'allow_certificate_download_after_deactivation' => ['nullable', 'boolean'],
            'manual_user_data_purge_enabled' => ['nullable', 'boolean'],
            'require_question_bank_for_activation' => ['nullable', 'boolean'],
            // Phase 3: Randomization
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            // Phase 3: Auto-rules
            'auto_result_rule_mode' => ['nullable', 'string', Rule::in(['none', 'name_rule'])],
            'auto_result_rule_config' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }
                    
                    $config = is_string($value) ? json_decode($value, true) : $value;
                    if (!is_array($config)) {
                        $fail('La configuración de reglas debe ser un JSON válido.');
                        return;
                    }

                    if (!isset($config['rules']) || !is_array($config['rules'])) {
                        $fail('La configuración debe contener un array de "rules".');
                        return;
                    }

                    foreach ($config['rules'] as $index => $rule) {
                        if (!isset($rule['decision']) || !in_array($rule['decision'], ['pass', 'fail'])) {
                            $fail("Regla {$index}: la decisión debe ser 'pass' o 'fail'.");
                        }
                        if (!isset($rule['name_pattern']) && !isset($rule['last_name_pattern'])) {
                            $fail("Regla {$index}: debe tener al menos un patrón (nombre o apellido).");
                        }
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
