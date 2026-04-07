@extends('layouts.app')

@section('content')
    <section class="min-h-screen space-y-8 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mis Borradores</h1>
                    <p class="mt-2 text-gray-600">Certificaciones en progreso - se autoguardan automáticamente</p>
                </div>
                <a href="{{ route('admin.certifications.wizard', ['step' => 1]) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nuevo borrador
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl">
            @if ($drafts->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No hay borradores</h3>
                    <p class="mt-2 text-slate-600">Comienza a crear una nueva certificación haciendo click en el botón de arriba</p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($drafts as $draft)
                        <div class="group overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-lg">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-slate-900">{{ $draft['name'] }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $draft['slug'] }}</p>
                                    </div>
                                    @if ($draft['expired'])
                                        <span class="inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Expirado</span>
                                    @else
                                        <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Paso {{ $draft['step'] }}/5</span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-6">
                                <!-- Barra de progreso -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs font-semibold text-slate-600 mb-2">
                                        <span>Progreso</span>
                                        <span>{{ $draft['progress'] }}%</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full bg-blue-500 transition-all" style="width: {{ $draft['progress'] }}%"></div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-500">Actualizado {{ $draft['updated_at'] }}</p>

                                @if (!$draft['expired'])
                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('admin.certifications.wizard', ['step' => $draft['step']]) }}" 
                                           class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">
                                            Continuar
                                        </a>
                                        <form method="POST" action="{{ route('admin.certifications.wizard.reset') }}" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este borrador?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                        ⚠ Este borrador expiró y será eliminado pronto
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Info Box -->
        <div class="mx-auto max-w-7xl rounded-lg border border-blue-200 bg-blue-50 p-6">
            <h3 class="font-semibold text-blue-900">ℹ️ Información sobre borradores</h3>
            <ul class="mt-3 space-y-2 text-sm text-blue-800">
                <li>✓ Los borradores se guardan <strong>automáticamente</strong> cada 30 segundos mientras trabajas</li>
                <li>✓ Puedes abandonar y volver después - tus cambios estarán guardados</li>
                <li>✓ Los borradores expiran después de <strong>7 días</strong> de inactividad</li>
                <li>✓ Cuando termines de llenar los 5 pasos, presiona "Crear certificación"</li>
            </ul>
        </div>
    </section>
@endsection
