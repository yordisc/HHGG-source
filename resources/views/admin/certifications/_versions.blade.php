<!-- Certification Versions History -->
<div class="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Historial de versiones
    </h2>

    @if ($versions->isEmpty())
        <p class="text-sm text-slate-500">No hay versiones anteriores de esta certificación.</p>
    @else
        <div class="space-y-4">
            @foreach ($versions as $index => $version)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">
                                Versión {{ $version->version_number }}
                                @if ($index === 0)
                                    <span class="ml-2 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Actual</span>
                                @endif
                                @if ($version->trashed())
                                    <span class="ml-2 inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Eliminada</span>
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ $version->created_at->format('d/m/Y H:i:s') }}</p>
                            <p class="mt-2 text-sm font-medium text-slate-700">{{ $version->change_reason }}</p>
                        </div>
                        
                        @if ($index > 0 && !$version->trashed())
                            <form method="POST" action="{{ route('admin.certifications.rollback-version', [$certification, $version]) }}" class="inline">
                                @csrf
                                @method('POST')
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100" onclick="return confirm('¿Deseas restaurar a esta versión? Se crearán cambios en la versión actual.')">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4 4h.01M7 20H5a2 2 0 01-2-2V9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2h-.5a2 2 0 00-1 .268V20a2 2 0 01-2 2z"/></svg>
                                    Restaurar
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Show changes -->
                    @if (!empty($version->changes))
                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-slate-900">
                                Ver cambios ({{ count($version->changes ?? []) }})
                            </summary>
                            <div class="mt-2 space-y-2 rounded-lg bg-white p-3">
                                @foreach (($version->changes ?? []) as $fieldName => $change)
                                    <div class="text-xs">
                                        <p class="font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $fieldName)) }}</p>
                                        <p class="text-slate-600">
                                            De: <code class="rounded bg-red-50 px-2 py-1 text-red-700">{{ $change['old'] ?? '(vacío)' }}</code>
                                        </p>
                                        <p class="text-slate-600">
                                            A: <code class="rounded bg-green-50 px-2 py-1 text-green-700">{{ $change['new'] ?? '(vacío)' }}</code>
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Version comparison -->
        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-sm font-semibold text-blue-900">💡 Consejo</p>
            <p class="mt-1 text-xs text-blue-800">Puedes comparar dos versiones o descargar versiones anteriores de la certificación para análisis de auditoría.</p>
        </div>
    @endif
</div>
