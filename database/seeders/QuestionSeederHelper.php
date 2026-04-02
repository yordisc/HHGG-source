<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Question;
use Carbon\Carbon;

/**
 * Helper class for seeding questions for certifications.
 * 
 * Provides reusable methods to seed questions, reducing code duplication
 * across certification-specific seeders.
 * 
 * Usage:
 *   QuestionSeederHelper::seedQuestionsForCertification(
 *       certificationSlug: 'hetero',
 *       questions: [
 *           ['prompt' => 'Question text', 'correct' => 1],
 *           ...
 *       ],
 *       minCount: 30,
 *       options: ['Siempre', 'A veces', 'Raramente', 'Nunca']
 *   )
 */
class QuestionSeederHelper
{
    /**
     * Seed questions for a specific certification.
     * 
     * @param string $certificationSlug The certification slug (e.g., 'hetero', 'good_girl')
     * @param array $questions Array of question data: [['prompt' => '...', 'correct' => 1], ...]
     * @param int $minCount Minimum question count before seeding (skip if already present)
     * @param array $options Question option labels (default: ['Siempre', 'A veces', 'Raramente', 'Nunca'])
     * @return int Number of rows inserted
     */
    public static function seedQuestionsForCertification(
        string $certificationSlug,
        array $questions,
        int $minCount = 30,
        array $options = null
    ): int {
        $options = $options ?? ['Siempre', 'A veces', 'Raramente', 'Nunca'];

        // Skip if questions already seeded for this certification
        $existingCount = Question::query()
            ->whereHas('certification', fn ($q) => $q->where('slug', $certificationSlug))
            ->count();

        if ($existingCount >= $minCount) {
            return 0;
        }

        // Resolve certification
        $certification = Certification::query()
            ->where('slug', $certificationSlug)
            ->firstOrFail();

        // Build rows with all question data
        $rows = [];
        $now = now();

        foreach ($questions as $q) {
            $rows[] = [
                'certification_id' => $certification->id,
                'prompt' => $q['prompt'],
                'option_1' => $options[0] ?? 'Siempre',
                'option_2' => $options[1] ?? 'A veces',
                'option_3' => $options[2] ?? 'Raramente',
                'option_4' => $options[3] ?? 'Nunca',
                'correct_option' => $q['correct'],
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return Question::insert($rows) ? count($rows) : 0;
    }

    /**
     * Get default question options.
     * Standard options: Siempre, A veces, Raramente, Nunca
     */
    public static function getDefaultOptions(): array
    {
        return ['Siempre', 'A veces', 'Raramente', 'Nunca'];
    }
}
