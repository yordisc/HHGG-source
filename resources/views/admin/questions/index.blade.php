@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="brand-title text-2xl font-bold text-[var(--ink)]">Admin de Preguntas</h1>
                <p class="mt-1 text-sm text-slate-600">Edita banco base y traducciones por idioma.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Panel
                </a>
                <a href="{{ route('admin.certifications.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Certificaciones
                </a>
                <a href="{{ route('admin.questions.create') }}" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">
                    Nueva pregunta
                </a>
                <a href="{{ route('home') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Volver al inicio
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                        Cerrar sesion admin
                    </button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('csv_file'))
            <div class="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('csv_file') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.questions.import.csv') }}" enctype="multipart/form-data" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            @csrf
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Importar CSV</p>
            <p class="mt-1 text-xs text-slate-500">Columnas requeridas: cert_type,prompt,option_1,option_2,option_3,option_4,correct_option. Opcionales: question_id,language,active.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input type="file" name="csv_file" accept=".csv,text/csv" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Subir CSV</button>
                <a href="{{ route('admin.questions.export.csv', ['cert_type' => $filterType ?: null]) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-700 transition hover:border-[var(--accent)] hover:text-[var(--accent)]">
                    Exportar CSV
                </a>
                <a href="{{ route('admin.questions.template.csv') }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-center text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                    Plantilla CSV
                </a>
            </div>
        </form>

        <form method="GET" class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto]">
            <select name="cert_type" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                <option value="">Todos los tipos</option>
                @foreach ($certifications as $slug => $name)
                    <option value="{{ $slug }}" @selected($filterType === $slug)>{{ $slug }} - {{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-[var(--accent)] px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
        </form>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Pregunta</th>
                        <th class="px-4 py-3">Activa</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($questions as $question)
                        @php
                            $questionSlug = $question->certification?->slug;
                            $questionName = $questionSlug !== null ? ($certifications[$questionSlug] ?? null) : null;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $question->id }}</td>
                            <td class="px-4 py-3">{{ $questionSlug ?? 'N/A' }}{{ $questionName ? ' - '.$questionName : '' }}</td>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($question->prompt, 90) }}</td>
                            <td class="px-4 py-3">{{ $question->active ? 'Si' : 'No' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.questions.edit', $question) }}" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:border-[var(--accent)] hover:text-[var(--accent)]">
                                    Editar
                                </a>
                                <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="ml-2 inline" onsubmit="return confirm('Deseas eliminar esta pregunta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay preguntas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $questions->links() }}
        </div>
    </section>
@endsection
