<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\CertificationVersion;

class CertificationVersioningService
{
    public function createVersion(Certification $certification, ?string $changeReason = null): CertificationVersion
    {
        $lastVersion = $certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        $versionNumber = ($lastVersion?->version_number ?? 0) + 1;

        $snapshot = $certification->toArray();
        $questionsSnapshot = $certification->questions()
            ->with('translations')
            ->get()
            ->toArray();

        // Detectar cambios si hay versión anterior
        $changes = null;
        if ($lastVersion) {
            $changes = $this->detectChanges($lastVersion->snapshot, $snapshot);
        }

        return CertificationVersion::create([
            'certification_id' => $certification->id,
            'version_number' => $versionNumber,
            'snapshot' => $snapshot,
            'questions_snapshot' => $questionsSnapshot,
            'change_reason' => $changeReason,
            'changes' => $changes,
        ]);
    }

    public function rollbackToVersion(Certification $certification, CertificationVersion $version): bool
    {
        try {
            // Restaurar certificación
            $certification->update($version->snapshot);

            // Crear nueva versión como registro del rollback
            $this->createVersion(
                $certification,
                "Rollback to version {$version->version_number}"
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getVersionComparison(CertificationVersion $v1, CertificationVersion $v2): array
    {
        $diff = [];

        foreach ($v1->snapshot as $key => $value) {
            if (($v2->snapshot[$key] ?? null) !== $value) {
                $diff[$key] = [
                    'from' => $value,
                    'to' => $v2->snapshot[$key] ?? null,
                ];
            }
        }

        return $diff;
    }

    private function detectChanges(array $oldSnapshot, array $newSnapshot): ?array
    {
        $changes = [];

        foreach ($newSnapshot as $key => $value) {
            if (($oldSnapshot[$key] ?? null) !== $value) {
                $changes[$key] = [
                    'old' => $oldSnapshot[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return !empty($changes) ? $changes : null;
    }
}
