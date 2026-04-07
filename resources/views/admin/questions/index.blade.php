@extends('layouts.app')

@section('content')
    <section class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Gestión de Preguntas</h1>
                    <p class="mt-2 text-slate-600">Edita el banco de preguntas y traducciones por idioma</p>
                </div>
                <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 font-semibold text-white shadow-lg transition hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nueva pregunta
                </a>
                <a href="{{ route('admin.questions.builder') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-3 font-semibold text-white shadow-lg transition hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 6.732z"/></svg>
                    Constructor visual
                </a>
            </div>

            @if (session('status'))
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-emerald-50/50 p-4 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <p class="text-sm font-medium text-emerald-900">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->has('csv_file'))
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-gradient-to-r from-rose-50 to-rose-50/50 p-4 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <p class="text-sm font-medium text-rose-900">{{ $errors->first('csv_file') }}</p>
                </div>
            @endif
        </div>

        <!-- CSV Import Section -->
        <div class="mx-auto max-w-7xl">
            <form method="POST" action="{{ route('admin.questions.validate.csv') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="mb-4 text-lg font-bold text-slate-900">Importar preguntas desde CSV</h2>
                <p class="mb-4 text-sm text-slate-600">Columnas requeridas: <code class="rounded bg-slate-100 px-2 py-1 font-mono text-xs">cert_type, prompt, option_1, option_2, option_3, option_4, correct_option</code></p>
                
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Selecciona archivo CSV</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 font-semibold text-white shadow-sm transition hover:shadow-md">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Vista Previa
                    </button>
                    <a href="{{ route('admin.questions.export.csv', ['cert_type' => $filterType ?: null]) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-6 py-2.5 font-semibold text-emerald-700 shadow-sm border border-emerald-200 transition hover:bg-emerald-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Exportar
                    </a>
                    <a href="{{ route('admin.questions.template.csv') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-6 py-2.5 font-semibold text-amber-700 shadow-sm border border-amber-200 transition hover:bg-amber-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Plantilla
                    </a>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="mx-auto max-w-7xl">
            <form method="GET" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Filtros Avanzados</h3>
                
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Búsqueda por texto -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Buscar pregunta</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Escribe para buscar..." class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <!-- Por certificación -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Certificación</label>
                        <select name="cert_type" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">Todas</option>
                            @foreach ($certifications as $slug => $name)
                                <option value="{{ $slug }}" @selected($filterType === $slug)>{{ $slug }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Por estado -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Estado</label>
                        <select name="active" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">Todas</option>
                            <option value="1" @selected($filterActive === '1')>✅ Activas</option>
                            <option value="0" @selected($filterActive === '0')>❌ Inactivas</option>
                        </select>
                    </div>

                    <!-- Ordenamiento -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Ordenar por</label>
                        <select name="sort" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="latest" @selected($sortBy === 'latest')>Más recientes</option>
                            <option value="oldest" @selected($sortBy === 'oldest')>Más antiguas</option>
                            <option value="alphabetical" @selected($sortBy === 'alphabetical')>Alfabético</option>
                        </select>
                    </div>

                    <!-- Por página -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Por página</label>
                        <select name="per_page" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="20" @selected($perPage === 20)>20</option>
                            <option value="50" @selected($perPage === 50)>50</option>
                            <option value="100" @selected($perPage === 100)>100</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 font-semibold text-white shadow-sm transition hover:shadow-md">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 000 2h14a1 1 0 100-2H3zM3 12a1 1 0 000 2h14a1 1 0 100-2H3zM3 20a1 1 0 000 2h14a1 1 0 100-2H3z"/></svg>
                        Aplicar filtros
                    </button>
                    <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-6 py-2.5 font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Questions Table -->
        <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Certificación</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Pregunta</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($questions as $question)
                                @php
                                    $questionSlug = $question->certification?->slug;
                                    $questionName = $questionSlug !== null ? ($certifications[$questionSlug] ?? null) : null;
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                            {{ $question->id }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            {{ $questionSlug ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900">{{ \Illuminate\Support\Str::limit($question->prompt, 90) }}</td>
                                    <td class="px-6 py-4">
                                        @if($question->active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                                Activa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                                                <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.questions.edit', $question) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Editar
                                            </a>
                                            <form action="{{ route('admin.questions.duplicate', $question) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 transition hover:bg-purple-100">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    Copiar
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="inline" onsubmit="return confirm('¿Confirmas eliminar esta pregunta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3"/></svg>
                                            <p class="text-slate-500">No hay preguntas registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $questions->links() }}
            </div>
        </div>
    </section>
@endsection
