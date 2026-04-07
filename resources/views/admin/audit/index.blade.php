@extends('layouts.app')

@section('content')
    <section class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Registro de Auditoría</h1>
                    <p class="mt-2 text-slate-600">Historial completo de operaciones administrativas</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="mx-auto max-w-7xl">
            <form method="GET" class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Acción</label>
                    <select name="filter" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Todas las acciones</option>
                        @foreach ($actions as $key => $label)
                            <option value="{{ $key }}" @selected($filter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Entidad</label>
                    <select name="entity" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Todos los registros</option>
                        @foreach ($entities as $key => $label)
                            <option value="{{ $key }}" @selected($entity === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 font-semibold text-white shadow-lg transition hover:shadow-xl">
                        Filtrar
                    </button>
                    @if($filter || $entity)
                        <a href="{{ route('admin.audit.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="mx-auto max-w-7xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Acción</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Entidad</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Registro</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">IP</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ match($log->action) {
                                        'create' => 'bg-emerald-100 text-emerald-700',
                                        'update' => 'bg-blue-100 text-blue-700',
                                        'delete' => 'bg-rose-100 text-rose-700',
                                        'import' => 'bg-purple-100 text-purple-700',
                                        'export' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    } }}">
                                        {{ $actions[$log->action] ?? $log->action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                    {{ $entities[$log->entity] ?? $log->entity }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if ($log->entity_name)
                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs font-medium">{{ $log->entity_name }}</code>
                                    @else
                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">#{{ $log->entity_id }}</code>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $log->ip_address }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-600">
                                        <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                        <div class="font-mono text-slate-500">{{ $log->created_at->format('H:i:s') }}</div>
                                    </div>
                                </td>
                            </tr>
                            @if ($log->changes)
                                <tr class="bg-blue-50 hover:bg-blue-50">
                                    <td colspan="5" class="px-6 py-4">
                                        <details class="group cursor-pointer">
                                            <summary class="flex items-center gap-2 font-semibold text-slate-900">
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-200 text-sm font-bold text-blue-700 group-open:hidden">+</span>
                                                <span class="hidden group-open:inline">−</span>
                                                Cambios realizados
                                            </summary>
                                            <div class="mt-4 rounded-lg border border-blue-200 bg-white p-4">
                                                <pre class="overflow-x-auto text-xs text-slate-700">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="text-slate-500">No hay registros de auditoría</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mx-auto max-w-7xl">
            {{ $logs->links() }}
        </div>
    </section>
@endsection
