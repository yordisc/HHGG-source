@php
    $isEdit = $user->exists;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700">
            Nombre
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600">
            El correo se genera automaticamente para el sistema.
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700">
            {{ $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
            <input type="password" name="password" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" autocomplete="new-password">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 pt-7">
            <input type="checkbox" name="email_verified" value="1" @checked(old('email_verified', $user->email_verified_at !== null))>
            Correo verificado
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
            Volver
        </a>
    </div>
</form>
