@extends('layouts.app')

@section('content')
    <section class="min-h-screen space-y-8 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-3xl font-bold text-transparent">Panel Admin</h1>
                    <p class="mt-2 text-lg text-slate-600">Administración centralizada de certificaciones, preguntas y usuarios</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.certifications.create') }}" class="ui-btn-compact bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md hover:shadow-lg hover:-translate-y-0.5">
                        <svg class="ui-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva certificación
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="ui-btn-compact border border-slate-300 bg-white text-slate-700 shadow-sm hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-emerald-50/50 px-6 py-4 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="ui-icon text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-emerald-900">{{ session('status') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Stats Grid -->
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="bg-gradient-to-br from-blue-50 to-transparent p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-600">Certificaciones</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $certificationsCount }}</p>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 sm:h-10 sm:w-10">
                                <svg class="ui-icon text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Total en el catálogo</p>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="bg-gradient-to-br from-emerald-50 to-transparent p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-600">Activas</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $activeCertificationsCount }}</p>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 sm:h-10 sm:w-10">
                                <svg class="ui-icon text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Disponibles para usuarios</p>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="bg-gradient-to-br from-purple-50 to-transparent p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-600">Preguntas</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $questionsCount }}</p>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-purple-50 sm:h-10 sm:w-10">
                                <svg class="ui-icon text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.797 0 5.028 2.236 5.028 5s-2.231 5-5.028 5c-1.743 0-3.223-.835-3.772-2m0 0V5m0 10V9"/></svg>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Banco global</p>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="bg-gradient-to-br from-amber-50 to-transparent p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-600">Usuarios</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $usersCount }}</p>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 sm:h-10 sm:w-10">
                                <svg class="ui-icon text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">En el sistema</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Accesos Rápidos -->
                <div class="lg:col-span-2">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-bold text-slate-900">Accesos rápidos</h2>
                            <p class="mt-1 text-sm text-slate-600">Funcionalidades operativas</p>
                        </div>
                        <div class="grid gap-4 p-6 sm:grid-cols-2">
                            <a href="{{ route('admin.certifications.index') }}" class="group rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 transition hover:border-blue-300 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">Certificaciones</p>
                                        <p class="mt-1 text-sm text-slate-600">Crear, editar y reordenar</p>
                                    </div>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 group-hover:bg-blue-200 sm:h-8 sm:w-8">
                                        <svg class="ui-icon text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('admin.questions.index') }}" class="group rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 transition hover:border-purple-300 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">Preguntas</p>
                                        <p class="mt-1 text-sm text-slate-600">CRUD e importación CSV</p>
                                    </div>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100 group-hover:bg-purple-200 sm:h-8 sm:w-8">
                                        <svg class="ui-icon text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.797 0 5.028 2.236 5.028 5s-2.231 5-5.028 5c-1.743 0-3.223-.835-3.772-2m0 0V5m0 10V9"/></svg>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="group rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 transition hover:border-amber-300 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">Usuarios</p>
                                        <p class="mt-1 text-sm text-slate-600">Gestión y exportación</p>
                                    </div>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 group-hover:bg-amber-200 sm:h-8 sm:w-8">
                                        <svg class="ui-icon text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('admin.audit.index') }}" class="group rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 transition hover:border-indigo-300 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">Auditoría</p>
                                        <p class="mt-1 text-sm text-slate-600">Registro de cambios</p>
                                    </div>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 group-hover:bg-indigo-200 sm:h-8 sm:w-8">
                                        <svg class="ui-icon text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('admin.certificates.templates.index') }}" class="group rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 transition hover:border-rose-300 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">Plantillas</p>
                                        <p class="mt-1 text-sm text-slate-600">Personalizar certificados</p>
                                    </div>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 group-hover:bg-rose-200 sm:h-8 sm:w-8">
                                        <svg class="ui-icon text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1m2-1v2.5M4 7l2 1m-2-1l2-1m-2 1v2.5"/></svg>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Certificaciones Recientes -->
                <div>
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-bold text-slate-900">Recientes</h2>
                            <p class="mt-1 text-sm text-slate-600">Últimas certificaciones</p>
                        </div>
                        <div class="divide-y divide-slate-200 p-4">
                            @forelse ($recentCertifications as $certification)
                                <div class="flex items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ $certification->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $certification->slug }}</p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $certification->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $certification->active ? '✓' : '—' }}
                                    </span>
                                </div>
                            @empty
                                <div class="flex h-24 items-center justify-center">
                                    <p class="text-sm text-slate-500">Sin certificaciones aún</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="border-t border-slate-200 px-6 py-3">
                            <a href="{{ route('admin.certifications.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                                Ver todas →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
