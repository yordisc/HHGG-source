@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Certificaciones</h1>
                <p class="mt-1 text-sm text-slate-600">Alta, edicion, control del catalogo y orden por arrastre.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">Panel</a>
                <a href="{{ route('admin.certifications.create') }}" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Nueva certificacion</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form id="certifications-reorder-form" method="POST" action="{{ route('admin.certifications.reorder') }}" class="mt-6 space-y-4">
            @csrf
            <div id="certifications-hidden-inputs" class="hidden"></div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Arrastra los elementos usando el icono de la izquierda. El nuevo orden se guarda al soltar.
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="grid grid-cols-[2.5rem_0.7fr_1.2fr_1.5fr_0.8fr_0.8fr_1.7fr] gap-0 bg-slate-50 px-4 py-3 text-left text-xs uppercase tracking-wide text-slate-600">
                    <div></div>
                    <div>ID</div>
                    <div>Slug</div>
                    <div>Nombre</div>
                    <div>Activa</div>
                    <div>Orden</div>
                    <div class="text-right">Acciones</div>
                </div>

                <div id="certifications-sortable" class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($certifications as $certification)
                        <div class="certification-row grid cursor-move grid-cols-[2.5rem_0.7fr_1.2fr_1.5fr_0.8fr_0.8fr_1.7fr] items-center gap-0 px-4 py-4 transition hover:bg-slate-50" draggable="true" data-certification-id="{{ $certification->id }}">
                            <div class="flex items-center justify-center text-slate-400" title="Arrastrar">
                                <span class="text-lg leading-none">⋮⋮</span>
                            </div>
                            <div class="font-semibold">{{ $certification->id }}</div>
                            <div>{{ $certification->slug }}</div>
                            <div>{{ $certification->name }}</div>
                            <div>{{ $certification->active ? 'Si' : 'No' }}</div>
                            <div class="certification-order">{{ $certification->home_order }}</div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <form action="{{ route('admin.certifications.toggle', $certification) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border px-3 py-1 text-xs font-semibold {{ $certification->active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                        {{ $certification->active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                <a href="{{ route('admin.certifications.edit', $certification) }}" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:border-[var(--accent)] hover:text-[var(--accent)]">Editar</a>
                                <form action="{{ route('admin.certifications.destroy', $certification) }}" method="POST" class="inline" onsubmit="return confirm('Deseas eliminar esta certificacion?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-slate-500">No hay certificaciones registradas.</div>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Guardar orden manualmente
                </button>
            </div>
        </form>

        <div class="mt-5">
            {{ $certifications->links() }}
        </div>
    </section>

    <script>
        (() => {
            const list = document.getElementById('certifications-sortable');
            const form = document.getElementById('certifications-reorder-form');
            const hiddenInputs = document.getElementById('certifications-hidden-inputs');

            if (!list || !form) {
                return;
            }

            let draggedRow = null;

            const updateInputOrder = () => {
                const rows = [...list.querySelectorAll('[data-certification-id]')];
                rows.forEach((row, index) => {
                    const orderCell = row.querySelector('.certification-order');
                    if (orderCell) {
                        orderCell.textContent = String(index + 1);
                    }
                });
            };

            const syncOrder = async () => {
                const certifications = [...list.querySelectorAll('[data-certification-id]')].map((row) => row.dataset.certificationId);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ certifications }),
                });

                if (!response.ok) {
                    throw new Error('No fue posible guardar el orden.');
                }
            };

            const populateHiddenInputs = () => {
                if (!hiddenInputs) {
                    return;
                }

                hiddenInputs.innerHTML = '';
                [...list.querySelectorAll('[data-certification-id]')].forEach((row) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'certifications[]';
                    input.value = row.dataset.certificationId;
                    hiddenInputs.appendChild(input);
                });
            };

            list.addEventListener('dragstart', (event) => {
                const row = event.target.closest('[data-certification-id]');
                if (!row) {
                    return;
                }

                draggedRow = row;
                row.classList.add('opacity-50');
                event.dataTransfer.effectAllowed = 'move';
            });

            list.addEventListener('dragend', () => {
                if (draggedRow) {
                    draggedRow.classList.remove('opacity-50');
                }
                draggedRow = null;
            });

            list.addEventListener('dragover', (event) => {
                event.preventDefault();
                const targetRow = event.target.closest('[data-certification-id]');
                if (!targetRow || !draggedRow || targetRow === draggedRow) {
                    return;
                }

                const targetRect = targetRow.getBoundingClientRect();
                const before = (event.clientY - targetRect.top) < (targetRect.height / 2);
                if (before) {
                    list.insertBefore(draggedRow, targetRow);
                } else {
                    list.insertBefore(draggedRow, targetRow.nextSibling);
                }

                updateInputOrder();
            });

            list.addEventListener('drop', async (event) => {
                event.preventDefault();
                if (!draggedRow) {
                    return;
                }

                updateInputOrder();

                try {
                    await syncOrder();
                    const statusBox = document.createElement('div');
                    statusBox.className = 'mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800';
                    statusBox.textContent = 'Orden actualizado correctamente.';
                    form.parentElement.insertBefore(statusBox, form.nextSibling);
                    window.setTimeout(() => statusBox.remove(), 2500);
                } catch (error) {
                    alert(error.message);
                }
            });

            form.addEventListener('submit', () => {
                populateHiddenInputs();
            });
        })();
    </script>
@endsection
