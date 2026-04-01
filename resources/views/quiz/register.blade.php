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
                <input name="document" type="text" value="{{ old('document') }}" autocomplete="off" placeholder="e.g., 12345678 or AB-123456" class="mt-1 w-full rounded-md border @error('document') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                @error('document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Letters, numbers, hyphens, and dots allowed (5-30 characters)</p>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700">{{ __('app.country') }} <span class="text-red-600">*</span></label>
                <input name="country" type="text" value="{{ old('country') }}" autocomplete="country-name" class="mt-1 w-full rounded-md border @error('country') border-red-500 @else border-slate-400 @enderror px-4 py-3 text-sm" required>
                @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50" id="submitBtn">
                {{ __('app.begin_quiz') }}
            </button>
            <p class="text-center text-sm text-slate-600">{{ __('app.registration_privacy') }}</p>
        </form>
    </section>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Starting exam...';
        });
    </script>
@endsection
