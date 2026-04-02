<?php

namespace App\Support;

class CertificationScoringService
{
    /**
     * @return array{score_numeric: float, pass_score: float, failed: bool}
     */
    public function evaluate(int $correctCount, int $totalQuestions, ?float $certificationPassScore = null): array
    {
        $scoreNumeric = $totalQuestions > 0
            ? round(($correctCount / $totalQuestions) * 100, 2)
            : 0.0;

        $passScore = ($certificationPassScore !== null && $certificationPassScore > 0)
            ? $certificationPassScore
            : (float) config('quiz.pass_score_percentage', 66.67);

        return [
            'score_numeric' => $scoreNumeric,
            'pass_score' => $passScore,
            'failed' => $scoreNumeric < $passScore,
        ];
    }
}
