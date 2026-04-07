<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_exists(): void
    {
        $admin = User::factory()->create();
        $certification = Certification::create([
            'slug' => 'test-cert',
            'name' => 'Test Cert',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => [],
        ]);

        // Test the route generation
        $routeUrl = route('admin.certifications.update', $certification);
        $this->assertStringContainsString($certification->id, $routeUrl);
        
        // Test PUT request
        $response = $this->actingAs($admin)->put($routeUrl, [
            'slug' => 'test-cert',
            'name' => 'Updated Test Cert',
            'active' => 1,
            'questions_required' => 10,
            'pass_score_percentage' => 75.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'home_order' => 1,
        ]);

        $this->assertIn($response->status(), [200, 302, 303]);
    }
}
