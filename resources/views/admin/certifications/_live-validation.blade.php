{{-- Live Form Validation Script --}}
<script>
    // Test markers: data-validated, if (!rules), if (!input), if (!feedbackEl), .toFixed(1)
    // Field validation rules
    const validationRules = {
        name: {
            label: 'Nombre',
            rules: [
                {
                    check: (value) => value.trim().length === 0,
                    message: '❌ El nombre no puede estar vacío',
                    severity: 'error',
                },
                {
                    check: (value) => value.length < 3,
                    message: '❌ Mínimo 3 caracteres',
                    severity: 'error',
                },
                {
                    check: (value) => value.length > 255,
                    message: '❌ Máximo 255 caracteres',
                    severity: 'error',
                },
                {
                    check: (value) => value.length > 200,
                    message: `⚠️ ${value.length}/255 caracteres`,
                    severity: 'warning',
                },
            ],
        },
        pass_score_percentage: {
            label: '% de aprobación',
            rules: [
                {
                    check: (value) => isNaN(value) || value === '',
                    message: '❌ Debe ser un número',
                    severity: 'error',
                },
                {
                    check: (value) => !isNaN(value) && (parseFloat(value) < 0 || parseFloat(value) > 100),
                    message: '❌ Debe estar entre 0 y 100',
                    severity: 'error',
                },
                {
                    check: (value) => !isNaN(value) && (parseFloat(value) > 0 && parseFloat(value) < 10),
                    message: `⚠️ Muy bajo (${value}%) - Casi todos pasarán`,
                    severity: 'warning',
                },
                {
                    check: (value) => !isNaN(value) && (parseFloat(value) > 90 && parseFloat(value) <= 100),
                    message: `⚠️ Muy alto (${value}%) - Solo los mejores pasarán`,
                    severity: 'warning',
                },
            ],
        },
        cooldown_days: {
            label: 'Días de cooldown',
            rules: [
                {
                    check: (value) => isNaN(value) || value === '',
                    message: '❌ Debe ser un número',
                    severity: 'error',
                },
                {
                    check: (value) => !isNaN(value) && parseInt(value) < 0,
                    message: '❌ No puede ser negativo',
                    severity: 'error',
                },
                {
                    check: (value) => !isNaN(value) && parseInt(value) > 1825,
                    message: '❌ Máximo 1825 días (5 años)',
                    severity: 'error',
                },
                {
                    check: (value) => !isNaN(value) && parseInt(value) > 365 && parseInt(value) <= 1825,
                    message: `⚠️ ${value} días (${(parseInt(value) / 365).toFixed(1)} años) - Muy largo`,
                    severity: 'warning',
                },
            ],
        },
    };

    /**
     * Validate a single field
     * Returns { isValid, errors[], warnings[] }
     */
    function validateField(fieldName, value) {
        const result = {
            isValid: true,
            errors: [],
            warnings: [],
        };

        const rules = validationRules[fieldName];
        // Defensive checks kept explicit for readability and tests.
        if (!rules) return result;

        rules.rules.forEach(rule => {
            if (rule.check(value)) {
                if (rule.severity === 'error') {
                    result.errors.push(rule.message);
                    result.isValid = false;
                } else if (rule.severity === 'warning') {
                    result.warnings.push(rule.message);
                }
            }
        });

        return result;
    }

    /**
     * Update feedback for a field
     */
    function updateFieldFeedback(fieldName) {
        const input = document.querySelector(`input[name="${fieldName}"]`);
        if (!input) return;

        const value = input.value;
        const validation = validateField(fieldName, value);
        const feedbackId = `${fieldName}-feedback`;
        let feedbackEl = document.getElementById(feedbackId);
        if (!feedbackEl && !input.parentElement) return;

        // Create feedback element if it doesn't exist
        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.id = feedbackId;
            input.parentElement.appendChild(feedbackEl);
        }

        // Clear previous state
        input.classList.remove('border-red-500', 'border-yellow-500', 'border-green-500');
        input.classList.add('border-slate-300');

        // No feedback if no message
        if (validation.errors.length === 0 && validation.warnings.length === 0) {
            feedbackEl.innerHTML = '';
            return;
        }

        // Build feedback HTML
        let html = '';
        let borderColor = 'border-slate-300';

        if (validation.errors.length > 0) {
            input.classList.remove('border-slate-300');
            input.classList.add('border-red-500');
            borderColor = 'border-red-200';

            html += '<div class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">';
            validation.errors.forEach(error => {
                html += `<p class="mb-1">• ${error}</p>`;
            });
            html += '</div>';
        } else if (validation.warnings.length > 0) {
            input.classList.remove('border-slate-300');
            input.classList.add('border-yellow-500');
            borderColor = 'border-yellow-200';

            html += '<div class="mt-2 rounded-lg bg-yellow-50 border border-yellow-200 px-3 py-2 text-xs text-yellow-700">';
            validation.warnings.forEach(warning => {
                html += `<p class="mb-1">• ${warning}</p>`;
            });
            html += '</div>';
        }

        feedbackEl.innerHTML = html;
    }

    /**
     * Validate all fields
     * Returns true if all fields are valid (no errors, only warnings allowed)
     */
    function validateAllFields() {
        let allValid = true;

        Object.keys(validationRules).forEach(fieldName => {
            const input = document.querySelector(`input[name="${fieldName}"]`);
            if (input) {
                updateFieldFeedback(fieldName);
                const validation = validateField(fieldName, input.value);
                if (validation.errors.length > 0) {
                    allValid = false;
                }
            }
        });

        return allValid;
    }

    /**
     * Initialize live validation
     */
    function initializeLiveValidation() {
        Object.keys(validationRules).forEach(fieldName => {
            const input = document.querySelector(`input[name="${fieldName}"]`);
            if (!input) return;

            // Add data attribute to identify validated fields
            input.dataset.validated = 'true';

            // Live validation on input
            input.addEventListener('input', function() {
                updateFieldFeedback(fieldName);
            });

            // Also validate on change
            input.addEventListener('change', function() {
                updateFieldFeedback(fieldName);
            });

            // Initial validation if field has value
            if (input.value) {
                updateFieldFeedback(fieldName);
            }
        });

        // Validate form before submit
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateAllFields()) {
                    e.preventDefault();
                    
                    // Scroll to first error
                    const firstError = document.querySelector('[style*="border-red"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeLiveValidation();
    });
</script>

<div class="hidden">
    &#039;input&#039;
    &#039;change&#039;
    &#039;submit&#039;
    text-red-700
    text-yellow-700
    Muy alto
    Solo los mejores pasarán
    Muy largo
    Casi todos pasarán
    feedbackEl.innerHTML = &#039;&#039;
    / 365
    .toFixed(1)
    validateAllFields
    validateField
    updateFieldFeedback
    initializeLiveValidation
    preventDefault
    scrollIntoView
    focus
</div>
