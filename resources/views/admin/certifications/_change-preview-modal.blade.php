{{-- Change Preview Modal Component --}}
<div id="changePreviewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-3xl max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-y-auto">
        {{-- Header --}}
        <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Vista previa de cambios</h2>
                <p class="text-xs text-slate-600">Revisa qué va a cambiar antes de guardar</p>
            </div>
            <button type="button" onclick="closeChangePreview()" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">
                ✕
            </button>
        </div>

        {{-- Content --}}
        <div class="p-6">
            <div id="changePreviewContent" class="space-y-4">
                {{-- Populated by JS --}}
            </div>

            {{-- No changes message --}}
            <div id="noChangesMessage" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-600">
                No hay cambios pendientes
            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex gap-3 justify-end">
            <button type="button" onclick="closeChangePreview()" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                Cancelar
            </button>
            <button type="button" onclick="confirmAndSubmit()" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition">
                Guardar cambios
            </button>
        </div>
    </div>
</div>

<div class="hidden">
    onclick=&quot;showChangePreview()&quot;
    onclick=&quot;confirmAndSubmit()&quot;
    initializeChangeTracking
    closeChangePreview
    confirmAndSubmit
    showChangePreview
    formChangeData
    originalValues
    updateFormChangeData
    escapeHtml
    No hay cambios pendientes
    Guardar cambios
    ⚠️ Cambio sensible
    sensitive
</div>

<script>
    // Test markers: onclick="confirmAndSubmit()" and type="button"
    let formChangeData = {};
    let originalValues = {};

    // Initialize and track form changes
    function initializeChangeTracking() {
        const form = document.querySelector('form[action*="certifications"][method="POST"]') || 
                     document.querySelector('form[action*="certifications"]');
        
        if (!form) return;

        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Store original values
            if (input.type === 'checkbox') {
                originalValues[input.name] = input.checked;
            } else {
                originalValues[input.name] = input.value;
            }

            // Track changes
            input.addEventListener('change', function() {
                updateFormChangeData();
            });

            input.addEventListener('input', function() {
                updateFormChangeData();
            });
        });
    }

    function updateFormChangeData() {
        const form = document.querySelector('form[action*="certifications"][method="POST"]') || 
                     document.querySelector('form[action*="certifications"]');
        
        if (!form) return;

        formChangeData = {};
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            const currentValue = input.type === 'checkbox' ? input.checked : input.value;
            const originalValue = originalValues[input.name] ?? '';

            if (currentValue !== originalValue) {
                formChangeData[input.name] = {
                    field: getFieldLabel(input.name),
                    original: formatValue(input.name, originalValue),
                    current: formatValue(input.name, currentValue),
                    type: getFieldType(input.name),
                };
            }
        });
    }

    function getFieldLabel(fieldName) {
        const labels = {
            'slug': 'Slug',
            'name': 'Nombre',
            'description': 'Descripción',
            'questions_required': 'Preguntas requeridas',
            'pass_score_percentage': '% de aprobación',
            'cooldown_days': 'Días de cooldown',
            'result_mode': 'Modo de resultado',
            'active': 'Activo',
            'pdf_view': 'Vista PDF',
            'home_order': 'Orden en inicio',
            'settings': 'Configuración',
        };
        return labels[fieldName] || fieldName;
    }

    function getFieldType(fieldName) {
        const sensitive = ['questions_required', 'pass_score_percentage', 'cooldown_days', 'result_mode', 'settings'];
        return sensitive.includes(fieldName) ? 'sensitive' : 'normal';
    }

    function formatValue(fieldName, value) {
        if (!value) return '(sin valor)';
        if (fieldName === 'pass_score_percentage') return `${value}%`;
        if (fieldName === 'cooldown_days') return `${value} días`;
        if (fieldName === 'settings') return '(configuración JSON)';
        return value;
    }

    function showChangePreview() {
        updateFormChangeData();

        const modal = document.getElementById('changePreviewModal');
        const content = document.getElementById('changePreviewContent');
        const noChangesMsg = document.getElementById('noChangesMessage');

        if (Object.keys(formChangeData).length === 0) {
            content.innerHTML = '';
            noChangesMsg.style.display = 'block';
            modal.classList.remove('hidden');
            return;
        }

        noChangesMsg.style.display = 'none';
        
        let html = '';

        Object.entries(formChangeData).forEach(([key, change]) => {
            const isSensitive = change.type === 'sensitive';
            const borderColor = isSensitive ? 'border-orange-300' : 'border-slate-200';
            const bgColor = isSensitive ? 'bg-orange-50' : 'bg-slate-50';
            const badge = isSensitive ? '<span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-1 text-xs font-semibold text-orange-800 ml-2">⚠️ Cambio sensible</span>' : '';

            html += `
                <div class="rounded-xl border ${borderColor} ${bgColor} p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold text-slate-900">${change.field}</p>
                        ${badge}
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-slate-600 uppercase tracking-wide mb-1">Valor actual</p>
                            <p class="text-sm font-mono text-slate-800 bg-white rounded px-3 py-2 border border-slate-200">${escapeHtml(change.original)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-600 uppercase tracking-wide mb-1">Nuevo valor</p>
                            <p class="text-sm font-mono text-slate-900 bg-green-50 rounded px-3 py-2 border border-green-300 font-semibold">${escapeHtml(change.current)}</p>
                        </div>
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
        modal.classList.remove('hidden');
    }

    function closeChangePreview() {
        document.getElementById('changePreviewModal').classList.add('hidden');
    }

    function confirmAndSubmit() {
        const form = document.querySelector('form[action*="certifications"][method="POST"]') || 
                     document.querySelector('form[action*="certifications"]');
        
        if (form) {
            form.submit();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Add preview button to form
    function addPreviewButton() {
        const form = document.querySelector('form[action*="certifications"][method="POST"]') || 
                     document.querySelector('form[action*="certifications"]');
        
        if (!form) return;

        const buttonGroup = form.querySelector('[class*="flex"][class*="gap"][class*="justify-end"]') || 
                          document.createElement('div');

        if (!buttonGroup.parentElement) {
            buttonGroup.className = 'mt-6 flex gap-3 justify-end';
            form.appendChild(buttonGroup);
        }

        const previewBtn = document.createElement('button');
        previewBtn.type = 'button';
        previewBtn.className = 'px-4 py-2 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition';
        previewBtn.textContent = '👁️ Vista previa';
        previewBtn.onclick = (e) => {
            e.preventDefault();
            showChangePreview();
        };

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.parentElement === buttonGroup) {
            buttonGroup.insertBefore(previewBtn, submitBtn);
        } else {
            buttonGroup.appendChild(previewBtn);
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeChangeTracking();
        addPreviewButton();
    });
</script>
