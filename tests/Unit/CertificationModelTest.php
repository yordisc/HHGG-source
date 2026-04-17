<?php

namespace Tests\Unit;

use App\Models\Certification;
use App\Models\CertificationStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_latest_statistics_returns_array(): void
    {
        $certification = Certification::create([
            'slug' => 'test-cert',
            'name' => 'Test Certification',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        // Create some statistics
        CertificationStatistic::create([
            'certification_id' => $certification->id,
            'date' => now()->subDays(5),
            'attempts_count' => 10,
            'passes_count' => 7,
            'failures_count' => 3,
            'completions_count' => 10,
        ]);

        CertificationStatistic::create([
            'certification_id' => $certification->id,
            'date' => now(),
            'attempts_count' => 15,
            'passes_count' => 12,
            'failures_count' => 3,
            'completions_count' => 15,
        ]);

        $stats = $certification->getLatestStatistics(days: 30);

        $this->assertIsArray($stats);
        $this->assertCount(2, $stats);
        $this->assertArrayHasKey('attempts_count', $stats[0]);
    }

    public function test_get_latest_statistics_filters_by_days(): void
    {
        $certification = Certification::create([
            'slug' => 'test-cert-days',
            'name' => 'Test Certification Days',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        // Create statistics from different time periods
        CertificationStatistic::create([
            'certification_id' => $certification->id,
            'date' => now()->subDays(40),
            'attempts_count' => 5,
            'passes_count' => 3,
            'failures_count' => 2,
            'completions_count' => 5,
        ]);

        CertificationStatistic::create([
            'certification_id' => $certification->id,
            'date' => now()->subDays(10),
            'attempts_count' => 20,
            'passes_count' => 15,
            'failures_count' => 5,
            'completions_count' => 20,
        ]);

        // Get stats for last 30 days (should exclude the 40-day old record)
        $stats = $certification->getLatestStatistics(days: 30);

        $this->assertCount(1, $stats);
        $this->assertSame(20, $stats[0]['attempts_count']);
    }

    public function test_get_latest_statistics_uses_where_date_method(): void
    {
        // This test validates that the fixed method name 'whereDate' works correctly
        // The typo 'wherDate' would cause BadMethodCallException
        $certification = Certification::create([
            'slug' => 'test-cert-wheredate',
            'name' => 'Test Certification WhereDate',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        // Should not throw BadMethodCallException with the fixed method name
        $stats = $certification->getLatestStatistics();

        $this->assertIsArray($stats);
    }
}
