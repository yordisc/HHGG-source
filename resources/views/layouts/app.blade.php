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
<body>
    <header class="sticky top-0 z-20 border-b border-slate-300/80 glass">
        <div class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="brand-title text-base font-bold text-[var(--ink)] sm:text-lg">{{ __('app.brand_name') }}</p>
                <p class="text-xs text-slate-700">{{ __('app.brand_tagline') }}</p>
            </div>

            <nav class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
                <span class="text-xs font-semibold text-slate-600">{{ __('app.language_label') }}</span>
                @foreach (($supportedLocales ?? config('app.supported_locales', ['en'])) as $locale)
                    <a href="{{ url('/locale/' . $locale) }}"
                       class="min-w-9 rounded-md border px-3 py-1 text-center text-xs font-semibold transition {{ ($currentLocale ?? app()->getLocale()) === $locale ? 'border-[var(--accent)] bg-[var(--accent)] text-white' : 'border-slate-300 bg-white/90 text-slate-700 hover:border-[var(--accent)]' }}">
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-5xl px-4 pb-10 sm:px-6">
        <div class="rounded-lg border border-slate-300/80 bg-white/90 p-4 text-xs text-slate-700 shadow-sm">
            <p>{{ __('app.disclaimer_full') }}</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
