@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Auditoria de cambios</h1>
                <p class="mt-1 text-sm text-slate-600">Registro de operaciones administrativas.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Panel</a>
        </div>

        <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <select name="filter" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                <option value="all" @selected($filter === 'all')>Todas las acciones</option>
                @foreach ($actions as $key => $label)
                    <option value="{{ $key }}" @selected($filter === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="entity" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                <option value="all" @selected(!$entity)>Todos los registros</option>
                @foreach ($entities as $key => $label)
                    <option value="{{ $key }}" @selected($entity === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
            <a href="{{ route('admin.audit.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Limpiar</a>
        </form>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Accion</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Registro</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ match($log->action) {
                                    'create' => 'bg-emerald-100 text-emerald-800',
                                    'update' => 'bg-blue-100 text-blue-800',
                                    'delete' => 'bg-rose-100 text-rose-800',
                                    'import' => 'bg-purple-100 text-purple-800',
                                    'export' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-slate-100 text-slate-800',
                                } }}">
                                    {{ $actions[$log->action] ?? $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $entities[$log->entity] ?? $log->entity }}</td>
                            <td class="px-4 py-3">
                                @if ($log->entity_name)
                                    <code class="text-xs text-slate-600">{{ $log->entity_name }}</code>
                                @else
                                    <code class="text-xs text-slate-400">#{{ $log->entity_id }}</code>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $log->ip_address }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        @if ($log->changes)
                            <tr class="bg-slate-50">
                                <td colspan="5" class="px-4 py-3 text-xs text-slate-600">
                                    <details>
                                        <summary class="cursor-pointer font-semibold">Cambios</summary>
                                        <pre class="mt-2 overflow-x-auto rounded bg-white p-2 text-xs">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay registros de auditoria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $logs->withQueryString()->links() }}
        </div>
    </section>
@endsection
