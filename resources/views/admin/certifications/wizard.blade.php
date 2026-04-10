@extends('layouts.app')

@php
    $stepTitles = [
        1 => 'Datos básicos',
        2 => 'Examen',
        3 => 'Reglas',
        4 => 'Presentación y configuración',
        5 => 'Revisión final',
    ];

    $settingsValue = old('settings');
    if ($settingsValue === null) {
        $settingsValue = is_array($draftSettings ?? null)
            ? json_encode($draftSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '';
    }

    $setting = static function (string $key, mixed $fallback = null) use ($draftSettings) {
        return old($key, $draftSettings[$key] ?? $fallback);
    };
@endphp

@section('content')
    <section class="mx-auto max-w-5xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Asistente de certificaciones</h1>
                <p class="mt-1 text-sm text-slate-600">Paso {{ $step }} de 5 - {{ $stepTitles[$step] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.certifications.wizard.drafts') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Mis borradores</a>
                <a href="{{ route('admin.certifications.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Volver</a>
                <form method="POST" action="{{ route('admin.certifications.wizard.reset') }}">
                    @csrf
                    <input type="hidden" name="draft_id" value="{{ $draftId }}">
                    <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">Reiniciar</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 grid gap-2 sm:grid-cols-5">
            @foreach ($stepTitles as $number => $title)
                <div class="rounded-2xl border px-4 py-3 text-sm font-semibold {{ $number === $step ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    {{ $number }}. {{ $title }}
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.certifications.wizard.store', ['step' => $step]) }}" class="space-y-6" id="wizardForm">
            @csrf
            <input type="hidden" name="draft_id" value="{{ $draftId }}">
            <div id="autoSaveStatus" class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700 hidden">
                Guardado automático: <span id="autoSaveTime">ahora</span>
            </div>

            @if ($step === 1)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Slug
                        <input type="text" name="slug" value="{{ old('slug', $draft->slug ?? '') }}" data-autosave="slug" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="mi-certificación">
                        @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Nombre
                        <input type="text" name="name" value="{{ old('name', $draft->name ?? '') }}" data-autosave="name" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <label class="block text-sm font-semibold text-slate-700">
                    Descripción
                    <textarea name="description" rows="4" data-autosave="description" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $draft->description ?? '') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
            @endif

            @if ($step === 2)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Preguntas requeridas
                        <input type="number" min="1" name="questions_required" value="{{ old('questions_required', $draft->questions_required ?? 30) }}" data-autosave="questions_required" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('questions_required')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Porcentaje de aprobación
                        <input type="number" min="0" max="100" step="0.01" name="pass_score_percentage" value="{{ old('pass_score_percentage', $draft->pass_score_percentage ?? 66.67) }}" data-autosave="pass_score_percentage" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('pass_score_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            @endif

            @if ($step === 3)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Días de cooldown
                        <input type="number" min="0" name="cooldown_days" value="{{ old('cooldown_days', $draft->cooldown_days ?? 30) }}" data-autosave="cooldown_days" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('cooldown_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Modo de resultado
                        <select name="result_mode" data-autosave="result_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @foreach ($resultModes as $value => $label)
                                <option value="{{ $value }}" @selected(old('result_mode', $draft->result_mode ?? 'binary_threshold') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">binary_threshold: aprueba por porcentaje. custom: usa reglas y JSON. generic: resultado simple.</p>
                        @error('result_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900">
                    <p class="font-semibold">Cómo elegir el modo de resultado</p>
                    <ul class="mt-2 space-y-1">
                        <li>1) Usa <strong>binary_threshold</strong> si solo necesitas porcentaje de aprobación.</li>
                        <li>2) Usa <strong>custom</strong> si necesitas reglas especiales por datos del usuario.</li>
                        <li>3) Usa <strong>generic</strong> si deseas un resultado sin lógica avanzada.</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800">Reglas automáticas</h3>
                    <label class="block text-sm font-semibold text-slate-700">
                        Modo de reglas automáticas
                        <select name="auto_result_rule_mode" data-autosave="auto_result_rule_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            <option value="none" @selected($setting('auto_result_rule_mode', 'none') === 'none')>Sin reglas</option>
                            <option value="name_rule" @selected($setting('auto_result_rule_mode', 'none') === 'name_rule')>Reglas por nombre/apellido</option>
                        </select>
                        @error('auto_result_rule_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>

                    <label class="block text-sm font-semibold text-slate-700">
                        Configuración de reglas (JSON)
                        <textarea name="auto_result_rule_config" rows="5" data-autosave="auto_result_rule_config" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs" placeholder='{"rules": [{"name_pattern": "Juan", "last_name_pattern": "Pérez", "decision": "pass", "description": "Aprobación automática"}]}'>{{ old('auto_result_rule_config', is_array($setting('auto_result_rule_config')) ? json_encode($setting('auto_result_rule_config'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $setting('auto_result_rule_config', '')) }}</textarea>
                        @error('auto_result_rule_config')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            @endif

            @if ($step === 4)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Vista PDF
                        <input type="text" name="pdf_view" value="{{ old('pdf_view', $draft->pdf_view ?? 'pdf.certificate') }}" data-autosave="pdf_view" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <p class="mt-1 text-xs text-slate-500">Sección de presentación: define la vista Blade que renderiza el certificado PDF.</p>
                        @error('pdf_view')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Orden en home
                        <input type="number" min="0" name="home_order" value="{{ old('home_order', $draft->home_order ?? 100) }}" data-autosave="home_order" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('home_order')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Modo de caducidad
                        <select name="expiry_mode" data-autosave="expiry_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            <option value="indefinite" @selected($setting('expiry_mode', 'indefinite') === 'indefinite')>Indefinida</option>
                            <option value="fixed" @selected($setting('expiry_mode', 'indefinite') === 'fixed')>Fija (días)</option>
                        </select>
                        @error('expiry_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Días de vigencia
                        <input type="number" min="1" max="3650" name="expiry_days" value="{{ $setting('expiry_days', '') }}" data-autosave="expiry_days" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="365">
                        @error('expiry_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="allow_certificate_download_after_deactivation" value="0">
                        <input type="checkbox" name="allow_certificate_download_after_deactivation" value="1" data-autosave="allow_certificate_download_after_deactivation" @checked((bool) $setting('allow_certificate_download_after_deactivation', true))>
                        Permitir descarga de certificado tras desactivar
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="manual_user_data_purge_enabled" value="0">
                        <input type="checkbox" name="manual_user_data_purge_enabled" value="1" data-autosave="manual_user_data_purge_enabled" @checked((bool) $setting('manual_user_data_purge_enabled', true))>
                        Permitir purga manual de datos de usuario
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="require_question_bank_for_activation" value="0">
                        <input type="checkbox" name="require_question_bank_for_activation" value="1" data-autosave="require_question_bank_for_activation" @checked((bool) $setting('require_question_bank_for_activation', true))>
                        Requerir banco de preguntas para activar
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="shuffle_questions" value="0">
                        <input type="checkbox" name="shuffle_questions" value="1" data-autosave="shuffle_questions" @checked((bool) $setting('shuffle_questions', true))>
                        Mezclar preguntas
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="shuffle_options" value="0">
                        <input type="checkbox" name="shuffle_options" value="1" data-autosave="shuffle_options" @checked((bool) $setting('shuffle_options', true))>
                        Mezclar opciones
                    </label>
                </div>

                <label class="block text-sm font-semibold text-slate-700">
                    Settings JSON
                    <textarea name="settings" rows="6" data-autosave="settings" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs" placeholder='{"theme":"default","show_score":true}'>{{ $settingsValue }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Opcional. Se fusiona con los campos avanzados del asistente. Si no usas JSON, déjalo vacío.</p>
                    @error('settings')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                    <p class="font-semibold">Qué incluye Presentación y configuración</p>
                    <ul class="mt-2 space-y-1">
                        <li>1) Vista PDF y orden en home.</li>
                        <li>2) Estado activa/inactiva al crear.</li>
                        <li>3) Caducidad, randomización y reglas de seguridad.</li>
                        <li>4) Configuración avanzada en JSON.</li>
                    </ul>
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" data-autosave="active" @checked((bool) $setting('active', true))>
                    Activa desde el inicio
                </label>
            @endif

            @if ($step === 5)
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700 sm:grid-cols-2">
                    <div><strong>Slug:</strong> {{ $draft->slug ?? 'N/D' }}</div>
                    <div><strong>Nombre:</strong> {{ $draft->name ?? 'N/D' }}</div>
                    <div><strong>Preguntas requeridas:</strong> {{ $draft->questions_required ?? 30 }}</div>
                    <div><strong>Aprobación:</strong> {{ $draft->pass_score_percentage ?? 66.67 }}%</div>
                    <div><strong>Cooldown:</strong> {{ $draft->cooldown_days ?? 30 }}</div>
                    <div><strong>Modo:</strong> {{ $draft->result_mode ?? 'binary_threshold' }}</div>
                    <div><strong>PDF:</strong> {{ $draft->pdf_view ?? 'pdf.certificate' }}</div>
                    <div><strong>Orden:</strong> {{ $draft->home_order ?? 100 }}</div>
                    <div><strong>Activa:</strong> {{ (bool) ($draftSettings['active'] ?? true) ? 'Si' : 'No' }}</div>
                    <div><strong>Caducidad:</strong> {{ $draftSettings['expiry_mode'] ?? 'indefinite' }}</div>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    @if ($step > 1)
                        <a href="{{ route('admin.certifications.wizard', ['step' => $step - 1]) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Anterior</a>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if ($step < 5)
                        <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">Siguiente</button>
                    @else
                        <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">Crear certificación</button>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <script>
        const draftId = '{{ $draftId }}';
        let autoSaveTimeout;
        const statusEl = document.getElementById('autoSaveStatus');
        const timeEl = document.getElementById('autoSaveTime');

        document.querySelectorAll('[data-autosave]').forEach((input) => {
            input.addEventListener('change', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(performAutoSave, 500);
            });
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(performAutoSave, 500);
            });
        });

        function performAutoSave() {
            const data = {};

            document.querySelectorAll('[data-autosave]').forEach((input) => {
                const fieldName = input.getAttribute('data-autosave');
                const fieldValue = input.type === 'checkbox' ? input.checked : input.value;
                data[fieldName] = fieldValue;
            });

            fetch('{{ route('admin.certifications.wizard.autosave') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({
                    draft_id: draftId,
                    step: {{ $step }},
                    data,
                }),
            })
                .then((response) => response.json())
                .then((result) => {
                    if (result.success) {
                        statusEl.classList.remove('hidden');
                        timeEl.textContent = result.saved_at;
                        setTimeout(() => statusEl.classList.add('hidden'), 2500);
                    }
                })
                .catch(() => {});
        }
    </script>
@endsection
