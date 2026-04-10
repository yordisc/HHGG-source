@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Nueva certificación</h1>
                <p class="mt-1 text-sm text-slate-600">Crea un nuevo elemento del catálogo visible en home.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Panel
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @include('admin.certifications._form', [
            'action' => route('admin.certifications.store'),
            'method' => 'POST',
            'buttonLabel' => 'Crear certificación',
        ])
    </section>
@endsection
