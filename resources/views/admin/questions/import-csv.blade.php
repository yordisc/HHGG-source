@extends('admin.layout')

@section('title', 'Importar CSV - Preguntas')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">📥 Importar Preguntas desde CSV</h1>
        <p class="text-gray-600 mt-2">Sube un archivo CSV para crear o actualizar preguntas en lote</p>
        <p class="text-xs text-gray-500 mt-1">Flujo recomendado: plantilla -> completar datos -> validar vista previa -> confirmar importación.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <h3 class="font-bold text-red-900 mb-2">❌ Errores</h3>
            <ul class="text-red-700 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario de Carga -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <form method="POST" action="{{ route('admin.questions.validate.csv') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-8">
                <label for="csv_file" class="block text-sm font-semibold text-gray-700 mb-3">
                    📄 Archivo CSV
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition cursor-pointer"
                     onclick="document.getElementById('csv_file').click();">
                    <input
                        type="file"
                        id="csv_file"
                        name="csv_file"
                        accept=".csv"
                        class="hidden"
                        required
                        onchange="this.form.parentElement.parentElement.querySelector('[data-filename]').textContent = this.files[0].name"
                    >
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v24a4 4 0 004 4h24a4 4 0 004-4V20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M32 4v12h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-700">Clickea para seleccionar o arrastra tu archivo</p>
                    <p data-filename class="mt-1 text-xs text-gray-500">Ningún archivo seleccionado</p>
                </div>
            </div>

            <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                🔍 Validar y Previsualizar
            </button>
        </form>
    </div>

    <!-- Instrucciones -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Formato del CSV -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-bold text-blue-900 mb-4">📋 Formato del CSV</h3>
            <p class="text-blue-800 text-sm mb-3">Tu archivo debe tener estas columnas (en este orden):</p>
            <div class="bg-white rounded border border-blue-200 p-3 text-xs font-mono text-gray-700 overflow-x-auto mb-4">
                <pre>question_id,cert_type,language,prompt,option_1,option_2,option_3,option_4,correct_option,active</pre>
            </div>
            <p class="text-blue-700 text-sm"><strong>Ejemplo:</strong></p>
            <div class="bg-white rounded border border-blue-200 p-3 text-xs font-mono text-gray-700 overflow-x-auto">
                <pre>,hhgc,en,What is your name?,Alice,Bob,Charlie,Diana,1,1
,hhgc,es,¿Cuál es tu nombre?,Alicia,Roberto,Carlos,Diana,1,1</pre>
            </div>
            <p class="mt-3 text-xs text-blue-700">Consejo: crea primero la fila en `en` (base), luego agrega traducciones con el mismo `question_id`.</p>
        </div>

        <!-- Guía de uso -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="font-bold text-green-900 mb-4">✓ Pasos para importar</h3>
            <ol class="text-green-800 text-sm space-y-2">
                <li><strong>1.</strong> Descarga el template CSV haciendo click <a href="{{ route('admin.questions.template.csv') }}" class="text-blue-600 hover:underline">aquí</a></li>
                <li><strong>2.</strong> Abre el archivo en Excel o Google Sheets</li>
                <li><strong>3.</strong> Completa tus preguntas y traducciones</li>
                <li><strong>4.</strong> Guarda como CSV (Texto separado por comas)</li>
                <li><strong>5.</strong> Sube el archivo aquí</li>
                <li><strong>6.</strong> Revisa la vista previa y confirma</li>
            </ol>
            <p class="mt-3 text-xs text-green-700">Si hay errores, corrige el CSV y vuelve a cargarlo; no confirmes con advertencias críticas.</p>
        </div>
    </div>

    <!-- Información de columnas -->
    <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h3 class="font-bold text-gray-900 mb-4">📖 Descripción de Columnas</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">question_id</code></p>
                <p class="text-gray-600 mt-1">Dejar vacío para crear nueva pregunta. Usar ID existente para actualizar.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">cert_type</code></p>
                <p class="text-gray-600 mt-1">Slug de la certificación (ej: hhgc, hhgp, etc)</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">language</code></p>
                <p class="text-gray-600 mt-1">Código de idioma (en, es, fr, etc). Inglés crea la pregunta base.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">prompt</code></p>
                <p class="text-gray-600 mt-1">Texto de la pregunta</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">option_1-4</code></p>
                <p class="text-gray-600 mt-1">Cuatro opciones de respuesta</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">correct_option</code></p>
                <p class="text-gray-600 mt-1">Número (1-4) de la opción correcta</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700"><code class="bg-gray-200 px-2 py-1 rounded">active</code></p>
                <p class="text-gray-600 mt-1">1 para activa, 0 para inactiva</p>
            </div>
        </div>
    </div>

    <!-- Botón de ayuda -->
    <div class="mt-8 text-center">
        <a href="{{ route('admin.questions.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
            ← Volver a Preguntas
        </a>
    </div>
</div>
@endsection
