@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('Importar Usuarios') }}</h1>
        <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            {{ __('Volver') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 font-medium mb-2">{{ __('Errores en la validación:') }}</p>
            <ul class="list-disc list-inside text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Cargar archivo CSV') }}</h2>
                
                <form action="{{ route('admin.users.import.csv') }}" method="POST" enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Archivo CSV') }}
                        </label>
                        <input type="file" id="file" name="file" accept=".csv,.txt"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('Formatos soportados: CSV, TXT (máximo 5 MB)') }}
                        </p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-medium text-blue-900 mb-2">{{ __('Formato esperado:') }}</h3>
                        <p class="text-sm text-blue-800 mb-2">{{ __('ID,Nombre,Email,Contraseña (opcional)') }}</p>
                        <pre class="text-xs bg-white p-2 rounded border border-blue-200 overflow-x-auto">1,Juan Pérez,juan@example.com,contraseña
2,María García,,generada_automaticamente
3,Pedro López</pre>
                    </div>

                    <div class="flex justify-between gap-3">
                        <a href="{{ route('admin.users.export.csv') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            {{ __('Descargar plantilla') }}
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            {{ __('Importar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help -->
        <div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Información') }}</h2>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ __('Campos obligatorios:') }}</h3>
                        <ul class="list-disc list-inside text-gray-600">
                            <li>ID (número)</li>
                            <li>Nombre (texto)</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ __('Campos opcionales:') }}</h3>
                        <ul class="list-disc list-inside text-gray-600">
                            <li>Email (si está vacío, se genera automáticamente)</li>
                            <li>Contraseña (si está vacía, se genera una aleatoria)</li>
                        </ul>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-yellow-800">
                            <strong>{{ __('Nota:') }}</strong> 
                            {{ __('Si un usuario con el mismo email ya existe, será actualizado instead de creado.') }}
                        </p>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded p-3">
                        <p class="text-green-800">
                            <strong>{{ __('Consejo:') }}</strong> 
                            {{ __('Puedes descargar la plantilla con todos los usuarios existentes y modificarla.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
