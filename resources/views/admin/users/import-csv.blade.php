@extends('layouts.app')

@section('content')
<section class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mx-auto max-w-7xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Importar Usuarios</h1>
                <p class="mt-2 text-slate-600">Carga masiva de usuarios mediante archivo CSV</p>
                <p class="mt-1 text-xs text-slate-500">Flujo recomendado: descargar plantilla -> completar -> importar -> revisar resumen.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mx-auto max-w-7xl">
            <div class="flex items-start gap-4 rounded-xl border border-rose-200 bg-gradient-to-r from-rose-50 to-rose-50/50 p-6 shadow-sm">
                <svg class="mt-0.5 h-6 w-6 flex-shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div>
                    <h3 class="font-bold text-rose-900">Errores en la validación</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm text-rose-800">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto max-w-7xl grid gap-8 lg:grid-cols-3">
        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50 px-6 py-6">
                    <h2 class="text-lg font-bold text-slate-900">Cargar archivo CSV</h2>
                    <p class="mt-1 text-sm text-slate-600">Selecciona un archivo para importar usuarios</p>
                    <p class="mt-1 text-xs text-slate-500">Acepta CSV/TXT separado por comas.</p>
                </div>
                
                <form action="{{ route('admin.users.import.csv') }}" method="POST" enctype="multipart/form-data" class="space-y-6 p-6">
                    @csrf

                    <!-- File Upload -->
                    <div>
                        <label for="file" class="block text-sm font-semibold text-slate-900 mb-3">Archivo CSV</label>
                        <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 transition hover:border-blue-400 hover:bg-blue-50">
                            <input type="file" id="file" name="file" accept=".csv,.txt" required
                                   class="hidden" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'Selecciona un archivo'">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <p class="mt-4 text-sm font-medium text-slate-900">
                                    <label for="file" class="cursor-pointer text-blue-600 hover:text-blue-700">Selecciona un archivo</label>
                                    o arrastra aquí
                                </p>
                                <p class="text-xs text-slate-500 mt-1">CSV o TXT (máximo 5 MB)</p>
                                <p id="file-name" class="mt-3 text-xs font-mono text-slate-600">Sin archivo seleccionado</p>
                            </div>
                        </div>
                    </div>

                    <!-- Format Example -->
                    <div class="rounded-lg border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-50/50 p-4">
                        <h3 class="text-sm font-bold text-blue-900 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/></svg>
                            Formato esperado
                        </h3>
                        <p class="text-xs font-mono text-blue-800 mb-3 bg-white rounded px-3 py-2 border border-blue-200">
                            ID,Nombre,Email,Contraseña
                        </p>
                        <pre class="text-xs bg-white rounded border border-blue-200 overflow-x-auto p-3 text-blue-900">1,Juan Pérez,juan@example.com,password123
2,María García,,generada_automaticamente
3,Pedro López</pre>
                        <p class="mt-2 text-xs text-blue-700">Si no envías email o contraseña, el sistema los genera automáticamente.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between gap-3 border-t border-slate-200 pt-6">
                        <a href="{{ route('admin.users.export.csv') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Descargar plantilla
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 font-semibold text-white shadow-lg transition hover:shadow-xl">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información -->
        <div class="space-y-6">
            <!-- Guía Campos -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50 px-6 py-4">
                    <h3 class="text-base font-bold text-slate-900">Campos</h3>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">*</span>
                            Obligatorios
                        </p>
                        <ul class="space-y-1 text-sm text-slate-600">
                            <li>• <strong>ID</strong> - Número único</li>
                            <li>• <strong>Nombre</strong> - Texto completo</li>
                        </ul>
                    </div>
                    <div class="border-t border-slate-200 pt-4">
                        <p class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">?</span>
                            Opcionales
                        </p>
                        <ul class="space-y-1 text-sm text-slate-600">
                            <li>• <strong>Email</strong> - Se genera si está vacío</li>
                            <li>• <strong>Contraseña</strong> - Se genera aleatoria</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-50/50 p-6">
                <h3 class="font-bold text-amber-900 mb-3 flex items-center gap-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Comportamiento
                </h3>
                <div class="space-y-3 text-sm">
                    <p class="text-amber-900">
                        Si un usuario con el mismo <strong>email</strong> ya existe, será <strong>actualizado</strong> en lugar de creado.
                    </p>
                    <p class="text-amber-900">
                        Puedes descargar la plantilla con todos los usuarios actuales y modificarla para actualizaciones rápidas.
                    </p>
                    <p class="text-amber-900">
                        Recomendación: prueba primero con 2-3 filas para validar el formato antes de una carga masiva.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Drag and drop functionality
    const fileInput = document.getElementById('file');
    const dropZone = fileInput.parentElement.parentElement;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        document.getElementById('file-name').textContent = files[0]?.name || 'Archivo cargado';
    });
</script>
@endsection
