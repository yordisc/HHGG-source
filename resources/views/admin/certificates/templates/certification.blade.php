@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Plantilla de certificado</h1>
                <p class="mt-1 text-sm text-slate-600">
                    @if ($customTemplate)
                        Personalizada para: <strong>{{ $certification->name }}</strong>
                    @else
                        Usando plantilla default
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.certifications.edit', $certification) }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Volver a certificación
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.certificates.templates.certification.save', $certification) }}"
            class="space-y-6">
            @csrf

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p class="font-semibold">Variables disponibles:</p>
                    <ul class="mt-2 space-y-1 font-mono text-xs">
                        <li>• <code class="text-yellow-600">@{{ nombre }}</code> • <code
                                class="text-yellow-600">@{{ nombre_completo }}</code> • <code
                                class="text-yellow-600">@{{ fecha }}</code></li>
                        <li>• <code class="text-yellow-600">@{{ serial }}</code> • <code
                                class="text-yellow-600">@{{ competencia }}</code> • <code
                                class="text-yellow-600">@{{ nombre_certificacion }}</code></li>
                        <li>• <code class="text-yellow-600">@{{ nota }}</code> • <code
                                class="text-yellow-600">@{{ pais_origen }}</code> • <code
                                class="text-yellow-600">@{{ documento_identidad }}</code></li>
                        <li>• <code class="text-yellow-600">@{{ horas_cursadas }}</code> • <code
                                class="text-yellow-600">@{{ mencion_honorifica }}</code></li>
                        <li>• <code class="text-yellow-600">@{{ verificacion_url }}</code> • <code
                                class="text-yellow-600">@{{ verificacion_qr }}</code> • <code
                                class="text-yellow-600">@{{ integridad_hash }}</code></li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p class="font-semibold">Efectos CSS útiles:</p>
                    <div class="mt-2 space-y-2 text-xs">
                        <button type="button" data-insert-effect="sepia"
                            class="w-full rounded-lg border border-amber-300 bg-amber-50 px-2 py-1.5 font-semibold text-amber-700 transition hover:bg-amber-100 text-[11px] text-left">
                            🟡 Sepia
                        </button>
                        <button type="button" data-insert-effect="blur"
                            class="w-full rounded-lg border border-blue-300 bg-blue-50 px-2 py-1.5 font-semibold text-blue-700 transition hover:bg-blue-100 text-[11px] text-left">
                            ✨ Blur
                        </button>
                        <button type="button" data-insert-effect="grayscale"
                            class="w-full rounded-lg border border-slate-300 bg-slate-100 px-2 py-1.5 font-semibold text-slate-700 transition hover:bg-slate-150 text-[11px] text-left">
                            ⚫ Grayscale
                        </button>
                        <button type="button" data-insert-effect="overlay"
                            class="w-full rounded-lg border border-purple-300 bg-purple-50 px-2 py-1.5 font-semibold text-purple-700 transition hover:bg-purple-100 text-[11px] text-left">
                            🎭 Overlay
                        </button>
                        <button type="button" data-insert-effect="gradient-bg"
                            class="w-full rounded-lg border border-pink-300 bg-pink-50 px-2 py-1.5 font-semibold text-pink-700 transition hover:bg-pink-100 text-[11px] text-left">
                            🌈 Gradient BG
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p class="font-semibold">Media disponible:</p>
                    <div class="mt-3 space-y-2 text-xs max-h-80 overflow-y-auto">
                        @forelse ($templateResources as $resource)
                            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                                @if ($resource['isImage'] && $resource['previewUrl'])
                                    <div class="h-20 bg-slate-100 overflow-hidden flex items-center justify-center">
                                        <img src="{{ $resource['previewUrl'] }}" alt="{{ $resource['name'] }}"
                                            class="h-full w-full object-cover">
                                    </div>
                                @endif
                                <div class="p-2">
                                    <p class="font-semibold text-slate-900 truncate">{{ $resource['type'] }}</p>
                                    <p class="font-mono text-[9px] text-slate-500 truncate">{{ $resource['name'] }}</p>
                                    <div class="mt-2 flex gap-1 flex-wrap">
                                        <button type="button" data-insert-img="{{ $resource['path'] }}"
                                            data-insert-name="{{ $resource['name'] }}"
                                            class="rounded-sm border border-blue-300 bg-blue-50 px-1.5 py-0.5 font-semibold text-blue-700 transition hover:bg-blue-100 text-[10px]">
                                            IMG
                                        </button>
                                        <button type="button" data-insert-bg="{{ $resource['path'] }}"
                                            class="rounded-sm border border-emerald-300 bg-emerald-50 px-1.5 py-0.5 font-semibold text-emerald-700 transition hover:bg-emerald-100 text-[10px]">
                                            BG
                                        </button>
                                        <button type="button" data-insert-css="{{ $resource['path'] }}"
                                            class="rounded-sm border border-purple-300 bg-purple-50 px-1.5 py-0.5 font-semibold text-purple-700 transition hover:bg-purple-100 text-[10px]">
                                            CSS
                                        </button>
                                        <button type="button" data-copy-path="{{ $resource['path'] }}"
                                            class="rounded-sm border border-slate-300 bg-slate-50 px-1.5 py-0.5 font-semibold text-slate-700 transition hover:bg-slate-100 text-[10px]">
                                            📋
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500">No hay archivos en <code>public/Certificates</code> ni
                                <code>public/Signature</code>.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="use_custom" value="0">
                    <input type="checkbox" name="use_custom" value="1" @checked($customTemplate !== null)
                        id="use-custom-toggle">
                    Crear plantilla personalizada para esta certificación
                </label>
                <p class="mt-2 text-xs text-slate-600">Si lo activas, podrás editar un diseño único para esta certificación.
                    En caso contrario, se usará el diseño default.</p>
            </div>

            <div id="custom-template-form" class="{{ $customTemplate ? '' : 'hidden' }} space-y-4">
                <label class="block text-sm font-semibold text-slate-700">
                    Contenido de la plantilla (HTML + CSS)
                    <textarea id="template-content" name="template_content" rows="16"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-xs">{{ old('template_content', $customTemplateContent) }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Pega un diseño completo en un solo bloque. El
                        <code>&lt;style&gt;</code> se separa automáticamente.
                    </p>
                    @error('html_template')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('css_template')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <input type="hidden" name="html_template" id="html-template"
                    value="{{ old('html_template', $customTemplate?->html_template ?? '') }}">
                <input type="hidden" name="css_template" id="css-template"
                    value="{{ old('css_template', $customTemplate?->css_template ?? '') }}">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit"
                    class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                    Guardar plantilla
                </button>
                <a href="{{ route('admin.certifications.edit', $certification) }}"
                    class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Cancelar
                </a>
            </div>
        </form>
    </section>

    <script>
        const toggle = document.getElementById('use-custom-toggle');
        const customForm = document.getElementById('custom-template-form');

        if (toggle && customForm) {
            toggle.addEventListener('change', () => {
                customForm.classList.toggle('hidden', !toggle.checked);
            });
        }

        const content = document.getElementById('template-content');
        const htmlField = document.getElementById('html-template');
        const cssField = document.getElementById('css-template');
        const copyButtons = document.querySelectorAll('[data-copy-path]');
        const imgButtons = document.querySelectorAll('[data-insert-img]');
        const cssButtons = document.querySelectorAll('[data-insert-css]');
        const bgButtons = document.querySelectorAll('[data-insert-bg]');
        const effectButtons = document.querySelectorAll('[data-insert-effect]');

        const effectSnippets = {
            sepia: 'filter: sepia(80%);',
            blur: 'filter: blur(2px);',
            grayscale: 'filter: grayscale(100%);',
            overlay: 'position: relative; overflow: hidden;',
            'gradient-bg': 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);',
        };

        const syncTemplateFields = () => {
            if (!content || !htmlField || !cssField) return;

            const value = content.value || '';
            const styleMatch = value.match(/<style[^>]*>([\s\S]*?)<\/style>/i);
            const css = styleMatch ? styleMatch[1].trim() : '';
            const html = value.replace(/<style[^>]*>[\s\S]*?<\/style>/ig, '').trim();

            htmlField.value = html;
            cssField.value = css;
        };

        const insertAtCursor = (text) => {
            if (!content) return;

            const start = content.selectionStart || 0;
            const end = content.selectionEnd || 0;
            const before = content.value.substring(0, start);
            const after = content.value.substring(end);

            content.value = before + text + after;
            content.selectionStart = content.selectionEnd = start + text.length;
            content.focus();

            content.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        };

        const showFeedback = (button, message = '✓ Insertado') => {
            const original = button.textContent;
            button.textContent = message;
            button.classList.add('bg-emerald-100', 'text-emerald-700', 'border-emerald-300');
            const classes = button.classList.value.match(/hover:bg-\S+/g) || [];
            classes.forEach(cls => button.classList.remove(cls));

            window.setTimeout(() => {
                button.textContent = original;
                button.classList.remove('bg-emerald-100', 'text-emerald-700', 'border-emerald-300');
            }, 1200);
        };

        if (content) {
            content.addEventListener('input', syncTemplateFields);
            content.form?.addEventListener('submit', syncTemplateFields);
            syncTemplateFields();
        }

        copyButtons.forEach((button) => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const path = button.getAttribute('data-copy-path');
                if (!path) return;

                try {
                    await navigator.clipboard.writeText(path);
                    showFeedback(button, '📋 Copiado');
                } catch (error) {
                    console.warn('No se pudo copiar la ruta', error);
                }
            });
        });

        imgButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const path = button.getAttribute('data-insert-img');
                const name = button.getAttribute('data-insert-name') || 'Imagen';

                if (!path) return;

                const imgTag = `<img src="${path}" alt="${name}" style="max-width: 100%; height: auto;" />`;
                insertAtCursor(imgTag);
                showFeedback(button, '✓ IMG');
            });
        });

        cssButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const path = button.getAttribute('data-insert-css');
                if (!path) return;

                const urlValue = `url('${path}')`;
                insertAtCursor(urlValue);
                showFeedback(button, '✓ CSS');
            });
        });

        bgButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const path = button.getAttribute('data-insert-bg');
                if (!path) return;

                const bgCSS =
                    `background-image: url('${path}');\nbackground-size: cover;\nbackground-position: center;`;
                insertAtCursor(bgCSS);
                showFeedback(button, '✓ BG');
            });
        });

        effectButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const effect = button.getAttribute('data-insert-effect');
                if (!effect || !effectSnippets[effect]) return;

                insertAtCursor(effectSnippets[effect]);
                showFeedback(button, '✓ Efecto');
            });
        });
    </script>
@endsection
