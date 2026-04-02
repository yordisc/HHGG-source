<?php

namespace App\Support;

use App\Models\Certification;

/**
 * @deprecated Use CertificationEligibilityService directly.
 */
class QuizEligibilityService
{
    public function evaluate(string $countryCode, string $documentType, string $document, string $certType): array
    {
        $certification = Certification::query()->active()->where('slug', $certType)->first();

        if ($certification === null) {
            return [
                'can_start' => false,
                'next_available_at' => null,
                'reason' => 'certification_not_found',
            ];
        }

        return app(CertificationEligibilityService::class)->evaluate(
            $countryCode,
            $documentType,
            $document,
            $certification
        );
    }
}
