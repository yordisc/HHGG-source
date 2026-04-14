<?php

namespace App\Http\Controllers\Api;

use App\Enums\QuestionType;
use App\Models\Certification;
use App\Models\CertificationVersion;
use App\Support\ActiveAttemptsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificationApiController
{
    /**
     * Get available questions for a certification
     * GET /admin/api/certifications/{id}/available-questions
     */
    public function availableQuestions(Certification $certification): JsonResponse
    {
        $certification->loadMissing(['questions.translations']);

        $currentLocale = app()->getLocale();

        $questions = $certification->questions
            ->where('active', true)
            ->map(function ($question) use ($currentLocale) {
                $translation = $question->translations->firstWhere('language', $currentLocale)
                    ?? $question->translations->first();

                return [
                    'id' => $question->id,
                    'prompt' => $translation?->prompt ?? $question->prompt,
                    'type' => $question->type ?? QuestionType::MCQ_4->value,
                    'active' => $question->active,
                    'translations_count' => $question->translations->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'count' => $questions->count(),
            'questions' => $questions,
        ]);
    }

    /**
     * Get active attempts for a certification
     * GET /admin/api/certifications/{id}/active-attempts
     */
    public function activeAttempts(Certification $certification): JsonResponse
    {
        $service = app(ActiveAttemptsService::class);
        $activeCount = $service->countActive($certification);

        if ($activeCount === 0) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'attempts' => [],
                'warning' => null,
            ]);
        }

        $attempts = $service->getActiveAttempts($certification);
        $warning = $service->getWarningMessage($certification);

        return response()->json([
            'success' => true,
            'count' => $activeCount,
            'attempts' => $attempts->values(),
            'warning' => $warning,
        ]);
    }

    /**
     * Compare two certification versions
     * GET /admin/api/certifications/{id}/versions/{versionId}/compare?to={toVersionId}
     */
    public function compareVersions(
        Certification $certification,
        int $versionId,
        Request $request
    ): JsonResponse {
        $fromVersion = $certification->versions()
            ->where('id', $versionId)
            ->firstOrFail();

        $toVersionId = (int) $request->query('to');

        if ($toVersionId === 0 || $toVersionId === $fromVersion->id) {
            // Compare with current version
            $toData = $certification->toArray();
            $toVersion = null;
            $toVersionNumber = 'current';
        } else {
            $toVersion = $certification->versions()
                ->where('id', $toVersionId)
                ->firstOrFail();
            $toData = $toVersion->snapshot ?? [];
            $toVersionNumber = $toVersion->version_number;
        }

        $fromData = $fromVersion->snapshot ?? [];

        // Fields to compare
        $compareFields = [
            'name',
            'description',
            'questions_required',
            'pass_score_percentage',
            'cooldown_days',
            'result_mode',
            'active',
            'pdf_view',
            'settings',
        ];

        $diff = [];

        foreach ($compareFields as $field) {
            $fromValue = $fromData[$field] ?? null;
            $toValue = $toData[$field] ?? null;

            if ($fromValue !== $toValue) {
                $diff[$field] = [
                    'from' => $fromValue,
                    'to' => $toValue,
                    'changed' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'from_version' => [
                'id' => $fromVersion->id,
                'version_number' => $fromVersion->version_number,
                'created_at' => $fromVersion->created_at,
            ],
            'to_version' => $toVersion ? [
                'id' => $toVersion->id,
                'version_number' => $toVersion->version_number,
                'created_at' => $toVersion->created_at,
            ] : [
                'version_number' => $toVersionNumber,
                'created_at' => $certification->updated_at,
            ],
            'differences' => $diff,
            'total_changes' => count($diff),
        ]);
    }
}
