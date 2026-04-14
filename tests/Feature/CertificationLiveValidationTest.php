<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationLiveValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->certification = Certification::create([
            'slug' => 'validation-test-cert',
            'name' => 'Validation Test Certificate',
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

        // Create sample questions
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

    public function test_live_validation_script_is_loaded(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that validation script is loaded
        $response->assertSeeText('validationRules');
        $response->assertSeeText('validateField');
        $response->assertSeeText('updateFieldFeedback');
        $response->assertSeeText('initializeLiveValidation');
    }

    public function test_name_field_validation_is_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that name validation rules are present
        $response->assertSeeText('validationRules');
        // Should have: empty check, min 3 chars, max 255
        $response->assertSeeText('El nombre no puede estar vacío');
        $response->assertSeeText('Mínimo 3 caracteres');
        $response->assertSeeText('Máximo 255 caracteres');
    }

    public function test_pass_score_percentage_validation_is_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that percentage validation rules are present
        $response->assertSeeText('Debe ser un número');
        $response->assertSeeText('Debe estar entre 0 y 100');
        $response->assertSeeText('Muy bajo');
        $response->assertSeeText('Muy alto');
    }

    public function test_cooldown_days_validation_is_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that cooldown validation rules are present
        $response->assertSeeText('No puede ser negativo');
        $response->assertSeeText('Máximo 1825 días');
    }

    public function test_validation_feedback_element_created(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that feedback creation logic is present
        $response->assertSeeText('feedback');
        $response->assertSeeText('getElementById');
        $response->assertSeeText('appendChild');
    }

    public function test_validation_error_styling_applied(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that error styling classes are present
        $response->assertSeeText('border-red-500');
        $response->assertSeeText('border-red-200');
        $response->assertSeeText('bg-red-50');
        $response->assertSeeText('text-red-700');
    }

    public function test_validation_warning_styling_applied(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that warning styling classes are present
        $response->assertSeeText('border-yellow-500');
        $response->assertSeeText('border-yellow-200');
        $response->assertSeeText('bg-yellow-50');
        $response->assertSeeText('text-yellow-700');
    }

    public function test_validation_on_input_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that input event listeners are set
        $response->assertSeeText('addEventListener');
        $response->assertSeeText("'input'");
        $response->assertSeeText('updateFieldFeedback');
    }

    public function test_validation_on_change_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that change event listeners are set
        $response->assertSeeText("'change'");
    }

    public function test_form_submit_validation_before_submit(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that form validation happens on submit
        $response->assertSeeText('validateAllFields');
        $response->assertSeeText("'submit'");
        $response->assertSeeText('preventDefault');
    }

    public function test_validation_scrolls_to_first_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that scroll to error logic is present
        $response->assertSeeText('scrollIntoView');
        $response->assertSeeText('smooth');
        $response->assertSeeText('focus');
    }

    public function test_percentage_low_warning_shown(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for low percentage warning
        $response->assertSeeText('Casi todos pasarán');
    }

    public function test_percentage_high_warning_shown(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for high percentage warning
        $response->assertSeeText('Solo los mejores pasarán');
    }

    public function test_cooldown_long_warning_shown(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for long cooldown warning
        $response->assertSeeText('Muy largo');
    }

    public function test_character_count_warning_for_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for character count display
        $response->assertSeeText('caracteres');
        $response->assertSeeText('/255');
    }

    public function test_validation_checks_are_ordered(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that rules array has proper structure
        $response->assertSeeText('rules:');
        $response->assertSeeText('check:');
        $response->assertSeeText('message:');
        $response->assertSeeText('severity:');
    }

    public function test_initial_validation_if_field_has_value(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that initial validation logic exists
        $response->assertSeeText('input.value');
        $response->assertSeeText('updateFieldFeedback');
    }

    public function test_feedback_cleared_when_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that feedback is cleared for valid values
        $response->assertSeeText('feedbackEl.innerHTML = \'\'');
    }

    public function test_validation_marked_as_applied(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that validated fields are marked
        $response->assertSeeText('data-validated');
    }

    public function test_all_validation_rules_have_messages(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Every rule should have a message
        $content = $response->content();

        // Count message: keys (should be at least 12)
        $messageCount = substr_count($content, 'message:');
        $this->assertGreaterThanOrEqual(12, $messageCount, 'Should have at least 12 validation messages');
    }

    public function test_validation_rules_structure_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check proper object structure
        $response->assertSeeText('const validationRules = {');
        $response->assertSeeText('label:');
        $response->assertSeeText('rules: [');
    }

    public function test_validation_console_errors_prevented(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for defensive coding
        $response->assertSeeText('if (!rules)');
        $response->assertSeeText('if (!input)');
        $response->assertSeeText('if (!feedbackEl)');
    }

    public function test_percentage_character_conversion(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for parsing
        $response->assertSeeText('parseFloat');
        $response->assertSeeText('parseInt');
        $response->assertSeeText('isNaN');
    }

    public function test_year_conversion_in_cooldown(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check for years calculation
        $response->assertSeeText('.toFixed(1)');
        $response->assertSeeText('/ 365');
        $response->assertSeeText('años');
    }

    public function test_validation_accepts_valid_values(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Valid Certification Name',
                'description' => 'A valid description',
                'questions_required' => 25,
                'pass_score_percentage' => 75,
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'active' => true,
            ]);

        // Should succeed
        $response->assertRedirect();
        $this->assertDatabaseHas('certifications', [
            'id' => $this->certification->id,
            'name' => 'Valid Certification Name',
            'pass_score_percentage' => 75,
        ]);
    }

    public function test_validation_prevents_invalid_percentage(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Valid Name',
                'description' => 'Description',
                'questions_required' => 25,
                'pass_score_percentage' => 150, // Invalid: > 100
                'cooldown_days' => 7,
                'result_mode' => 'binary_threshold',
                'active' => true,
            ]);

        // Should fail
        $response->assertSessionHasErrors('pass_score_percentage');
    }

    public function test_validation_prevents_invalid_cooldown(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Valid Name',
                'description' => 'Description',
                'questions_required' => 25,
                'pass_score_percentage' => 75,
                'cooldown_days' => 2000, // Invalid: > 1825
                'result_mode' => 'binary_threshold',
                'active' => true,
            ]);

        // Should fail
        $response->assertSessionHasErrors('cooldown_days');
    }
}
