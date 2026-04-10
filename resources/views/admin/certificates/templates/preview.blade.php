@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Vista previa: {{ $template->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Visualización con datos de ejemplo.</p>
            </div>
            <a href="{{ route('admin.certificates.templates.edit', $template) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Editar plantilla
            </a>
        </div>

        <style>
            {!! $template->css_template !!}
        </style>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <div class="prose max-w-none rounded-xl bg-white p-8 shadow-sm dark:prose-invert">
                {!! str_replace(
                    ['{{nombre}}', '{{fecha}}', '{{serial}}', '{{competencia}}', '{{nota}}', '{{verificacion_url}}', '{{verificacion_qr}}', '{{integridad_hash}}'],
                    [$sampleData['nombre'], $sampleData['fecha'], $sampleData['serial'], $sampleData['competencia'], $sampleData['nota'], $sampleData['verificacion_url'], $sampleData['verificacion_qr'], $sampleData['integridad_hash']],
                    $template->html_template
                ) !!}
            </div>
        </div>
    </section>
@endsection
