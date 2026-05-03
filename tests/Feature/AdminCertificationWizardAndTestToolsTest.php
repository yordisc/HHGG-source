<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\CertificationDraft;
use App\Models\CertificationVersion;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationWizardAndTestToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_certification_through_wizard(): void
    {
        $this->asAdmin()
            ->get(route('admin.certifications.create'))
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 1]));

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 1]), [
                'slug' => 'wizard-cert',
                'name' => 'Wizard Cert',
                'description' => 'Certificacion creada desde wizard',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 2]));

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 2]), [
                'questions_required' => 12,
                'pass_score_percentage' => 75,
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 3]));

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 3]), [
                'cooldown_days' => 14,
                'result_mode' => 'binary_threshold',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 4]));

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 4]), [
                'active' => 1,
                'pdf_view' => 'pdf.certificate',
                'home_order' => 15,
                'settings' => '{"theme":"bright"}',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 5]));

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 5]))
            ->assertRedirect();

        $this->assertDatabaseHas('certifications', [
            'slug' => 'wizard-cert',
            'name' => 'Wizard Cert',
            'questions_required' => 12,
            'result_mode' => 'binary_threshold',
            'home_order' => 15,
        ]);
    }

    public function test_admin_can_generate_and_clear_test_questions(): void
    {
        $certification = Certification::create([
            'slug' => 'testable',
            'name' => 'Testable',
            'description' => null,
            'active' => true,
            'questions_required' => 3,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => [],
        ]);

        $this->asAdmin()
            ->post(route('admin.certifications.test-questions', $certification), [
                'count' => 5,
            ])
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $this->assertSame(5, Question::query()->where('certification_id', $certification->id)->where('is_test_question', true)->count());
        $this->assertSame(5, QuestionTranslation::query()->count());

        $this->asAdmin()
            ->delete(route('admin.certifications.test-questions.clear', $certification))
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $this->assertSame(0, Question::query()->where('certification_id', $certification->id)->where('is_test_question', true)->count());
        $this->assertSame(0, QuestionTranslation::query()->count());
    }

    public function test_admin_can_view_test_functioning_page(): void
    {
        $certification = Certification::create([
            'slug' => 'preview-cert',
            'name' => 'Preview Cert',
            'description' => null,
            'active' => true,
            'questions_required' => 1,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 2,
            'settings' => [],
        ]);

        $question = Question::create([
            'certification_id' => $certification->id,
            'prompt' => 'Base question',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'active' => true,
            'is_test_question' => false,
        ]);

        QuestionTranslation::create([
            'question_id' => $question->id,
            'language' => 'es',
            'prompt' => 'Pregunta base',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
        ]);

        $this->asAdmin()
            ->get(route('admin.certifications.test', $certification))
            ->assertOk()
            ->assertSee('Prueba de funcionamiento')
            ->assertSee('Pregunta base')
            ->assertSee('wire:click="answer(1)"', false);
    }

    public function test_admin_can_open_certification_edit_page_with_array_changes_in_version_history(): void
    {
        $certification = Certification::create([
            'slug' => 'version-array-test',
            'name' => 'Version Array Test',
            'description' => 'Certificacion con historial complejo',
            'active' => true,
            'questions_required' => 1,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 3,
            'settings' => ['theme' => 'light', 'layout' => ['pages' => 2]],
        ]);

        CertificationVersion::create([
            'certification_id' => $certification->id,
            'version_number' => 1,
            'snapshot' => ['settings' => ['theme' => 'dark']],
            'questions_snapshot' => [],
            'change_reason' => 'Ajuste de configuracion',
            'changes' => [
                'settings' => [
                    'old' => ['theme' => 'dark', 'layout' => ['pages' => 1]],
                    'new' => ['theme' => 'light', 'layout' => ['pages' => 2]],
                ],
                'questions_required' => [
                    'old' => 3,
                    'new' => 1,
                ],
            ],
        ]);

        $this->asAdmin()
            ->get(route('admin.certifications.edit', $certification))
            ->assertOk()
            ->assertSee('Historial de versiones')
            ->assertSee('settings')
            ->assertSee('layout')
            ->assertSee('Questions required');
    }

    public function test_wizard_rejects_draft_id_that_does_not_belong_to_session(): void
    {
        $this->asAdmin()
            ->get(route('admin.certifications.wizard', ['step' => 1]))
            ->assertOk();

        $sessionDraftId = (int) CertificationDraft::query()->latest('id')->value('id');
        $mismatchedDraftId = $sessionDraftId + 999;

        $this->asAdmin()
            ->post(route('admin.certifications.wizard.store', ['step' => 1]), [
                'draft_id' => $mismatchedDraftId,
                'slug' => 'wizard-cert-locked',
                'name' => 'Wizard Cert Locked',
                'description' => 'Intento con draft ajeno',
            ])
            ->assertForbidden();
    }
}
