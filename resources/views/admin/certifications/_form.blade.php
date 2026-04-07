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
