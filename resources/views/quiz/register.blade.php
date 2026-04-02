@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl rounded-lg border border-slate-300 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="brand-title text-2xl font-bold text-[var(--ink)] sm:text-3xl">{{ __('app.registration_title') }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ __('app.registration_subtitle') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ __('app.registration_note') }}</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-400 bg-red-50 p-4">
                <h3 class="text-sm font-semibold text-red-800">{{ __('validation.errors') }}</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm text-red-700">• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('quiz.start') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="cert_type" value="{{ $certType }}">

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.first_name') }} <span class="text-red-600">*</span></label>
                <input name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" class="mt-1 w-full rounded-md border @error('first_name') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.last_name') }} <span class="text-red-600">*</span></label>
                <input name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" class="mt-1 w-full rounded-md border @error('last_name') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.document') }} <span class="text-red-600">*</span></label>
                <input name="document" id="document" type="text" value="{{ old('document') }}" autocomplete="off" placeholder="AB-12345678" class="mt-1 w-full rounded-md border @error('document') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                @error('document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500" id="documentHint">{{ __('app.document_format_help', ['example' => 'AB-12345678']) }}</p>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.country') }} <span class="text-red-600">*</span></label>
                <select name="country_code" id="countryCode" class="mt-1 w-full rounded-md border @error('country_code') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                    <option value="">{{ __('app.select_country') }}</option>
                    @foreach($countryOptions as $code => $name)
                        <option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $name }} ({{ $code }})</option>
                    @endforeach
                </select>
                @error('country_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.document_type') }} <span class="text-red-600">*</span></label>
                <select name="document_type" id="documentType" class="mt-1 w-full rounded-md border @error('document_type') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                    <option value="">{{ __('app.select_document_type') }}</option>
                </select>
                @error('document_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <p id="eligibilityStatus" class="hidden rounded-md border px-3 py-2 text-sm"></p>

            <button type="submit" class="w-full rounded-md bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50" id="submitBtn">
                {{ __('app.begin_quiz') }}
            </button>
            <p class="text-center text-sm text-slate-600">{{ __('app.registration_privacy') }}</p>
        </form>
    </section>

    <script>
        (() => {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            const countryCodeEl = document.getElementById('countryCode');
            const documentTypeEl = document.getElementById('documentType');
            const documentEl = document.getElementById('document');
            const statusEl = document.getElementById('eligibilityStatus');
            const hintEl = document.getElementById('documentHint');
            const certType = form.querySelector('input[name="cert_type"]').value;
            const csrfToken = form.querySelector('input[name="_token"]').value;
            const documentTypeMap = @json($documentTypeMap);
            const documentHintMap = @json($documentHintMap);
            const specificFormatCountries = @json($specificFormatCountries);
            const oldDocumentType = @json(old('document_type'));
            let debounceRef = null;

            const setStatus = (message, type = 'neutral') => {
                statusEl.textContent = message;
                statusEl.classList.remove('hidden', 'border-red-300', 'bg-red-50', 'text-red-700', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-700', 'border-slate-300', 'bg-slate-50', 'text-slate-700');

                if (type === 'error') {
                    statusEl.classList.add('border-red-300', 'bg-red-50', 'text-red-700');
                    return;
                }

                if (type === 'success') {
                    statusEl.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
                    return;
                }

                statusEl.classList.add('border-slate-300', 'bg-slate-50', 'text-slate-700');
            };

            const refreshDocumentTypes = () => {
                const countryCode = countryCodeEl.value;
                const options = documentTypeMap[countryCode] || {};
                const currentValue = documentTypeEl.value;

                documentTypeEl.innerHTML = '';
                const empty = document.createElement('option');
                empty.value = '';
                empty.textContent = "{{ __('app.select_document_type') }}";
                documentTypeEl.appendChild(empty);

                Object.entries(options).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    documentTypeEl.appendChild(option);
                });

                if (oldDocumentType && options[oldDocumentType] && currentValue === '') {
                    documentTypeEl.value = oldDocumentType;
                    return;
                }

                if (options[currentValue]) {
                    documentTypeEl.value = currentValue;
                }
            };

            const refreshFormatHelp = () => {
                const countryCode = countryCodeEl.value;
                const documentType = documentTypeEl.value;

                if (!countryCode || !documentType) {
                    hintEl.textContent = "{{ __('app.generic_document_format') }}";
                    return;
                }

                if (!specificFormatCountries.includes(countryCode)) {
                    hintEl.textContent = "{{ __('app.generic_document_format') }}";
                    return;
                }

                const example = documentHintMap[countryCode]?.[documentType] || "{{ __('app.generic_document_format') }}";
                hintEl.textContent = "{{ __('app.document_format_help', ['example' => '__EXAMPLE__']) }}".replace('__EXAMPLE__', example);
            };

            const normalizeDocumentInput = () => {
                const countryCode = countryCodeEl.value;
                const value = documentEl.value;

                if (!countryCode || specificFormatCountries.includes(countryCode)) {
                    return;
                }

                const normalized = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (normalized !== value) {
                    documentEl.value = normalized;
                }
            };

            const checkEligibility = async () => {
                const countryCode = countryCodeEl.value;
                const documentType = documentTypeEl.value;
                const documentValue = documentEl.value.trim();

                if (!countryCode || !documentType || documentValue.length < 5) {
                    submitBtn.disabled = true;
                    return;
                }

                const response = await fetch("{{ route('quiz.eligibility') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        country_code: countryCode,
                        document_type: documentType,
                        document: documentValue,
                        cert_type: certType,
                    }),
                });

                const payload = await response.json();
                if (!response.ok || !payload.can_start) {
                    submitBtn.disabled = true;
                    setStatus(payload.message || "{{ __('app.not_available_now') }}", 'error');
                    return;
                }

                submitBtn.disabled = false;
                setStatus(payload.message || "{{ __('app.quiz_ready_to_start') }}", 'success');
            };

            const triggerCheck = () => {
                if (debounceRef) {
                    clearTimeout(debounceRef);
                }

                debounceRef = setTimeout(() => {
                    void checkEligibility();
                }, 350);
            };

            submitBtn.disabled = true;
            refreshDocumentTypes();
            refreshFormatHelp();

            countryCodeEl.addEventListener('change', () => {
                refreshDocumentTypes();
                refreshFormatHelp();
                triggerCheck();
            });

            documentTypeEl.addEventListener('change', () => {
                refreshFormatHelp();
                triggerCheck();
            });

            documentEl.addEventListener('input', () => {
                normalizeDocumentInput();
                triggerCheck();
            });

            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.textContent = "{{ __('app.starting_exam') }}";
            });

            if (documentEl.value.trim().length >= 5) {
                triggerCheck();
            }
        })();
    </script>
@endsection
