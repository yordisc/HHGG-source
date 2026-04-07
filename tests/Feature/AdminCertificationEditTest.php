<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationEditTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create();

        // Set admin authentication in session (CRITICAL - required by admin.auth middleware)
        session(['admin_authenticated' => true]);

        // Create certification with active questions
        $this->certification = Certification::create([
            'slug' => 'original-cert',
            'name' => 'Original Cert',
            'description' => null,
            'active' => true,
            'questions_required' => 30,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => [],
        ]);

        // Add 50 active questions
        for ($i = 1; $i <= 50; $i++) {
            Question::create([
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
        }
    }

    public function test_admin_can_edit_basic_certification_fields(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name',
                'description' => 'New description',
                'active' => 1,
                'questions_required' => 35,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
                'settings' => null,
            ])
            ->assertRedirect(route('admin.certifications.edit', $this->certification));

        $this->certification->refresh();
        $this->assertEquals('Updated Name', $this->certification->name);
        $this->assertEquals('New description', $this->certification->description);
        $this->assertEquals(35, $this->certification->questions_required);
        $this->assertEquals(75.0, $this->certification->pass_score_percentage);
    }

    public function test_slug_must_be_unique(): void
    {
        Certification::create([
            'slug' => 'taken-slug',
            'name' => 'Other',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 2,
            'settings' => [],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'taken-slug',
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ])
            ->assertSessionHasErrors('slug');

        $this->certification->refresh();
        $this->assertEquals('original-cert', $this->certification->slug);
    }

    public function test_slug_format_validation(): void
    {
        foreach (['UPPERCASE-SLUG', 'slug with spaces', 'ab', 'slug!special'] as $slug) {
            $this->actingAs($this->admin)
                ->put(route('admin.certifications.update', $this->certification), [
                    'slug' => $slug,
                    'name' => $this->certification->name,
                    'active' => 1,
                    'questions_required' => 30,
                    'pass_score_percentage' => 70.0,
                    'cooldown_days' => 30,
                    'result_mode' => 'binary_threshold',
                    'home_order' => 1,
                ])
                ->assertSessionHasErrors('slug');
        }
    }

    public function test_pass_score_percentage_must_be_0_to_100(): void
    {
        foreach ([-10, 101, 150] as $score) {
            $this->actingAs($this->admin)
                ->put(route('admin.certifications.update', $this->certification), [
                    'slug' => $this->certification->slug,
                    'name' => $this->certification->name,
                    'active' => 1,
                    'questions_required' => 30,
                    'pass_score_percentage' => $score,
                    'cooldown_days' => 30,
                    'result_mode' => 'binary_threshold',
                    'home_order' => 1,
                ])
                ->assertSessionHasErrors('pass_score_percentage');
        }
    }

    public function test_questions_required_cannot_exceed_active_questions(): void
    {
        $cert = Certification::create([
            'slug' => 'limited-cert',
            'name' => 'Limited Cert',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 2,
            'settings' => [],
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Question::create([
                'certification_id' => $cert->id,
                'prompt' => "Q {$i}",
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

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $cert), [
                'slug' => $cert->slug,
                'name' => $cert->name,
                'active' => 1,
                'questions_required' => 20,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ])
            ->assertSessionHasErrors('questions_required');

        $cert->refresh();
        $this->assertEquals(10, $cert->questions_required);
    }

    public function test_can_reduce_questions_required_to_match_available(): void
    {
        $cert = Certification::create([
            'slug' => 'reducible-cert',
            'name' => 'Reducible',
            'description' => null,
            'active' => true,
            'questions_required' => 25,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 3,
            'settings' => [],
        ]);

        for ($i = 1; $i <= 25; $i++) {
            Question::create([
                'certification_id' => $cert->id,
                'prompt' => "Q {$i}",
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

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $cert), [
                'slug' => $cert->slug,
                'name' => $cert->name,
                'active' => 1,
                'questions_required' => 20,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ])
            ->assertRedirect();

        $cert->refresh();
        $this->assertEquals(20, $cert->questions_required);
    }

    public function test_cooldown_days_cannot_exceed_1825(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 10000,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ])
            ->assertSessionHasErrors('cooldown_days');
    }

    public function test_invalid_json_settings_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
                'settings' => '{ invalid json',
            ])
            ->assertSessionHasErrors('settings');
    }

    public function test_valid_json_settings_is_saved(): void
    {
        $settings = json_encode([
            'theme' => 'dark',
            'language' => 'es',
            'show_answers_after' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
                'settings' => $settings,
            ])
            ->assertRedirect();

        $this->certification->refresh();
        $this->assertEquals(['theme' => 'dark', 'language' => 'es', 'show_answers_after' => true], $this->certification->settings);
    }

    public function test_prevents_edit_when_active_attempts_exist(): void
    {
        // Create an active attempt (in-progress)
        Certificate::create([
            'serial' => 'TEST-' . time(),
            'certification_id' => $this->certification->id,
            'result_key' => 'key-' . time(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'US',
            'country_code' => 'US',
            'document_type' => 'passport',
            'document_hash' => 'hash',
            'doc_lookup_hash' => 'lookup',
            'identity_lookup_hash' => 'identity',
            'doc_partial' => 'partial',
            'score_correct' => 0,
            'score_incorrect' => 0,
            'total_questions' => 0,
            'score_numeric' => 0,
            'issued_at' => now(),
            'completed_at' => null, // Active attempt
            'expires_at' => now()->addDays(30),
        ]);

        // Try to change a sensitive field (should be blocked)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Changed Name',
                'active' => 1,
                'questions_required' => 25, // Sensitive change
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ]);

        // Should be blocked with error
        $response->assertSessionHas('error');
        $this->certification->refresh();
        $this->assertEquals('Original Cert', $this->certification->name);
        $this->assertEquals(30, $this->certification->questions_required);
    }

    public function test_allows_non_sensitive_edits_with_active_attempts(): void
    {
        // Create an active attempt
        Certificate::create([
            'serial' => 'TEST-' . time(),
            'certification_id' => $this->certification->id,
            'result_key' => 'key-' . time(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'US',
            'country_code' => 'US',
            'document_type' => 'passport',
            'document_hash' => 'hash',
            'doc_lookup_hash' => 'lookup',
            'identity_lookup_hash' => 'identity',
            'doc_partial' => 'partial',
            'score_correct' => 0,
            'score_incorrect' => 0,
            'total_questions' => 0,
            'score_numeric' => 0,
            'issued_at' => now(),
            'completed_at' => null, // Active attempt
            'expires_at' => now()->addDays(30),
        ]);

        // Change a non-sensitive field (name, description, etc.)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'New Name', // Non-sensitive change
                'description' => 'New description',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ]);

        // Should be allowed but with warning
        $response->assertRedirect();
        $this->certification->refresh();
        $this->assertEquals('New Name', $this->certification->name);
        $this->assertEquals('New description', $this->certification->description);
    }

    public function test_prevents_sensitive_changes_list_all_blocked_fields(): void
    {
        // Create an active attempt
        Certificate::create([
            'serial' => 'TEST-' . time(),
            'certification_id' => $this->certification->id,
            'result_key' => 'key-' . time(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'US',
            'country_code' => 'US',
            'document_type' => 'passport',
            'document_hash' => 'hash',
            'doc_lookup_hash' => 'lookup',
            'identity_lookup_hash' => 'identity',
            'doc_partial' => 'partial',
            'score_correct' => 0,
            'score_incorrect' => 0,
            'total_questions' => 0,
            'score_numeric' => 0,
            'issued_at' => now(),
            'completed_at' => null,
            'expires_at' => now()->addDays(30),
        ]);

        // Test each sensitive field individually
        $sensitiveFields = [
            'questions_required' => 25,
            'pass_score_percentage' => 75.0,
            'cooldown_days' => 60,
            'result_mode' => 'custom',
        ];

        foreach ($sensitiveFields as $field => $newValue) {
            $data = [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => $this->certification->questions_required,
                'pass_score_percentage' => $this->certification->pass_score_percentage,
                'cooldown_days' => $this->certification->cooldown_days,
                'result_mode' => $this->certification->result_mode,
                'home_order' => 1,
                $field => $newValue,
            ];

            $response = $this->actingAs($this->admin)
                ->put(route('admin.certifications.update', $this->certification), $data);

            $response->assertSessionHas('error');
        }
    }

    public function test_settings_validation_rejects_invalid_structure(): void
    {
        // Test invalid settings with wrong types
        $invalidSettings = json_encode([
            'timer_minutes' => 'not_a_number', // Should be numeric
            'randomize_questions' => 'yes', // Should be boolean
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
                'settings' => $invalidSettings,
            ])
            ->assertSessionHasErrors('settings');
    }

    public function test_settings_validation_accepts_valid_structure(): void
    {
        $validSettings = json_encode([
            'timer_minutes' => 90,
            'randomize_questions' => true,
            'show_answers' => false,
            'pass_score_percentage' => 70,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
                'settings' => $validSettings,
            ])
            ->assertRedirect();

        $this->certification->refresh();
        $this->assertEquals(90, $this->certification->settings['timer_minutes']);
        $this->assertTrue($this->certification->settings['randomize_questions']);
        $this->assertFalse($this->certification->settings['show_answers']);
    }

    public function test_allows_edit_when_no_active_attempts(): void
    {
        Certificate::create([
            'serial' => 'TEST-DONE-' . time(),
            'certification_id' => $this->certification->id,
            'result_key' => 'key-done-' . time(),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'country' => 'US',
            'country_code' => 'US',
            'document_type' => 'passport',
            'document_hash' => 'hash',
            'doc_lookup_hash' => 'lookup',
            'identity_lookup_hash' => 'identity',
            'doc_partial' => 'partial',
            'score_correct' => 30,
            'score_incorrect' => 10,
            'total_questions' => 40,
            'score_numeric' => 75.0,
            'issued_at' => now(),
            'completed_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'New Name',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ])
            ->assertRedirect();

        $this->certification->refresh();
        $this->assertEquals('New Name', $this->certification->name);
    }

    public function test_version_is_created_on_update(): void
    {
        $initialVersionCount = $this->certification->versions()->count();

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $this->assertGreaterThan($initialVersionCount, $this->certification->versions()->count());
    }

    public function test_active_status_can_be_toggled(): void
    {
        $this->certification->update(['active' => false]);

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => $this->certification->name,
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $this->assertTrue($this->certification->active);
    }
}
