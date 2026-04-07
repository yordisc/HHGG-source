<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationWizardAndTestToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_certification_through_wizard(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.certifications.create'))
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 1]));

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.wizard.store', ['step' => 1]), [
                'slug' => 'wizard-cert',
                'name' => 'Wizard Cert',
                'description' => 'Certificacion creada desde wizard',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 2]));

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.wizard.store', ['step' => 2]), [
                'questions_required' => 12,
                'pass_score_percentage' => 75,
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 3]));

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.wizard.store', ['step' => 3]), [
                'cooldown_days' => 14,
                'result_mode' => 'binary_threshold',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 4]));

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.wizard.store', ['step' => 4]), [
                'active' => 1,
                'pdf_view' => 'pdf.certificate',
                'home_order' => 15,
                'settings' => '{"theme":"bright"}',
            ])
            ->assertRedirect(route('admin.certifications.wizard', ['step' => 5]));

        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.test-questions', $certification), [
                'count' => 5,
            ])
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $this->assertSame(5, Question::query()->where('certification_id', $certification->id)->where('is_test_question', true)->count());
        $this->assertSame(5, QuestionTranslation::query()->count());

        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.certifications.test', $certification))
            ->assertOk()
            ->assertSee('Prueba de funcionamiento')
            ->assertSee('Pregunta base');
    }
}