<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationChangePreviewModalTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        session(['admin_authenticated' => true]);

        $this->certification = Certification::create([
            'slug' => 'modal-test-cert',
            'name' => 'Modal Test Certification',
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

    public function test_edit_page_includes_change_preview_modal(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that modal HTML is present
        $response->assertSeeText('Vista previa de cambios');
        $response->assertSeeText('Revisa qué va a cambiar antes de guardar');
        $response->assertSeeText('changePreviewModal');
    }

    public function test_edit_page_includes_preview_button(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check for preview button
        $response->assertSeeText('Vista previa');
    }

    public function test_modal_javascript_is_loaded(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that key JavaScript functions are present
        $response->assertSeeText('showChangePreview');
        $response->assertSeeText('closeChangePreview');
        $response->assertSeeText('confirmAndSubmit');
        $response->assertSeeText('initializeChangeTracking');
    }

    public function test_modal_includes_field_labels(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that field label mappings are present
        $response->assertSeeText('Slug');
        $response->assertSeeText('Nombre');
        $response->assertSeeText('Descripción');
        $response->assertSeeText('Preguntas requeridas');
        $response->assertSeeText('% de aprobación');
        $response->assertSeeText('Días de cooldown');
        $response->assertSeeText('Modo de resultado');
    }

    public function test_modal_includes_sensitive_field_detection(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that sensitive field detection is present
        $response->assertSeeText('sensitive');
        $response->assertSeeText('Cambio sensible');
    }

    public function test_modal_change_tracking_setup(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that change tracking is properly initialized
        $response->assertSeeText('formChangeData');
        $response->assertSeeText('originalValues');
        $response->assertSeeText('updateFormChangeData');
    }

    public function test_modal_escape_html_function_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check for XSS protection
        $response->assertSeeText('escapeHtml');
    }

    public function test_modal_hidden_by_default(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Modal should have 'hidden' class by default
        $content = $response->content();
        $this->assertStringContainsString('id="changePreviewModal" class="hidden', $content);
    }

    public function test_modal_displays_when_form_submitted(): void
    {
        // This is a scenario test - in real usage:
        // 1. User clicks "Vista previa" button
        // 2. JavaScript calls showChangePreview()
        // 3. Modal becomes visible
        // 4. User sees the diff
        // 5. User clicks "Guardar cambios"
        // 6. confirmAndSubmit() executes form.submit()

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Verify the button clicks handler is set up
        $response->assertSeeText('onclick="showChangePreview()"');
    }

    public function test_modal_submit_button_executes_form_submit(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Verify the confirm button is wired to submission
        $response->assertSeeText('onclick="confirmAndSubmit()"');
        $response->assertSeeText('Guardar cambios');
    }

    public function test_sensitive_fields_are_tagged(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Verify sensitive field detection logic is in place
        $content = $response->content();
        
        // Check that the array includes sensitive fields
        $this->assertStringContainsString('questions_required', $content);
        $this->assertStringContainsString('pass_score_percentage', $content);
        $this->assertStringContainsString('cooldown_days', $content);
        $this->assertStringContainsString('result_mode', $content);
        $this->assertStringContainsString('settings', $content);
    }

    public function test_modal_no_changes_message(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        
        // Check that "no changes" message exists
        $response->assertSeeText('No hay cambios pendientes');
    }
}
