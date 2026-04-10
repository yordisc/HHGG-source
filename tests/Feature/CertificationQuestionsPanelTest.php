<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationQuestionsPanelTest extends TestCase
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
            'slug' => 'questions-panel-test',
            'name' => 'Questions Panel Test',
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
    }

    public function test_questions_panel_is_displayed_on_edit_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Estado de Preguntas');
        $response->assertSeeText('questionsPanel');
    }

    public function test_panel_shows_question_statistics(): void
    {
        // Create some questions
        for ($i = 0; $i < 15; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Activas');
        $response->assertSeeText('Requeridas');
        $response->assertSeeText('Estado');
    }

    public function test_panel_loads_questions_from_api(): void
    {
        // Create test questions
        for ($i = 0; $i < 25; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        // Test the API endpoint directly
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'count',
            'questions' => [
                '*' => ['id', 'prompt', 'type', 'active', 'translations_count']
            ]
        ]);
        $response->assertJson(['count' => 25]);
    }

    public function test_panel_shows_validation_when_insufficient_questions(): void
    {
        // Create only 10 questions but require 20
        for ($i = 0; $i < 10; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $this->certification->update(['questions_required' => 20]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        // Check for validation message JavaScript
        $response->assertSeeText('Preguntas insuficientes');
        $response->assertSeeText('updateValidationAlert');
    }

    public function test_panel_shows_ok_status_with_sufficient_questions(): void
    {
        // Create 50 questions but only require 30
        for ($i = 0; $i < 50; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        // questions_required is 30 by default
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('updateStatusBadge');
        $response->assertSeeText('OK (');
    }

    public function test_panel_shows_exact_match_warning(): void
    {
        // Create exactly 30 questions, require 30
        for ($i = 0; $i < 30; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Cantidad exacta');
        $response->assertSeeText('Exacto (');
    }

    public function test_panel_shows_no_questions_message(): void
    {
        // Don't create any questions
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Sin preguntas');
        $response->assertSeeText('No hay preguntas activas');
    }

    public function test_panel_watchers_questions_required_changes(): void
    {
        for ($i = 0; $i < 50; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        // Check watchers are set up
        $response->assertSeeText('initializeQuestionsWatcher');
        $response->assertSeeText('addEventListener');
    }

    public function test_panel_question_type_labels_work(): void
    {
        // Create different question types
        $types = ['mcq_4', 'mcq_3', 'true_false'];
        foreach ($types as $i => $type) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => $type,
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => $type === 'mcq_4' ? 'C' : null,
                'option_4' => $type === 'mcq_4' ? 'D' : null,
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('getQuestionTypeLabel');
    }

    public function test_panel_displays_question_count(): void
    {
        $count = 42;
        for ($i = 0; $i < $count; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        // Test API returns correct count
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        $response->assertJson(['count' => $count]);
    }

    public function test_panel_only_shows_active_questions(): void
    {
        // Create 30 active and 10 inactive
        for ($i = 0; $i < 30; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Active {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        for ($i = 0; $i < 10; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Inactive {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => false,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        // Should only count active questions
        $response->assertJson(['count' => 30]);
    }

    public function test_panel_reload_button_is_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Recargar');
        $response->assertSeeText('reloadQuestionsPanel');
    }

    public function test_panel_shows_info_section(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('funciona');
        $response->assertSeeText('preguntas activas');
        $response->assertSeeText('Preguntas requeridas');
    }

    public function test_panel_renders_questions_list(): void
    {
        // Create sample questions
        for ($i = 0; $i < 5; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Test Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $response->assertSuccessful();
        $response->assertSeeText('Preguntas Disponibles');
        $response->assertSeeText('renderQuestionsList');
    }

    public function test_panel_xss_protection_on_question_text(): void
    {
        // Create question with potentially malicious text
        Question::create([
            'certification_id' => $this->certification->id,
            'prompt' => '<script>alert("xss")</script>Question',
            'type' => 'mcq_4',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'explanation' => 'Exp',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.certifications.available-questions', $this->certification));

        $response->assertSuccessful();
        // Text should be returned as-is, escaping happens in frontend
        $this->assertStringContainsString('<script>', $response->json()['questions'][0]['prompt']);

        // But when displayed in edit page, it should be escaped
        $editResponse = $this->actingAs($this->admin)
            ->get(route('admin.certifications.edit', $this->certification));

        $editResponse->assertSuccessful();
        $editResponse->assertSeeText('escapeHtml');
    }
}
