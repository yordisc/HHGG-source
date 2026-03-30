@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl rounded-lg border border-slate-300 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="brand-title text-2xl font-bold text-[var(--ink)] sm:text-3xl">{{ __('app.result_title') }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ __('app.result_subtitle') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ __('app.result_share_hint') }}</p>

        <div class="mt-6 rounded-lg border border-slate-300 bg-slate-50 p-5">
            <p class="text-xs font-semibold uppercase text-slate-700">{{ __('app.result_label') }}</p>
            <p class="mt-1 text-2xl font-bold text-[var(--ink)]">{{ __('app.result_' . $certificate->result_key) }}</p>
            <p class="mt-3 text-sm text-slate-700">{{ __('app.score_summary', ['correct' => $certificate->score_correct, 'total' => $certificate->total_questions]) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $certificate->serial }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <a href="{{ route('cert.show', ['serial' => $certificate->serial]) }}" class="rounded-md border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.view_certificate') }}
            </a>
            <a href="{{ route('cert.pdf', ['serial' => $certificate->serial]) }}" class="rounded-md bg-[var(--accent)] px-4 py-3 text-center text-sm font-semibold text-white transition hover:brightness-110">
                {{ __('app.download_pdf') }}
            </a>
            <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-md border border-slate-400 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                {{ __('app.add_to_linkedin') }}
            </a>
        </div>

        <p class="mt-4 text-sm text-slate-600">{{ __('app.linkedin_hint') }}</p>
    </section>
@endsection
