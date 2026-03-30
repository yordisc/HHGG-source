@extends('layouts.app')

@section('content')
    <section class="reveal rounded-lg border border-slate-300 bg-white/95 p-5 shadow-sm sm:p-10">
        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div>
                <p class="mb-2 inline-flex rounded-md border border-slate-400 bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                    {{ __('app.badge_humor') }}
                </p>
                <h1 class="brand-title text-2xl font-bold leading-tight text-[var(--ink)] sm:text-4xl lg:text-5xl">
                    {{ __('app.hero_title') }}
                </h1>
                <p class="mt-4 max-w-2xl text-sm text-slate-800 sm:text-base">
                    {{ __('app.hero_subtitle') }}
                </p>
                <p class="mt-3 max-w-2xl text-sm text-slate-700">
                    {{ __('app.hero_microcopy') }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm sm:p-5">
                <form method="POST" action="{{ route('search') }}" class="space-y-3">
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
                    <button type="submit" class="w-full rounded-md bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                        {{ __('app.search_button') }}
                    </button>
                    <p class="text-sm text-slate-600">{{ __('app.search_hint') }}</p>
                    <p class="text-sm text-slate-600">{{ __('app.search_examples') }}</p>
                </form>
            </div>
        </div>
    </section>

    @if (session('search_message'))
        <section class="mt-5 reveal rounded-lg border border-slate-400 bg-slate-50 p-4 text-sm text-slate-800">
            {{ session('search_message') }}
        </section>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2">
        <article class="reveal rounded-lg border border-slate-300 bg-white p-5 shadow-sm">
            <h2 class="brand-title text-lg font-bold text-[var(--ink)] sm:text-xl">{{ __('app.cert_one_title') }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ __('app.cert_one_desc') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.certificate_card_hint') }}</p>
            <a href="{{ route('quiz.register', ['certType' => 'social_energy']) }}" class="mt-5 block w-full rounded-md border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.start_exam') }}
            </a>
        </article>

        <article class="reveal rounded-lg border border-slate-300 bg-white p-5 shadow-sm" style="animation-delay:120ms;">
            <h2 class="brand-title text-lg font-bold text-[var(--ink)] sm:text-xl">{{ __('app.cert_two_title') }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ __('app.cert_two_desc') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.certificate_card_hint') }}</p>
            <a href="{{ route('quiz.register', ['certType' => 'life_style']) }}" class="mt-5 block w-full rounded-md border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.start_exam') }}
            </a>
        </article>
    </section>
@endsection
