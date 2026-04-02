@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Editar Pregunta #{{ $question->id }}</h1>
                <p class="mt-1 text-sm text-slate-600">Actualiza la pregunta base y sus traducciones.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.questions.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Volver al listado
                </a>
                <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Deseas eliminar esta pregunta?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.questions.update', $question) }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-slate-700">Tipo</label>
                    <select name="cert_type" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        @foreach ($certifications as $slug => $name)
                            <option value="{{ $slug }}" @selected(old('cert_type', $question->certification?->slug) === $slug)>{{ $slug }} - {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <input type="checkbox" name="active" value="1" @checked(old('active', $question->active))>
                        Activa
                    </label>
                </div>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-bold text-slate-800">Base (fallback)</h2>
                <div>
                    <label class="text-xs font-semibold text-slate-700">Pregunta</label>
                    <textarea name="prompt" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">{{ old('prompt', $question->prompt) }}</textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="option_1" value="{{ old('option_1', $question->option_1) }}" placeholder="Opcion 1" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_2" value="{{ old('option_2', $question->option_2) }}" placeholder="Opcion 2" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_3" value="{{ old('option_3', $question->option_3) }}" placeholder="Opcion 3" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <input name="option_4" value="{{ old('option_4', $question->option_4) }}" placeholder="Opcion 4" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700">Respuesta correcta (1-4)</label>
                    <input name="correct_option" type="number" min="1" max="4" value="{{ old('correct_option', $question->correct_option) }}" class="mt-1 w-28 rounded-xl border border-slate-300 px-4 py-2 text-sm">
                </div>
            </div>

            @foreach ($supportedLocales as $locale)
                @php
                    $t = $translations->get($locale);
                @endphp
                <div class="space-y-4 rounded-2xl border border-slate-200 p-4">
                    <h3 class="text-sm font-bold text-slate-800">Traduccion {{ strtoupper($locale) }}</h3>
                    <div>
                        <label class="text-xs font-semibold text-slate-700">Pregunta</label>
                        <textarea name="translations[{{ $locale }}][prompt]" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm">{{ old('translations.'.$locale.'.prompt', $t?->prompt) }}</textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="translations[{{ $locale }}][option_1]" value="{{ old('translations.'.$locale.'.option_1', $t?->option_1) }}" placeholder="Opcion 1" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <input name="translations[{{ $locale }}][option_2]" value="{{ old('translations.'.$locale.'.option_2', $t?->option_2) }}" placeholder="Opcion 2" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <input name="translations[{{ $locale }}][option_3]" value="{{ old('translations.'.$locale.'.option_3', $t?->option_3) }}" placeholder="Opcion 3" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        <input name="translations[{{ $locale }}][option_4]" value="{{ old('translations.'.$locale.'.option_4', $t?->option_4) }}" placeholder="Opcion 4" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                    Guardar cambios
                </button>
            </div>
        </form>
    </section>
@endsection
