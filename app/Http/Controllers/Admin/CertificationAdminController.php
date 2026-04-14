<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\CertificationDraft;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Support\ActiveAttemptsService;
use App\Support\CertificationValidationService;
use App\Support\CertificationVersioningService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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
            $data = $this->normalizeData($request->validated(), $certification);

            // Detect changes using the model dirty state to avoid array comparison warnings.
            $certification->fill($data);
            $changes = $certification->getDirty();

            // Check if changes are allowed given active attempts
            $attemptsService = app(ActiveAttemptsService::class);
            [$isAllowed, $reason] = $attemptsService->isChangeAllowed($certification, $changes);

            if (!$isAllowed) {
                return redirect()
                    ->route('admin.certifications.edit', $certification)
                    ->withInput()
                    ->with('error', $reason);
            }

            // Perform the update
            $certification->save();

            // Log changes if any
            if (!empty($changes)) {
                try {
                    AuditLog::log('update', 'Certification', $certification->id, $certification->name, $changes);
                } catch (\Exception $e) {
                    // Log audit failures but don't fail the update
                    Log::warning('Failed to create audit log for certification update', ['error' => $e->getMessage()]);
                }
            }

            // If there are active attempts but non-sensitive changes were made, show warning
            $warningMessage = $attemptsService->getWarningMessage($certification);
            if ($warningMessage && !empty($changes)) {
                return redirect()
                    ->route('admin.certifications.edit', $certification)
                    ->with('warning', $warningMessage);
            }
        } catch (\Exception $e) {
            Log::error('Certification update failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
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

    public function reorder(Request $request): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Orden de certificaciones actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', 'Orden de certificaciones actualizado correctamente.');
    }

    public function wizard(Request $request, int $step = 1): View|RedirectResponse
    {
        $step = max(1, min(5, $step));
        $draftSessionId = $this->wizardDraftSessionKey();
        $storedDraftId = $request->session()->get($draftSessionId);

        // Obtener draft existente por sesión o crear uno nuevo
        $draft = null;

        if ($storedDraftId !== null) {
            $draft = CertificationDraft::query()
                ->whereKey((int) $storedDraftId)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();
        }

        // Si no hay draft, crear uno nuevo
        if (!$draft) {
            $draft = CertificationDraft::create($this->newDraftPayload($step));
            $request->session()->put($draftSessionId, $draft->id);
        } else {
            // Actualizar paso actual
            $draft->update(['current_step' => $step]);
            $request->session()->put($draftSessionId, $draft->id);
        }

        return view('admin.certifications.wizard', [
            'step' => $step,
            'draft' => $draft,
            'draftId' => $draft->id,
            'draftSettings' => is_array($draft->settings) ? $draft->settings : [],
            'resultModes' => $this->resultModes(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function wizardStore(Request $request, int $step = 1): RedirectResponse
    {
        $step = max(1, min(5, $step));
        $data = $request->validate($this->wizardRules($step));
        $draftSessionId = $this->wizardDraftSessionKey();
        $sessionDraftId = $request->session()->get($draftSessionId);

        $draftId = $request->input('draft_id');
        $draftQuery = CertificationDraft::query();

        if ($draftId) {
            if ($sessionDraftId === null || (int) $sessionDraftId !== (int) $draftId) {
                abort(403, 'Draft no pertenece a la sesión actual.');
            }

            $draft = (clone $draftQuery)->findOrFail($draftId);
        } elseif ($sessionDraftId !== null) {
            $draft = (clone $draftQuery)->findOrFail((int) $sessionDraftId);
        } else {
            $draft = CertificationDraft::create($this->newDraftPayload($step));
            $request->session()->put($draftSessionId, $draft->id);
        }

        $nextSettings = $this->mergeWizardSettings($draft, $data);

        // Actualizar draft con los nuevos datos
        $draftData = [
            'slug' => $data['slug'] ?? $draft->slug,
            'name' => $data['name'] ?? $draft->name,
            'description' => $data['description'] ?? $draft->description,
            'questions_required' => $data['questions_required'] ?? $draft->questions_required,
            'pass_score_percentage' => $data['pass_score_percentage'] ?? $draft->pass_score_percentage,
            'cooldown_days' => $data['cooldown_days'] ?? $draft->cooldown_days,
            'result_mode' => $data['result_mode'] ?? $draft->result_mode,
            'pdf_view' => $data['pdf_view'] ?? $draft->pdf_view,
            'home_order' => $data['home_order'] ?? $draft->home_order,
            'current_step' => $step,
            'settings' => $nextSettings,
        ];

        $draft->update($draftData);

        // Si es el último paso, crear la certificación
        if ($step === 5) {
            try {
                $certification = DB::transaction(function () use ($draft, $data): Certification {
                    $payload = array_merge(
                        $draft->only([
                            'slug',
                            'name',
                            'description',
                            'questions_required',
                            'pass_score_percentage',
                            'cooldown_days',
                            'result_mode',
                            'pdf_view',
                            'home_order',
                        ]),
                        is_array($draft->settings) ? $draft->settings : [],
                        $data
                    );

                    $certification = Certification::query()->create($this->normalizeData($payload));
                    $draft->delete();

                    return $certification;
                });

                $request->session()->forget($draftSessionId);

                return redirect()
                    ->route('admin.certifications.edit', $certification)
                    ->with('status', '✓ Certificacion creada correctamente desde el asistente.');
            } catch (\Exception $e) {
                Log::error('wizard.create.failed', ['error' => $e->getMessage()]);
                return redirect()
                    ->route('admin.certifications.wizard', ['step' => 5])
                    ->with('error', 'Error al crear certificacion: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.certifications.wizard', ['step' => $step + 1]);
    }

    public function wizardReset(Request $request): RedirectResponse
    {
        $draftId = $request->input('draft_id');
        $draftSessionId = $this->wizardDraftSessionKey();
        $sessionDraftId = $request->session()->get($draftSessionId);

        if ($draftId) {
            if ($sessionDraftId === null || (int) $sessionDraftId !== (int) $draftId) {
                abort(403, 'Draft no pertenece a la sesión actual.');
            }

            // Eliminar un draft específico
            CertificationDraft::query()
                ->findOrFail($draftId)
                ->delete();

            $request->session()->forget($draftSessionId);

            return redirect()
                ->route('admin.certifications.wizard.drafts')
                ->with('status', 'Borrador eliminado correctamente.');
        }

        // Eliminar el draft actual (para reiniciar desde el wizard)
        $draft = $request->session()->has($draftSessionId)
            ? CertificationDraft::query()->find($request->session()->get($draftSessionId))
            : null;

        if ($draft) {
            $draft->delete();
        }

        $request->session()->forget($draftSessionId);

        return redirect()
            ->route('admin.certifications.wizard', ['step' => 1])
            ->with('status', 'El asistente se reinicio correctamente.');
    }

    public function wizardAutoSave(Request $request): JsonResponse
    {
        $draftId = $request->input('draft_id');
        $step = $request->input('step', 1);
        $data = $request->input('data', []);
        $draftSessionId = $this->wizardDraftSessionKey();
        $sessionDraftId = $request->session()->get($draftSessionId);

        try {
            if ($draftId !== null && ($sessionDraftId === null || (int) $sessionDraftId !== (int) $draftId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Draft no pertenece a la sesión actual.',
                ], 403);
            }

            $draft = $draftId
                ? CertificationDraft::query()->findOrFail($draftId)
                : CertificationDraft::query()->findOrFail((int) $sessionDraftId);

            // Actualizar campos directos del draft
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['slug'])) $updateData['slug'] = $data['slug'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['questions_required'])) $updateData['questions_required'] = $data['questions_required'];
            if (isset($data['pass_score_percentage'])) $updateData['pass_score_percentage'] = $data['pass_score_percentage'];
            if (isset($data['cooldown_days'])) $updateData['cooldown_days'] = $data['cooldown_days'];
            if (isset($data['result_mode'])) $updateData['result_mode'] = $data['result_mode'];
            if (isset($data['pdf_view'])) $updateData['pdf_view'] = $data['pdf_view'];
            if (isset($data['home_order'])) $updateData['home_order'] = $data['home_order'];
            if (! isset($updateData['current_step'])) {
                $updateData['current_step'] = (int) $step;
            }

            // Guardar opciones avanzadas dentro de settings del draft
            $settings = is_array($draft->settings) ? $draft->settings : [];
            if (array_key_exists('settings', $data)) {
                $decodedSettings = $this->decodeSettings($data['settings']);
                $settings = is_array($decodedSettings) ? array_merge($settings, $decodedSettings) : $settings;
            }

            foreach ($this->wizardAdvancedSettingKeys() as $key) {
                if (array_key_exists($key, $data)) {
                    $settings[$key] = $data[$key];
                }
            }

            $updateData['settings'] = $settings;

            if (!empty($updateData)) {
                $draft->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Guardado automático completado',
                'draft' => $draft,
                'saved_at' => now()->format('H:i:s'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un borrador activo en esta sesión.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('wizard.autosave.failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function wizardDrafts(): View
    {
        $draft = session()->has($this->wizardDraftSessionKey())
            ? CertificationDraft::query()->find(session()->get($this->wizardDraftSessionKey()))
            : null;

        $drafts = collect($draft ? [$draft] : [])
            ->map(function ($draft) {
                return [
                    'id' => $draft->id,
                    'name' => $draft->name,
                    'slug' => $draft->slug,
                    'step' => $draft->current_step,
                    'progress' => round(($draft->current_step / 5) * 100),
                    'updated_at' => $draft->updated_at->diffForHumans(),
                    'expires_at' => $draft->expires_at,
                    'expired' => $draft->isExpired(),
                ];
            });

        return view('admin.certifications.wizard-drafts', [
            'drafts' => $drafts,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
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
                1 => 'Opcion distractora A ' . $index,
                2 => 'Opcion distractora B ' . $index,
                3 => 'Opcion distractora C ' . $index,
                4 => 'Opcion distractora D ' . $index,
            ];
            $options[$correctOption] = 'Respuesta correcta ' . $index;

            $question = Question::query()->create([
                'certification_id' => $certification->id,
                'prompt' => 'Pregunta de prueba ' . $index . ': selecciona la respuesta correcta.',
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
                    'prompt' => 'Pregunta de prueba ' . $index . ': selecciona la respuesta correcta.',
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

        AuditLog::log('delete', 'Question', $certification->id, $certification->name . ' (test questions)', [
            'count' => $deleted,
            'type' => 'test_questions',
        ]);

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', "Se eliminaron {$deleted} preguntas de prueba.");
    }

    public function revokeCertificate(Request $request, Certification $certification, Certificate $certificate): RedirectResponse
    {
        if ((int) $certificate->certification_id !== (int) $certification->id) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($certificate->revoked_at !== null) {
            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('warning', 'Este certificado ya estaba revocado.');
        }

        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => trim((string) $data['reason']),
        ]);

        AuditLog::log('revoke', 'Certificate', $certificate->id, $certificate->serial, [
            'certification_id' => $certification->id,
            'reason' => $certificate->revoked_reason,
            'revoked_at' => $certificate->revoked_at?->toDateTimeString(),
        ]);

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', 'Certificado revocado correctamente.');
    }

    public function restoreCertificate(Certification $certification, Certificate $certificate): RedirectResponse
    {
        if ((int) $certificate->certification_id !== (int) $certification->id) {
            abort(404);
        }

        if ($certificate->revoked_at === null) {
            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('warning', 'El certificado ya estaba activo.');
        }

        $previousRevokedAt = $certificate->revoked_at?->toDateTimeString();
        $previousReason = $certificate->revoked_reason;

        $certificate->update([
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        AuditLog::log('restore', 'Certificate', $certificate->id, $certificate->serial, [
            'certification_id' => $certification->id,
            'previous_revoked_at' => $previousRevokedAt,
            'previous_reason' => $previousReason,
        ]);

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', 'Certificado restaurado correctamente.');
    }

    private function formViewData(Certification $certification): array
    {
        $diagnostics = $certification->exists
            ? app(CertificationValidationService::class)->review($certification)
            : [];

        $recentCertificates = $certification->exists
            ? $certification->certificates()->latest('issued_at')->latest('id')->limit(8)->get()
            : collect();

        return [
            'certification' => $certification,
            'resultModes' => $this->resultModes(),
            'diagnostics' => $diagnostics,
            'recentCertificates' => $recentCertificates,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ];
    }

    public function duplicate(Certification $certification): RedirectResponse
    {
        try {
            DB::transaction(function () use ($certification): void {
                $newCert = $certification->replicate();
                $newCert->slug = $certification->slug . '_copy_' . uniqid();
                $newCert->name = $certification->name . ' (Copy)';
                $newCert->active = false;
                $newCert->save();

                if ($certification->certificateTemplate) {
                    $template = $certification->certificateTemplate->replicate();
                    $template->certification_id = $newCert->id;
                    $template->save();
                }

                foreach ($certification->questions as $question) {
                    $newQuestion = $question->replicate();
                    $newQuestion->certification_id = $newCert->id;
                    $newQuestion->save();

                    foreach ($question->translations as $translation) {
                        $newTranslation = $translation->replicate();
                        $newTranslation->question_id = $newQuestion->id;
                        $newTranslation->save();
                    }
                }

                AuditLog::log('duplicate', 'Certification', $newCert->id, $newCert->name, [
                    'cloned_from' => $certification->id,
                    'questions_copied' => $certification->questions->count(),
                ]);
            });

            return redirect()
                ->route('admin.certifications.edit', Certification::query()->latest('id')->firstOrFail())
                ->with('status', 'Certificación clonada. Puedes editar el slug y activar cuando esté lista.');
        } catch (\Exception $e) {
            Log::error('admin.certifications.duplicate.failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al clonar la certificación: ' . $e->getMessage());
        }
    }

    public function checkSlug(Request $request)
    {
        $slug = trim($request->query('slug', ''));

        if (empty($slug) || !preg_match('/^[a-z0-9_-]{3,60}$/', $slug)) {
            return response()->json(['available' => false]);
        }

        $exists = Certification::where('slug', $slug)->exists();

        return response()->json(['available' => !$exists]);
    }

    private function normalizeData(array $data, ?Certification $existingCertification = null): array
    {
        $settings = $this->decodeSettings($data['settings'] ?? null);
        $existingSettings = $existingCertification?->settings;
        $existingHomeOrder = $existingCertification?->home_order;
        $existingPdfView = $existingCertification?->pdf_view;

        return [
            'slug' => trim((string) $data['slug']),
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'active' => (bool) ($data['active'] ?? false),
            'questions_required' => (int) $data['questions_required'],
            'pass_score_percentage' => (float) $data['pass_score_percentage'],
            'cooldown_days' => (int) $data['cooldown_days'],
            'result_mode' => trim((string) $data['result_mode']),
            'pdf_view' => trim((string) ($data['pdf_view'] ?? $existingPdfView ?? '')) ?: 'pdf.certificate',
            'home_order' => (int) ($data['home_order'] ?? $existingHomeOrder ?? 999),
            'settings' => array_key_exists('settings', $data)
                ? $settings
                : $existingSettings,
            // Phase 3: Expiry & Retention
            'expiry_mode' => trim((string) ($data['expiry_mode'] ?? 'indefinite')),
            'expiry_days' => isset($data['expiry_days']) ? (int) $data['expiry_days'] : null,
            'allow_certificate_download_after_deactivation' => (bool) ($data['allow_certificate_download_after_deactivation'] ?? true),
            'manual_user_data_purge_enabled' => (bool) ($data['manual_user_data_purge_enabled'] ?? true),
            'require_question_bank_for_activation' => (bool) ($data['require_question_bank_for_activation'] ?? true),
            // Phase 3: Randomization
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? true),
            'shuffle_options' => (bool) ($data['shuffle_options'] ?? true),
            // Phase 3: Auto-rules
            'auto_result_rule_mode' => trim((string) ($data['auto_result_rule_mode'] ?? 'none')),
            'auto_result_rule_config' => $this->decodeAutoRuleConfig($data['auto_result_rule_config'] ?? null),
        ];
    }

    private function decodeAutoRuleConfig(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function decodeSettings(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
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
            \App\Enums\ResultMode::BINARY_THRESHOLD->value => \App\Enums\ResultMode::BINARY_THRESHOLD->value,
            \App\Enums\ResultMode::CUSTOM->value => \App\Enums\ResultMode::CUSTOM->value,
            \App\Enums\ResultMode::GENERIC->value => \App\Enums\ResultMode::GENERIC->value,
        ];
    }

    private function wizardDraftSessionKey(): string
    {
        return 'admin_certification_wizard_draft_id';
    }

    private function newDraftPayload(int $step): array
    {
        return [
            'user_id' => null,
            'current_step' => $step,
            'slug' => 'draft-' . uniqid(),
            'name' => 'Borrador sin título',
            'questions_required' => 30,
            'pass_score_percentage' => 70,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 999,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function showVersions(Certification $certification): View
    {
        $versions = $certification->versions()
            ->latest('version_number')
            ->get();

        return view('admin.certifications.versions', [
            'certification' => $certification,
            'versions' => $versions,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function rollbackVersion(Certification $certification, int $versionId): RedirectResponse
    {
        $version = $certification->versions()->findOrFail($versionId);

        try {
            $service = app(CertificationVersioningService::class);
            $rolledBack = $service->rollbackToVersion($certification, $version);
            if (! $rolledBack) {
                throw new \RuntimeException('Rollback no completado.');
            }

            Log::info('certification.rollback.success', [
                'certification_id' => $certification->id,
                'version_id' => $versionId,
            ]);

            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('status', "Certificación restaurada a la versión {$version->version_number}.");
        } catch (\Exception $e) {
            Log::error('certification.rollback.failed', [
                'certification_id' => $certification->id,
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.certifications.edit', $certification)
                ->with('error', 'Error al restaurar la versión: ' . $e->getMessage());
        }
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
                'auto_result_rule_mode' => ['nullable', 'string', 'in:none,name_rule'],
                'auto_result_rule_config' => ['nullable', 'json'],
            ],
            4 => [
                'pdf_view' => ['nullable', 'string', 'max:120'],
                'home_order' => ['required', 'integer', 'min:0', 'max:9999'],
                'active' => ['nullable', 'boolean'],
                'settings' => ['nullable', 'json'],
                'expiry_mode' => ['nullable', 'string', 'in:indefinite,fixed'],
                'expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
                'allow_certificate_download_after_deactivation' => ['nullable', 'boolean'],
                'manual_user_data_purge_enabled' => ['nullable', 'boolean'],
                'require_question_bank_for_activation' => ['nullable', 'boolean'],
                'shuffle_questions' => ['nullable', 'boolean'],
                'shuffle_options' => ['nullable', 'boolean'],
            ],
            5 => [],
        };
    }

    private function wizardAdvancedSettingKeys(): array
    {
        return [
            'active',
            'expiry_mode',
            'expiry_days',
            'allow_certificate_download_after_deactivation',
            'manual_user_data_purge_enabled',
            'require_question_bank_for_activation',
            'shuffle_questions',
            'shuffle_options',
            'auto_result_rule_mode',
            'auto_result_rule_config',
        ];
    }

    private function mergeWizardSettings(CertificationDraft $draft, array $stepData): array
    {
        $settings = is_array($draft->settings) ? $draft->settings : [];

        if (array_key_exists('settings', $stepData)) {
            $decodedSettings = $this->decodeSettings($stepData['settings']);
            if (is_array($decodedSettings)) {
                $settings = array_merge($settings, $decodedSettings);
            }
        }

        foreach ($this->wizardAdvancedSettingKeys() as $key) {
            if (array_key_exists($key, $stepData)) {
                $settings[$key] = $stepData[$key];
            }
        }

        return $settings;
    }
}
