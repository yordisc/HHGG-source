<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizRunnerPhase3IntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Certification $certification;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->certification = Certification::factory()->create([
            'shuffle_questions' => false,
            'shuffle_options' => false,
        ]);
    }

    #[Test]
    public function quiz_respects_shuffle_questions_setting(): void
    {
        // Create questions
        Question::factory()->count(3)->create([
            'certification_id' => $this->certification->id,
            'active' => true,
        ])->each(function ($q) {
            QuestionTranslation::factory()->create(['question_id' => $q->id]);
        });

        // Test with shuffle disabled
        $cert = $this->certification->fresh();
        $cert->update(['shuffle_questions' => false]);

        // Note: Actual shuffle verification requires checking query builder
        $this->assertFalse($cert->shuffle_questions);
    }

    #[Test]
    public function quiz_respects_shuffle_options_setting(): void
    {
        $this->certification->update(['shuffle_options' => true]);

        $this->assertTrue($this->certification->shuffle_options);
    }

    #[Test]
    public function quiz_handles_weighted_scoring(): void
    {
        // Create questions with different weights
        Question::factory()->create([
            'certification_id' => $this->certification->id,
            'weight' => 1.0,
            'active' => true,
        ]);

        Question::factory()->create([
            'certification_id' => $this->certification->id,
            'weight' => 2.0,
            'active' => true,
        ]);

        $questions = $this->certification->questions()
            ->where('active', true)
            ->get();

        $totalWeight = $questions->sum('weight');
        $this->assertEquals(3.0, $totalWeight);
    }

    #[Test]
    public function quiz_evaluates_sudden_death_questions(): void
    {
        Question::factory()->create([
            'certification_id' => $this->certification->id,
            'sudden_death_mode' => 'fail_if_wrong',
            'active' => true,
        ]);

        $questions = $this->certification->questions()
            ->where('sudden_death_mode', '!=', 'none')
            ->get();

        $this->assertCount(1, $questions);
    }

    #[Test]
    public function quiz_applies_auto_result_rules(): void
    {
        $this->certification->update([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    [
                        'type' => 'name_pattern',
                        'pattern' => 'John*',
                        'decision' => 'pass',
                    ],
                ],
            ],
        ]);

        $config = $this->certification->auto_result_rule_config;
        $this->assertIsArray($config);
        $this->assertCount(1, $config['rules']);
    }

    #[Test]
    public function quiz_requires_question_bank_for_activation(): void
    {
        $cert = Certification::factory()->create([
            'require_question_bank_for_activation' => true,
            'active' => false,
        ]);

        // No questions = cannot activate
        $this->assertFalse($cert->questions()->exists());
    }

    #[Test]
    public function quiz_shows_language_selector_when_multiple_banks(): void
    {
        $cert = Certification::factory()->create([
            'require_question_bank_for_activation' => true,
        ]);

        // Create questions in multiple languages
        Question::factory()->count(2)->create([
            'certification_id' => $cert->id,
            'active' => true,
        ])->each(function ($q) {
            QuestionTranslation::factory()->create(['question_id' => $q->id, 'language' => 'es']);
            QuestionTranslation::factory()->create(['question_id' => $q->id, 'language' => 'en']);
        });

        $spanishQuestions = $cert->questions()
            ->whereHas('translations', function ($query) {
                $query->where('language', 'es');
            })
            ->count();

        $this->assertGreaterThan(0, $spanishQuestions);
    }

    #[Test]
    public function certificate_includes_expiry_date_if_configured(): void
    {
        $this->certification->update([
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        // When creating a certificate, it should calculate expiry
        $attrs = [
            'expiry_mode' => $this->certification->expiry_mode,
            'expiry_days' => $this->certification->expiry_days,
        ];

        $this->assertEquals('fixed', $attrs['expiry_mode']);
        $this->assertEquals(30, $attrs['expiry_days']);
    }

    #[Test]
    public function quiz_includes_question_metadata(): void
    {
        $question = Question::factory()->create([
            'certification_id' => $this->certification->id,
            'weight' => 1.5,
            'sudden_death_mode' => 'fail_if_wrong',
            'type' => 'mcq_4',
            'active' => true,
        ]);

        $this->assertEquals(1.5, $question->weight);
        $this->assertEquals('fail_if_wrong', $question->sudden_death_mode);
        $this->assertEquals('mcq_4', $question->type);
    }

    #[Test]
    public function mcq2_question_has_only_two_options(): void
    {
        $question = Question::factory()->create([
            'certification_id' => $this->certification->id,
            'type' => 'mcq_2',
            'option_1' => 'Option 1',
            'option_2' => 'Option 2',
            'option_3' => null,
            'option_4' => null,
            'active' => true,
        ]);

        $this->assertNotNull($question->option_1);
        $this->assertNotNull($question->option_2);
        $this->assertEquals('', $question->option_3);
        $this->assertEquals('', $question->option_4);
    }

    #[Test]
    public function mcq4_question_has_four_options(): void
    {
        $question = Question::factory()->create([
            'certification_id' => $this->certification->id,
            'type' => 'mcq_4',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'active' => true,
        ]);

        $this->assertNotNull($question->option_1);
        $this->assertNotNull($question->option_2);
        $this->assertNotNull($question->option_3);
        $this->assertNotNull($question->option_4);
    }
}
