@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Certificados emitidos</h1>
                <p class="mt-1 text-sm text-slate-600">Filtra por estado y certificación para auditar, revocar o restaurar.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.certificates.export.csv', request()->query()) }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    Exportar CSV
                </a>
                <a href="{{ route('admin.certificates.api.index', request()->query()) }}" target="_blank" class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                    Ver API JSON
                </a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Volver al panel
                </a>
            </div>
        </div>

        <form method="GET" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-4">
            <label class="block text-sm font-semibold text-slate-700">
                Buscar
                <input type="text" name="search" value="{{ $search }}" placeholder="Serial o nombre" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Estado
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all" @selected($status === 'all')>Todos</option>
                    <option value="active" @selected($status === 'active')>Vigentes</option>
                    <option value="revoked" @selected($status === 'revoked')>Revocados</option>
                </select>
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Certificación
                <select name="certification_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">Todas</option>
                    @foreach ($certifications as $certification)
                        <option value="{{ $certification->id }}" @selected($certificationId === $certification->id)>
                            {{ $certification->name }} ({{ $certification->slug }})
                        </option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Aplicar</button>
                <a href="{{ route('admin.certificates.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Limpiar</a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Serial</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Titular</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Certificación</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Estado</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Emitido</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-800">{{ $certificate->serial }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $certificate->first_name }} {{ $certificate->last_name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $certificate->certification?->name ?? 'N/D' }}</td>
                            <td class="px-4 py-3">
                                @if ($certificate->revoked_at)
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Revocado</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Vigente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $certificate->issued_at?->format('Y-m-d H:i') ?? 'N/D' }}</td>
                            <td class="px-4 py-3">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('cert.show', ['serial' => $certificate->serial]) }}" target="_blank" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Ver</a>
                                        <a href="{{ $certificate->verification_url }}" target="_blank" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Verificación</a>
                                    </div>

                                    @if ($certificate->revoked_at)
                                        <form method="POST" action="{{ route('admin.certifications.certificates.restore', [$certificate->certification_id, $certificate->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-emerald-300 bg-white px-3 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">Restaurar</button>
                                        </form>
                                        @if ($certificate->revoked_reason)
                                            <p class="text-xs text-rose-700">Motivo: {{ $certificate->revoked_reason }}</p>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.certifications.certificates.revoke', [$certificate->certification_id, $certificate->id]) }}" class="flex flex-wrap items-start gap-2">
                                            @csrf
                                            <input type="text" name="reason" required maxlength="500" placeholder="Motivo" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                            <button type="submit" class="rounded-lg border border-rose-300 bg-white px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" onclick="return confirm('¿Revocar certificado?');">Revocar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No se encontraron certificados con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $certificates->links() }}
        </div>
    </section>
@endsection
