<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Support\Collection;

class ActiveAttemptsService
{
    /**
     * Get count of active attempts for a certification
     */
    public function countActive(Certification $certification): int
    {
        return Certificate::where('certification_id', $certification->id)
            ->whereNull('completed_at')
            ->count();
    }

    /**
     * Get active attempts with user details
     * 
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     started_at: string,
     *     time_elapsed: string,
     * }>
     */
    public function getActiveAttempts(Certification $certification): Collection
    {
        return Certificate::query()
            ->where('certification_id', $certification->id)
            ->whereNull('completed_at')
            ->select('id', 'first_name', 'last_name', 'document_hash', 'last_attempt_at', 'created_at')
            ->get()
            ->map(function (Certificate $attempt) {
                $elapsed = $attempt->last_attempt_at
                    ? $attempt->last_attempt_at->diffForHumans()
                    : $attempt->created_at->diffForHumans();

                return [
                    'id' => $attempt->id,
                    'name' => "{$attempt->first_name} {$attempt->last_name}",
                    'document_partial' => $attempt->doc_partial ?? 'N/A',
                    'started_at' => $attempt->created_at->format('Y-m-d H:i'),
                    'time_elapsed' => $elapsed,
                ];
            });
    }

    /**
     * Check if a change is allowed given active attempts
     * Returns [bool $allowed, ?string $reason]
     *
     * @param  array  $changes  Fields being changed
     * @return array{0: bool, 1: ?string}
     */
    public function isChangeAllowed(Certification $certification, array $changes): array
    {
        $activeCount = $this->countActive($certification);

        if ($activeCount === 0) {
            return [true, null];
        }

        // Sensitive fields that cannot be changed while attempts are active
        $sensitiveFields = [
            'questions_required',
            'pass_score_percentage',
            'cooldown_days',
            'result_mode',
        ];

        $changedSensitive = array_intersect(array_keys($changes), $sensitiveFields);

        if (!empty($changedSensitive)) {
            $fieldsList = implode(', ', $changedSensitive);
            $reason = "No se puede editar los campos: {$fieldsList}. "
                . "Hay {$activeCount} intento(s) en curso que no pueden ser afectados.";
            return [false, $reason];
        }

        // Non-sensitive changes are allowed
        return [true, null];
    }

    /**
     * Get formatted warning message for the edit form
     */
    public function getWarningMessage(Certification $certification): ?string
    {
        $activeCount = $this->countActive($certification);

        if ($activeCount === 0) {
            return null;
        }

        return "⚠️ Hay {$activeCount} intento(s) en curso. "
            . "Solo puedes cambiar el nombre, descripción u ordenamiento. "
            . "Los cambios en preguntas requeridas, scoring o reglas se aplicarán solo a nuevos intentos.";
    }
}
