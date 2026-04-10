<?php

namespace App\Support;

use App\Models\Certificate;

class CertificateIntegrityService
{
    public function verificationToken(Certificate $certificate): string
    {
        $payload = implode('|', [
            (string) $certificate->serial,
            (string) $certificate->issued_at?->format(DATE_ATOM),
            (string) $certificate->doc_lookup_hash,
        ]);

        $raw = hash_hmac('sha256', $payload, (string) config('app.key'), true);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function verificationTokenHash(Certificate $certificate): string
    {
        return hash('sha256', $this->verificationToken($certificate));
    }

    public function contentHash(Certificate $certificate): string
    {
        $payload = implode('|', [
            (string) $certificate->serial,
            trim((string) $certificate->first_name),
            trim((string) $certificate->last_name),
            (string) $certificate->result_key,
            (string) $certificate->score_numeric,
            (string) $certificate->issued_at?->format(DATE_ATOM),
            (string) $certificate->expires_at?->format(DATE_ATOM),
            (string) $certificate->certification_id,
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function isValidVerificationToken(Certificate $certificate, string $token): bool
    {
        $providedHash = hash('sha256', $token);

        if (!empty($certificate->verification_token_hash)) {
            return hash_equals((string) $certificate->verification_token_hash, $providedHash);
        }

        return hash_equals($this->verificationToken($certificate), $token);
    }

    public function verificationUrl(Certificate $certificate): string
    {
        return route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $this->verificationToken($certificate),
        ]);
    }

    public function verificationQrUrl(Certificate $certificate, int $size = 220): string
    {
        return 'https://quickchart.io/qr?size='.$size.'&text='.urlencode($this->verificationUrl($certificate));
    }
}
