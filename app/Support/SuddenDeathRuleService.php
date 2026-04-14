<?php

namespace App\Support;

use App\Enums\SuddenDeathMode;
use App\Models\Question;

/**
 * Service para evaluar reglas de muerte súbita.
 *
 * Responsabilidades:
 * - Evaluar preguntas con sudden_death_mode
 * - Interrumpir evaluación por aprobación/desaprobación automática
 * - Generar razón de decisión para auditoría
 */
class SuddenDeathRuleService
{
    /**
     * Evaluar si hay muerte súbita tras una respuesta en el examen.
     *
     * @param Question $question
     * @param bool $isCorrect true si respondió correctamente
     * @return array ['triggered' => bool, 'decision' => 'pass'|'fail'|null, 'reason' => string]
     */
    public function evaluateForQuestion(Question $question, bool $isCorrect): array
    {
        $mode = $question->sudden_death_mode ?? SuddenDeathMode::NONE->value;

        if ($mode === SuddenDeathMode::NONE->value) {
            return [
                'triggered' => false,
                'decision' => null,
                'reason' => '',
            ];
        }

        if ($mode === SuddenDeathMode::FAIL_IF_WRONG->value && !$isCorrect) {
            return [
                'triggered' => true,
                'decision' => 'fail',
                'reason' => "Pregunta de muerte súbita fallida: {$question->prompt}",
            ];
        }

        if ($mode === SuddenDeathMode::PASS_IF_CORRECT->value && $isCorrect) {
            return [
                'triggered' => true,
                'decision' => 'pass',
                'reason' => "Pregunta de muerte súbita pasada: {$question->prompt}",
            ];
        }

        return [
            'triggered' => false,
            'decision' => null,
            'reason' => '',
        ];
    }

    /**
     * Evaluar todas las preguntas con muerte súbita en una lista de respuestas.
     *
     * Retorna el PRIMER resultado de muerte súbita encontrado (terminates en orden).
     *
     * @param array $answers Estructura:
     *     [
     *         ['question_id' => 1, 'correct' => true, 'question' => Question instance],
     *         ['question_id' => 2, 'correct' => false, 'question' => Question instance],
     *     ]
     * @return array ['triggered' => bool, 'decision' => 'pass'|'fail'|null, 'reason' => string]
     */
    public function evaluateBatch(array $answers): array
    {
        foreach ($answers as $answer) {
            $question = $answer['question'] ?? null;
            if (!$question instanceof Question) {
                continue;
            }

            $result = $this->evaluateForQuestion($question, $answer['correct'] ?? false);

            if ($result['triggered']) {
                return $result;
            }
        }

        return [
            'triggered' => false,
            'decision' => null,
            'reason' => '',
        ];
    }

    /**
     * Determinar si una pregunta tiene modo muerte súbita.
     *
     * @param Question $question
     * @return bool
     */
    public function hasSuddenDeathMode(Question $question): bool
    {
        return ($question->sudden_death_mode ?? SuddenDeathMode::NONE->value) !== SuddenDeathMode::NONE->value;
    }

    /**
     * Obtener descripción legible del modo de muerte súbita.
     *
     * IMPORTANTE: Esto es solo para admin. Nunca mostrar al usuario durante examen.
     *
     * @param string $mode
     * @return string
     */
    public function getModeName(string $mode): string
    {
        return match ($mode) {
            SuddenDeathMode::FAIL_IF_WRONG->value => 'Muerte súbita: falla si es incorrecto',
            SuddenDeathMode::PASS_IF_CORRECT->value => 'Muerte súbita: pasa si es correcto',
            default => 'Normal (sin muerte súbita)',
        };
    }

    /**
     * Validar valores permitidos de sudden_death_mode.
     *
     * @param string $mode
     * @return bool
     */
    public function isValidMode(string $mode): bool
    {
        return in_array($mode, [
            SuddenDeathMode::NONE->value,
            SuddenDeathMode::FAIL_IF_WRONG->value,
            SuddenDeathMode::PASS_IF_CORRECT->value,
        ]);
    }

    /**
     * Contar preguntas con muerte súbita en una certificación.
     *
     * @param int $certificationId
     * @return array ['total' => int, 'fail_if_wrong' => int, 'pass_if_correct' => int]
     */
    public function countSuddenDeathQuestions(int $certificationId): array
    {
        $total = Question::where('certification_id', $certificationId)
            ->where('active', true)
            ->whereIn('sudden_death_mode', [SuddenDeathMode::FAIL_IF_WRONG->value, SuddenDeathMode::PASS_IF_CORRECT->value])
            ->count();

        $failIfWrong = Question::where('certification_id', $certificationId)
            ->where('active', true)
            ->where('sudden_death_mode', SuddenDeathMode::FAIL_IF_WRONG->value)
            ->count();

        $passIfCorrect = Question::where('certification_id', $certificationId)
            ->where('active', true)
            ->where('sudden_death_mode', SuddenDeathMode::PASS_IF_CORRECT->value)
            ->count();

        return [
            'total' => $total,
            'fail_if_wrong' => $failIfWrong,
            'pass_if_correct' => $passIfCorrect,
        ];
    }
}
