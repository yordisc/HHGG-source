<?php

namespace App\Support;

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
    public const MODE_NONE = 'none';
    public const MODE_FAIL_IF_WRONG = 'fail_if_wrong';
    public const MODE_PASS_IF_CORRECT = 'pass_if_correct';

    /**
     * Evaluar si hay muerte súbita tras una respuesta en el examen.
     * 
     * @param Question $question
     * @param bool $isCorrect true si respondió correctamente
     * @return array ['triggered' => bool, 'decision' => 'pass'|'fail'|null, 'reason' => string]
     */
    public function evaluateForQuestion(Question $question, bool $isCorrect): array
    {
        $mode = $question->sudden_death_mode ?? self::MODE_NONE;

        if ($mode === self::MODE_NONE) {
            return [
                'triggered' => false,
                'decision' => null,
                'reason' => '',
            ];
        }

        if ($mode === self::MODE_FAIL_IF_WRONG && !$isCorrect) {
            return [
                'triggered' => true,
                'decision' => 'fail',
                'reason' => "Pregunta de muerte súbita fallida: {$question->prompt}",
            ];
        }

        if ($mode === self::MODE_PASS_IF_CORRECT && $isCorrect) {
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
        return ($question->sudden_death_mode ?? self::MODE_NONE) !== self::MODE_NONE;
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
        return match($mode) {
            self::MODE_FAIL_IF_WRONG => 'Muerte súbita: falla si es incorrecto',
            self::MODE_PASS_IF_CORRECT => 'Muerte súbita: pasa si es correcto',
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
            self::MODE_NONE,
            self::MODE_FAIL_IF_WRONG,
            self::MODE_PASS_IF_CORRECT,
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
            ->whereIn('sudden_death_mode', [self::MODE_FAIL_IF_WRONG, self::MODE_PASS_IF_CORRECT])
            ->count();

        $failIfWrong = Question::where('certification_id', $certificationId)
            ->where('active', true)
            ->where('sudden_death_mode', self::MODE_FAIL_IF_WRONG)
            ->count();

        $passIfCorrect = Question::where('certification_id', $certificationId)
            ->where('active', true)
            ->where('sudden_death_mode', self::MODE_PASS_IF_CORRECT)
            ->count();

        return [
            'total' => $total,
            'fail_if_wrong' => $failIfWrong,
            'pass_if_correct' => $passIfCorrect,
        ];
    }
}
