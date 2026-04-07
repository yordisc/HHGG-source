@extends('layouts.app')

@section('content')
    <div class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Historial de versiones</h1>
                        <p class="mt-2 text-gray-600">{{ $certification->name }}</p>
                    </div>
                    <a href="{{ route('admin.certifications.edit', $certification) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver a editar
                    </a>
                </div>
            </div>

            <!-- Status messages -->
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-emerald-800">
                    ✓ {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">
                    ✗ {{ session('error') }}
                </div>
            @endif

            <!-- Versions list -->
            @if ($versions->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No hay versiones anteriores</h3>
                    <p class="mt-2 text-slate-600">Los cambios que hagas se registrarán automáticamente</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($versions as $index => $version)
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-bold text-slate-900">
                                                Versión {{ $version->version_number }}
                                            </h3>
                                            @if ($index === 0)
                                                <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                    ★ Versión actual
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="mt-2 text-sm text-slate-600">
                                            <strong>Motivo:</strong> {{ $version->change_reason }}
                                        </p>
                                        
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $version->created_at->format('d/m/Y ') }} 
                                            <strong>{{ $version->created_at->format('H:i:s') }}</strong>
                                        </p>
                                    </div>

                                    @if ($index > 0)
                                        <form method="POST" action="{{ route('admin.certifications.rollback-version', [$certification, $version]) }}" class="inline">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100" onclick="return confirm('¿Deseas restaurar a esta versión? Se crearán cambios en la versión actual.')">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4 4h.01M7 20H5a2 2 0 01-2-2V9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2h-.5a2 2 0 00-1 .268V20a2 2 0 01-2 2z"/></svg>
                                                Restaurar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Info panel -->
            <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-6">
                <h3 class="font-semibold text-blue-900">ℹ️ Información sobre versiones</h3>
                <ul class="mt-3 space-y-2 text-sm text-blue-800">
                    <li>✓ Cada cambio en la certificación se registra automáticamente</li>
                    <li>✓ Puedes ver exactamente qué cambió en cada versión</li>
                    <li>✓ Puedes restaurar cualquier versión anterior</li>
                    <li>✓ Cuando restauras, se crea una nueva versión con el cambio</li>
                    <li>✓ Las versiones no se pueden eliminar (útil para auditoría)</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
