<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCertificationPhase3FieldsTest extends TestCase
{
    use RefreshDatabase;

    private Certification $certification;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->certification = Certification::factory()->create([
            'active' => false,
            'questions_required' => 10,
        ]);

        Question::factory()->count(10)->create([
            'certification_id' => $this->certification->id,
            'active' => true,
        ]);
    }

    #[Test]
    public function admin_can_update_certification_with_expiry_fields(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => true,
                'questions_required' => 10,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'expiry_mode' => 'fixed',
                'expiry_days' => 30,
                'allow_certificate_download_after_deactivation' => true,
            ]);

        $response->assertRedirect(route('admin.certifications.edit', $this->certification));

        $this->certification->refresh();
        $this->assertEquals('fixed', $this->certification->expiry_mode);
        $this->assertEquals(30, $this->certification->expiry_days);
        $this->assertTrue($this->certification->allow_certificate_download_after_deactivation);
    }

    #[Test]
    public function admin_can_update_certification_with_retention_fields(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'manual_user_data_purge_enabled' => true,
            ]);

        $response->assertRedirect();

        $this->certification->refresh();
        $this->assertTrue($this->certification->manual_user_data_purge_enabled);
    }

    #[Test]
    public function admin_can_update_certification_with_randomization_fields(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'shuffle_questions' => true,
                'shuffle_options' => true,
            ]);

        $response->assertRedirect();

        $this->certification->refresh();
        $this->assertTrue($this->certification->shuffle_questions);
        $this->assertTrue($this->certification->shuffle_options);
    }

    #[Test]
    public function admin_can_update_certification_with_question_bank_requirement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'require_question_bank_for_activation' => true,
            ]);

        $response->assertRedirect();

        $this->certification->refresh();
        $this->assertTrue($this->certification->require_question_bank_for_activation);
    }

    #[Test]
    public function admin_can_update_certification_with_auto_rules(): void
    {
        $config = [
            'rules' => [
                [
                    'name_pattern' => 'John*',
                    'last_name_pattern' => null,
                    'decision' => 'pass',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'auto_result_rule_mode' => 'name_rule',
                'auto_result_rule_config' => json_encode($config),
            ]);

        $response->assertRedirect();

        $this->certification->refresh();
        $this->assertEquals('name_rule', $this->certification->auto_result_rule_mode);
        $this->assertIsArray($this->certification->auto_result_rule_config);
    }

    #[Test]
    public function validation_rejects_invalid_expiry_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'expiry_mode' => 'invalid_mode',
            ]);

        $response->assertSessionHasErrors('expiry_mode');
    }

    #[Test]
    public function validation_requires_expiry_days_for_fixed_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => 'test-cert',
                'name' => 'Test Certification',
                'home_order' => 1,
                'active' => false,
                'questions_required' => 1,
                'pass_score_percentage' => 70,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'expiry_mode' => 'fixed',
                'expiry_days' => null,
            ]);

        $response->assertSessionHasErrors('expiry_days');
    }

    #[Test]
    public function admin_can_create_certification_with_phase3_fields(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.certifications.store'), [
                'slug' => 'new-cert',
                'name' => 'New Certification',
                'home_order' => 1,
                'active' => true,
                'questions_required' => 10,
                'pass_score_percentage' => 75,
                'cooldown_days' => 14,
                'result_mode' => 'binary_threshold',
                'expiry_mode' => 'indefinite',
                'shuffle_questions' => true,
                'shuffle_options' => false,
            ]);

        $response->assertRedirect();

        $certification = Certification::where('slug', 'new-cert')->first();
        $this->assertNotNull($certification);
        $this->assertEquals('indefinite', $certification->expiry_mode);
        $this->assertTrue($certification->shuffle_questions);
        $this->assertFalse($certification->shuffle_options);
    }
}
