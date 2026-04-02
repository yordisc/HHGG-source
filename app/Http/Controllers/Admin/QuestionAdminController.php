<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportQuestionsCsvRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $certificationSlug = (string) $data['cert_type'];
        $certificationId = $this->resolveCertificationId($certificationSlug);

        $question = Question::create([
            'certification_id' => $certificationId,
            'prompt' => $data['prompt'],
            'option_1' => $data['option_1'],
            'option_2' => $data['option_2'],
            'option_3' => $data['option_3'],
            'option_4' => $data['option_4'],
            'correct_option' => $data['correct_option'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        $this->saveTranslations($question, $data['translations'] ?? []);
        $this->incrementMetric('admin.questions.created');
        Log::info('admin.questions.created', [
            'question_id' => $question->id,
            'certification_slug' => $certificationSlug,
        ]);

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('status', 'Pregunta creada correctamente.');
    }

    public function index(Request $request): View
    {
        $filterType = (string) $request->query('cert_type', '');
        $certifications = $this->certificationOptions();

        $questions = Question::query()
            ->when(array_key_exists($filterType, $certifications), function ($query) use ($filterType): void {
                $query->whereHas('certification', function ($certQuery) use ($filterType): void {
                    $certQuery->where('slug', $filterType);
                });
            })
            ->with('certification')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'filterType' => $filterType,
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

        $question->update([
            'certification_id' => $certificationId,
            'prompt' => $data['prompt'],
            'option_1' => $data['option_1'],
            'option_2' => $data['option_2'],
            'option_3' => $data['option_3'],
            'option_4' => $data['option_4'],
            'correct_option' => $data['correct_option'],
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

        $header = array_map(fn ($value) => mb_strtolower(trim((string) $value)), $header);
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
                'csv_file' => 'Error importando CSV: '.$exception->getMessage(),
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

    public function exportCsv(Request $request): StreamedResponse
    {
        $filterType = (string) $request->query('cert_type', '');
        $certifications = $this->certificationOptions();

        $this->incrementMetric('admin.questions.export_csv.requested');
        Log::info('admin.questions.export_csv.requested', [
            'filter_type' => $filterType === '' ? 'all' : $filterType,
        ]);

        $fileName = 'questions_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
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

    private function resolveCertificationId(string $slug): int
    {
        return (int) Certification::query()
            ->where('slug', $slug)
            ->firstOrFail()
            ->id;
    }

    public function downloadTemplateCsv(): StreamedResponse
    {
        $this->incrementMetric('admin.questions.template_csv.downloaded');
        Log::info('admin.questions.template_csv.downloaded');

        $fileName = 'questions_template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
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
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
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
        $key = 'metrics.'.now()->format('Ymd').'.'.$metric;

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addDays(35));
        }

        Cache::increment($key);
    }
}
