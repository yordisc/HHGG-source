<?php

namespace Tests\Unit;

use App\Support\CertificationResultResolverService;
use Tests\TestCase;

class CertificationResultResolverServiceTest extends TestCase
{
    public function test_uses_legacy_binary_map_for_known_certifications(): void
    {
        $resolver = new CertificationResultResolverService();

        $this->assertSame('hetero_exitoso', $resolver->resolve('hetero', 'binary_threshold', false));
        $this->assertSame('hetero_rebeldon', $resolver->resolve('hetero', 'binary_threshold', true));
        $this->assertSame('good_girl_pura', $resolver->resolve('good_girl', 'binary_threshold', false));
        $this->assertSame('good_girl_desatada', $resolver->resolve('good_girl', 'binary_threshold', true));
    }

    public function test_uses_generic_fallback_for_unknown_certifications(): void
    {
        $resolver = new CertificationResultResolverService();

        $this->assertSame('passed_generic', $resolver->resolve('new_cert', 'binary_threshold', false));
        $this->assertSame('failed_generic', $resolver->resolve('new_cert', 'binary_threshold', true));
    }

    public function test_prioritizes_configured_result_keys_from_settings(): void
    {
        $resolver = new CertificationResultResolverService();

        $settings = [
            'result_keys' => [
                'pass' => 'custom_pass',
                'fail' => 'custom_fail',
            ],
        ];

        $this->assertSame('custom_pass', $resolver->resolve('any', 'binary_threshold', false, $settings));
        $this->assertSame('custom_fail', $resolver->resolve('any', 'binary_threshold', true, $settings));
    }
}
