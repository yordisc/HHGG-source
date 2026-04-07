<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Models\AuditLog;
use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Support\CertificationValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CertificationAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.certifications.index', [
            'certifications' => Certification::query()->ordered()->paginate(20),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        return redirect()->route('admin.certifications.wizard', ['step' => 1]);
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        $data = $this->normalizeData($request->validated());
        $certification = Certification::query()->create($data);

        AuditLog::log('create', 'Certification', $certification->id, $certification->name, $data);

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', 'Certificacion creada correctamente.');
    }

    public function edit(Certification $certification): View
    {
        return view('admin.certifications.edit', $this->formViewData($certification));
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): RedirectResponse
    {
        try {
            $data = $this->normalizeData($request->validated());
            
            // Get old values before update
            $oldValues = $certification->toArray();
            
            // Perform the update
            $certification->update($data);

            // Log changes if any
            $changes = array_diff_assoc($data, $oldValues);
            if (!empty($changes)) {
                try {
                    AuditLog::log('update', 'Certification', $certification->id, $oldValues['name'] ?? $certification->name, $changes);
                } catch (\Exception $e) {
                    // Log audit failures but don't fail the update
                    \Log::warning('Failed to create audit log for certification update', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Certification update failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('error', 'Error al actualizar: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', 'Certificacion actualizada correctamente.');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $name = $certification->name;
        $id = $certification->id;
        $certification->delete();

        AuditLog::log('delete', 'Certification', $id, $name);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', 'Certificacion eliminada correctamente.');
    }

    public function toggle(Certification $certification): RedirectResponse
    {
        $certification->update([
            'active' => ! $certification->active,
        ]);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', $certification->active ? 'Certificacion activada correctamente.' : 'Certificacion desactivada correctamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'certifications' => ['required', 'array', 'min:1'],
            'certifications.*' => ['integer', 'distinct', 'exists:certifications,id'],
        ]);

        $certificationIds = array_values(array_map('intval', $data['certifications']));

        DB::transaction(function () use ($certificationIds): void {
            foreach ($certificationIds as $index => $certificationId) {
                Certification::query()
                    ->whereKey($certificationId)
                    ->update(['home_order' => $index + 1]);
            }
        });

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', 'Orden de certificaciones actualizado correctamente.');
    }

    public function wizard(Request $request, int $step = 1): View|RedirectResponse
    {
        $step = max(1, min(5, $step));
        $draft = session('admin.certification_wizard', []);

        return view('admin.certifications.wizard', [
            'step' => $step,
            'draft' => is_array($draft) ? $draft : [],
            'resultModes' => $this->resultModes(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function wizardStore(Request $request, int $step = 1): RedirectResponse
    {
        $step = max(1, min(5, $step));
        $draft = session('admin.certification_wizard', []);
        $data = $request->validate($this->wizardRules($step));

        if ($step === 5) {
            $requiredDraftKeys = ['slug', 'name', 'questions_required', 'pass_score_percentage', 'cooldown_days', 'result_mode', 'home_order'];

            foreach ($requiredDraftKeys as $requiredDraftKey) {
                if (! array_key_exists($requiredDraftKey, $draft)) {
                    session()->forget('admin.certification_wizard');

                    return redirect()
                        ->route('admin.certifications.wizard', ['step' => 1])
                        ->with('status', 'El asistente perdio datos. Vuelve a completar los pasos.');
                }
            }

            $payload = array_merge($draft, $data);
            $certification = Certification::query()->create($this->normalizeData($payload));

            session()->forget('admin.certification_wizard');

            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('status', 'Certificacion creada correctamente desde el asistente.');
        }

        session(['admin.certification_wizard' => array_merge($draft, $data)]);

        return redirect()->route('admin.certifications.wizard', ['step' => $step + 1]);
    }

    public function wizardReset(): RedirectResponse
    {
        session()->forget('admin.certification_wizard');

        return redirect()
            ->route('admin.certifications.wizard', ['step' => 1])
            ->with('status', 'El asistente se reinicio correctamente.');
    }

    public function test(Certification $certification): View
    {
        $certification->loadMissing(['questions.translations']);
        $diagnostics = app(CertificationValidationService::class)->review($certification);

        $sampleQuestions = $certification->questions
            ->where('active', true)
            ->shuffle()
            ->take(3)
            ->map(function (Question $question): array {
                $translation = $question->translations->firstWhere('language', 'es')
                    ?? $question->translations->first();

                return [
                    'id' => $question->id,
                    'prompt' => $translation?->prompt ?? $question->prompt,
                    'options' => [
                        $translation?->option_1 ?? $question->option_1,
                        $translation?->option_2 ?? $question->option_2,
                        $translation?->option_3 ?? $question->option_3,
                        $translation?->option_4 ?? $question->option_4,
                    ],
                ];
            })
            ->values();

        return view('admin.certifications.test', [
            'certification' => $certification,
            'diagnostics' => $diagnostics,
            'sampleQuestions' => $sampleQuestions,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function generateTestQuestions(Certification $certification, Request $request): RedirectResponse
    {
        $count = max(1, min(20, (int) $request->input('count', 5)));

        $created = 0;
        for ($index = 1; $index <= $count; $index++) {
            $correctOption = random_int(1, 4);
            $options = [
                1 => 'Opcion distractora A '.$index,
                2 => 'Opcion distractora B '.$index,
                3 => 'Opcion distractora C '.$index,
                4 => 'Opcion distractora D '.$index,
            ];
            $options[$correctOption] = 'Respuesta correcta '.$index;

            $question = Question::query()->create([
                'certification_id' => $certification->id,
                'prompt' => 'Pregunta de prueba '.$index.': selecciona la respuesta correcta.',
                'option_1' => $options[1],
                'option_2' => $options[2],
                'option_3' => $options[3],
                'option_4' => $options[4],
                'correct_option' => $correctOption,
                'active' => true,
                'is_test_question' => true,
            ]);

            QuestionTranslation::query()->updateOrCreate(
                [
                    'question_id' => $question->id,
                    'language' => 'es',
                ],
                [
                    'prompt' => 'Pregunta de prueba '.$index.': selecciona la respuesta correcta.',
                    'option_1' => $options[1],
                    'option_2' => $options[2],
                    'option_3' => $options[3],
                    'option_4' => $options[4],
                ]
            );

            $created++;
        }

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', "Se agregaron {$created} preguntas de prueba.");
    }

    public function clearTestQuestions(Certification $certification): RedirectResponse
    {
        $deleted = Question::query()
            ->where('certification_id', $certification->id)
            ->where('is_test_question', true)
            ->count();

        Question::query()
            ->where('certification_id', $certification->id)
            ->where('is_test_question', true)
            ->delete();

        AuditLog::log('delete', 'Question', $certification->id, $certification->name.' (test questions)', [
            'count' => $deleted,
            'type' => 'test_questions',
        ]);

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', "Se eliminaron {$deleted} preguntas de prueba.");
    }

    private function formViewData(Certification $certification): array
    {
        $diagnostics = $certification->exists
            ? app(CertificationValidationService::class)->review($certification)
            : [];

        return [
            'certification' => $certification,
            'resultModes' => $this->resultModes(),
            'diagnostics' => $diagnostics,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ];
    }

    private function normalizeData(array $data): array
    {
        $settings = $this->decodeSettings($data['settings'] ?? null);

        return [
            'slug' => trim((string) $data['slug']),
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'active' => (bool) ($data['active'] ?? false),
            'questions_required' => (int) $data['questions_required'],
            'pass_score_percentage' => (float) $data['pass_score_percentage'],
            'cooldown_days' => (int) $data['cooldown_days'],
            'result_mode' => trim((string) $data['result_mode']),
            'pdf_view' => trim((string) ($data['pdf_view'] ?? '')) ?: 'pdf.certificate',
            'home_order' => (int) $data['home_order'],
            'settings' => $settings,
        ];
    }

    private function decodeSettings(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function resultModes(): array
    {
        return [
            'binary_threshold' => 'binary_threshold',
            'custom' => 'custom',
            'generic' => 'generic',
        ];
    }

    private function wizardRules(int $step): array
    {
        return match ($step) {
            1 => [
                'slug' => ['required', 'string', 'max:60'],
                'name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:1000'],
            ],
            2 => [
                'questions_required' => ['required', 'integer', 'min:1', 'max:999'],
                'pass_score_percentage' => ['required', 'numeric', 'between:0,100'],
            ],
            3 => [
                'cooldown_days' => ['required', 'integer', 'min:0', 'max:3650'],
                'result_mode' => ['required', 'string', 'in:binary_threshold,custom,generic'],
            ],
            4 => [
                'pdf_view' => ['nullable', 'string', 'max:120'],
                'home_order' => ['required', 'integer', 'min:0', 'max:9999'],
                'active' => ['nullable', 'boolean'],
                'settings' => ['nullable', 'json'],
            ],
            5 => [],
        };
    }
}
