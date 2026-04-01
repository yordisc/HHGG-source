@extends('layouts.app')

@section('content')
    <section class="reveal relative overflow-hidden rounded-[2rem] border border-slate-300 bg-white/90 p-6 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.45)] sm:p-10">
        <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-[rgba(29,53,87,0.08)] blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-12 bottom-0 h-56 w-56 rounded-full bg-[rgba(141,122,59,0.08)] blur-3xl"></div>

        <div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
            <div class="relative z-10">
                <p class="mb-3 inline-flex rounded-full border border-slate-300 bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-700 shadow-sm">
                    {{ __('app.badge_humor') }}
                </p>
                <h1 class="brand-title max-w-2xl text-3xl font-bold leading-tight text-[var(--ink)] sm:text-5xl lg:text-6xl">
                    {{ __('app.hero_title') }}
                </h1>
                <p class="mt-5 max-w-2xl text-base text-slate-800 sm:text-lg">
                    {{ __('app.hero_subtitle') }}
                </p>
                <p class="mt-4 max-w-2xl text-sm text-slate-700 sm:text-base">
                    {{ __('app.hero_microcopy') }}
                </p>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">30</p>
                        <p class="mt-1 text-sm font-semibold text-[var(--ink)]">{{ __('app.hero_stat_one') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">100%</p>
                        <p class="mt-1 text-sm font-semibold text-[var(--ink)]">{{ __('app.hero_stat_two') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">PDF</p>
                        <p class="mt-1 text-sm font-semibold text-[var(--ink)]">{{ __('app.hero_stat_three') }}</p>
                    </div>
                </div>
            </div>

            <div id="search-box" class="relative z-10 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">{{ __('app.search_label') }}</p>
                        <h2 class="mt-1 text-lg font-bold text-[var(--ink)]">{{ __('app.search_button') }}</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">{{ __('app.search_examples') }}</span>
                </div>

                <form method="POST" action="{{ route('search') }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="query" class="text-xs font-semibold text-slate-700">{{ __('app.search_label') }}</label>
                    <input
                        id="query"
                        name="query"
                        type="text"
                        value="{{ old('query') }}"
                        placeholder="{{ __('app.search_placeholder') }}"
                        class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm outline-none transition focus:border-[var(--accent)] focus:ring-2 focus:ring-slate-200"
                    >
                    @error('query')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="w-full rounded-full bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                        {{ __('app.search_button') }}
                    </button>
                    <p class="text-sm text-slate-600">{{ __('app.search_hint') }}</p>
                </form>
            </div>
        </div>
    </section>

    @if (session('search_message'))
        <section class="mt-5 reveal rounded-lg border border-slate-400 bg-slate-50 p-4 text-sm text-slate-800">
            {{ session('search_message') }}
        </section>
    @endif

    <section id="quiz-cards" class="mt-8 grid gap-4 sm:grid-cols-2">
        <article class="reveal rounded-[1.5rem] border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
            <h2 class="brand-title text-lg font-bold text-[var(--ink)] sm:text-xl">{{ __('app.cert_one_title') }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ __('app.cert_one_desc') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.certificate_card_hint') }}</p>
            <a href="{{ route('quiz.register', ['certType' => 'hetero']) }}" class="mt-5 block w-full rounded-full border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.start_exam') }}
            </a>
        </article>

        <article class="reveal rounded-[1.5rem] border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg" style="animation-delay:120ms;">
            <h2 class="brand-title text-lg font-bold text-[var(--ink)] sm:text-xl">{{ __('app.cert_two_title') }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ __('app.cert_two_desc') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.certificate_card_hint') }}</p>
            <a href="{{ route('quiz.register', ['certType' => 'good_girl']) }}" class="mt-5 block w-full rounded-full border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.start_exam') }}
            </a>
        </article>
    </section>
@endsection
