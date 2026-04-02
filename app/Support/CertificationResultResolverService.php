<?php

namespace App\Support;

class CertificationResultResolverService
{
    /**
     * Resolve result key for a certification attempt.
     *
     * @param  array<string, mixed>  $settings
     */
    public function resolve(string $certType, string $resultMode, bool $failed, array $settings = []): string
    {
        $configured = $this->configuredKeys($settings);
        if ($configured !== null) {
            return $failed ? $configured['fail'] : $configured['pass'];
        }

        if ($resultMode === 'binary_threshold') {
            $legacyBinaryMap = [
                'hetero' => ['pass' => 'hetero_exitoso', 'fail' => 'hetero_rebeldon'],
                'good_girl' => ['pass' => 'good_girl_pura', 'fail' => 'good_girl_desatada'],
            ];

            if (isset($legacyBinaryMap[$certType])) {
                return $failed ? $legacyBinaryMap[$certType]['fail'] : $legacyBinaryMap[$certType]['pass'];
            }
        }

        return $failed ? 'failed_generic' : 'passed_generic';
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{pass: string, fail: string}|null
     */
    private function configuredKeys(array $settings): ?array
    {
        $raw = $settings['result_keys'] ?? null;
        if (!is_array($raw)) {
            return null;
        }

        $pass = isset($raw['pass']) ? trim((string) $raw['pass']) : '';
        $fail = isset($raw['fail']) ? trim((string) $raw['fail']) : '';

        if ($pass === '' || $fail === '') {
            return null;
        }

        return ['pass' => $pass, 'fail' => $fail];
    }
}
