@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Panel Admin</h1>
                    <p class="mt-1 text-sm text-slate-600">Administracion central de certificaciones, preguntas y usuarios.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.certifications.create') }}" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">
                        Nueva certificacion
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                        Usuarios
                    </a>
                    <a href="{{ route('admin.questions.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                        Preguntas
                    </a>
                    <a href="{{ route('admin.audit.index') }}" class="rounded-xl border border-purple-300 px-4 py-2 text-sm font-semibold text-purple-700 transition hover:border-purple-500 hover:text-purple-600">
                        Auditoría
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                            Cerrar sesion
                        </button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Certificaciones</p>
                <p class="mt-2 text-3xl font-bold text-[var(--ink)]">{{ $certificationsCount }}</p>
                <p class="mt-1 text-sm text-slate-600">Catálogo total configurado.</p>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Activas</p>
                <p class="mt-2 text-3xl font-bold text-[var(--ink)]">{{ $activeCertificationsCount }}</p>
                <p class="mt-1 text-sm text-slate-600">Visibles en home y quiz.</p>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Preguntas</p>
                <p class="mt-2 text-3xl font-bold text-[var(--ink)]">{{ $questionsCount }}</p>
                <p class="mt-1 text-sm text-slate-600">Banco global de preguntas.</p>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usuarios</p>
                <p class="mt-2 text-3xl font-bold text-[var(--ink)]">{{ $usersCount }}</p>
                <p class="mt-1 text-sm text-slate-600">Registros disponibles para exportacion.</p>
            </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-[var(--ink)]">Accesos rapidos</h2>
                        <p class="text-sm text-slate-600">Entradas directas a las secciones que ya estan operativas.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('admin.certifications.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-[var(--accent)] hover:bg-white">
                        <p class="text-sm font-semibold text-[var(--ink)]">Certificaciones</p>
                        <p class="mt-1 text-sm text-slate-600">Crear, editar, activar y reordenar el catalogo.</p>
                    </a>
                    <a href="{{ route('admin.questions.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-[var(--accent)] hover:bg-white">
                        <p class="text-sm font-semibold text-[var(--ink)]">Preguntas</p>
                        <p class="mt-1 text-sm text-slate-600">CRUD, traducciones e importacion CSV.</p>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-[var(--accent)] hover:bg-white">
                        <p class="text-sm font-semibold text-[var(--ink)]">Usuarios</p>
                        <p class="mt-1 text-sm text-slate-600">Listado, edicion y exportacion CSV.</p>
                    </a>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-[var(--ink)]">Certificaciones recientes</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentCertifications as $certification)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-[var(--ink)]">{{ $certification->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $certification->slug }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $certification->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $certification->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">Aun no hay certificaciones configuradas.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
