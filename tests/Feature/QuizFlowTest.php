<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizFlowTest extends TestCase
{
    use RefreshDatabase;

    private int $heteroCertificationId;

    protected function setUp(): void
    {
        parent::setUp();

        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'questions_required' => 30,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 10,
        ]);

        $this->heteroCertificationId = $certification->id;
    }

    public function test_quiz_register_returns_ok_for_valid_type(): void
    {
        $response = $this->get(route('quiz.register', ['certType' => 'hetero']));

        $response->assertOk();
    }

    public function test_quiz_register_returns_404_for_invalid_type(): void
    {
        $response = $this->get('/exam/invalid_type/register');

        $response->assertNotFound();
    }

    public function test_quiz_start_sets_candidate_session_and_redirects(): void
    {
        $payload = [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'document' => 'CC-123456789',
            'country_code' => 'CO',
            'document_type' => 'CC',
            'cert_type' => 'hetero',
        ];

        $response = $this->post(route('quiz.start'), $payload);

        $response->assertRedirect(route('quiz.show', ['certType' => 'hetero']));
        $this->assertTrue(session()->has('quiz_candidate.hetero'));
    }

    public function test_quiz_show_redirects_to_register_when_session_is_missing(): void
    {
        $response = $this->get(route('quiz.show', ['certType' => 'hetero']));

        $response->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
    }

    public function test_quiz_show_returns_ok_when_candidate_session_exists(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Question::create([
                'certification_id' => $this->heteroCertificationId,
                'prompt' => 'Question '.$i,
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'active' => true,
            ]);
        }

        session([
            'quiz_candidate.hetero' => [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country' => 'Colombia',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'doc_lookup_hash' => 'hash',
                'identity_lookup_hash' => 'identity_hash',
                'doc_partial' => '2345',
                'document_hash' => bcrypt('ABC12345'),
                'started_at' => now()->toISOString(),
            ],
        ]);

        $response = $this->get(route('quiz.show', ['certType' => 'hetero']));
        $response->assertOk();
    }
}
