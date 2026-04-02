<?php

namespace Tests\Unit;

use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificationEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificationEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_start_when_no_previous_attempt_exists(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'alpha_cert',
            'name' => 'Alpha Cert',
            'active' => true,
            'cooldown_days' => 30,
        ]);

        $service = new CertificationEligibilityService();
        $result = $service->evaluate('VE', 'V', 'V-12.345.678', $certification);

        $this->assertTrue($result['can_start']);
        $this->assertNull($result['next_available_at']);
    }

    public function test_blocks_start_during_cooldown_period(): void
    {
        Carbon::setTestNow(now());

        $certification = Certification::query()->create([
            'slug' => 'beta_cert',
            'name' => 'Beta Cert',
            'active' => true,
            'cooldown_days' => 30,
        ]);

        $document = 'V-12.345.678';
        $countryCode = 'VE';
        $documentType = 'V';

        Certificate::query()->create([
            'serial' => 'CERT-'.date('Y').'-BE-'.Str::upper(Str::random(6)),
            'certification_id' => $certification->id,
            'result_key' => 'passed_generic',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'country' => 'Venezuela',
            'country_code' => $countryCode,
            'document_type' => $documentType,
            'document_hash' => bcrypt($document),
            'doc_lookup_hash' => Certificate::documentLookupHash($document),
            'identity_lookup_hash' => Certificate::identityLookupHash($countryCode, $documentType, $document),
            'doc_partial' => Certificate::documentPartial($document),
            'score_correct' => 20,
            'score_incorrect' => 10,
            'total_questions' => 30,
            'score_numeric' => 66.67,
            'issued_at' => now()->subDays(10),
            'completed_at' => now()->subDays(10),
            'next_available_at' => now()->addDays(20),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now()->subDays(10),
        ]);

        $service = new CertificationEligibilityService();
        $result = $service->evaluate($countryCode, $documentType, $document, $certification);

        $this->assertFalse($result['can_start']);
        $this->assertNotNull($result['next_available_at']);
        $this->assertSame('cooldown_active', $result['reason']);

        Carbon::setTestNow();
    }
}
