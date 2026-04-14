<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationUnsavedWarningTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->certification = Certification::create([
            'slug' => 'unsaved-warning-test',
            'name' => 'Unsaved Warning Test Cert',
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

    public function test_unsaved_warning_modal_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check modal HTML
        $response->assertSeeText('Cambios sin guardar');
        $response->assertSeeText('unsavedChangesModal');
        $response->assertSeeText('Tienes cambios sin guardar');
    }

    public function test_unsaved_warning_tracking_initialized(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check tracking functions
        $response->assertSeeText('initializeUnsavedChangesTracking');
        $response->assertSeeText('updateUnsavedState');
        $response->assertSeeText('formHasChanged');
    }

    public function test_unsaved_warning_title_indicator(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check title indicator function
        $response->assertSeeText('updateTitleIndicator');
        $response->assertSeeText('brand-title');
    }

    public function test_unsaved_warning_modal_buttons(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check buttons
        $response->assertSeeText('Descartar cambios');
        $response->assertSeeText('discardChangesAndLeave');
        $response->assertSeeText('Continuar editando');
        $response->assertSeeText('continueEditing');
    }

    public function test_unsaved_warning_event_listeners(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check event listeners
        $response->assertSeeText("'change'");
        $response->assertSeeText("'input'");
        $response->assertSeeText('addEventListener');
    }

    public function test_unsaved_warning_beforeunload_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check beforeunload handler
        $response->assertSeeText('beforeunload');
        $response->assertSeeText('hasUnsavedChanges');
    }

    public function test_unsaved_warning_navigation_interception(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check link interception
        $response->assertSeeText('closest');
        $response->assertSeeText('[href]');
        $response->assertSeeText('pendingNavigation');
    }

    public function test_unsaved_warning_allows_form_submit(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check that form submit bypasses check
        $response->assertSeeText("'submit'");
        $response->assertSeeText('form[method="POST"]');
    }

    public function test_unsaved_warning_form_data_comparison(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check FormData comparison
        $response->assertSeeText('FormData');
        $response->assertSeeText('URLSearchParams');
        $response->assertSeeText('toString()');
    }

    public function test_unsaved_warning_modal_hidden_by_default(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Modal should be hidden
        $content = $response->content();
        $this->assertStringContainsString('id="unsavedChangesModal" class="hidden', $content);
    }

    public function test_unsaved_warning_shows_modal_function(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check modal show/hide functions
        $response->assertSeeText('showUnsavedChangesModal');
        $response->assertSeeText('hideUnsavedChangesModal');
    }

    public function test_unsaved_warning_removes_hidden_class(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check classList manipulation
        $response->assertSeeText('classList.remove');
        $response->assertSeeText('classList.add');
    }

    public function test_unsaved_warning_updates_save_button(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check save button state update
        $response->assertSeeText('updateSaveButton');
        $response->assertSeeText('[type="submit"]');
    }

    public function test_unsaved_warning_save_button_opacity_changes(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check opacity manipulation
        $response->assertSeeText("style.opacity");
        $response->assertSeeText("style.cursor");
    }

    public function test_unsaved_warning_internal_links_intercepted(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check link interception logic
        $response->assertSeeText('.closest');
        $response->assertSeeText('URL');
        $response->assertSeeText('origin');
    }

    public function test_unsaved_warning_skips_external_links(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // External links should not be intercepted
        $response->assertSeeText("target === '_blank'");
        $response->assertSeeText('url.origin !== window.location.origin');
    }

    public function test_unsaved_warning_skips_hash_links(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Hash links should not trigger warning
        $response->assertSeeText("startsWith('#')");
    }

    public function test_unsaved_warning_title_asterisk_added(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check asterisk logic
        $this->assertStringContainsString("includes('*')", $response->content());
    }

    public function test_unsaved_warning_title_color_changes(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check color change
        $response->assertSeeText("style.color");
        $response->assertSeeText("'var(--accent)'");
        $response->assertSeeText("'var(--ink)'");
    }

    public function test_unsaved_warning_prevents_navigation(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check preventDefault
        $response->assertSeeText("preventDefault()");
    }

    public function test_unsaved_warning_allows_save_and_navigate(): void
    {
        // Actually submit the form
        $response = $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'questions_required' => 25,
                'pass_score_percentage' => 75,
                'cooldown_days' => 14,
                'result_mode' => 'binary_threshold',
                'active' => true,
            ]);

        // Should redirect (no unsaved warning modal needed)
        $response->assertRedirect();
        $this->assertDatabaseHas('certifications', [
            'id' => $this->certification->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_unsaved_warning_message_matches_unsaved_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check combined logic
        $response->assertSeeText('hasUnsavedChanges');
        $response->assertSeeText('updateUnsavedState');
        $response->assertSeeText('window.formHasChanged');
    }

    public function test_unsaved_warning_pending_navigation_stored(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check pending navigation variable
        $response->assertSeeText('pendingNavigation');
        $response->assertSeeText('= null');
        $response->assertSeeText('= link.getAttribute');
    }

    public function test_unsaved_warning_navigation_callback_types(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check callback handling
        $response->assertSeeText('typeof pendingNavigation === \'function\'');
        $response->assertSeeText('typeof pendingNavigation === \'string\'');
        $response->assertSeeText('window.location.href');
    }

    public function test_unsaved_warning_modal_accessible_keyboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Check button elements (keyboard accessible)
        $response->assertSeeText('type="button"');
        $response->assertSeeText('onclick=');
    }

    public function test_unsaved_warning_form_tracking_data_preserved(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();

        // Original data is captured on init
        $response->assertSeeText('originalFormData');
        $response->assertSeeText('original');
        $response->assertSeeText('currentFormData');
    }
}
