<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificateIntegrityService;
use App\Support\CertificationExpirationService;
use App\Support\CertificationResultResolverService;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class CreateCertificateAction
{
    public function execute(
        Certification $certification,
        array $candidate,
        string $certType,
        string $resultMode,
        array $resultSettings,
        bool $failed,
        float $scoreNumeric,
        int $correctCount,
        int $incorrectCount,
        int $totalQuestions,
        CarbonInterface $completedAt,
        int $cooldownDays,
        string $resultDecisionSource,
        string $resultDecisionReason,
    ): Certificate {
        $resultKey = app(CertificationResultResolverService::class)->resolve(
            $certType,
            $resultMode,
            $failed,
            $resultSettings
        );

        $expirationService = app(CertificationExpirationService::class);
        $certificationExpiresAt = $expirationService->calculateCertificationExpiryDate($certification, $completedAt);
        $downloadExpiresAt = $expirationService->calculateDownloadExpiryDate($certification, $certificationExpiresAt);

        $serial = 'CERT-' . date('Y') . '-' . strtoupper(substr($certType, 0, 2)) . '-' . Str::upper(Str::random(6));

        $certificate = Certificate::create([
            'serial' => $serial,
            'certification_id' => $certification->id,
            'result_key' => $resultKey,
            'first_name' => $candidate['first_name'],
            'last_name' => $candidate['last_name'],
            'country' => $candidate['country'],
            'country_code' => $candidate['country_code'] ?? null,
            'document_type' => $candidate['document_type'] ?? null,
            'document_hash' => $candidate['document_hash'],
            'doc_lookup_hash' => $candidate['doc_lookup_hash'],
            'identity_lookup_hash' => $candidate['identity_lookup_hash'] ?? null,
            'doc_partial' => $candidate['doc_partial'],
            'score_correct' => $correctCount,
            'score_incorrect' => $incorrectCount,
            'total_questions' => $totalQuestions,
            'score_numeric' => $scoreNumeric,
            'issued_at' => $completedAt,
            'completed_at' => $completedAt,
            'next_available_at' => $completedAt->copy()->addDays($cooldownDays),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => $completedAt,
            'certification_expires_at' => $certificationExpiresAt,
            'download_expires_at' => $downloadExpiresAt,
            'result_decision_source' => $resultDecisionSource,
            'result_decision_reason' => $resultDecisionReason,
        ]);

        $integrity = app(CertificateIntegrityService::class);
        $certificate->update([
            'content_hash' => $integrity->contentHash($certificate),
            'verification_token_hash' => $integrity->verificationTokenHash($certificate),
        ]);

        return $certificate;
    }
}
