@extends('layouts.app')

@section('content')
    <div class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Constructor Visual de Preguntas</h1>
                    <p class="mt-2 text-gray-600">Crea preguntas interactivas con múltiples tipos de respuesta</p>
                </div>
                <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver a preguntas
                </a>
            </div>

            <!-- Main content -->
            <form action="{{ route('admin.questions.store') }}" method="POST" class="grid gap-6 lg:grid-cols-3">
                @csrf

                <!-- Left sidebar - Certification selector -->
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Paso 1: Selecciona certificación</h3>
                    <label class="mt-4 block text-sm font-semibold text-slate-700">
                        Certificación
                        <select name="certification_id" id="certificationSelect" required class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
                            <option value="">-- Selecciona una certificación --</option>
                            @foreach ($certifications as $cert)
                                <option value="{{ $cert->id }}">{{ $cert->name }}</option>
                            @endforeach
                        </select>
                        @error('certification_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>

                    <!-- Type selector -->
                    <div class="mt-6 border-t border-slate-200 pt-6">
                        <h3 class="font-semibold text-slate-900">Paso 2: Tipo de pregunta</h3>
                        
                        <div class="mt-4 space-y-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-slate-200 p-4 transition hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <input type="radio" name="type" value="mcq_4" checked class="text-blue-600" id="type-mcq4" onchange="updatePreview()">
                                <div>
                                    <p class="font-semibold text-slate-900">Opción múltiple (4 opciones)</p>
                                    <p class="text-xs text-slate-500">La opción clásica con 4 respuestas</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-slate-200 p-4 transition hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <input type="radio" name="type" value="mcq_3" class="text-blue-600" id="type-mcq3" onchange="updatePreview()">
                                <div>
                                    <p class="font-semibold text-slate-900">Opción múltiple (3 opciones)</p>
                                    <p class="text-xs text-slate-500">Más simple con 3 respuestas</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-slate-200 p-4 transition hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <input type="radio" name="type" value="true_false" class="text-blue-600" id="type-tf" onchange="updatePreview()">
                                <div>
                                    <p class="font-semibold text-slate-900">Verdadero o Falso</p>
                                    <p class="text-xs text-slate-500">Solo dos opciones: Verdadero o Falso</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-slate-200 p-4 transition hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <input type="radio" name="type" value="matching" class="text-blue-600" id="type-matching" onchange="updatePreview()">
                                <div>
                                    <p class="font-semibold text-slate-900">Emparejamiento</p>
                                    <p class="text-xs text-slate-500">Conectar elementos relacionados</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-slate-200 p-4 transition hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <input type="radio" name="type" value="fill_blank" class="text-blue-600" id="type-blank" onchange="updatePreview()">
                                <div>
                                    <p class="font-semibold text-slate-900">Completar espacios</p>
                                    <p class="text-xs text-slate-500">Rellenar palabra faltante</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Main content area -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Question content -->
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-slate-900">Paso 3: Contenido de la pregunta</h3>

                        <label class="block text-sm font-semibold text-slate-700">
                            Pregunta (Texto)
                            <textarea name="prompt" id="promptField" rows="3" placeholder="Escribe el enunciado de la pregunta aquí..." required class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">{{ old('prompt') }}</textarea>
                            @error('prompt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>

                        <!-- Image upload -->
                        <label class="mt-4 block text-sm font-semibold text-slate-700">
                            Imagen (Opcional)
                            <div class="mt-2 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 transition hover:border-blue-400">
                                <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p class="mt-2 text-sm text-slate-600">Arrastra la imagen aquí o haz click para seleccionar</p>
                                <input type="file" name="image" id="imageInput" accept="image/*" class="mt-2 hidden" onchange="previewImage(this)">
                                <input type="hidden" name="image_path" id="imagePath">
                                <button type="button" onclick="document.getElementById('imageInput').click()" class="mt-3 text-blue-600 underline hover:text-blue-700">Seleccionar imagen</button>
                            </div>
                            <img id="imagePreview" src="" alt="Image preview" class="mt-3 hidden max-h-48 rounded-lg border border-slate-200">
                        </label>

                        <!-- Explanation -->
                        <label class="mt-4 block text-sm font-semibold text-slate-700">
                            Explicación de la respuesta (Opcional)
                            <textarea name="explanation" id="explanationField" rows="3" placeholder="Explica por qué la respuesta es correcta..." class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">{{ old('explanation') }}</textarea>
                            @error('explanation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                    </div>

                    <!-- Options area based on type -->
                    <div id="optionsContainer" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <!-- This will be dynamically populated by JavaScript -->
                    </div>

                    <!-- Settings -->
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-slate-900">Paso 4: Configuración</h3>
                        
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1" @checked(old('active', true)) class="rounded border-slate-300 text-blue-600">
                            Pregunta activa
                        </label>

                        <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 ml-6">
                            <input type="hidden" name="is_test_question" value="0">
                            <input type="checkbox" name="is_test_question" value="1" @checked(old('is_test_question', false)) class="rounded border-slate-300 text-blue-600">
                            Pregunta de prueba
                        </label>
                    </div>

                    <!-- Submit -->
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                            ✓ Crear pregunta
                        </button>
                        <a href="{{ route('admin.questions.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview panel at the bottom -->
    <div class="fixed bottom-0 left-0 right-0 border-t border-slate-200 bg-white p-6 shadow-2xl">
        <div class="mx-auto max-w-6xl">
            <h3 class="font-semibold text-slate-900 mb-4">Vista previa de la pregunta</h3>
            <div id="previewPanel" class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-sm text-slate-700">
                <p class="font-semibold"><span id="previewPrompt">Escribe tu pregunta aquí...</span></p>
                <div id="previewOptions" class="mt-4 space-y-2"></div>
            </div>
        </div>
    </div>

    <!-- JavaScript for dynamic form -->
    <script>
        const TYPE_TEMPLATES = {
            mcq_4: {
                fields: [
                    { name: 'option_1', label: 'Opción 1' },
                    { name: 'option_2', label: 'Opción 2' },
                    { name: 'option_3', label: 'Opción 3' },
                    { name: 'option_4', label: 'Opción 4' },
                ]
            },
            mcq_3: {
                fields: [
                    { name: 'option_1', label: 'Opción 1' },
                    { name: 'option_2', label: 'Opción 2' },
                    { name: 'option_3', label: 'Opción 3' },
                ]
            },
            true_false: {
                fields: [
                    { name: 'option_1', label: 'Verdadero', value: 'Verdadero' },
                    { name: 'option_2', label: 'Falso', value: 'Falso' },
                ]
            },
            matching: {
                fields: [
                    { name: 'option_1', label: 'Par 1 - Elemento A' },
                    { name: 'option_2', label: 'Par 1 - Elemento B' },
                    { name: 'option_3', label: 'Par 2 - Elemento A' },
                    { name: 'option_4', label: 'Par 2 - Elemento B' },
                ]
            },
            fill_blank: {
                fields: [
                    { name: 'option_1', label: 'Respuesta correcta a completar' },
                ]
            }
        };

        function updatePreview() {
            const type = document.querySelector('input[name="type"]:checked').value;
            const prompt = document.getElementById('promptField').value || 'Escribe tu pregunta aquí...';
            const correct = document.querySelector('input[name="correct_option"]')?.value || '1';
            
            document.getElementById('previewPrompt').textContent = prompt;
            
            const template = TYPE_TEMPLATES[type];
            const optionsHTML = template.fields
                .map((field, idx) => {
                    const num = idx + 1;
                    const isCorrect = (num.toString() === correct) ? '✓' : '○';
                    const value = document.querySelector(`input[name="${field.name}"]`)?.value || '';
                    return `<p><strong>${isCorrect}</strong> ${value || '(vacío)'}</p>`;
                })
                .join('');
            
            document.getElementById('previewOptions').innerHTML = optionsHTML;
            renderOptions(type);
        }

        function renderOptions(type) {
            const template = TYPE_TEMPLATES[type];
            const container = document.getElementById('optionsContainer');
            
            let html = '<h3 class="mb-4 font-semibold text-slate-900">Opción de respuesta</h3>';
            html += '<div class="space-y-3">';

            template.fields.forEach((field, idx) => {
                const num = idx + 1;
                const value = document.querySelector(`input[name="${field.name}"]`)?.value || '';
                
                html += `
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <label class="text-sm font-semibold text-slate-700">
                            ${field.label}
                            <input type="text" name="${field.name}" value="${value}" ${field.value ? 'readonly' : ''} 
                                   placeholder="Escribe la opción..." 
                                   class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-2 text-sm" 
                                   onchange="updatePreview()">
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 mt-6">
                            <input type="radio" name="correct_option" value="${num}" class="text-blue-600" onchange="updatePreview()">
                            Respuesta correcta
                        </label>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    document.getElementById('imagePath').value = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', () => {
            updatePreview();
        });

        // Update preview on prompt change
        document.getElementById('promptField').addEventListener('input', updatePreview);
    </script>

    <style>
        #previewPanel {
            padding-bottom: 2rem;
        }
    </style>
@endsection
