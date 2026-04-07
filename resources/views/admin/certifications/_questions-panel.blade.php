{{-- Questions Panel for Certification Editing --}}
<div id="questionsPanel" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50 to-slate-50 p-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-900">📋 Estado de Preguntas</h3>
            <p class="text-xs text-slate-600 mt-1">Validación en vivo del contenido disponible</p>
        </div>
        <button type="button" onclick="reloadQuestionsPanel()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
            🔄 Recargar
        </button>
    </div>

    {{-- Statistics --}}
    <div class="grid gap-3 sm:grid-cols-3 mb-4">
        <div class="rounded-xl bg-white px-4 py-3 border border-blue-200 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-blue-600 font-semibold">Activas</p>
            <p id="totalActiveQuestions" class="text-2xl font-bold text-blue-900 mt-1">0</p>
            <p class="text-xs text-blue-700 mt-1">disponibles para usar</p>
        </div>

        <div class="rounded-xl bg-white px-4 py-3 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Requeridas</p>
            <div class="flex items-baseline gap-2 mt-1">
                <p id="requiredInput" class="text-2xl font-bold text-slate-900">0</p>
                <p class="text-xs text-slate-600">(en config)</p>
            </div>
        </div>

        <div class="rounded-xl bg-white px-4 py-3 border border-slate-200 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Estado</p>
            <p id="statusBadge" class="text-sm font-bold mt-2">
                <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-yellow-800">
                    ⏳ Cargando...
                </span>
            </p>
        </div>
    </div>

    {{-- Validation Alert --}}
    <div id="validationAlert" class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 hidden">
        {{-- Populated by JavaScript --}}
    </div>

    {{-- Questions List --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
            <p class="text-sm font-semibold text-slate-700">Preguntas Disponibles</p>
        </div>

        <div id="questionsList" class="max-h-48 overflow-y-auto">
            <div class="px-4 py-8 text-center">
                <p class="text-sm text-slate-500">⏳ Cargando preguntas...</p>
            </div>
        </div>
    </div>

    {{-- Info Footer --}}
    <div class="mt-4 rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-xs text-blue-800">
        <p class="font-semibold mb-1">💡 Cómo funciona</p>
        <ul class="space-y-1 text-blue-700">
            <li>• Se muestran solo <strong>preguntas activas</strong> para esta certificación</li>
            <li>• Si cambias el valor de "Preguntas requeridas", la validación se actualiza automáticamente</li>
            <li>• No puedes guardar si necesitas más preguntas que las disponibles</li>
        </ul>
    </div>
</div>

<script>
    // Test marker: questionsPanel
    const certificationId = '{{ $certification->id ?? "" }}';
    let cachedQuestions = [];

    async function loadQuestionsData() {
        if (!certificationId) {
            console.warn('Certification ID not found');
            return;
        }

        try {
            const response = await fetch(`/admin/api/certifications/${certificationId}/available-questions`);
            const data = await response.json();

            if (data.success) {
                cachedQuestions = data.questions || [];
                updateQuestionsPanel();
            } else {
                showPanelError('No se pudieron cargar las preguntas.');
            }
        } catch (error) {
            console.error('Failed to load questions:', error);
            showPanelError('Error al conectar con el servidor.');
        }
    }

    function updateQuestionsPanel() {
        const totalActive = cachedQuestions.length;
        const requiredInput = document.querySelector('input[name="questions_required"]');
        const required = requiredInput ? parseInt(requiredInput.value) || 0 : 0;

        // Update statistics
        document.getElementById('totalActiveQuestions').textContent = totalActive;
        document.getElementById('requiredInput').textContent = required;

        // Update status badge
        updateStatusBadge(totalActive, required);

        // Update validation alert
        updateValidationAlert(totalActive, required);

        // Update questions list
        renderQuestionsList(totalActive);
    }

    function updateStatusBadge(active, required) {
        const badge = document.getElementById('statusBadge');
        let html = '';

        if (active === 0) {
            html = '<span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">❌ Sin preguntas</span>';
        } else if (active < required) {
            html = `<span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">❌ Insuficientes (${active}/${required})</span>`;
        } else if (active === required) {
            html = `<span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">⚠️ Exacto (${active}/${required})</span>`;
        } else {
            html = `<span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">✅ OK (${active}/${required})</span>`;
        }

        badge.innerHTML = html;
    }

    function updateValidationAlert(active, required) {
        const alert = document.getElementById('validationAlert');

        if (active >= required && required > 0) {
            // All good
            alert.classList.add('hidden');
            alert.innerHTML = '';
            return;
        }

        // Show warning or error
        let html = '';
        let bgColor = 'bg-blue-50 border-blue-200';
        let textColor = 'text-blue-800';

        if (required === 0) {
            // No requirement set
            html = '<p class="font-semibold mb-1">⚠️ Sin requerimiento de preguntas</p>';
            html += '<p class="text-xs">Establece "Preguntas requeridas" para habilitar la validación.</p>';
            bgColor = 'bg-yellow-50 border-yellow-200';
            textColor = 'text-yellow-800';
        } else if (active < required) {
            // Not enough
            const missing = required - active;
            html = `<p class="font-semibold mb-1">❌ Preguntas insuficientes</p>`;
            html += `<p class="text-xs">Necesitas <strong>${required}</strong> preguntas pero solo tienes <strong>${active}</strong> activas. `;
            html += `Te faltan <strong>${missing}</strong> pregunta${missing > 1 ? 's' : ''}.`;
            html += `</p>`;
            bgColor = 'bg-red-50 border-red-200';
            textColor = 'text-red-800';
        } else if (active === required) {
            // Exactly matching
            html = `<p class="font-semibold mb-1">⚠️ Cantidad exacta</p>`;
            html += `<p class="text-xs">Tienes exactamente ${required} preguntas requeridas. Considera tener unas de más por si necesitas deshabilitar alguna.</p>`;
            bgColor = 'bg-yellow-50 border-yellow-200';
            textColor = 'text-yellow-800';
        }

        alert.className = `rounded-xl border px-4 py-3 text-sm ${bgColor} ${textColor} mb-4`;
        alert.innerHTML = html;
        alert.classList.remove('hidden');
    }

    function renderQuestionsList(totalActive) {
        const list = document.getElementById('questionsList');

        if (cachedQuestions.length === 0) {
            list.innerHTML = '<div class="px-4 py-8 text-center"><p class="text-sm text-red-600">❌ No hay preguntas activas en esta certificación</p></div>';
            return;
        }

        let html = '';
        cachedQuestions.forEach((q, idx) => {
            const translationCount = q.translations_count || 0;
            const typeLabel = getQuestionTypeLabel(q.type);
            
            html += `
                <div class="px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-900 line-clamp-2">${escapeHtml(q.prompt)}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="inline-block bg-slate-100 px-2 py-1 rounded">${typeLabel}</span>
                                <span>ID: ${q.id}</span>
                                ${translationCount > 1 ? `<span class="text-green-600">🌐 ${translationCount} idiomas</span>` : ''}
                            </div>
                        </div>
                        <span class="text-lg text-slate-400 ml-2">#${idx + 1}</span>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
    }

    function getQuestionTypeLabel(type) {
        const labels = {
            'mcq_4': 'Multiple Choice (4)',
            'mcq_3': 'Multiple Choice (3)',
            'mcq_5': 'Multiple Choice (5)',
            'true_false': 'Verdadero/Falso',
            'matching': 'Coincidencia',
            'essay': 'Ensayo',
        };
        return labels[type] || type;
    }

    function showPanelError(message) {
        const list = document.getElementById('questionsList');
        list.innerHTML = `<div class="px-4 py-8 text-center"><p class="text-sm text-red-600">⚠️ ${message}</p></div>`;
        
        const badge = document.getElementById('statusBadge');
        badge.innerHTML = '<span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">❌ Error cargando</span>';
    }

    function reloadQuestionsPanel() {
        document.getElementById('questionsList').innerHTML = '<div class="px-4 py-8 text-center"><p class="text-sm text-slate-500">⏳ Recargando...</p></div>';
        loadQuestionsData();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Watch for changes in questions_required input
    function initializeQuestionsWatcher() {
        const requiredInput = document.querySelector('input[name="questions_required"]');
        if (!requiredInput) return;

        requiredInput.addEventListener('change', updateQuestionsPanel);
        requiredInput.addEventListener('input', updateQuestionsPanel);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadQuestionsData();
        initializeQuestionsWatcher();
    });
</script>
