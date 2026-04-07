<div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-4">Imagen de Certificación</p>

    {{-- Current Image Display --}}
    @if ($imageUrl)
        <div class="mb-4">
            <img src="{{ $imageUrl }}" alt="Imagen actual del certificado" class="w-full h-auto rounded-lg border border-slate-200 shadow-sm mb-3">
            <button
                type="button"
                wire:click="deleteImage"
                class="w-full rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-50"
                {{ $isLoading ? 'disabled' : '' }}
            >
                {{ $isLoading ? 'Procesando...' : 'Eliminar imagen' }}
            </button>
        </div>
    @endif

    {{-- Upload Area --}}
    <div class="relative">
        <input
            type="file"
            wire:model="image"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="absolute inset-0 cursor-pointer opacity-0"
            {{ $isLoading ? 'disabled' : '' }}
        >
        <div class="rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:bg-slate-100 hover:border-slate-400">
            <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <p class="mt-2 text-sm font-semibold text-slate-700">
                @if ($imageUrl && !$image)
                    Cambiar imagen
                @else
                    Cargar imagen
                @endif
            </p>
            <p class="text-xs text-slate-500">JPEG, PNG, WebP o GIF - Máx 5 MB</p>
        </div>
    </div>

    {{-- Message/Status --}}
    @if ($message)
        <div class="mt-3 rounded-lg p-2 text-xs font-medium {{ $isError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @error('image')
        <div class="mt-2 rounded-lg bg-rose-50 p-2 text-xs font-medium text-rose-700">
            {{ $message }}
        </div>
    @enderror
</div>
