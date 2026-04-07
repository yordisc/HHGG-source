<?php

namespace App\Support;

use App\Models\Certification;

class CertificationValidationService
{
    /**
     * @return array{
     *   ready: bool,
     *   summary: array{total_questions:int, active_questions:int, required_questions:int, supported_locales: array<int, string>},
     *   warnings: array<int, string>
     * }
     */
    public function review(Certification $certification): array
    {
        $supportedLocales = config('app.supported_locales', ['en']);

        $certification->loadMissing(['questions.translations']);

        $questions = $certification->questions;
        $activeQuestions = $questions->where('active', true);
        $requiredQuestions = (int) ($certification->questions_required ?: 0);
        $warnings = [];

        if ($questions->isEmpty()) {
            $warnings[] = 'La certificacion no tiene preguntas registradas.';
        }

        if ($activeQuestions->isEmpty()) {
            $warnings[] = 'La certificacion no tiene preguntas activas.';
        }

        if ($requiredQuestions > 0 && $activeQuestions->count() < $requiredQuestions) {
            $warnings[] = 'Solo hay '.$activeQuestions->count().' preguntas activas y se requieren '.$requiredQuestions.'.';
        }

        $missingSpanish = $this->missingTranslationsCount($questions, 'es');
        if ($missingSpanish > 0) {
            $warnings[] = 'Faltan traducciones al español en '.$missingSpanish.' preguntas.';
        }

        foreach ($supportedLocales as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $missingCount = $this->missingTranslationsCount($questions, $locale);
            if ($missingCount > 0) {
                $warnings[] = 'Faltan traducciones en '.strtoupper($locale).' para '.$missingCount.' preguntas.';
            }
        }

        $rawSettings = $certification->getRawOriginal('settings');
        if (is_string($rawSettings) && trim($rawSettings) !== '') {
            json_decode($rawSettings, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $warnings[] = 'El campo settings contiene JSON invalido.';
            }
        }

        $pdfView = trim((string) ($certification->pdf_view ?: 'pdf.certificate'));
        if ($pdfView !== '' && !view()->exists($pdfView)) {
            $warnings[] = 'La vista PDF configurada no existe: '.$pdfView.'.';
        }

        return [
            'ready' => $warnings === [],
            'summary' => [
                'total_questions' => $questions->count(),
                'active_questions' => $activeQuestions->count(),
                'required_questions' => $requiredQuestions,
                'supported_locales' => $supportedLocales,
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  iterable<\App\Models\Question>  $questions
     */
    private function missingTranslationsCount(iterable $questions, string $locale): int
    {
        $count = 0;

        foreach ($questions as $question) {
            $hasTranslation = $question->translations->contains('language', $locale);

            if (! $hasTranslation) {
                $count++;
            }
        }

        return $count;
    }
}