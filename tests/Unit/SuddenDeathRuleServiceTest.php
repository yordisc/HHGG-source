<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Support\SuddenDeathRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuddenDeathRuleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SuddenDeathRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SuddenDeathRuleService::class);
    }

    #[Test]
    public function it_evaluates_fail_if_wrong_on_correct_answer(): void
    {
        $question = Question::factory()->create([
            'sudden_death_mode' => 'fail_if_wrong',
        ]);

        $result = $this->service->evaluateForQuestion($question, true);

        $this->assertFalse($result['triggered']);
        $this->assertNull($result['decision']);
    }

    #[Test]
    public function it_evaluates_fail_if_wrong_on_wrong_answer(): void
    {
        $question = Question::factory()->create([
            'sudden_death_mode' => 'fail_if_wrong',
        ]);

        $result = $this->service->evaluateForQuestion($question, false);

        $this->assertTrue($result['triggered']);
        $this->assertEquals('fail', $result['decision']);
    }

    #[Test]
    public function it_evaluates_pass_if_correct_on_correct_answer(): void
    {
        $question = Question::factory()->create([
            'sudden_death_mode' => 'pass_if_correct',
        ]);

        $result = $this->service->evaluateForQuestion($question, true);

        $this->assertTrue($result['triggered']);
        $this->assertEquals('pass', $result['decision']);
    }

    #[Test]
    public function it_evaluates_pass_if_correct_on_wrong_answer(): void
    {
        $question = Question::factory()->create([
            'sudden_death_mode' => 'pass_if_correct',
        ]);

        $result = $this->service->evaluateForQuestion($question, false);

        $this->assertFalse($result['triggered']);
        $this->assertNull($result['decision']);
    }

    #[Test]
    public function it_ignores_none_mode(): void
    {
        $question = Question::factory()->create([
            'sudden_death_mode' => 'none',
        ]);

        $result = $this->service->evaluateForQuestion($question, false);

        $this->assertFalse($result['triggered']);
        $this->assertNull($result['decision']);
    }

    #[Test]
    public function it_evaluates_batch_and_returns_first_death(): void
    {
        $q1 = Question::factory()->create(['sudden_death_mode' => 'fail_if_wrong']);
        $q2 = Question::factory()->create(['sudden_death_mode' => 'fail_if_wrong']);
        $q3 = Question::factory()->create(['sudden_death_mode' => 'fail_if_wrong']);

        $answers = [
            [
                'question_id' => $q1->id,
                'correct' => true,
                'question' => $q1,
            ],
            [
                'question_id' => $q2->id,
                'correct' => false,
                'question' => $q2,
            ],
            [
                'question_id' => $q3->id,
                'correct' => false,
                'question' => $q3,
            ],
        ];

        $result = $this->service->evaluateBatch($answers);

        $this->assertTrue($result['triggered']);
        $this->assertEquals('fail', $result['decision']);
    }

    #[Test]
    public function it_has_sudden_death_mode(): void
    {
        $this->assertTrue($this->service->hasSuddenDeathMode(
            Question::factory()->create(['sudden_death_mode' => 'fail_if_wrong'])
        ));
        $this->assertTrue($this->service->hasSuddenDeathMode(
            Question::factory()->create(['sudden_death_mode' => 'pass_if_correct'])
        ));
        $this->assertFalse($this->service->hasSuddenDeathMode(
            Question::factory()->create(['sudden_death_mode' => 'none'])
        ));
    }

    #[Test]
    public function it_validates_mode(): void
    {
        $this->assertTrue($this->service->isValidMode('fail_if_wrong'));
        $this->assertTrue($this->service->isValidMode('pass_if_correct'));
        $this->assertTrue($this->service->isValidMode('none'));
        $this->assertFalse($this->service->isValidMode('invalid'));
    }

    #[Test]
    public function it_counts_sudden_death_questions_by_mode(): void
    {
        $certification = \App\Models\Certification::factory()->create();

        Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
            'sudden_death_mode' => 'fail_if_wrong',
        ]);
        Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
            'sudden_death_mode' => 'fail_if_wrong',
        ]);
        Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
            'sudden_death_mode' => 'pass_if_correct',
        ]);
        Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
            'sudden_death_mode' => 'none',
        ]);

        $counts = $this->service->countSuddenDeathQuestions($certification->id);
        $this->assertEquals(3, $counts['total']);
        $this->assertEquals(2, $counts['fail_if_wrong']);
        $this->assertEquals(1, $counts['pass_if_correct']);
    }

    #[Test]
    public function it_gets_mode_name_for_display(): void
    {
        $this->assertIsString($this->service->getModeName('fail_if_wrong'));
        $this->assertIsString($this->service->getModeName('pass_if_correct'));
        $this->assertIsString($this->service->getModeName('none'));
    }
}
