@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-md rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Acceso Admin</h1>
        <p class="mt-2 text-sm text-slate-600">Ingresa la clave ADMIN_ACCESS_KEY para gestionar el banco de preguntas.</p>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-blue-300 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold text-slate-700">Clave admin</label>
                <input type="password" name="admin_key" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" autocomplete="current-password">
                @error('admin_key')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                Entrar
            </button>
        </form>
    </section>
@endsection
