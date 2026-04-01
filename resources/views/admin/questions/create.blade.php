@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Nueva Pregunta</h1>
                <p class="mt-1 text-sm text-slate-600">Crea pregunta base y traducciones opcionales.</p>
            </div>
            <a href="{{ route('admin.questions.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Volver al listado
            </a>
        </div>

        <form method="POST" action="{{ route('admin.questions.store') }}" class="mt-6 space-y-6">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-slate-700">Tipo</label>
                    <select name="cert_type" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <option value="hetero" @selected(old('cert_type') === 'hetero')>hetero</option>
                        <option value="good_girl" @selected(old('cert_type') === 'good_girl')>good_girl</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                        Activa
                    </label>
                </div>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-bold text-slate-800">Base (fallback)</h2>
                <div>
                    <label class="text-xs font-semibold text-slate-700">Pregunta</label>
                    <textarea name="prompt" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">{{ old('prompt') }}</textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="option_1" value="{{ old('option_1') }}" placeholder="Opcion 1" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_2" value="{{ old('option_2') }}" placeholder="Opcion 2" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_3" value="{{ old('option_3') }}" placeholder="Opcion 3" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_4" value="{{ old('option_4') }}" placeholder="Opcion 4" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700">Respuesta correcta (1-4)</label>
                    <input name="correct_option" type="number" min="1" max="4" value="{{ old('correct_option', 1) }}" class="mt-1 w-28 rounded-xl border border-slate-300 px-4 py-2 text-sm">
                </div>
            </div>

            @foreach ($supportedLocales as $locale)
                @if ($locale !== 'en')
                    <div class="space-y-4 rounded-2xl border border-slate-200 p-4">
                        <h3 class="text-sm font-bold text-slate-800">Traduccion {{ strtoupper($locale) }}</h3>
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Pregunta</label>
                            <textarea name="translations[{{ $locale }}][prompt]" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">{{ old('translations.'.$locale.'.prompt') }}</textarea>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input name="translations[{{ $locale }}][option_1]" value="{{ old('translations.'.$locale.'.option_1') }}" placeholder="Opcion 1" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                            <input name="translations[{{ $locale }}][option_2]" value="{{ old('translations.'.$locale.'.option_2') }}" placeholder="Opcion 2" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                            <input name="translations[{{ $locale }}][option_3]" value="{{ old('translations.'.$locale.'.option_3') }}" placeholder="Opcion 3" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                            <input name="translations[{{ $locale }}][option_4]" value="{{ old('translations.'.$locale.'.option_4') }}" placeholder="Opcion 4" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                    Crear pregunta
                </button>
            </div>
        </form>
    </section>
@endsection
