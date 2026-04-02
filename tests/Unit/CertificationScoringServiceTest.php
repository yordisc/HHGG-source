<?php

namespace Tests\Unit;

use App\Support\CertificationScoringService;
use Tests\TestCase;

class CertificationScoringServiceTest extends TestCase
{
    public function test_calculates_score_and_failed_flag_using_certification_threshold(): void
    {
        $service = new CertificationScoringService();

        $result = $service->evaluate(18, 30, 70.0);

        $this->assertSame(60.0, $result['score_numeric']);
        $this->assertSame(70.0, $result['pass_score']);
        $this->assertTrue($result['failed']);
    }

    public function test_uses_global_default_threshold_when_certification_threshold_is_invalid(): void
    {
        config()->set('quiz.pass_score_percentage', 66.67);

        $service = new CertificationScoringService();
        $result = $service->evaluate(20, 30, 0.0);

        $this->assertSame(66.67, $result['pass_score']);
        $this->assertFalse($result['failed']);
    }
}
