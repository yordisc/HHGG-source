@extends('layouts.app')

@section('content')
    <div class="home-shell">
        <!-- Hero Section -->
        <section class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <!-- Hero Content -->
                <div>
                    <div class="home-chip">
                        <svg class="ui-icon text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        <span class="text-xs font-semibold text-slate-600">{{ __('app.badge_humor') }}</span>
                    </div>

                    <h1 class="mt-6 text-2xl font-bold leading-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        {{ __('app.hero_title') }}
                    </h1>

                    <p class="mt-6 text-lg text-slate-700">
                        {{ __('app.hero_subtitle') }}
                    </p>

                    <p class="mt-4 text-base text-slate-600">
                        {{ __('app.hero_microcopy') }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="group rounded-xl border border-slate-200 bg-white/80 p-4 shadow-sm transition hover:shadow-md">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 p-2">
                                    <svg class="ui-icon text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500 uppercase">30</p>
                                    <p class="text-sm font-bold text-slate-900">{{ __('app.hero_stat_one') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="group rounded-xl border border-slate-200 bg-white/80 p-4 shadow-sm transition hover:shadow-md">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 p-2">
                                    <svg class="ui-icon text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500 uppercase">100%</p>
                                    <p class="text-sm font-bold text-slate-900">{{ __('app.hero_stat_two') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="group rounded-xl border border-slate-200 bg-white/80 p-4 shadow-sm transition hover:shadow-md">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-gradient-to-br from-purple-100 to-purple-50 p-2">
                                    <svg class="ui-icon text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500 uppercase">PDF</p>
                                    <p class="text-sm font-bold text-slate-900">{{ __('app.hero_stat_three') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Box -->
                <div class="relative">
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 blur-2xl"></div>
                    <div class="home-panel p-5 sm:p-7">
                        <div class="mb-6 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 sm:text-xs">{{ __('app.search_label') }}</p>
                                <h2 class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">{{ __('app.search_button') }}</h2>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-blue-100 to-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700 sm:px-3 sm:text-xs">
                                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                {{ __('app.search_examples') }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('search') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="query" class="block text-sm font-semibold text-slate-700 mb-2">{{ __('app.search_label') }}</label>
                                <input
                                    id="query"
                                    name="query"
                                    type="text"
                                    value="{{ old('query') }}"
                                    placeholder="{{ __('app.search_placeholder') }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder-slate-400 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none sm:px-4 sm:py-3"
                                >
                                @error('query')
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="home-primary-btn w-full">
                                {{ __('app.search_button') }}
                            </button>
                            <p class="text-center text-xs text-slate-500">{{ __('app.search_hint') }}</p>
                        </form>
                    </div>
                </div>
            </div>

            @if (session('search_message'))
                <div class="mt-8 flex items-start gap-4 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-blue-50/50 p-4 shadow-sm">
                    <svg class="ui-icon mt-0.5 flex-shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2z" clip-rule="evenodd"/></svg>
                    <p class="text-sm text-blue-900">{{ session('search_message') }}</p>
                </div>
            @endif
        </section>

        <!-- Certifications Grid -->
        <section class="mx-auto max-w-7xl">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-slate-900">{{ __('app.search_examples') }}</h2>
                <p class="mt-2 text-slate-600">{{ __('app.hero_subtitle') }}</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                @forelse ($certifications as $index => $certification)
                    <article class="home-card group" style="animation: fadeInUp 0.6s ease-out {{ $index * 100 }}ms both;">
                        <div class="relative h-32 overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 sm:h-40">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 to-transparent"></div>
                            <div class="absolute top-3 right-3 rounded-lg bg-white/20 p-1.5 backdrop-blur-md sm:top-4 sm:right-4 sm:p-2">
                                <svg class="h-4 w-4 text-white sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                        </div>
                        <div class="p-5 sm:p-6">
                            <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ $certification->name }}</h3>
                            <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $certification->description ?: __('app.certificate_card_hint') }}</p>
                            <a href="{{ route('quiz.register', ['certType' => $certification->slug]) }}" class="home-primary-btn mt-6 group/btn">
                                {{ __('app.start_exam') }}
                                <svg class="ui-icon transition group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                        <svg class="mx-auto h-8 w-8 text-slate-300 sm:h-10 sm:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p class="mt-4 text-slate-500">{{ __('app.no_certifications_available') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
