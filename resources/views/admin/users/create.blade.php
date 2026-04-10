@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Nuevo usuario</h1>
                <p class="mt-1 text-sm text-slate-600">Crea una cuenta individual. Para muchas cuentas, usa Importar CSV.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Panel
            </a>
        </div>

        @include('admin.users._form', [
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'buttonLabel' => 'Crear usuario',
        ])
    </section>
@endsection
