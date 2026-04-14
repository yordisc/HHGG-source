@extends('layouts.app')

@section('content')
    <section
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 px-4 py-8">
        <div class="w-full max-w-lg">
            <div class="text-center mb-12">
                <div
                    class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-700 shadow-lg mb-4">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-slate-900">Admin</h1>
                <p class="mt-2 text-slate-600">Panel de Control - Certificaciones HHGG</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-8">
                    <h2 class="text-2xl font-bold text-white">Ingresa al Panel</h2>
                    <p class="mt-2 text-blue-100">Utiliza tu cuenta de administrador</p>
                </div>

                <div class="px-8 py-8">
                    @if (session('status'))
                        <div
                            class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-blue-50/50 p-4">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-blue-900">{{ session('status') }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-900 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Correo electrónico
                                </span>
                            </label>
                            <input type="email" id="email" name="email"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="admin@ejemplo.com" autocomplete="email" required>
                            @error('email')
                                <p class="mt-2 flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-900 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Contraseña
                                </span>
                            </label>
                            <input type="password" id="password" name="password"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="********" autocomplete="current-password" required>
                            @error('password')
                                <p class="mt-2 flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Acceder al Panel
                        </button>
                    </form>
                </div>

                <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50 px-8 py-6">
                    <p class="text-xs text-slate-600">
                        <strong class="text-slate-900">Ayuda:</strong> Si no tienes acceso, contacta al administrador del
                        sistema.
                    </p>
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-amber-200 bg-gradient-to-r from-amber-50 to-amber-50/50 p-4">
                <div class="flex gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-xs text-amber-900">
                        Acceso restringido. Usa una cuenta de administrador autorizada.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
