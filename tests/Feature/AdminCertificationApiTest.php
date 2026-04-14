<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use App\Models\CertificationVersion;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->certification = Certification::create([
            'slug' => 'api-test-cert',
            'name' => 'API Test Certification',
            'description' => 'Test certificate',
            'active' => true,
            'questions_required' => 30,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => null,
        ]);

        // Create 50 active questions with translations
        for ($i = 1; $i <= 50; $i++) {
            $question = Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'Option A',
                'option_2' => 'Option B',
                'option_3' => 'Option C',
                'option_4' => 'Option D',
                'correct_option' => 1,
                'explanation' => 'Explanation',
                'active' => true,
            ]);

            // Add translations
            QuestionTranslation::create([
                'question_id' => $question->id,
                'language' => 'es',
                'prompt' => "Pregunta {$i}",
                'option_1' => 'Opción A',
                'option_2' => 'Opción B',
                'option_3' => 'Opción C',
                'option_4' => 'Opción D',
            ]);
        }

        // Create some inactive questions
        for ($i = 1; $i <= 10; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Inactive Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'Option A',
                'option_2' => 'Option B',
                'option_3' => 'Option C',
                'option_4' => 'Option D',
                'correct_option' => 1,
                'explanation' => 'Explanation',
                'active' => false,
            ]);
        }
    }

    // ============ Available Questions Tests ============

    public function test_available_questions_endpoint_returns_active_questions(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'count',
            'questions' => [
                '*' => [
                    'id',
                    'prompt',
                    'type',
                    'active',
                    'translations_count',
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(50, $data['count']);
    }

    public function test_available_questions_excludes_inactive_questions(): void
    {
        // Add 5 more active questions
        for ($i = 1; $i <= 5; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "New Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Ex',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.available-questions', $this->certification));

        $data = $response->json();
        // Should have 50 + 5 = 55 active questions, not the 10 inactive
        $this->assertEquals(55, $data['count']);

        foreach ($data['questions'] as $question) {
            $this->assertTrue($question['active']);
        }
    }

    public function test_available_questions_includes_translations(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.available-questions', $this->certification));

        $data = $response->json();

        foreach ($data['questions'] as $question) {
            $this->assertGreaterThan(0, $question['translations_count']);
        }
    }

    public function test_available_questions_respects_current_locale(): void
    {
        // Get with Spanish locale
        $response = $this->actingAs($this->admin)
            ->withHeader('Accept-Language', 'es')
            ->get(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        $data = $response->json();

        // Should have Spanish prompts (they start with "Pregunta")
        $firstQuestion = $data['questions'][0] ?? null;
        $this->assertNotNull($firstQuestion);
    }

    // ============ Active Attempts Tests ============

    public function test_active_attempts_endpoint_returns_empty_when_no_attempts(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.active-attempts', $this->certification));

        $response->assertSuccessful();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(0, $data['count']);
        $this->assertEmpty($data['attempts']);
        $this->assertNull($data['warning']);
    }

    public function test_active_attempts_endpoint_returns_active_attempts(): void
    {
        // Create multiple active attempts
        for ($i = 1; $i <= 3; $i++) {
            Certificate::create([
                'serial' => 'TEST-' . time() . "-{$i}",
                'certification_id' => $this->certification->id,
                'result_key' => 'key-' . time() . "-{$i}",
                'first_name' => "John{$i}",
                'last_name' => "Doe{$i}",
                'country' => 'US',
                'country_code' => 'US',
                'document_type' => 'passport',
                'document_hash' => "hash{$i}",
                'doc_lookup_hash' => 'lookup',
                'identity_lookup_hash' => 'identity',
                'doc_partial' => '1111',
                'score_correct' => 0,
                'score_incorrect' => 0,
                'total_questions' => 0,
                'score_numeric' => 0,
                'issued_at' => now(),
                'completed_at' => null, // Active
                'expires_at' => now()->addDays(30),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.active-attempts', $this->certification));

        $response->assertSuccessful();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['count']);
        $this->assertCount(3, $data['attempts']);
        $this->assertNotNull($data['warning']);
    }

    public function test_active_attempts_excludes_completed_attempts(): void
    {
        // Create 2 active attempts
        for ($i = 1; $i <= 2; $i++) {
            Certificate::create([
                'serial' => 'ACTIVE-' . time() . "-{$i}",
                'certification_id' => $this->certification->id,
                'result_key' => 'key-' . time() . "-{$i}",
                'first_name' => "Active{$i}",
                'last_name' => "User{$i}",
                'country' => 'US',
                'country_code' => 'US',
                'document_type' => 'passport',
                'document_hash' => "hash{$i}",
                'doc_lookup_hash' => 'lookup',
                'identity_lookup_hash' => 'identity',
                'doc_partial' => '1111',
                'score_correct' => 0,
                'score_incorrect' => 0,
                'total_questions' => 0,
                'score_numeric' => 0,
                'issued_at' => now(),
                'completed_at' => null,
                'expires_at' => now()->addDays(30),
            ]);
        }

        // Create 2 completed attempts
        for ($i = 1; $i <= 2; $i++) {
            Certificate::create([
                'serial' => 'COMPLETED-' . time() . "-{$i}",
                'certification_id' => $this->certification->id,
                'result_key' => 'key-' . time() . "-{$i}",
                'first_name' => "Completed{$i}",
                'last_name' => "User{$i}",
                'country' => 'US',
                'country_code' => 'US',
                'document_type' => 'passport',
                'document_hash' => "hash{$i}",
                'doc_lookup_hash' => 'lookup',
                'identity_lookup_hash' => 'identity',
                'doc_partial' => '1111',
                'score_correct' => 30,
                'score_incorrect' => 20,
                'total_questions' => 50,
                'score_numeric' => 75.0,
                'issued_at' => now(),
                'completed_at' => now(), // Completed
                'expires_at' => now()->addDays(365),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.active-attempts', $this->certification));

        $data = $response->json();

        // Should only count 2 active, not the 2 completed
        $this->assertEquals(2, $data['count']);
        $this->assertCount(2, $data['attempts']);
    }

    public function test_active_attempts_includes_user_details(): void
    {
        Certificate::create([
            'serial' => 'TEST-' . time(),
            'certification_id' => $this->certification->id,
            'result_key' => 'key-' . time(),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'country' => 'US',
            'country_code' => 'US',
            'document_type' => 'passport',
            'document_hash' => 'hash123',
            'doc_lookup_hash' => 'lookup',
            'identity_lookup_hash' => 'identity',
            'doc_partial' => '1234',
            'score_correct' => 0,
            'score_incorrect' => 0,
            'total_questions' => 0,
            'score_numeric' => 0,
            'issued_at' => now(),
            'completed_at' => null,
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.active-attempts', $this->certification));

        $data = $response->json();
        $attempt = $data['attempts'][0] ?? null;

        $this->assertNotNull($attempt);
        $this->assertEquals('Jane Smith', $attempt['name']);
        $this->assertNotNull($attempt['started_at']);
        $this->assertNotNull($attempt['time_elapsed']);
    }

    // ============ Version Comparison Tests ============

    public function test_compare_versions_with_current(): void
    {
        // Create a version
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name',
                'active' => 1,
                'questions_required' => 25,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $version = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Make another update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Final Name',
                'active' => 1,
                'questions_required' => 20,
                'pass_score_percentage' => 80.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        // Compare old version with current
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.versions.compare', [
                'certification' => $this->certification,
                'versionId' => $version->id,
            ]));

        $response->assertSuccessful();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertNotNull($data['differences']);
        $this->assertGreaterThan(0, $data['total_changes']);
    }

    public function test_compare_versions_shows_differences(): void
    {
        // First update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'First Name',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $firstVersion = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Second update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Second Name',
                'active' => 0,
                'questions_required' => 25,
                'pass_score_percentage' => 80.0,
                'cooldown_days' => 60,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $secondVersion = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Compare first with second
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.versions.compare', [
                'certification' => $this->certification,
                'versionId' => $firstVersion->id,
            ]) . '?to=' . $secondVersion->id);

        $data = $response->json();
        $differences = $data['differences'];

        // Should show changes in name, active, questions_required, pass_score_percentage, cooldown_days
        $this->assertArrayHasKey('name', $differences);
        $this->assertArrayHasKey('active', $differences);
        $this->assertArrayHasKey('questions_required', $differences);
        $this->assertArrayHasKey('pass_score_percentage', $differences);
        $this->assertArrayHasKey('cooldown_days', $differences);
    }

    public function test_compare_versions_returns_proper_structure(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $version = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.versions.compare', [
                'certification' => $this->certification,
                'versionId' => $version->id,
            ]));

        $response->assertJsonStructure([
            'success',
            'from_version' => [
                'id',
                'version_number',
                'created_at',
            ],
            'to_version' => [
                'version_number',
                'created_at',
            ],
            'differences',
            'total_changes',
        ]);
    }

    public function test_compare_versions_with_invalid_version_fails(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('api.certifications.versions.compare', [
                'certification' => $this->certification,
                'versionId' => 99999,
            ]));

        $response->assertNotFound();
    }
}
