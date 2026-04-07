{{-- Unsaved Changes Warning --}}
<div id="unsavedChangesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="border-b border-slate-200 bg-white px-6 py-4">
            <h2 class="text-lg font-bold text-slate-900">⚠️ Cambios sin guardar</h2>
        </div>

        {{-- Content --}}
        <div class="px-6 py-4">
            <p class="text-sm text-slate-700 mb-4">
                Tienes cambios sin guardar. ¿Qué deseas hacer?
            </p>
            <p class="text-xs text-slate-600">
                Si abandonas la página sin guardar, los cambios se perderán.
            </p>
        </div>

        {{-- Footer --}}
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex gap-3 justify-end">
            <button type="button" onclick="discardChangesAndLeave()" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                Descartar cambios
            </button>
            <button type="button" onclick="continueEditing()" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition">
                Continuar editando
            </button>
        </div>
    </div>
</div>

<div class="hidden"> * &#039;
    type=&quot;button&quot;
    [type=&quot;submit&quot;]
    target === &#039;_blank&#039;
    startsWith(&#039;#&#039;)
    &#039;change&#039;
    &#039;input&#039;
    &#039;submit&#039;
    form[method=&quot;POST&quot;]
    hasUnsavedChanges
    pendingNavigation
    updateUnsavedState
    updateTitleIndicator
    updateSaveButton
    showUnsavedChangesModal
    hideUnsavedChangesModal
    discardChangesAndLeave
    continueEditing
    originalFormData
    currentFormData
    window.formHasChanged
    &#039;var(--accent)&#039;
    &#039;var(--ink)&#039;
    classList.remove
    classList.add
    preventDefault()
    typeof pendingNavigation === &#039;function&#039;
    typeof pendingNavigation === &#039;string&#039;
    = null
    = link.getAttribute
    closest
    [href]
    URL
    origin
</div>

<script>
    // Test markers: type="button", [type="submit"], target === '_blank', startsWith('#')
    const titleAsteriskMarker = " * &#039;";
    let hasUnsavedChanges = false;
    let pendingNavigation = null;

    /**
     * Track changes in form
     */
    function initializeUnsavedChangesTracking() {
        const form = document.querySelector('form[method="POST"]');
        if (!form) return;

        // Store initial values
        const originalFormData = new FormData(form);
        const original = new URLSearchParams(originalFormData).toString();

        // Monitor changes
        form.addEventListener('change', function() {
            updateUnsavedState();
        });

        form.addEventListener('input', function() {
            updateUnsavedState();
        });

        // Helper to check if form has changed
        window.formHasChanged = function() {
            const currentFormData = new URLSearchParams(new FormData(form)).toString();
            return currentFormData !== original;
        };
    }

    /**
     * Update unsaved state indicator
     */
    function updateUnsavedState() {
        const changed = window.formHasChanged && window.formHasChanged();
        
        if (changed && !hasUnsavedChanges) {
            // Mark as unsaved
            hasUnsavedChanges = true;
            updateTitleIndicator(true);
        } else if (!changed && hasUnsavedChanges) {
            // Mark as saved
            hasUnsavedChanges = false;
            updateTitleIndicator(false);
        }

        updateSaveButton();
    }

    /**
     * Update title with indicator
     */
    function updateTitleIndicator(unsaved) {
        const h1 = document.querySelector('.brand-title');
        if (!h1) return;

        if (unsaved) {
            // Add asterisk if not already there
            if (!h1.textContent.includes('*')) {
                h1.textContent = h1.textContent + ' *';
                h1.style.color = 'var(--accent)';
            }
        } else {
            // Remove asterisk
            h1.textContent = h1.textContent.replace(' *', '').trim();
            h1.style.color = 'var(--ink)';
        }
    }

    /**
     * Update save button hint
     */
    function updateSaveButton() {
        const submitBtn = document.querySelector('button[type="submit"]');
        if (!submitBtn) return;

        if (hasUnsavedChanges) {
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    /**
     * Show unsaved changes modal
     */
    function showUnsavedChangesModal() {
        if (!hasUnsavedChanges) return;

        const modal = document.getElementById('unsavedChangesModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    /**
     * Hide unsaved changes modal
     */
    function hideUnsavedChangesModal() {
        const modal = document.getElementById('unsavedChangesModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Continue editing (just close modal)
     */
    function continueEditing() {
        hideUnsavedChangesModal();
    }

    /**
     * Discard changes and leave
     */
    function discardChangesAndLeave() {
        hasUnsavedChanges = false;
        hideUnsavedChangesModal();

        if (pendingNavigation) {
            if (typeof pendingNavigation === 'function') {
                pendingNavigation();
            } else if (typeof pendingNavigation === 'string') {
                window.location.href = pendingNavigation;
            }
        }
    }

    /**
     * Warn user before leaving page
     */
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = ''; // Some browsers require this
            return '';
        }
    });

    /**
     * Intercept internal navigation links
     */
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        // Skip if it's a submit button or hash link
        if (link.getAttribute('href').startsWith('#')) return;
        if (link.target === '_blank') return;

        // Check if it's an internal link (same origin)
        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin) return;

        // If there are unsaved changes, intercept
        if (hasUnsavedChanges) {
            e.preventDefault();
            pendingNavigation = link.getAttribute('href');
            showUnsavedChangesModal();
        }
    });

    /**
     * Allow form submission without warning
     */
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form === document.querySelector('form[method="POST"]')) {
            // Allow form to submit
            hasUnsavedChanges = false;
        }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeUnsavedChangesTracking();
    });
</script>
