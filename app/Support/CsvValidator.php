<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CsvImportLog;
use App\Models\Question;
use Illuminate\Http\UploadedFile;

class CsvValidator
{
    private array $errors = [];
    private array $warnings = [];
    private array $preview = [];
    private int $totalRows = 0;

    public function validate(UploadedFile $file): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->preview = [];
        $this->totalRows = 0;

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            $this->errors[] = 'No fue posible leer el archivo CSV.';
            return $this->getResult();
        }

        // Leer header
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->errors[] = 'El archivo CSV está vacío.';
            return $this->getResult();
        }

        $header = array_map(fn ($value) => mb_strtolower(trim((string) $value)), $header);
        $required = ['cert_type', 'prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'correct_option'];

        // Validar columnas requeridas
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                $this->errors[] = "Falta columna requerida: {$col}";
            }
        }

        if (!empty($this->errors)) {
            fclose($handle);
            return $this->getResult();
        }

        $rowNum = 1;
        $previewCount = 0;
        $certificationMap = Certification::query()->pluck('id', 'slug')->all();

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $this->totalRows++;
            $item = array_combine($header, array_pad($row, count($header), ''));

            if (!is_array($item)) {
                continue;
            }

            // Validar fila
            $this->validateRow($item, $rowNum);

            // Guardar preview (primeras 5 filas)
            if ($previewCount < 5 && isset($item['cert_type']) && isset($item['prompt'])) {
                $questionId = (int) ($item['question_id'] ?? 0);
                $certType = trim((string) ($item['cert_type'] ?? ''));
                $language = mb_strtolower(trim((string) ($item['language'] ?? 'en')));
                
                $certExists = isset($certificationMap[$certType]);
                $isEnglish = $language === 'en';
                $questionExists = $questionId > 0 && Question::where('id', $questionId)->exists();

                $created = false;
                $updated = false;

                if (!$certExists) {
                    // No se procesará si no existe certificación
                } elseif ($isEnglish) {
                    if ($questionId === 0) {
                        $created = true;
                    } else {
                        $updated = $questionExists;
                    }
                }

                $this->preview[] = [
                    'row' => $rowNum,
                    'cert_type' => $certType,
                    'language' => $language,
                    'prompt' => mb_substr($item['prompt'], 0, 100),
                    'correct_option' => $item['correct_option'] ?? '',
                    'created' => $created,
                    'updated' => $updated,
                ];
                $previewCount++;
            }

            $rowNum++;
        }

        fclose($handle);

        return $this->getResult();
    }

    private function validateRow(array $item, int $rowNum): void
    {
        $certType = trim($item['cert_type'] ?? '');
        $prompt = trim($item['prompt'] ?? '');
        $correct = (int)($item['correct_option'] ?? 0);

        if (!$certType) {
            $this->errors[] = "Fila {$rowNum}: cert_type vacío";
        }

        if (!$prompt) {
            $this->errors[] = "Fila {$rowNum}: prompt vacío";
        }

        if ($correct < 1 || $correct > 4) {
            $this->errors[] = "Fila {$rowNum}: correct_option debe ser 1-4";
        }

        // Warning si alguna opción está vacía
        for ($i = 1; $i <= 4; $i++) {
            $optionKey = "option_{$i}";
            if (!isset($item[$optionKey]) || !trim($item[$optionKey])) {
                $this->warnings[] = "Fila {$rowNum}: opción {$i} vacía";
            }
        }
    }

    public function getResult(): array
    {
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'preview' => $this->preview,
            'total_rows' => $this->totalRows,
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
        ];
    }

    public function createLog(UploadedFile $file, int $created, int $updated, int $translations, int $skipped, array $errors): CsvImportLog
    {
        return CsvImportLog::create([
            'filename' => $file->getClientOriginalName(),
            'file_size_bytes' => $file->getSize(),
            'total_rows' => $this->totalRows,
            'created_count' => $created,
            'updated_count' => $updated,
            'translation_count' => $translations,
            'skipped_count' => $skipped,
            'errors' => !empty($errors) ? $errors : null,
            'status' => empty($errors) ? 'completed' : 'completed_with_errors',
            'preview_rows' => $this->preview,
        ]);
    }
}
