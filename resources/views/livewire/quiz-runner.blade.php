<div class="rounded-lg border border-slate-300 bg-white p-6 shadow-sm sm:p-8">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="brand-title text-2xl font-bold text-[var(--ink)]">{{ __('app.quiz_title') }}</h2>
        <span class="rounded-md border border-slate-400 px-3 py-1 text-xs font-semibold text-slate-600">
            {{ __('app.question_counter', ['current' => $currentIndex + 1, 'total' => $total]) }}
        </span>
    </div>

    <div class="mb-5 h-2 rounded-sm bg-slate-200">
        <div class="h-2 rounded-sm bg-[var(--accent)] transition-all"
            style="width: {{ max(3, (($currentIndex + 1) / max($total, 1)) * 100) }}%"></div>
    </div>

    <p class="mb-2 text-sm text-slate-600">{{ __('app.quiz_select_prompt') }}</p>
    <p class="mb-5 whitespace-normal break-words text-base font-semibold leading-relaxed text-slate-900 sm:text-lg">
        {{ $currentQuestion['prompt'] ?? '' }}
    </p>

    <div class="space-y-3">
        @foreach ($currentQuestion['options'] ?? [] as $optionIndex => $optionText)
            <button type="button" wire:click="answer({{ $optionIndex + 1 }})" wire:loading.attr="disabled"
                wire:target="answer"
                class="flex w-full items-start rounded-md border border-slate-400 bg-white px-4 py-3 text-left text-sm font-medium leading-relaxed text-slate-700 transition hover:border-[var(--accent)] hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                <span class="whitespace-normal break-words">{{ $optionText }}</span>
            </button>
        @endforeach
    </div>

    <div wire:loading wire:target="answer"
        class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
        Procesando respuesta... evita hacer multiples clics.
    </div>

    <p class="mt-4 text-sm text-slate-600">{{ __('app.quiz_progress_hint') }}</p>
</div>
