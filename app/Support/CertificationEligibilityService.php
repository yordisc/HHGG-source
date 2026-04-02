<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Certification;
use Carbon\CarbonInterface;

class CertificationEligibilityService
{
    /**
     * @return array{
     *   can_start: bool,
     *   next_available_at: CarbonInterface|null,
     *   reason: string|null
     * }
     */
    public function evaluate(string $countryCode, string $documentType, string $document, Certification $certification): array
    {
        $identityHash = Certificate::identityLookupHash($countryCode, $documentType, $document);

        $latest = Certificate::query()
            ->where('certification_id', $certification->id)
            ->where('identity_lookup_hash', $identityHash)
            ->orderByDesc('completed_at')
            ->orderByDesc('issued_at')
            ->first();

        if ($latest === null) {
            $legacyDocHash = Certificate::documentLookupHash($document);
            $latest = Certificate::query()
                ->where('certification_id', $certification->id)
                ->where('doc_lookup_hash', $legacyDocHash)
                ->orderByDesc('completed_at')
                ->orderByDesc('issued_at')
                ->first();
        }

        if ($latest === null) {
            return [
                'can_start' => true,
                'next_available_at' => null,
                'reason' => null,
            ];
        }

        $cooldownDays = (int) ($certification->cooldown_days ?: config('quiz.cooldown_days', 30));
        $pivot = $latest->completed_at ?? $latest->last_attempt_at ?? $latest->issued_at;

        if ($pivot === null) {
            return [
                'can_start' => false,
                'next_available_at' => now()->addDays($cooldownDays),
                'reason' => 'unknown_last_attempt',
            ];
        }

        $nextAvailableAt = $latest->next_available_at ?? $pivot->copy()->addDays($cooldownDays);

        if (now()->lt($nextAvailableAt)) {
            return [
                'can_start' => false,
                'next_available_at' => $nextAvailableAt,
                'reason' => 'cooldown_active',
            ];
        }

        return [
            'can_start' => true,
            'next_available_at' => null,
            'reason' => null,
        ];
    }
}
