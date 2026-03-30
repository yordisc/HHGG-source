@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl rounded-lg border border-slate-300 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="brand-title text-2xl font-bold text-[var(--ink)] sm:text-3xl">{{ __('app.certificate_title') }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ __('app.certificate_verified') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ __('app.certificate_public_hint') }}</p>

        <div class="mt-6 space-y-2 rounded-lg border border-slate-300 bg-slate-50 p-5 text-sm text-slate-700">
            <p><span class="font-semibold">{{ __('app.full_name') }}:</span> {{ $certificate->first_name }} {{ $certificate->last_name }}</p>
            <p><span class="font-semibold">{{ __('app.country') }}:</span> {{ $certificate->country }}</p>
            <p><span class="font-semibold">{{ __('app.serial') }}:</span> {{ $certificate->serial }}</p>
            <p><span class="font-semibold">{{ __('app.status') }}:</span> {{ __('app.result_' . $certificate->result_key) }}</p>
            <p><span class="font-semibold">{{ __('app.valid_until') }}:</span> {{ $certificate->expires_at?->format('Y-m-d') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <a href="{{ route('result.show', ['serial' => $certificate->serial]) }}" class="rounded-md border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.view_result') }}
            </a>
            <a href="{{ route('cert.pdf', ['serial' => $certificate->serial]) }}" class="rounded-md bg-[var(--accent)] px-4 py-3 text-center text-sm font-semibold text-white transition hover:brightness-110">
                {{ __('app.download_pdf') }}
            </a>
            <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-md border border-slate-400 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.add_to_linkedin') }}
            </a>
        </div>

        <p class="mt-4 text-sm text-slate-600">{{ __('app.certificate_actions_hint') }}</p>
    </section>
@endsection
