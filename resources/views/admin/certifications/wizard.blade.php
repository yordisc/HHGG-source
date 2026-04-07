@extends('layouts.app')

@php
    $stepTitles = [
        1 => 'Datos basicos',
        2 => 'Examen',
        3 => 'Reglas',
        4 => 'Presentacion',
        5 => 'Revision final',
    ];
@endphp

@section('content')
    <section class="mx-auto max-w-4xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Asistente de certificaciones</h1>
                <p class="mt-1 text-sm text-slate-600">Paso {{ $step }} de 5 - {{ $stepTitles[$step] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.certifications.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Volver</a>
                <form method="POST" action="{{ route('admin.certifications.wizard.reset') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">Reiniciar</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-6 grid gap-2 sm:grid-cols-5">
            @foreach ($stepTitles as $number => $title)
                <div class="rounded-2xl border px-4 py-3 text-sm font-semibold {{ $number === $step ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    {{ $number }}. {{ $title }}
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.certifications.wizard.store', ['step' => $step]) }}" class="space-y-6">
            @csrf

            @if ($step === 1)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Slug
                        <input type="text" name="slug" value="{{ old('slug', $draft['slug'] ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Nombre
                        <input type="text" name="name" value="{{ old('name', $draft['name'] ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <label class="block text-sm font-semibold text-slate-700">
                    Descripcion
                    <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $draft['description'] ?? '') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
            @endif

            @if ($step === 2)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Preguntas requeridas
                        <input type="number" min="1" name="questions_required" value="{{ old('questions_required', $draft['questions_required'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('questions_required')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        % de aprobacion
                        <input type="number" min="0" max="100" step="0.01" name="pass_score_percentage" value="{{ old('pass_score_percentage', $draft['pass_score_percentage'] ?? 66.67) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('pass_score_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            @endif

            @if ($step === 3)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Dias de cooldown
                        <input type="number" min="0" name="cooldown_days" value="{{ old('cooldown_days', $draft['cooldown_days'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('cooldown_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Modo de resultado
                        <select name="result_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @foreach ($resultModes as $value => $label)
                                <option value="{{ $value }}" @selected(old('result_mode', $draft['result_mode'] ?? 'binary_threshold') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('result_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            @endif

            @if ($step === 4)
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Vista PDF
                        <input type="text" name="pdf_view" value="{{ old('pdf_view', $draft['pdf_view'] ?? 'pdf.certificate') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('pdf_view')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Orden en home
                        <input type="number" min="0" name="home_order" value="{{ old('home_order', $draft['home_order'] ?? 100) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('home_order')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>

                <label class="block text-sm font-semibold text-slate-700">
                    Settings JSON
                    <textarea name="settings" rows="5" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('settings', $draft['settings'] ?? '') }}</textarea>
                    @error('settings')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $draft['active'] ?? true))>
                    Activa desde el inicio
                </label>
            @endif

            @if ($step === 5)
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
                    <div><strong>Slug:</strong> {{ $draft['slug'] ?? 'N/D' }}</div>
                    <div><strong>Nombre:</strong> {{ $draft['name'] ?? 'N/D' }}</div>
                    <div><strong>Preguntas requeridas:</strong> {{ $draft['questions_required'] ?? 30 }}</div>
                    <div><strong>% aprobacion:</strong> {{ $draft['pass_score_percentage'] ?? 66.67 }}</div>
                    <div><strong>Cooldown:</strong> {{ $draft['cooldown_days'] ?? 30 }}</div>
                    <div><strong>Modo:</strong> {{ $draft['result_mode'] ?? 'binary_threshold' }}</div>
                    <div><strong>PDF:</strong> {{ $draft['pdf_view'] ?? 'pdf.certificate' }}</div>
                    <div><strong>Orden:</strong> {{ $draft['home_order'] ?? 100 }}</div>
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
                        <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">Crear certificacion</button>
                    @endif
                </div>
            </div>
        </form>
    </section>
@endsection