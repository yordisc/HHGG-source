@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Prueba de funcionamiento</h1>
                <p class="mt-1 text-sm text-slate-600">Revisión en español para verificar que la certificación está lista.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (($diagnostics['summary']['active_questions'] ?? 0) >= ($diagnostics['summary']['required_questions'] ?? 0) && ($diagnostics['summary']['required_questions'] ?? 0) > 0)
                    <a href="{{ route('quiz.register', ['certType' => $certification->slug]) }}" target="_blank" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Iniciar prueba real
                    </a>
                @else
                    <form action="{{ route('admin.certifications.test-questions', $certification) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="count" value="5">
                        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">
                            Agregar 5 preguntas de prueba
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.certifications.edit', $certification) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Volver a la certificación</a>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Preguntas totales</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $diagnostics['summary']['total_questions'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Preguntas activas</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $diagnostics['summary']['active_questions'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Requeridas</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $diagnostics['summary']['required_questions'] ?? 0 }}</p>
            </div>
        </div>

        @if (!empty($diagnostics['warnings']))
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Protecciones activas</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($diagnostics['warnings'] as $warning)
                        <li>• {{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                La certificación está lista para pruebas.
            </div>
        @endif

        @if (($diagnostics['summary']['active_questions'] ?? 0) < ($diagnostics['summary']['required_questions'] ?? 0))
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No hay banco suficiente para ejecutar la prueba completa. Puedes agregar 5 preguntas de prueba con el botón superior.
            </div>
        @endif

        <div class="mt-6 space-y-3">
            <h2 class="text-lg font-semibold text-[var(--ink)]">Muestra de preguntas en español</h2>
            @forelse ($sampleQuestions as $question)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="font-semibold text-slate-900">{{ $question['prompt'] }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($question['options'] as $option)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $option }}</div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    No hay preguntas suficientes para mostrar una muestra.
                </div>
            @endforelse
        </div>
    </section>
@endsection