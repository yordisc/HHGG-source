@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl overflow-hidden rounded-[2rem] border border-slate-300 bg-white/95 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.45)]">
        <div class="border-b border-slate-200 bg-gradient-to-r from-[rgba(29,53,87,0.08)] to-[rgba(141,122,59,0.08)] px-6 py-6 sm:px-8">
            <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-600">{{ __('app.result_label') }}</p>
            <h1 class="brand-title mt-2 text-3xl font-bold text-[var(--ink)] sm:text-4xl">{{ __('app.result_title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-700 sm:text-base">{{ __('app.result_subtitle') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.result_share_hint') }}</p>
        </div>

        {{-- Certificate Image Display --}}
        @if ($certificate->hasImage())
            <div class="border-b border-slate-200 px-6 py-6 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-4">Imagen del Certificado</p>
                <img src="{{ $certificate->getImageUrl() }}" alt="Imagen del certificado" class="w-full h-auto rounded-xl border border-slate-200 shadow-sm">
            </div>
        @endif

        <div class="grid gap-4 px-6 py-6 sm:px-8 lg:grid-cols-[1.3fr_0.7fr]">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('app.result_label') }}</p>
                <p class="mt-2 text-3xl font-bold text-[var(--ink)]">{{ __('app.result_' . $certificate->result_key) }}</p>
                <p class="mt-3 text-sm text-slate-700">{{ __('app.score_summary', ['correct' => $certificate->score_correct, 'total' => $certificate->total_questions]) }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ __('app.serial') }}: {{ $certificate->serial }}</p>
            </div>

            <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.certificate_actions_hint') }}</p>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('cert.show', ['serial' => $certificate->serial]) }}" class="rounded-full border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                        {{ __('app.view_certificate') }}
                    </a>
                    <a href="{{ route('cert.pdf', ['serial' => $certificate->serial]) }}" class="rounded-full bg-[var(--accent)] px-4 py-3 text-center text-sm font-semibold text-white transition hover:brightness-110">
                        {{ __('app.download_pdf') }}
                    </a>
                    <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-slate-400 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                        {{ __('app.add_to_linkedin') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-200 px-6 py-4 sm:px-8">
            <p class="text-sm text-slate-600">{{ __('app.linkedin_hint') }}</p>
        </div>
    </section>
@endsection
