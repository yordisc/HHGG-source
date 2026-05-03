<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Enums\SuddenDeathMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportQuestionsCsvRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Certification;
use App\Models\CsvImportLog;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Support\CsvValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionAdminController extends Controller
{
    public function create(): View
    {
        return view('admin.questions.create', [
            'certifications' => $this->certificationOptions(),
            'supportedLocales' => config('app.supported_locales', ['en']),
            'currentLocale' => app()->getLocale(),
        ]);
    }

    public function builder(): View
    {
        return view('admin.questions.builder', [
            'certifications' => Certification::where('active', true)->get(),
            'supportedLocales' => config('app.supported_locales', ['en']),
            'currentLocale' => app()->getLocale(),
        ]);
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Determinar certification_id
        if (isset($data['certification_id'])) {
            $certificationId = $data['certification_id'];
        } else {
            $certificationSlug = (string) $data['cert_type'];
            $certificationId = $this->resolveCertificationId($certificationSlug);
        }

        // Preparar datos base de la pregunta
        $questionData = [
            'certification_id' => $certificationId,
            'prompt' => $data['prompt'],
            'correct_option' => $data['correct_option'],
            'active' => (bool) ($data['active'] ?? false),
            'is_test_question' => (bool) ($data['is_test_question'] ?? false),
        ];

        // Agregar campos específicos del builder
        if (isset($data['type'])) {
            $questionData['type'] = $data['type'];
        }

        if (isset($data['weight'])) {
            $questionData['weight'] = (float) $data['weight'];
        }

        if (isset($data['sudden_death_mode'])) {
            $questionData['sudden_death_mode'] = $data['sudden_death_mode'];
        }

        if (isset($data['explanation'])) {
            $questionData['explanation'] = $data['explanation'];
        }

        if (isset($data['image_path'])) {
            $questionData['image_path'] = $data['image_path'];
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions', 'public');
            $questionData['image_path'] = $imagePath;
        }

        // Agregar opciones según el tipo
        $type = $data['type'] ?? QuestionType::MCQ_4->value;

        if ($type === QuestionType::MCQ_2->value) {
            $questionData['option_1'] = $data['option_1'] ?? '';
            $questionData['option_2'] = $data['option_2'] ?? '';
            $questionData['option_3'] = null;
            $questionData['option_4'] = null;
        } else {
            // mcq_4 (default)
            $questionData['option_1'] = $data['option_1'] ?? '';
            $questionData['option_2'] = $data['option_2'] ?? '';
            $questionData['option_3'] = $data['option_3'] ?? '';
            $questionData['option_4'] = $data['option_4'] ?? '';
        }

        $question = Question::create($questionData);

        $this->saveTranslations($question, $data['translations'] ?? []);
        $this->incrementMetric('admin.questions.created');
        Log::info('admin.questions.created', [
            'question_id' => $question->id,
            'type' => $type,
        ]);

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('status', 'Pregunta creada correctamente.');
    }

    public function index(Request $request): View
    {
        $filterType = $this->queryString($request, 'cert_type');
        $filterActive = $this->queryString($request, 'active');
        $search = $this->queryString($request, 'search');
        $sortBy = $this->queryString($request, 'sort', 'latest');
        $perPage = $this->queryInt($request, 'per_page', 20);

        if (!in_array($sortBy, ['latest', 'oldest', 'alphabetical'], true)) {
            $sortBy = 'latest';
        }

        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $certifications = $this->certificationOptions();

        $questions = Question::query()
            ->when(array_key_exists($filterType, $certifications), function ($query) use ($filterType): void {
                $query->whereHas('certification', function ($certQuery) use ($filterType): void {
                    $certQuery->where('slug', $filterType);
                });
            })
            ->when($filterActive !== '', function ($query) use ($filterActive): void {
                $query->where('active', (bool) $filterActive);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('prompt', 'like', '%' . $search . '%');
            })
            ->with('certification')
            ->when($sortBy === 'oldest', function ($query) {
                $query->oldest('id');
            })
            ->when($sortBy === 'alphabetical', function ($query) {
                $query->orderBy('prompt');
            })
            ->when($sortBy !== 'oldest' && $sortBy !== 'alphabetical', function ($query) {
                $query->latest('id');
            })
            ->paginate($perPage);

        return view('admin.questions.index', [
            'questions' => $questions,
            'filterType' => $filterType,
            'filterActive' => $filterActive,
            'search' => $search,
            'sortBy' => $sortBy,
            'perPage' => $perPage,
            'certifications' => $certifications,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function edit(Question $question): View
    {
        $question->load('translations');

        return view('admin.questions.edit', [
            'question' => $question,
            'certifications' => $this->certificationOptions(),
            'translations' => $question->translations->keyBy('language'),
            'supportedLocales' => config('app.supported_locales', ['en']),
            'currentLocale' => app()->getLocale(),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $data = $request->validated();
        $certificationSlug = (string) $data['cert_type'];
        $certificationId = $this->resolveCertificationId($certificationSlug);

        $imagePath = $question->image_path;
        if (!empty($data['remove_image']) && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $question->update([
            'certification_id' => $certificationId,
            'prompt' => $data['prompt'],
            'option_1' => $data['option_1'],
            'option_2' => $data['option_2'],
            'option_3' => $data['option_3'],
            'option_4' => $data['option_4'],
            'correct_option' => $data['correct_option'],
            'type' => $data['type'] ?? QuestionType::MCQ_4->value,
            'weight' => isset($data['weight']) ? (float) $data['weight'] : 1.0,
            'sudden_death_mode' => $data['sudden_death_mode'] ?? SuddenDeathMode::NONE->value,
            'image_path' => $imagePath,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        $this->saveTranslations($question, $data['translations'] ?? []);
        $this->incrementMetric('admin.questions.updated');
        Log::info('admin.questions.updated', [
            'question_id' => $question->id,
            'certification_slug' => $certificationSlug,
        ]);

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('status', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $deletedQuestionId = $question->id;
        $deletedCertificationSlug = $question->certification?->slug;

        $question->delete();
        $this->incrementMetric('admin.questions.deleted');
        Log::warning('admin.questions.deleted', [
            'question_id' => $deletedQuestionId,
            'certification_slug' => $deletedCertificationSlug,
        ]);

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Pregunta eliminada correctamente.');
    }

    public function importCsv(ImportQuestionsCsvRequest $request): RedirectResponse
    {
        $file = $request->file('csv_file');

        if ($file === null) {
            return back()->withErrors(['csv_file' => 'No se recibio archivo CSV.']);
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'No fue posible leer el archivo CSV.']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'El archivo CSV esta vacio.']);
        }

        $header = array_map(fn($value) => mb_strtolower(trim((string) $value)), $header);
        $required = ['cert_type', 'prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'correct_option'];

        foreach ($required as $requiredColumn) {
            if (!in_array($requiredColumn, $header, true)) {
                fclose($handle);
                return back()->withErrors([
                    'csv_file' => "Falta columna requerida en CSV: {$requiredColumn}",
                ]);
            }
        }

        $created = 0;
        $updated = 0;
        $translations = 0;
        $skipped = 0;
        $certificationMap = Certification::query()->pluck('id', 'slug')->all();

        Log::info('admin.questions.import_csv.started', [
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ]);

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $item = array_combine($header, array_pad($row, count($header), ''));

                if (!is_array($item)) {
                    $skipped++;
                    continue;
                }

                $certType = trim((string) ($item['cert_type'] ?? ''));
                $certificationId = $certificationMap[$certType] ?? null;
                if ($certificationId === null) {
                    $skipped++;
                    continue;
                }

                $language = mb_strtolower(trim((string) ($item['language'] ?? 'en')));
                if ($language === '') {
                    $language = 'en';
                }

                $questionId = (int) ($item['question_id'] ?? 0);
                $question = $questionId > 0 ? Question::find($questionId) : null;

                if ($language === 'en') {
                    if ($question === null) {
                        $question = Question::create([
                            'certification_id' => $certificationId,
                            'prompt' => (string) ($item['prompt'] ?? ''),
                            'option_1' => (string) ($item['option_1'] ?? ''),
                            'option_2' => (string) ($item['option_2'] ?? ''),
                            'option_3' => (string) ($item['option_3'] ?? ''),
                            'option_4' => (string) ($item['option_4'] ?? ''),
                            'correct_option' => max(1, min(4, (int) ($item['correct_option'] ?? 1))),
                            'active' => $this->toBool($item['active'] ?? '1'),
                        ]);
                        $created++;
                    } else {
                        $question->update([
                            'certification_id' => $certificationId,
                            'prompt' => (string) ($item['prompt'] ?? $question->prompt),
                            'option_1' => (string) ($item['option_1'] ?? $question->option_1),
                            'option_2' => (string) ($item['option_2'] ?? $question->option_2),
                            'option_3' => (string) ($item['option_3'] ?? $question->option_3),
                            'option_4' => (string) ($item['option_4'] ?? $question->option_4),
                            'correct_option' => max(1, min(4, (int) ($item['correct_option'] ?? $question->correct_option))),
                            'active' => array_key_exists('active', $item) ? $this->toBool($item['active']) : $question->active,
                        ]);
                        $updated++;
                    }

                    continue;
                }

                if ($question === null) {
                    $skipped++;
                    continue;
                }

                QuestionTranslation::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'language' => $language,
                    ],
                    [
                        'prompt' => trim((string) ($item['prompt'] ?? '')) ?: $question->prompt,
                        'option_1' => trim((string) ($item['option_1'] ?? '')) ?: $question->option_1,
                        'option_2' => trim((string) ($item['option_2'] ?? '')) ?: $question->option_2,
                        'option_3' => trim((string) ($item['option_3'] ?? '')) ?: $question->option_3,
                        'option_4' => trim((string) ($item['option_4'] ?? '')) ?: $question->option_4,
                    ]
                );

                $translations++;
            }

            fclose($handle);
            DB::commit();
        } catch (\Throwable $exception) {
            fclose($handle);
            DB::rollBack();

            Log::error('admin.questions.import_csv.failed', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'csv_file' => 'Error importando CSV: ' . $exception->getMessage(),
            ]);
        }

        $this->incrementMetric('admin.questions.import_csv.completed');
        Log::info('admin.questions.import_csv.completed', [
            'created' => $created,
            'updated' => $updated,
            'translations' => $translations,
            'skipped' => $skipped,
        ]);

        return back()->with('status', "Importacion lista. Creadas: {$created}, actualizadas: {$updated}, traducciones: {$translations}, omitidas: {$skipped}.");
    }

    public function confirmCsvImport(Request $request): RedirectResponse
    {
        $tempPath = $request->input('temp_path');
        $certType = $request->input('cert_type');

        if (!$tempPath || !file_exists(storage_path('app/' . $tempPath))) {
            return back()->withErrors(['csv_file' => 'Archivo temporal no encontrado.']);
        }

        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            storage_path('app/' . $tempPath),
            basename($tempPath),
            'text/csv'
        );

        try {
            $certificationMap = Certification::query()->pluck('id', 'slug')->all();
            $created = 0;
            $updated = 0;
            $translations = 0;
            $skipped = 0;

            $handle = @fopen($file->getRealPath(), 'r');
            if (!$handle) {
                throw new \Exception('No se puede leer el archivo CSV.');
            }

            $header = fgetcsv($handle);
            if (!$header) {
                throw new \Exception('El archivo CSV está vacío o es inválido.');
            }

            DB::beginTransaction();

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $item = array_combine($header, array_pad($row, count($header), ''));
                if (!is_array($item)) {
                    $skipped++;
                    continue;
                }

                $csvCertType = trim((string) ($item['cert_type'] ?? ''));
                $certificationId = $certificationMap[$csvCertType] ?? null;
                if ($certificationId === null) {
                    $skipped++;
                    continue;
                }

                $language = mb_strtolower(trim((string) ($item['language'] ?? 'en')));
                if ($language === '') {
                    $language = 'en';
                }

                $questionId = (int) ($item['question_id'] ?? 0);
                $question = $questionId > 0 ? Question::find($questionId) : null;

                if ($language === 'en') {
                    if ($question === null) {
                        $question = Question::create([
                            'certification_id' => $certificationId,
                            'prompt' => (string) ($item['prompt'] ?? ''),
                            'option_1' => (string) ($item['option_1'] ?? ''),
                            'option_2' => (string) ($item['option_2'] ?? ''),
                            'option_3' => (string) ($item['option_3'] ?? ''),
                            'option_4' => (string) ($item['option_4'] ?? ''),
                            'correct_option' => max(1, min(4, (int) ($item['correct_option'] ?? 1))),
                            'active' => $this->toBool($item['active'] ?? '1'),
                        ]);
                        $created++;
                    } else {
                        $question->update([
                            'certification_id' => $certificationId,
                            'prompt' => (string) ($item['prompt'] ?? $question->prompt),
                            'option_1' => (string) ($item['option_1'] ?? $question->option_1),
                            'option_2' => (string) ($item['option_2'] ?? $question->option_2),
                            'option_3' => (string) ($item['option_3'] ?? $question->option_3),
                            'option_4' => (string) ($item['option_4'] ?? $question->option_4),
                            'correct_option' => max(1, min(4, (int) ($item['correct_option'] ?? $question->correct_option))),
                            'active' => array_key_exists('active', $item) ? $this->toBool($item['active']) : $question->active,
                        ]);
                        $updated++;
                    }
                    continue;
                }

                if ($question === null) {
                    $skipped++;
                    continue;
                }

                QuestionTranslation::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'language' => $language,
                    ],
                    [
                        'prompt' => trim((string) ($item['prompt'] ?? '')) ?: $question->prompt,
                        'option_1' => trim((string) ($item['option_1'] ?? '')) ?: $question->option_1,
                        'option_2' => trim((string) ($item['option_2'] ?? '')) ?: $question->option_2,
                        'option_3' => trim((string) ($item['option_3'] ?? '')) ?: $question->option_3,
                        'option_4' => trim((string) ($item['option_4'] ?? '')) ?: $question->option_4,
                    ]
                );
                $translations++;
            }

            fclose($handle);
            DB::commit();

            // Registrar log de importación
            CsvImportLog::create([
                'filename' => $file->getClientOriginalName(),
                'file_size_bytes' => $file->getSize(),
                'total_rows' => $created + $updated + $skipped,
                'created_count' => $created,
                'updated_count' => $updated,
                'translation_count' => $translations,
                'skipped_count' => $skipped,
                'errors' => [],
                'status' => 'success',
                'preview_rows' => null,
            ]);

            // Limpiar archivo temporal
            @unlink(storage_path('app/' . $tempPath));

            $this->incrementMetric('admin.questions.import_csv.completed');
            Log::info('admin.questions.import_csv.completed', [
                'created' => $created,
                'updated' => $updated,
                'translations' => $translations,
                'skipped' => $skipped,
            ]);

            return redirect()
                ->route('admin.questions.index')
                ->with('status', "✅ Importación completada. Creadas: {$created}, actualizadas: {$updated}, traducciones: {$translations}, omitidas: {$skipped}.");
        } catch (\Throwable $exception) {
            DB::rollBack();
            @unlink(storage_path('app/' . $tempPath));

            Log::error('admin.questions.import_csv.failed', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'csv_file' => 'Error en importación: ' . $exception->getMessage(),
            ]);
        }
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filterType = (string) $request->query('cert_type', '');
        $certifications = $this->certificationOptions();

        $this->incrementMetric('admin.questions.export_csv.requested');
        Log::info('admin.questions.export_csv.requested', [
            'filter_type' => $filterType === '' ? 'all' : $filterType,
        ]);

        $fileName = 'questions_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($filterType, $certifications): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'question_id',
                'language',
                'cert_type',
                'prompt',
                'option_1',
                'option_2',
                'option_3',
                'option_4',
                'correct_option',
                'active',
            ]);

            Question::query()
                ->when(array_key_exists($filterType, $certifications), function ($query) use ($filterType): void {
                    $query->whereHas('certification', function ($certQuery) use ($filterType): void {
                        $certQuery->where('slug', $filterType);
                    });
                })
                ->with(['translations', 'certification'])
                ->orderBy('id')
                ->chunkById(200, function ($questions) use ($output): void {
                    foreach ($questions as $question) {
                        $certificationSlug = $question->certification?->slug ?? '';

                        fputcsv($output, [
                            $question->id,
                            'en',
                            $certificationSlug,
                            $question->prompt,
                            $question->option_1,
                            $question->option_2,
                            $question->option_3,
                            $question->option_4,
                            $question->correct_option,
                            $question->active ? '1' : '0',
                        ]);

                        foreach ($question->translations as $translation) {
                            fputcsv($output, [
                                $question->id,
                                $translation->language,
                                $certificationSlug,
                                $translation->prompt,
                                $translation->option_1,
                                $translation->option_2,
                                $translation->option_3,
                                $translation->option_4,
                                $question->correct_option,
                                $question->active ? '1' : '0',
                            ]);
                        }
                    }
                });

            fclose($output);
        }, $fileName, $headers);
    }

    private function certificationOptions(): array
    {
        return Certification::query()
            ->active()
            ->ordered()
            ->pluck('name', 'slug')
            ->all();
    }

    private function queryString(Request $request, string $key, string $default = ''): string
    {
        $value = $request->query($key, $default);

        if (!is_scalar($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    private function queryInt(Request $request, string $key, int $default): int
    {
        $value = $request->query($key, $default);

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return $default;
        }

        return (int) $value;
    }

    private function resolveCertificationId(string $slug): int
    {
        return (int) Certification::query()
            ->where('slug', $slug)
            ->firstOrFail()
            ->id;
    }

    public function duplicate(Question $question): RedirectResponse
    {
        try {
            $newQuestion = $question->replicate();
            $newQuestion->save();

            foreach ($question->translations as $translation) {
                $newTranslation = $translation->replicate();
                $newTranslation->question_id = $newQuestion->id;
                $newTranslation->save();
            }

            $this->incrementMetric('admin.questions.duplicated');
            Log::info('admin.questions.duplicated', [
                'original_id' => $question->id,
                'duplicate_id' => $newQuestion->id,
            ]);

            return redirect()
                ->route('admin.questions.edit', $newQuestion)
                ->with('status', 'Pregunta duplicada correctamente. Puedes editar la copia.');
        } catch (\Exception $e) {
            Log::error('admin.questions.duplicate.failed', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al duplicar la pregunta: ' . $e->getMessage());
        }
    }

    public function validateCsv(Request $request)
    {
        $file = $request->file('csv_file');

        if (!$file) {
            return back()->withErrors(['csv_file' => 'No se recibió archivo CSV.']);
        }

        $validator = new CsvValidator();
        $result = $validator->validate($file);

        // Guardar archivo temporalmente para confirmación
        $tempPath = $file->store('csv-temp');

        return view('admin.questions.csv-preview', [
            'result' => $result,
            'tempPath' => $tempPath,
            'fileName' => $file->getClientOriginalName(),
            'certifications' => $this->certificationOptions(),
        ]);
    }

    public function downloadTemplateCsv(): StreamedResponse
    {
        $this->incrementMetric('admin.questions.template_csv.downloaded');
        Log::info('admin.questions.template_csv.downloaded');

        $fileName = 'questions_template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'question_id',
                'language',
                'cert_type',
                'prompt',
                'option_1',
                'option_2',
                'option_3',
                'option_4',
                'correct_option',
                'active',
            ]);

            // Fila base (EN): crea o actualiza pregunta principal.
            fputcsv($output, [
                '',
                'en',
                'hetero',
                'What do you do when you see an attractive person? #999',
                'Always',
                'Sometimes',
                'Rarely',
                'Never',
                '1',
                '1',
            ]);

            // Fila traduccion (ES): requiere question_id existente para actualizar/insertar traduccion.
            fputcsv($output, [
                '1',
                'es',
                'hetero',
                '¿Qué haces cuando ves una persona atractiva? #1',
                'Siempre',
                'A veces',
                'Raramente',
                'Nunca',
                '1',
                '1',
            ]);

            // Otro ejemplo base para good_girl.
            fputcsv($output, [
                '',
                'en',
                'good_girl',
                'How often do you apologize unnecessarily? #999',
                'Always',
                'Sometimes',
                'Rarely',
                'Never',
                '1',
                '1',
            ]);

            fclose($output);
        }, $fileName, $headers);
    }

    private function saveTranslations(Question $question, array $translations): void
    {
        foreach ($translations as $language => $translationData) {
            if (!is_array($translationData)) {
                continue;
            }

            $hasAnyValue = collect($translationData)
                ->filter(fn($value) => is_string($value) && trim($value) !== '')
                ->isNotEmpty();

            if (!$hasAnyValue) {
                continue;
            }

            QuestionTranslation::query()->updateOrCreate(
                [
                    'question_id' => $question->id,
                    'language' => $language,
                ],
                [
                    'prompt' => (string) ($translationData['prompt'] ?? $question->prompt),
                    'option_1' => (string) ($translationData['option_1'] ?? $question->option_1),
                    'option_2' => (string) ($translationData['option_2'] ?? $question->option_2),
                    'option_3' => (string) ($translationData['option_3'] ?? $question->option_3),
                    'option_4' => (string) ($translationData['option_4'] ?? $question->option_4),
                ]
            );
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'si', 'yes', 'on'], true);
    }

    private function incrementMetric(string $metric): void
    {
        $key = 'metrics.' . now()->format('Ymd') . '.' . $metric;

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addDays(35));
        }

        Cache::increment($key);
    }
}
