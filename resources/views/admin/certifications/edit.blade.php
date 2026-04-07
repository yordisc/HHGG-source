@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Editar certificacion</h1>
                <p class="mt-1 text-sm text-slate-600">Actualiza la configuracion funcional del catalogo.</p>
            </div>
            <a href="{{ route('admin.certifications.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Volver al listado
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <a href="{{ route('admin.certifications.test', $certification) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Probar funcionamiento
            </a>
            <a href="{{ route('admin.certificates.templates.certification', $certification) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Editar plantilla de certificado
            </a>
            <form action="{{ route('admin.certifications.test-questions', $certification) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="count" value="5">
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Agregar 5 preguntas de prueba
                </button>
            </form>
            <form action="{{ route('admin.certifications.test-questions.clear', $certification) }}" method="POST" class="inline" onsubmit="return confirm('Se eliminaran todas las preguntas de prueba de esta certificacion. ¿Continuar?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                    Borrar preguntas de prueba
                </button>
            </form>
        </div>

        @include('admin.certifications._form', [
            'action' => route('admin.certifications.update', $certification),
            'method' => 'PUT',
            'buttonLabel' => 'Guardar cambios',
        ])
    </section>
@endsection
