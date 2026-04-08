<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Certification;
use Carbon\Carbon;

/**
 * Service para calcular vigencia de certificaciones y fechas de expiración.
 * 
 * Responsabilidades:
 * - Calcular vigencia de certificación para intento y descarga
 * - Resolver certification_expires_at y download_expires_at al generar certificado
 * - Determinar si una certificación desactivada permite descarga histórica
 */
class CertificationExpirationService
{
    /**
     * Calcular fecha de expiración de certificación basada en la configuración.
     * 
     * @param Certification $certification
     * @param Carbon|null $issuedAt Fecha de emisión (default: ahora)
     * @return Carbon|null null si es indefinite, fecha si es fixed
     */
    public function calculateCertificationExpiryDate(
        Certification $certification,
        ?Carbon $issuedAt = null
    ): ?Carbon {
        if ($certification->expiry_mode === 'indefinite') {
            return null;
        }

        if ($certification->expiry_mode !== 'fixed' || !$certification->expiry_days) {
            return null;
        }

        $issuedAt = $issuedAt ?? Carbon::now();
        return $issuedAt->copy()->addDays($certification->expiry_days);
    }

    /**
     * Calcular fecha de expiración para descarga de certificado.
     * 
     * Si allow_certificate_download_after_deactivation es true, la descarga
     * se permite hasta certification_expires_at. Si es false, se deniega
     * inmediatamente al desactivar.
     * 
     * @param Certification $certification
     * @param Carbon|null $certificationExpiresAt
     * @return Carbon|null
     */
    public function calculateDownloadExpiryDate(
        Certification $certification,
        ?Carbon $certificationExpiresAt = null
    ): ?Carbon {
        if (!$certification->allow_certificate_download_after_deactivation) {
            // No hay periodo de gracia para descarga
            return null;
        }

        // La descarga se permite hasta cuando expire la certificación
        return $certificationExpiresAt;
    }

    /**
     * Determinar si una certificación (desactivada) permite descarga histórica.
     * 
     * @param Certification $certification
     * @return bool
     */
    public function allowsHistoricalDownload(Certification $certification): bool
    {
        if ($certification->active) {
            return true; // Certificación activa siempre permite descarga
        }

        return $certification->allow_certificate_download_after_deactivation;
    }

    /**
     * Verificar si un certificado está expirado para visualización.
     * 
     * @param Certificate $certificate
     * @return bool
     */
    public function isCertificateExpired(Certificate $certificate): bool
    {
        if ($certificate->certification_expires_at === null) {
            return false;
        }

        return $certificate->certification_expires_at->isPast();
    }

    /**
     * Verificar si un certificado está expirado para descarga.
     * 
     * @param Certificate $certificate
     * @return bool
     */
    public function isDownloadExpired(Certificate $certificate): bool
    {
        if ($certificate->download_expires_at === null) {
            return false;
        }

        return $certificate->download_expires_at->isPast();
    }

    /**
     * Obtener días restantes para que un certificado expire.
     * 
     * @param Certificate $certificate
     * @return int|null null si no tiene expiración, días restantes si aplica
     */
    public function getDaysUntilExpiry(Certificate $certificate): ?int
    {
        if ($certificate->certification_expires_at === null) {
            return null;
        }

        return Carbon::now()->diffInDays($certificate->certification_expires_at, false);
    }
}
