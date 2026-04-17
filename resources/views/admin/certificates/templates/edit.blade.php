@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Editar plantilla</h1>
                <p class="mt-1 text-sm text-slate-600">Actualiza el diseño de certificados.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.certificates.templates.preview', $template) }}" target="_blank"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Vista previa
                </a>
                <a href="{{ route('admin.certificates.templates.index') }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Volver
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.certificates.templates.update', $template) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold">Variables disponibles:</p>
                <ul class="mt-2 space-y-1 font-mono text-xs">
                    <li>• <code class="text-yellow-600">{{ '{{' }}nombre{{ ' ?>' }}'}}</code> • <code
                            class="text-yellow-600">{{ '{{' }}fecha{{ ' ?>' }}'}}</code> • <code
                            class="text-yellow-600">{{ '{{' }}serial{{ ' ?>' }}'}}</code> • <code
                            class="text-yellow-600">{{ '{{' }}competencia{{ ' ?>' }}'}}</code> • <code
                            class="text-yellow-600">{{ '{{' }}nota{{ ' ?>' }}'}}</code></li>
                    <li>• <code class="text-yellow-600">{{ '{{' }}verificacion_url{{ ' ?>' }}'}}</code> •
                        <code class="text-yellow-600">{{ '{{' }}verificacion_qr{{ ' ?>' }}'}}</code> •
                        <code class="text-yellow-600">{{ '{{' }}integridad_hash{{ ' ?>' }}'}}</code></li>
                    <li>• <code class="text-yellow-600">{{ '{{' }}logo_institucion{{ ' ?>' }}'}}</code> •
                        <code class="text-yellow-600">{{ '{{' }}firma_director{{ ' ?>' }}'}}</code></li>
                </ul>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <label class="block text-sm font-semibold text-slate-700">
                    Slug
                    <input type="text" name="slug" value="{{ old('slug', $template->slug) }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('slug')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">
                    Nombre
                    <input type="text" name="name" value="{{ old('name', $template->name) }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <label class="block text-sm font-semibold text-slate-700">
                HTML de la plantilla
                <textarea name="html_template" rows="12"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('html_template', $template->html_template) }}</textarea>
                @error('html_template')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                CSS (estilos opcionales)
                <textarea name="css_template" rows="8"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('css_template', $template->css_template) }}</textarea>
                @error('css_template')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $template->is_default))>
                Establecer como plantilla default
            </label>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit"
                    class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.certificates.templates.index') }}"
                    class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
@endsection
