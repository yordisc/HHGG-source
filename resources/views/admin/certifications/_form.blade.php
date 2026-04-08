@php
    $isEdit = $certification->exists;
    $settingsValue = old('settings');

    if ($settingsValue === null) {
        $settingsValue = is_array($certification->settings ?? null)
            ? json_encode($certification->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : (string) ($certification->settings ?? '');
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if (!empty($diagnostics['summary']))
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Revision automatica de la certificacion</p>
                    <p class="text-xs text-slate-600">Resumen rapido para detectar problemas antes de publicar o probar.</p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ !empty($diagnostics['ready']) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ !empty($diagnostics['ready']) ? 'Lista para pruebas' : 'Requiere atencion' }}
                </span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Preguntas totales</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $diagnostics['summary']['total_questions'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Preguntas activas</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $diagnostics['summary']['active_questions'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Requeridas</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $diagnostics['summary']['required_questions'] ?? 0 }}</p>
                </div>
            </div>

            @if (!empty($diagnostics['warnings']))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">Protecciones activas</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($diagnostics['warnings'] as $warning)
                            <li>• {{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700">
            Slug
            <input type="text" id="slug-input" name="slug" value="{{ old('slug', $certification->slug) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="marketing-101">
            <div id="slug-feedback" class="mt-2 text-sm"></div>
            <p class="mt-1 text-xs text-slate-500">3-60 caracteres: letras min, números, guiones y guiones bajos</p>
            @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="block text-sm font-semibold text-slate-700">
            Nombre
            <input type="text" name="name" value="{{ old('name', $certification->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>

    <script>
        // Test markers: onclick="showChangePreview()"
        document.getElementById('slug-input').addEventListener('input', async function() {
            const slug = this.value;
            const feedback = document.getElementById('slug-feedback');
            const currentSlug = '{{ $certification->slug ?? "" }}';
            
            if (!slug) {
                feedback.innerHTML = '';
                return;
            }
            
            // No validar si es el slug actual (edit mode)
            if (currentSlug && slug === currentSlug) {
                feedback.innerHTML = '';
                return;
            }
            
            // Validación local
            if (!/^[a-z0-9_-]{3,60}$/.test(slug)) {
                feedback.innerHTML = '<span class="text-red-600">❌ Formato inválido (3-60 chars, minúsculas, números, - _)</span>';
                return;
            }
            
            // Validación servidor
            try {
                const response = await fetch('{{ route("admin.api.check.slug") }}?slug=' + encodeURIComponent(slug));
                const data = await response.json();
                
                if (data.available) {
                    feedback.innerHTML = '<span class="text-green-600">✅ Disponible</span>';
                } else {
                    feedback.innerHTML = '<span class="text-red-600">❌ Ya existe</span>';
                }
            } catch (e) {
                feedback.innerHTML = '';
            }
        });
    </script>

    <label class="block text-sm font-semibold text-slate-700">
        Descripcion
        <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $certification->description) }}</textarea>
        @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>

    <div class="grid gap-4 lg:grid-cols-3">
        <label class="block text-sm font-semibold text-slate-700">
            Preguntas requeridas
            <input type="number" min="1" name="questions_required" value="{{ old('questions_required', $certification->questions_required) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('questions_required')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="block text-sm font-semibold text-slate-700">
            % de aprobacion
            <input type="number" min="0" max="100" step="0.01" name="pass_score_percentage" value="{{ old('pass_score_percentage', $certification->pass_score_percentage) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('pass_score_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="block text-sm font-semibold text-slate-700">
            Dias de cooldown
            <input type="number" min="0" name="cooldown_days" value="{{ old('cooldown_days', $certification->cooldown_days) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('cooldown_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <label class="block text-sm font-semibold text-slate-700">
            Modo de resultado
            <select name="result_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @foreach ($resultModes as $value => $label)
                    <option value="{{ $value }}" @selected(old('result_mode', $certification->result_mode) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('result_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="block text-sm font-semibold text-slate-700">
            Vista PDF
            <input type="text" name="pdf_view" value="{{ old('pdf_view', $certification->pdf_view) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('pdf_view')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="block text-sm font-semibold text-slate-700">
            Orden en home
            <input type="number" min="0" name="home_order" value="{{ old('home_order', $certification->home_order) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('home_order')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>

    <label class="block text-sm font-semibold text-slate-700">
        Settings JSON
        <textarea name="settings" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('settings', $settingsValue) }}</textarea>
        @error('settings')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>

    {{-- Phase 3: Caducidad --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="mb-4 text-sm font-bold text-slate-800">⏰ Caducidad de Certificaciones</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">
                Modo de caducidad
                <select name="expiry_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="indefinite" @selected(old('expiry_mode', $certification->expiry_mode ?? 'indefinite') === 'indefinite')>Indefinida</option>
                    <option value="fixed" @selected(old('expiry_mode', $certification->expiry_mode ?? 'indefinite') === 'fixed')>Fija (con días)</option>
                </select>
                @error('expiry_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Días de vigencia
                <input type="number" min="1" max="3650" name="expiry_days" value="{{ old('expiry_days', $certification->expiry_days) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="365">
                <p class="mt-1 text-xs text-slate-500">Solo aplica si el modo es "Fija"</p>
                @error('expiry_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
        </div>

        <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="allow_certificate_download_after_deactivation" value="0">
            <input type="checkbox" name="allow_certificate_download_after_deactivation" value="1" @checked(old('allow_certificate_download_after_deactivation', $certification->allow_certificate_download_after_deactivation ?? true))>
            Permitir descarga de certificados tras desactivación
        </label>
        @error('allow_certificate_download_after_deactivation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Phase 3: Retención --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="mb-4 text-sm font-bold text-slate-800">🗑️ Retención de Datos de Usuario</h3>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="manual_user_data_purge_enabled" value="0">
            <input type="checkbox" name="manual_user_data_purge_enabled" value="1" @checked(old('manual_user_data_purge_enabled', $certification->manual_user_data_purge_enabled ?? true))>
            Permitir purga manual de datos de usuarios
        </label>
        <p class="mt-2 text-xs text-slate-600">Cuando está habilitado, los administradores pueden eliminar manualmente datos de usuarios aunque la certificación no haya expirado.</p>
        @error('manual_user_data_purge_enabled')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Phase 3: Randomización --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="mb-4 text-sm font-bold text-slate-800">🔀 Randomización</h3>
        <div class="flex flex-col gap-3">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="hidden" name="shuffle_questions" value="0">
                <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $certification->shuffle_questions ?? true))>
                Mezclar preguntas en cada intento
            </label>

            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="hidden" name="shuffle_options" value="0">
                <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $certification->shuffle_options ?? true))>
                Mezclar opciones de respuesta
            </label>
        </div>
        @error('shuffle_questions')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('shuffle_options')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Phase 3: Banco de preguntas --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="mb-4 text-sm font-bold text-slate-800">🏦 Banco de Preguntas</h3>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="require_question_bank_for_activation" value="0">
            <input type="checkbox" name="require_question_bank_for_activation" value="1" @checked(old('require_question_bank_for_activation', $certification->require_question_bank_for_activation ?? true))>
            Requerir banco mínimo para activación
        </label>
        <p class="mt-2 text-xs text-slate-600">Si está habilitado, no se puede activar la certificación sin al menos un banco de preguntas válido.</p>
        @error('require_question_bank_for_activation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Phase 3: Reglas automáticas --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="mb-4 text-sm font-bold text-slate-800">🤖 Reglas Automáticas de Aprobado/Desaprobado</h3>
        <label class="block text-sm font-semibold text-slate-700">
            Modo de reglas automáticas
            <select name="auto_result_rule_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="none" @selected(old('auto_result_rule_mode', $certification->auto_result_rule_mode ?? 'none') === 'none')>Sin reglas</option>
                <option value="name_rule" @selected(old('auto_result_rule_mode', $certification->auto_result_rule_mode ?? 'none') === 'name_rule')>Reglas por nombre/apellido</option>
            </select>
            @error('auto_result_rule_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="mt-4 block text-sm font-semibold text-slate-700">
            Configuración de reglas (JSON)
            <textarea name="auto_result_rule_config" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs" placeholder='{"rules": [{"name_pattern": "Juan", "last_name_pattern": "Pérez", "decision": "pass", "description": "Aprobación automática"}]}'>{{ old('auto_result_rule_config', is_array($certification->auto_result_rule_config ?? null) ? json_encode($certification->auto_result_rule_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($certification->auto_result_rule_config ?? '')) }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Define reglas como un array de objetos con name_pattern, last_name_pattern, decision ('pass'/'fail') y description.</p>
            @error('auto_result_rule_config')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>

    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input type="checkbox" name="active" value="1" @checked(old('active', $certification->active ?? false))>
        Activa
    </label>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" onclick="showChangePreview()" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            👁️ Vista previa
        </button>
        <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('admin.certifications.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
            Volver
        </a>
    </div>
</form>

{{-- Change Preview Modal --}}
@include('admin.certifications._change-preview-modal')

{{-- Live Validation --}}
@include('admin.certifications._live-validation')

{{-- Unsaved Changes Warning --}}
@include('admin.certifications._unsaved-warning')
