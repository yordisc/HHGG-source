<?php

namespace Tests\Feature;

use App\Livewire\QuizRunner;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizRunnerLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_runner_redirects_to_register_when_candidate_session_missing(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'questions_required' => 1,
            'pass_score_percentage' => 50,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 10,
        ]);

        Livewire::test(QuizRunner::class, ['certType' => 'hetero'])
            ->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
    }

    public function test_quiz_runner_redirects_when_question_bank_is_insufficient(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'questions_required' => 3,
            'pass_score_percentage' => 50,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 10,
        ]);

        Question::query()->create([
            'certification_id' => $certification->id,
            'prompt' => 'Only one question',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'active' => true,
        ]);

        session([
            'quiz_candidate.hetero' => [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country' => 'Colombia',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'doc_lookup_hash' => 'hash',
                'identity_lookup_hash' => 'identity_hash',
                'doc_partial' => '6789',
                'document_hash' => bcrypt('CC-123456789'),
                'started_at' => now()->toISOString(),
            ],
        ]);

        Livewire::test(QuizRunner::class, ['certType' => 'hetero'])
            ->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
    }

    public function test_quiz_runner_finishes_attempt_and_creates_certificate(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'questions_required' => 1,
            'pass_score_percentage' => 50,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 10,
        ]);

        $question = Question::query()->create([
            'certification_id' => $certification->id,
            'prompt' => 'Question one',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'active' => true,
        ]);

        QuestionTranslation::query()->create([
            'question_id' => $question->id,
            'language' => app()->getLocale(),
            'prompt' => 'Question one',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
        ]);

        session([
            'quiz_candidate.hetero' => [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country' => 'Colombia',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'doc_lookup_hash' => 'hash',
                'identity_lookup_hash' => 'identity_hash',
                'doc_partial' => '6789',
                'document_hash' => bcrypt('CC-123456789'),
                'started_at' => now()->toISOString(),
            ],
        ]);

        $component = Livewire::test(QuizRunner::class, ['certType' => 'hetero']);

        $attempt = session('quiz_attempt.hetero');
        $this->assertIsArray($attempt);

        $correctDisplayed = (int) ($attempt['questions'][0]['correct_displayed'] ?? 1);
        $component->call('answer', $correctDisplayed);

        $this->assertDatabaseCount('certificates', 1);

        $certificate = Certificate::query()->first();
        $this->assertNotNull($certificate);
        $this->assertSame($certification->id, $certificate->certification_id);
        $this->assertNotNull($certificate->next_available_at);

        $this->assertFalse(session()->has('quiz_candidate.hetero'));
        $this->assertFalse(session()->has('quiz_attempt.hetero'));
    }
}
