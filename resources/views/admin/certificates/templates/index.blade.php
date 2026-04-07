@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Plantillas de certificados</h1>
                <p class="mt-1 text-sm text-slate-600">Diseño default y personalizado para documentos PDF.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Panel</a>
                <a href="{{ route('admin.certificates.templates.create') }}" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Nueva plantilla</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $template->name }}</td>
                            <td class="px-4 py-3">{{ $template->slug }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $template->is_default ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $template->is_default ? 'Default' : 'Personalizada' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.certificates.templates.preview', $template) }}" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:border-[var(--accent)] hover:text-[var(--accent)]">Preview</a>
                                <a href="{{ route('admin.certificates.templates.edit', $template) }}" class="ml-2 rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:border-[var(--accent)] hover:text-[var(--accent)]">Editar</a>
                                <form action="{{ route('admin.certificates.templates.destroy', $template) }}" method="POST" class="ml-2 inline" onsubmit="return confirm('Deseas eliminar esta plantilla?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay plantillas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $templates->links() }}
        </div>
    </section>
@endsection
