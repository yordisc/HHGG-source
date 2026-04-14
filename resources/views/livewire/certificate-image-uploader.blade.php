<div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-4">Imagen de Certificación</p>

    {{-- Current Image Display --}}
    @if ($imageUrl)
        <div class="mb-4">
            <img src="{{ $imageUrl }}" alt="Imagen actual del certificado"
                class="w-full h-auto rounded-lg border border-slate-200 shadow-sm mb-3">
            <button type="button" wire:click="deleteImage"
                class="w-full rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-50"
                {{ $isLoading ? 'disabled' : '' }}>
                {{ $isLoading ? 'Procesando...' : 'Eliminar imagen' }}
            </button>
        </div>
    @endif

    {{-- URL Input Area --}}
    <div class="space-y-2">
        <label for="certificate-image-url" class="text-xs font-semibold uppercase tracking-wide text-slate-600">URL de
            imagen</label>
        <input id="certificate-image-url" type="url" wire:model.defer="imageUrlInput"
            placeholder="https://ejemplo.com/imagen-certificado.png"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm" {{ $isLoading ? 'disabled' : '' }}>
        <p class="text-xs text-slate-500">Se valida que la URL sea accesible y que responda con Content-Type de imagen.
        </p>
        <button type="button" wire:click="saveImageUrl"
            class="w-full rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 disabled:opacity-50"
            {{ $isLoading ? 'disabled' : '' }}>
            {{ $isLoading ? 'Validando URL...' : 'Guardar URL de imagen' }}
        </button>
    </div>

    {{-- Message/Status --}}
    @if ($message)
        <div
            class="mt-3 rounded-lg p-2 text-xs font-medium {{ $isError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @error('imageUrlInput')
        <div class="mt-2 rounded-lg bg-rose-50 p-2 text-xs font-medium text-rose-700">
            {{ $message }}
        </div>
    @enderror
</div>
