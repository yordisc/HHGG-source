@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Plantilla de certificado</h1>
                <p class="mt-1 text-sm text-slate-600">
                    @if ($customTemplate)
                        Personalizada para: <strong>{{ $certification->name }}</strong>
                    @else
                        Usando plantilla default
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.certifications.edit', $certification) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Volver a certificación
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.certificates.templates.certification.save', $certification) }}" class="space-y-6">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="use_custom" value="1" @checked($customTemplate !== null) id="use-custom-toggle">
                    Crear plantilla personalizada para esta certificación
                </label>
                <p class="mt-2 text-xs text-slate-600">Si lo activas, podrás editar un diseño único para esta certificación. En caso contrario, se usará el diseño default.</p>
            </div>

            <div id="custom-template-form" class="{{ $customTemplate ? '' : 'hidden' }} space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p class="font-semibold">Variables disponibles:</p>
                    <ul class="mt-2 space-y-1 font-mono text-xs">
                        <li>• <code class="text-yellow-600">{{'{{'}}nombre{{'}}'}}</code> • <code class="text-yellow-600">{{'{{'}}fecha{{'}}'}}</code> • <code class="text-yellow-600">{{'{{'}}serial{{'}}'}}</code></li>
                    </ul>
                </div>

                <label class="block text-sm font-semibold text-slate-700">
                    HTML
                    <textarea name="html_template" rows="10" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('html_template', $customTemplate?->html_template ?? '<div class="certificate"><h1>{{nombre}}</h1><p>Certificado emitido en: {{fecha}}</p></div>') }}</textarea>
                    @error('html_template')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">
                    CSS (opcional)
                    <textarea name="css_template" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('css_template', $customTemplate?->css_template ?? '') }}</textarea>
                    @error('css_template')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                    Guardar plantilla
                </button>
                <a href="{{ route('admin.certifications.edit', $certification) }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Cancelar
                </a>
            </div>
        </form>
    </section>

    <script>
        const toggle = document.getElementById('use-custom-toggle');
        const customForm = document.getElementById('custom-template-form');

        if (toggle && customForm) {
            toggle.addEventListener('change', () => {
                customForm.classList.toggle('hidden', !toggle.checked);
            });
        }
    </script>
@endsection
