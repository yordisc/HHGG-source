@extends('admin.layout')

@section('title', 'Vista Previa - Importar CSV')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Vista Previa de Importación CSV</h1>
        <p class="text-gray-600 mt-2">Archivo: <strong>{{ $fileName }}</strong></p>
    </div>

    <!-- Estado de Validación -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="text-blue-600 text-sm font-semibold">TOTAL FILAS</div>
            <div class="text-3xl font-bold text-blue-900 mt-2">{{ $result['total_rows'] }}</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="text-green-600 text-sm font-semibold">✓ CREARÁN</div>
            <div class="text-3xl font-bold text-green-900 mt-2">{{ $result['preview'][0]['created'] ?? 0 }}</div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="text-yellow-600 text-sm font-semibold">⚠ ACTUALIZARÁN</div>
            <div class="text-3xl font-bold text-yellow-900 mt-2">{{ $result['preview'][0]['updated'] ?? 0 }}</div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="text-red-600 text-sm font-semibold">✗ ERRORES</div>
            <div class="text-3xl font-bold text-red-900 mt-2">{{ $result['error_count'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Errores si existen -->
    @if (!empty($result['errors']))
        <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <h3 class="font-bold text-red-900 mb-3">❌ Errores Encontrados</h3>
            <div class="space-y-2">
                @foreach ($result['errors'] as $error)
                    <div class="text-red-700 text-sm">
                        <strong>Fila {{ $error['row'] ?? 'N/A' }}:</strong> {{ $error['message'] ?? $error }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Advertencias si existen -->
    @if (!empty($result['warnings']))
        <div class="mb-8 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
            <h3 class="font-bold text-yellow-900 mb-3">⚠ Advertencias</h3>
            <div class="space-y-2">
                @foreach ($result['warnings'] as $warning)
                    <div class="text-yellow-700 text-sm">
                        <strong>Fila {{ $warning['row'] ?? 'N/A' }}:</strong> {{ $warning['message'] ?? $warning }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Vista previa de datos -->
    @if (!empty($result['preview']))
        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">📋 Primeras 5 filas a importar</h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Fila</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Certificación</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Idioma</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Pregunta</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Opción Correcta</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($result['preview'] as $index => $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['cert_type'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                        {{ strtoupper($row['language'] ?? 'EN') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ $row['prompt'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    @if ($row['correct_option'] ?? null)
                                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">
                                            Opción {{ $row['correct_option'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($row['created'])
                                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">
                                            🆕 Crear
                                        </span>
                                    @elseif ($row['updated'])
                                        <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">
                                            ✏️ Actualizar
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">Omitir</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Botones de acción -->
    <div class="flex gap-4 justify-between mt-8">
        <a href="{{ route('admin.questions.index') }}" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 transition">
            ← Cancelar
        </a>

        @if ($result['error_count'] === 0)
            <form method="POST" action="{{ route('admin.questions.confirm.csv') }}" class="flex gap-2">
                @csrf
                <input type="hidden" name="temp_path" value="{{ $tempPath }}">
                <input type="hidden" name="cert_type" value="{{ $certifications[0] ?? '' }}">

                <button type="submit" class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                    ✓ Confirmar Importación
                </button>
            </form>
        @else
            <div class="text-center">
                <p class="text-red-600 font-semibold mb-2">⚠ No se puede importar mientras haya errores</p>
                <p class="text-gray-600 text-sm">Por favor, corrige los errores en tu archivo CSV</p>
            </div>
        @endif
    </div>

    <!-- Información útil -->
    <div class="mt-12 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-bold text-blue-900 mb-3">ℹ️ Información</h3>
        <ul class="text-blue-800 text-sm space-y-2">
            <li>✓ <strong>Crear:</strong> Pregunta nueva que se añadirá a la base de datos</li>
            <li>✓ <strong>Actualizar:</strong> Pregunta existente que será modificada</li>
            <li>✓ <strong>Omitir:</strong> Fila que no se procesará (sinónimos en otros idiomas sin pregunta base)</li>
            <li>✓ <strong>Columnas requeridas:</strong> question_id, cert_type, language, prompt, option_1, option_2, option_3, option_4, correct_option</li>
        </ul>
    </div>
</div>
@endsection
