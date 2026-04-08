<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service para gestionar retención y purga de datos de usuarios.
 * 
 * Responsabilidades:
 * - Ejecutar purga automática al vencer vigencia
 * - Ejecutar purga manual aunque la vigencia no haya vencido
 * - Purga de certificados, intentos, imágenes y trazas según política
 */
class CertificationDataRetentionService
{
    protected CertificateImageStorageService $imageService;

    public function __construct(CertificateImageStorageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Purgar datos de usuario de una certificación vencida.
     * 
     * Ejecutable vía job programado diario.
     * 
     * @param Certification $certification
     * @return array resumen de eliminación ['certificates_deleted' => int, 'images_deleted' => int, ...]
     */
    public function purgeExpiredCertificationData(Certification $certification, bool $force = false): array
    {
        $summary = [
            'certificates_deleted' => 0,
            'images_deleted' => 0,
            'audit_logs_deleted' => 0,
        ];

        if (!$force && !$this->shouldAutoPurge($certification)) {
            return $summary;
        }

        // Obtener certificados expirados
        $expiredCertificates = Certificate::where('certification_id', $certification->id)
            ->whereNotNull('certification_expires_at')
            ->where('certification_expires_at', '<', now())
            ->get();

        $summary = $this->deleteCertificatesAndRelated($expiredCertificates, $summary);

        return $summary;
    }

    /**
     * Purgar datos de usuario manualmente, sin importar if vencimiento.
     * 
     * Acción administrativa explícita, registrada en auditoría.
     * 
     * @param Certification $certification
     * @param ?int $userId Si se proporciona, purgar solo datos de ese usuario
     * @return array
     */
    public function manuallyPurgeUserData(Certification $certification, ?int $userId = null): array
    {
        $summary = [
            'certificates_deleted' => 0,
            'images_deleted' => 0,
            'audit_logs_deleted' => 0,
        ];

        $query = Certificate::where('certification_id', $certification->id);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $certificates = $query->get();
        $summary = $this->deleteCertificatesAndRelated($certificates, $summary);

        return $summary;
    }

    /**
     * Eliminar certificados y sus datos asociados.
     * 
     * @param Collection $certificates
     * @param array $summary
     * @return array
     */
    protected function deleteCertificatesAndRelated(Collection $certificates, array $summary): array
    {
        foreach ($certificates as $certificate) {
            // Eliminar imagen del certificado
            if ($certificate->certificate_image_path) {
                try {
                    $this->imageService->delete($certificate->certificate_image_path);
                    $summary['images_deleted']++;
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete image: {$certificate->certificate_image_path}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Eliminar audit logs de este certificado
            $auditDeleted = \App\Models\AuditLog::where('entity_type', 'Certificate')
                ->where('entity_id', $certificate->id)
                ->delete();
            $summary['audit_logs_deleted'] += $auditDeleted;

            // Eliminar certificado
            $certificate->delete();
            $summary['certificates_deleted']++;
        }

        return $summary;
    }

    /**
     * Determinar si una certificación debe ser purgada automáticamente.
     * 
     * @param Certification $certification
     * @return bool
     */
    protected function shouldAutoPurge(Certification $certification): bool
    {
        // La purga automática se dispara si:
        // 1. expiry_mode es 'fixed'
        // 2. manual_user_data_purge_enabled es true
        // 3. La certificación no está activa (fue desactivada o es temporal)

        return $certification->expiry_mode === 'fixed' 
            && $certification->manual_user_data_purge_enabled
            && !$certification->active;
    }

    /**
     * Obtener estadísticas de datos a purgar para una certificación.
     * 
     * @param Certification $certification
     * @return array
     */
    public function getPurgeStatistics(Certification $certification): array
    {
        $expiredCertificates = Certificate::where('certification_id', $certification->id)
            ->whereNotNull('certification_expires_at')
            ->where('certification_expires_at', '<', now())
            ->count();

        $totalCertificates = Certificate::where('certification_id', $certification->id)->count();
        $certificatesWithImages = Certificate::where('certification_id', $certification->id)
            ->whereNotNull('certificate_image_path')
            ->count();

        return [
            'total_certificates' => $totalCertificates,
            'expired_certificates' => $expiredCertificates,
            'certificates_with_images' => $certificatesWithImages,
            'will_auto_purge' => $this->shouldAutoPurge($certification),
        ];
    }
}
