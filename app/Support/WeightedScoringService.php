<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Service para calcular scoring con pesos.
 * 
 * Responsabilidades:
 * - Calcular score ponderado: suma de pesos correctos / suma de pesos totales
 * - Normalizar pesos para visualización y validación
 * - Soportar pesos extremos
 */
class WeightedScoringService
{
    /**
     * Calcular score ponderado basado en respuestas y pesos de preguntas.
     * 
     * Formula: (suma_pesos_correctos / suma_pesos_totales) * 100
     * 
     * @param array $answers Array con estructura:
     *     [
     *         ['question_id' => 1, 'correct' => true, 'weight' => 2.5],
     *         ['question_id' => 2, 'correct' => false, 'weight' => 1.0],
     *     ]
     * @return float Score 0-100
     */
    public function calculateWeightedScore(array $answers): float
    {
        if (empty($answers)) {
            return 0.0;
        }

        $totalWeight = 0.0;
        $correctWeight = 0.0;

        foreach ($answers as $answer) {
            $weight = $answer['weight'] ?? 1.0;
            $totalWeight += $weight;

            if ($answer['correct'] ?? false) {
                $correctWeight += $weight;
            }
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        $percentage = ($correctWeight / $totalWeight) * 100;

        return (float) round($percentage, 2);
    }

    /**
     * Calcular estadísticas detalladas del scoring.
     * 
     * @param array $answers
     * @return array
     */
    public function calculateScoringStatistics(array $answers): array
    {
        $totalWeight = 0.0;
        $correctWeight = 0.0;
        $incorrectWeight = 0.0;
        $questionCount = count($answers);
        $correctCount = 0;

        foreach ($answers as $answer) {
            $weight = $answer['weight'] ?? 1.0;
            $totalWeight += $weight;

            if ($answer['correct'] ?? false) {
                $correctWeight += $weight;
                $correctCount++;
            } else {
                $incorrectWeight += $weight;
            }
        }

        $percentage = $totalWeight > 0 
            ? round(($correctWeight / $totalWeight) * 100, 2)
            : 0.0;

        return [
            'total_weight' => (float) round($totalWeight, 4),
            'correct_weight' => (float) round($correctWeight, 4),
            'incorrect_weight' => (float) round($incorrectWeight, 4),
            'score_percentage' => (float) $percentage,
            'questions_total' => $questionCount,
            'questions_correct' => $correctCount,
            'questions_incorrect' => $questionCount - $correctCount,
            'average_weight_per_question' => (float) round($totalWeight / max($questionCount, 1), 4),
        ];
    }

    /**
     * Validar que los pesos sean válidos.
     * 
     * @param array $answers
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validateWeights(array $answers): array
    {
        $errors = [];

        foreach ($answers as $index => $answer) {
            $weight = $answer['weight'] ?? 1.0;

            if ($weight <= 0) {
                $errors[] = "Pregunta {$index}: peso debe ser mayor a 0";
            }

            if ($weight > 99999.99) {
                $errors[] = "Pregunta {$index}: peso demasiado alto";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Normalizar pesos para que sumen exactamente 100.
     * 
     * Útil para visualización y comparación.
     * 
     * @param array $answers
     * @return array Answers con weights normalizados
     */
    public function normalizeWeights(array $answers): array
    {
        $totalWeight = 0.0;
        foreach ($answers as $answer) {
            $totalWeight += $answer['weight'] ?? 1.0;
        }

        if ($totalWeight === 0.0) {
            return $answers;
        }

        $normalized = [];
        foreach ($answers as $answer) {
            $answer['normalized_weight'] = (float) round(
                (($answer['weight'] ?? 1.0) / $totalWeight) * 100,
                2
            );
            $normalized[] = $answer;
        }

        return $normalized;
    }

    /**
     * Obtener distribución de pesos.
     * 
     * Útil para análisis y visualización.
     * 
     * @param array $answers
     * @return array
     */
    public function getWeightDistribution(array $answers): array
    {
        $distribution = [
            'total' => 0.0,
            'by_group' => [],
        ];

        foreach ($answers as $answer) {
            $weight = $answer['weight'] ?? 1.0;
            $distribution['total'] += $weight;

            // Agrupar por rango
            if ($weight < 0.5) {
                $group = 'very_light';
            } elseif ($weight < 1.0) {
                $group = 'light';
            } elseif ($weight <= 2.0) {
                $group = 'normal';
            } elseif ($weight <= 5.0) {
                $group = 'heavy';
            } else {
                $group = 'very_heavy';
            }

            if (!isset($distribution['by_group'][$group])) {
                $distribution['by_group'][$group] = 0;
            }
            $distribution['by_group'][$group]++;
        }

        return $distribution;
    }
}
