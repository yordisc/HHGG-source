@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl overflow-hidden rounded-[2rem] border border-slate-300 bg-white/95 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.45)]">
        <div class="border-b border-slate-200 bg-gradient-to-r from-[rgba(29,53,87,0.08)] to-[rgba(141,122,59,0.08)] px-6 py-6 sm:px-8">
            <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-600">{{ __('app.certificate_title') }}</p>
            <h1 class="brand-title mt-2 text-3xl font-bold text-[var(--ink)] sm:text-4xl">{{ __('app.certificate_verified') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-700 sm:text-base">{{ __('app.certificate_public_hint') }}</p>
        </div>

        {{-- Certificate Image Display --}}
        @if ($certificate->hasImage())
            <div class="border-b border-slate-200 px-6 py-6 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-4">Imagen del Certificado</p>
                <img src="{{ $certificate->getImageUrl() }}" alt="Imagen del certificado" class="w-full h-auto rounded-xl border border-slate-200 shadow-sm">
            </div>
        @endif

        <div class="grid gap-4 px-6 py-6 sm:px-8 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
                <div class="grid gap-3 sm:grid-cols-2">
                    <p><span class="font-semibold">{{ __('app.full_name') }}:</span> {{ $certificate->first_name }} {{ $certificate->last_name }}</p>
                    <p><span class="font-semibold">{{ __('app.country') }}:</span> {{ $certificate->country }}</p>
                    <p><span class="font-semibold">{{ __('app.serial') }}:</span> {{ $certificate->serial }}</p>
                    <p><span class="font-semibold">{{ __('app.status') }}:</span> {{ __('app.result_' . $certificate->result_key) }}</p>
                    <p><span class="font-semibold">{{ __('app.valid_until') }}:</span> {{ $certificate->expires_at?->format('Y-m-d') }}</p>
                </div>
            </div>

            <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.certificate_actions_hint') }}</p>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('result.show', ['serial' => $certificate->serial]) }}" class="rounded-full border border-slate-400 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                        {{ __('app.view_result') }}
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
    </section>
@endsection
