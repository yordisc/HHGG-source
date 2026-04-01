<?php

namespace Tests\Feature;

use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_returns_ok_and_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_search_by_serial_redirects_to_public_certificate(): void
    {
        $certificate = Certificate::create([
            'serial' => 'CERT-2026-HO-ABC123',
            'cert_type' => 'hetero',
            'result_key' => 'hetero_exitoso',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'country' => 'CO',
            'document_hash' => bcrypt('12345678'),
            'doc_lookup_hash' => Certificate::documentLookupHash('12345678'),
            'doc_partial' => '5678',
            'score_correct' => 24,
            'score_incorrect' => 6,
            'total_questions' => 30,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);

        $response = $this->post('/search', [
            'query' => $certificate->serial,
        ]);

        $response->assertRedirect(route('cert.show', ['serial' => $certificate->serial]));
    }
}
