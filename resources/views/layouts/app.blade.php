<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.brand_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Source+Serif+4:wght@500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #142033;
            --gold: #8d7a3b;
            --paper: #f4f3ef;
            --accent: #1d3557;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: linear-gradient(180deg, #f7f7f4 0%, #ecebe4 100%);
            color: var(--ink);
            line-height: 1.55;
        }

        .brand-title {
            font-family: 'Source Serif 4', serif;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .glass {
            backdrop-filter: blur(6px);
            background: rgba(255, 255, 255, 0.9);
        }

        .reveal {
            animation: reveal 600ms ease-out both;
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="relative overflow-x-hidden">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-[rgba(29,53,87,0.08)] blur-3xl"></div>
        <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-[rgba(141,122,59,0.08)] blur-3xl"></div>
    </div>

    <header class="sticky top-0 z-20 border-b border-slate-300/80 glass">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <a href="{{ route('home') }}" class="block">
                <p class="brand-title text-base font-bold text-[var(--ink)] sm:text-lg">{{ __('app.brand_name') }}</p>
                <p class="text-xs text-slate-700">{{ __('app.brand_tagline') }}</p>
            </a>

            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <a href="{{ route('home') }}" class="rounded-full border border-slate-300 bg-white/90 px-3 py-1.5 text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">{{ __('app.menu_home') }}</a>
                <a href="{{ route('home') }}#search-box" class="rounded-full border border-slate-300 bg-white/90 px-3 py-1.5 text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">{{ __('app.menu_search') }}</a>
                <a href="{{ route('home') }}#quiz-cards" class="rounded-full border border-slate-300 bg-white/90 px-3 py-1.5 text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">{{ __('app.menu_quizzes') }}</a>
            </div>

            <nav class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
                <span class="text-xs font-semibold text-slate-600">{{ __('app.language_label') }}</span>
                @foreach (($supportedLocales ?? config('app.supported_locales', ['en'])) as $locale)
                    <a href="{{ url('/locale/' . $locale) }}"
                       class="min-w-9 rounded-full border px-3 py-1.5 text-center text-xs font-semibold transition {{ ($currentLocale ?? app()->getLocale()) === $locale ? 'border-[var(--accent)] bg-[var(--accent)] text-white' : 'border-slate-300 bg-white/90 text-slate-700 hover:border-[var(--accent)]' }}">
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-10 sm:px-6">
        <div class="rounded-lg border border-slate-300/80 bg-white/90 p-4 text-xs text-slate-700 shadow-sm">
            <p>{{ __('app.disclaimer_full') }}</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
