<?php

namespace App\Support;

use App\Models\Certification;

/**
 * Service para evaluar reglas automáticas de aprobado/desaprobado.
 * 
 * Responsabilidades:
 * - Evaluar reglas por nombre y apellido del candidato
 * - Retornar decisión automática (pass, fail, none) y razón
 */
class AutoResultRuleService
{
    public const DECISION_PASS = 'pass';
    public const DECISION_FAIL = 'fail';
    public const DECISION_NONE = 'none';

    /**
     * Evaluar reglas automáticas para un candidato.
     * 
     * @param Certification $certification
     * @param string $firstName
     * @param string $lastName
     * @return array ['decision' => 'pass'|'fail'|'none', 'reason' => string]
     */
    public function evaluate(
        Certification $certification,
        string $firstName,
        string $lastName
    ): array {
        if ($certification->auto_result_rule_mode === 'none' || !$certification->auto_result_rule_config) {
            return [
                'decision' => self::DECISION_NONE,
                'reason' => '',
            ];
        }

        $config = $certification->auto_result_rule_config;

        // Validar que la configuración tenga reglas
        $rules = $config['rules'] ?? [];
        if (empty($rules)) {
            return [
                'decision' => self::DECISION_NONE,
                'reason' => '',
            ];
        }

        // Normalizar nombres para búsqueda
        $firstName = mb_strtolower(trim($firstName));
        $lastName = mb_strtolower(trim($lastName));

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $firstName, $lastName);

            if ($result['matched']) {
                return [
                    'decision' => $rule['decision'] ?? self::DECISION_NONE,
                    'reason' => $result['reason'],
                ];
            }
        }

        return [
            'decision' => self::DECISION_NONE,
            'reason' => '',
        ];
    }

    /**
     * Evaluar una regla individual contra nombre/apellido.
     * 
     * @param array $rule Estructura:
     *     [
     *         'name_pattern' => 'Juan' (null para ignorar nombre),
     *         'last_name_pattern' => 'Pérez' (null para ignorar apellido),
     *         'decision' => 'pass'|'fail',
     *         'description' => 'Aprobación automática para Juan Pérez'
     *     ]
     * @param string $firstName
     * @param string $lastName
     * @return array ['matched' => bool, 'reason' => string]
     */
    protected function evaluateRule(array $rule, string $firstName, string $lastName): array
    {
        $namePattern = isset($rule['name_pattern']) 
            ? mb_strtolower(trim($rule['name_pattern']))
            : null;

        $lastNamePattern = isset($rule['last_name_pattern'])
            ? mb_strtolower(trim($rule['last_name_pattern']))
            : null;

        // Si no hay patrones, no puede haber coincidencia
        if (!$namePattern && !$lastNamePattern) {
            return [
                'matched' => false,
                'reason' => '',
            ];
        }

        // Evaluar nombre si hay patrón
        if ($namePattern && !$this->matchesPattern($firstName, $namePattern)) {
            return [
                'matched' => false,
                'reason' => '',
            ];
        }

        // Evaluar apellido si hay patrón
        if ($lastNamePattern && !$this->matchesPattern($lastName, $lastNamePattern)) {
            return [
                'matched' => false,
                'reason' => '',
            ];
        }

        // Ambos patrones coinciden (o no hay restricción)
        $reason = $rule['description'] ?? "Regla automática: {$firstName} {$lastName}";

        return [
            'matched' => true,
            'reason' => $reason,
        ];
    }

    /**
     * Verificar si una cadena coincide con un patrón.
     * 
     * Soporta:
     * - Coincidencia exacta: "Juan" coincide solo con "Juan"
     * - Prefijo con *: "Ju*" coincide con "Juan", "Julia", "Juliana"
     * - Sufijo con *: "*van" coincide con "Juan", "Ivan"
     * - Contiene con *: "*ua*" coincide con "Juan", "Guanajuato"
     * 
     * @param string $value
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern(string $value, string $pattern): bool
    {
        // Si no hay asteriscos, es búsqueda exacta
        if (strpos($pattern, '*') === false) {
            return $value === $pattern;
        }

        // Convertir patrón a regex escapando todo menos el wildcard '*'
        $parts = array_map(static fn (string $part): string => preg_quote($part, '/'), explode('*', $pattern));
        $regex = '/^' . implode('.*', $parts) . '$/';

        return (bool) preg_match($regex, $value);
    }

    /**
     * Validar configuración de reglas.
     * 
     * @param array $config
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validateConfig(array $config): array
    {
        $errors = [];

        if (!isset($config['rules']) || !is_array($config['rules'])) {
            $errors[] = "Campo 'rules' es requerido y debe ser un array";
        } else {
            foreach ($config['rules'] as $index => $rule) {
                if (!isset($rule['decision']) || !in_array($rule['decision'], [self::DECISION_PASS, self::DECISION_FAIL])) {
                    $errors[] = "Regla {$index}: decision debe ser 'pass' o 'fail'";
                }

                if (
                    !isset($rule['name_pattern']) && !isset($rule['last_name_pattern'])
                ) {
                    $errors[] = "Regla {$index}: debe tener al menos name_pattern o last_name_pattern";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Crear configuración vacía para iniciar.
     * 
     * @return array
     */
    public function createEmptyConfig(): array
    {
        return [
            'rules' => [],
        ];
    }

    /**
     * Agregar regla a configuración.
     * 
     * @param array $config
     * @param string|null $namePattern
     * @param string|null $lastNamePattern
     * @param string $decision 'pass'|'fail'
     * @param string $description
     * @return array Configuración actualizada
     */
    public function addRule(
        array $config,
        ?string $namePattern,
        ?string $lastNamePattern,
        string $decision,
        string $description
    ): array {
        $rule = [
            'decision' => $decision,
            'description' => $description,
        ];

        if ($namePattern !== null) {
            $rule['name_pattern'] = $namePattern;
        }

        if ($lastNamePattern !== null) {
            $rule['last_name_pattern'] = $lastNamePattern;
        }

        $config['rules'][] = $rule;

        return $config;
    }

    /**
     * Obtener descripción legible del modo de reglas.
     * 
     * @param string $mode
     * @return string
     */
    public function getModeName(string $mode): string
    {
        return match($mode) {
            'name_rule' => 'Reglas automáticas por nombre/apellido',
            default => 'Sin reglas automáticas',
        };
    }
}
