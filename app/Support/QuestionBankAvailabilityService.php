<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;

/**
 * Service para verificar disponibilidad de bancos de preguntas por idioma.
 * 
 * Responsabilidades:
 * - Verificar disponibilidad de banco para locale activo
 * - Resolver idiomas disponibles por certificación
 * - Entregar motivo de bloqueo cuando no hay banco en el idioma actual
 * - Resolver si corresponde selector manual cuando hay múltiples idiomas
 */
class QuestionBankAvailabilityService
{
    /**
     * Verificar si hay banco de preguntas disponible para un locale específico.
     * 
     * @param Certification $certification
     * @param string $locale Ej: 'es', 'en', 'fr'
     * @return bool
     */
    public function isBankAvailable(Certification $certification, string $locale): bool
    {
        return QuestionTranslation::whereHas('question', function ($query) use ($certification) {
            $query->where('certification_id', $certification->id)
                ->where('active', true);
        })
            ->where('language', $locale)
            ->exists();
    }

    /**
     * Obtener lista de idiomas con banco de preguntas disponible.
     * 
     * @param Certification $certification
     * @return array Ej: ['es', 'en', 'fr']
     */
    public function getAvailableLanguages(Certification $certification): array
    {
        return QuestionTranslation::whereHas('question', function ($query) use ($certification) {
            $query->where('certification_id', $certification->id)
                ->where('active', true);
        })
            ->distinct('language')
            ->pluck('language')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Verificar si debe mostrarse selector manual de idioma.
     * 
     * Condiciones:
     * 1. Existen 2+ idiomas disponibles
     * 2. El locale del navegador NO está en la lista disponible
     * 
     * @param Certification $certification
     * @param string $currentLocale Locale actual del navegador
     * @return bool
     */
    public function shouldShowLanguageSelector(Certification $certification, string $currentLocale): bool
    {
        $availableLanguages = $this->getAvailableLanguages($certification);

        // Si solo hay 1 o 0 idiomas, no mostrar selector
        if (count($availableLanguages) <= 1) {
            return false;
        }

        // Si el locale actual está disponible, no mostrar selector
        if (in_array($currentLocale, $availableLanguages)) {
            return false;
        }

        // Mostrar selector
        return true;
    }

    /**
     * Obtener motivo de bloqueo si no hay banco disponible.
     * 
     * @param Certification $certification
     * @param string $locale
     * @return string|null null si hay banco, motivo si está bloqueado
     */
    public function getBlockReason(Certification $certification, string $locale): ?string
    {
        if ($this->isBankAvailable($certification, $locale)) {
            return null;
        }

        $availableLanguages = $this->getAvailableLanguages($certification);

        if (empty($availableLanguages)) {
            return "No hay banco de preguntas disponible para ningún idioma en esta certificación.";
        }

        return "No hay banco de preguntas disponible para el idioma actual ({$locale}). "
            . "Idiomas disponibles: " . implode(", ", $availableLanguages);
    }

    /**
     * Verificar si certificación puede ser activada según requisito de banco.
     * 
     * Si require_question_bank_for_activation es true, debe haber al menos
     * un banco válido (con preguntas activas) antes de activar.
     * 
     * @param Certification $certification
     * @return bool
     */
    public function canActivate(Certification $certification): bool
    {
        if (!$certification->require_question_bank_for_activation) {
            return true;
        }

        // Verificar si hay al menos un banco con preguntas activas
        $hasValidBank = Question::where('certification_id', $certification->id)
            ->where('active', true)
            ->whereHas('translations')
            ->exists();

        return $hasValidBank;
    }

    /**
     * Obtener motivo si no puede activarse.
     * 
     * @param Certification $certification
     * @return string|null
     */
    public function getActivationBlockReason(Certification $certification): ?string
    {
        if ($this->canActivate($certification)) {
            return null;
        }

        return "No se puede activar esta certificación sin al menos un banco de preguntas válido.";
    }

    /**
     * Contar preguntas activas por idioma.
     * 
     * @param Certification $certification
     * @return array Ej: ['es' => 23, 'en' => 20]
     */
    public function getQuestionCountByLanguage(Certification $certification): array
    {
        $counts = [];
        $languages = $this->getAvailableLanguages($certification);

        foreach ($languages as $locale) {
            $count = QuestionTranslation::whereHas('question', function ($query) use ($certification) {
                $query->where('certification_id', $certification->id)
                    ->where('active', true);
            })
                ->where('language', $locale)
                ->count();

            $counts[$locale] = $count;
        }

        return $counts;
    }
}
