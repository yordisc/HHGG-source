@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Usuarios</h1>
                <p class="mt-1 text-sm text-slate-600">Listado, edicion y exportacion de cuentas del sistema.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Panel</a>
                <a href="{{ route('admin.users.import.form') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-500 hover:text-blue-600">Importar CSV</a>
                <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Nuevo usuario</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o correo" class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm sm:max-w-md">
            <button type="submit" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white">Buscar</button>
            <a href="{{ route('admin.users.export.csv', ['search' => $search ?: null]) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                Exportar CSV
            </a>
        </form>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Verificado</th>
                        <th class="px-4 py-3">Creado</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $user->id }}</td>
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->email_verified_at ? 'Si' : 'No' }}</td>
                            <td class="px-4 py-3">{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:border-[var(--accent)] hover:text-[var(--accent)]">Editar</a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="ml-2 inline" onsubmit="return confirm('Deseas eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $users->links() }}
        </div>
    </section>
@endsection
