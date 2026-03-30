@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl rounded-lg border border-slate-300 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="brand-title text-2xl font-bold text-[var(--ink)] sm:text-3xl">{{ __('app.registration_title') }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ __('app.registration_subtitle') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ __('app.registration_note') }}</p>

        <form action="{{ route('quiz.start') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="cert_type" value="{{ $certType }}">

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.first_name') }}</label>
                <input name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" class="mt-1 w-full rounded-md border border-slate-400 px-4 py-3 text-sm" required>
                @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.last_name') }}</label>
                <input name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" class="mt-1 w-full rounded-md border border-slate-400 px-4 py-3 text-sm" required>
                @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.document') }}</label>
                <input name="document" type="text" value="{{ old('document') }}" autocomplete="off" class="mt-1 w-full rounded-md border border-slate-400 px-4 py-3 text-sm" required>
                @error('document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.country') }}</label>
                <input name="country" type="text" value="{{ old('country') }}" autocomplete="country-name" class="mt-1 w-full rounded-md border border-slate-400 px-4 py-3 text-sm" required>
                @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                {{ __('app.begin_quiz') }}
            </button>
            <p class="text-center text-sm text-slate-600">{{ __('app.registration_privacy') }}</p>
        </form>
    </section>
@endsection
