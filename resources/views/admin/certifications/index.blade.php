@extends('layouts.app')

@section('content')
    <section class="min-h-screen space-y-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Gestión de Certificaciones</h1>
                    <p class="mt-2 text-slate-600">Crea, edita y organiza el catálogo completo de certificaciones</p>
                </div>
                <a href="{{ route('admin.certifications.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 font-semibold text-white shadow-lg transition hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nueva certificación
                </a>
            </div>

            @if (session('status'))
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-emerald-50/50 p-4 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <p class="text-sm font-medium text-emerald-900">{{ session('status') }}</p>
                </div>
            @endif
        </div>

        <!-- Tabla de Certificaciones -->
        <div class="mx-auto max-w-7xl">
            <form id="certifications-reorder-form" method="POST" action="{{ route('admin.certifications.reorder') }}">
                @csrf
                <div id="certifications-hidden-inputs" class="hidden"></div>

                <!-- Info -->
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-blue-50/50 p-4 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2z" clip-rule="evenodd"/></svg>
                    <p class="text-sm text-blue-900"><strong>Tip:</strong> Arrastra las filas para reordenar. El cambio se guarda automáticamente.</p>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 w-12"></th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Slug</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nombre</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Orden</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="certifications-sortable" class="divide-y divide-slate-100">
                                @forelse ($certifications as $certification)
                                    <tr class="certification-row transition hover:bg-slate-50 cursor-move" draggable="true" data-certification-id="{{ $certification->id }}">
                                        <td class="px-6 py-4 text-slate-400 select-none" title="Arrastrar para reordenar">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                                {{ $certification->id }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $certification->slug }}</td>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $certification->name }}</td>
                                        <td class="px-6 py-4">
                                            @if($certification->active)
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                                    Activa
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                                                    <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                                                    Inactiva
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-50 text-sm font-bold text-blue-700 certification-order">
                                                {{ $certification->home_order }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <form action="{{ route('admin.certifications.toggle', $certification) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg {{ $certification->active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} px-3 py-1.5 text-xs font-semibold transition">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-4.803c1.753-2.566 4.49-4.197 7.616-4.197.88 0 1.734.122 2.555.357m5.408 7.61a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                                        {{ $certification->active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.certifications.edit', $certification) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    Editar
                                                </a>
                                                <form action="{{ route('admin.certifications.destroy', $certification) }}" method="POST" class="inline" onsubmit="return confirm('¿Confirmas eliminar esta certificación?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12">
                                            <div class="flex flex-col items-center gap-2">
                                                <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                                <p class="text-slate-500">No hay certificaciones registradas</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 font-semibold text-white shadow-lg transition hover:shadow-xl">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Guardar orden
                    </button>
                </div>
            </form>
        </div>

        <!-- Pagination -->
        <div class="mx-auto max-w-7xl">
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
                    statusBox.className = 'mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-emerald-50/50 p-4 shadow-sm';
                    statusBox.innerHTML = '<svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><p class="text-sm font-medium text-emerald-900">Orden actualizado correctamente</p>';
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
