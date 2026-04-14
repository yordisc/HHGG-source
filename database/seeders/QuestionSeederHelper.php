<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Question;

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

        // Resolve certification
        $certification = Certification::query()
            ->where('slug', $certificationSlug)
            ->firstOrFail();

        $existingPrompts = Question::query()
            ->where('certification_id', $certification->id)
            ->pluck('prompt')
            ->map(static fn($prompt) => trim((string) $prompt))
            ->filter(static fn(string $prompt) => $prompt !== '')
            ->flip()
            ->all();

        $existingCount = count($existingPrompts);
        if ($existingCount >= $minCount && $existingCount >= count($questions)) {
            return 0;
        }

        // Insert only prompts that are missing for this certification.
        $rows = [];
        $now = now();
        $seenInBatch = [];

        foreach ($questions as $q) {
            $prompt = trim((string) ($q['prompt'] ?? ''));
            if ($prompt === '') {
                continue;
            }

            if (isset($existingPrompts[$prompt]) || isset($seenInBatch[$prompt])) {
                continue;
            }

            $seenInBatch[$prompt] = true;

            $rows[] = [
                'certification_id' => $certification->id,
                'prompt' => $prompt,
                'option_1' => $options[0] ?? 'Siempre',
                'option_2' => $options[1] ?? 'A veces',
                'option_3' => $options[2] ?? 'Raramente',
                'option_4' => $options[3] ?? 'Nunca',
                'correct_option' => (int) ($q['correct'] ?? 1),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
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
